# Sistema Cache

## Objetivo

Este documento concentra la documentacion del sistema de cache del runtime SPA de VoltStack.

Sirve para:

- describir lo que hoy ya existe en el cache de navegacion SPA
- dejar claro el contrato declarativo actual de `volt:cache`
- registrar riesgos, limites y decisiones del MVP
- preparar un apartado separado para un futuro cache de datos

## Alcance Actual

Hoy el runtime implementa principalmente **cache de navegacion SPA**.

Esto significa:

- cache de documentos HTML obtenidos por `prefetch` o por `visit()`
- cache en memoria del navegador
- cache limitada a la pestaña actual
- sin persistencia entre recargas completas
- sin compartir estado entre tabs

No existe aun un sistema formal de **cache de datos** desacoplado del HTML.

## Estado General

- `[x]` cache temporal de navegacion por URL
- `[x]` reuse de requests en vuelo
- `[x]` expiracion por TTL
- `[x]` control declarativo por enlace con `volt:cache`
- `[x]` control por documento destino con meta tags
- `[x]` invalidacion explicita por evento runtime
- `[x]` eventos runtime para observar hits, misses y stores
- `[-]` fragment preserve SPA opt-in por clave declarativa
- `[ ]` fragment cache SPA mas avanzado
- `[ ]` cache de datos separada del documento HTML

## 1. Cache De Navegacion SPA

### 1.1 Objetivo

El cache de navegacion SPA busca:

- reutilizar respuestas HTML recientes
- evitar requests duplicadas hacia la misma ruta
- bajar la latencia percibida de `volt:navigate`
- coordinarse con `prefetch`, `preload` y el control de layout/head

### 1.2 Modelo Actual

Estado runtime relevante:

```js
runtime.navigationCache = new Map();
runtime.navigationInFlight = new Map();
```

La cache usa como clave principal una URL absoluta normalizada.

Ademas, el runtime conserva aliases por:

- URL solicitada
- `finalUrl` resultante

Esto permite reutilizar o invalidar correctamente entradas cuando:

- la URL canonica cambia
- hay redirects
- se navega por distintas formas equivalentes de una misma ruta

### 1.3 Forma De Una Entrada

Una entrada actual del cache de navegacion tiene una forma conceptual como esta:

```js
{
  cacheKey: "https://app.test/formExample::https://app.test/formExample",
  aliases: [
    "https://app.test/formExample"
  ],
  url: "https://app.test/formExample",
  finalUrl: "https://app.test/formExample",
  html: "<!doctype html>...</html>",
  fetchedAt: 1718200000000,
  lastAccessedAt: 1718200001000,
  expiresAt: 1718200005000,
  source: "prefetch",
  cacheControl: {
    mode: "default",
    ttl: null,
    raw: "",
    source: "default"
  }
}
```

Campos practicos mas importantes:

- `url`: URL original pedida
- `finalUrl`: URL final resuelta por el response
- `html`: documento serializado
- `fetchedAt`: momento de almacenamiento
- `lastAccessedAt`: ultimo acceso util para poda
- `expiresAt`: limite de vigencia
- `source`: origen de la entrada (`prefetch`, `navigate`, etc.)
- `cacheControl`: politica efectiva aplicada

### 1.4 Politica Base Del MVP

- TTL global base: `5s`
- maximo recomendado de entradas: `10`
- si una entrada expira: se elimina
- si una URL ya esta en vuelo: se reutiliza la promesa
- si la entrada sigue vigente: puede reutilizarse sin volver a pedir la ruta

### 1.5 Flujo General

#### Caso A: `prefetch`

1. el usuario acerca intencion de navegacion
2. el runtime calcula la URL normalizada
3. revisa si existe entrada vigente
4. si la hay, devuelve hit
5. si no la hay, revisa si existe request en vuelo
6. si tampoco existe, hace `requestPage(url)`
7. guarda la respuesta si la politica lo permite
8. puede disparar `preload` de assets criticos del `head`

#### Caso B: `visit()`

1. el runtime resuelve la politica efectiva de cache
2. si la politica exige invalidar o no leer cache, actua primero sobre la entrada
3. intenta reutilizar la entrada vigente si esta permitido
4. si no hay entrada util, reutiliza request en vuelo o hace fetch nuevo
5. valida layout, reconciliacion de `head` y mutacion del `body`
6. actualiza `history`
7. emite hooks runtime asociados

## 2. Control Declarativo Actual

### 2.1 `volt:cache` Por Enlace

Estado actual:

- `[x]` disponible

Modos soportados:

- `volt:cache="no-store"`
- `volt:cache="reload"`
- `volt:cache="invalidate"`
- `volt:cache="ttl=15s"`
- `volt:cache="max-age=15s"`
- combinaciones simples como `volt:cache="reload ttl=15s"`

#### Significado de cada modo

`default`:

- si no declaras `volt:cache`, el enlace usa la politica por defecto
- puede leer cache vigente
- puede guardar la nueva respuesta

`no-store`:

- no lee cache para esa navegacion
- no guarda la respuesta nueva en cache
- puede limpiar la entrada previa para no mantenerla en memoria

`reload`:

- no reutiliza la entrada actual
- fuerza nueva lectura desde red
- la respuesta nueva si puede quedar cacheada

`invalidate`:

- invalida primero la entrada de esa URL
- despues deja que la navegacion siga normal
- la respuesta nueva si puede repoblar la cache

`ttl=...` o `max-age=...`:

- redefine el TTL de la entrada a guardar
- no cambia por si solo el modo base, salvo que se combine con otro token

### 2.2 Control Desde El Documento Destino

Estado actual:

- `[x]` disponible

Meta tags soportados:

```html
<meta name="volt-cache-control" content="no-store">
<meta name="volt-cache-control" content="reload">
<meta name="volt-cache-control" content="ttl=15s">
<meta name="volt-cache-control" content="reload ttl=15s">
<meta name="volt:navigation-cache" content="invalidate">
```

Uso esperado:

- ajustar politica desde el documento destino
- indicar que una vista no debe persistirse
- ampliar o reducir TTL en rutas concretas
- reforzar una estrategia de frescura desde el propio layout/head

## 3. Integracion Con Otros Bloques Del Runtime

### 3.1 Con `volt:navigate`

- el cache participa directamente en `visit()`
- puede devolver hit antes del fetch
- puede forzar miss cuando la politica asi lo pida

### 3.2 Con `volt:prefetch`

- `prefetch` reutiliza cache si esta permitido
- `prefetch` tambien respeta `volt:cache`
- con `no-store`, el prefetch no deja una entrada persistente
- con `reload` o `invalidate`, el runtime refresca la ruta en vez de reutilizar la entrada previa

### 3.3 Con `navigationInFlight`

- si la URL ya esta en vuelo, no se abre otra request equivalente
- la promesa existente se reutiliza
- si una navegacion real llega con politica incompatible, el runtime puede abortar un prefetch previo

### 3.4 Con Layout Y `head`

- una entrada cacheada no evita la validacion de layout
- si el layout cambia y no es compatible, se mantiene el fallback a full reload
- la reconciliacion del `head` sigue aplicando aunque el documento venga del cache

## 4. Eventos Runtime De Cache

Eventos actuales emitidos:

- `volt:cache-hit`
- `volt:cache-miss`
- `volt:cache-store`
- `volt:cache-invalidate`
- `volt:cache-clear`

### 4.1 Significado De Cada Evento

`volt:cache-hit`:

- indica que el runtime encontro una entrada vigente y la reutilizo
- normalmente aparece cuando una navegacion o un prefetch puede resolverse desde memoria sin volver a red
- sirve para confirmar que la politica efectiva permitio leer cache

`volt:cache-miss`:

- indica que no habia una entrada reutilizable para esa URL
- puede ocurrir porque no existia entrada previa, porque expiro por TTL o porque la politica activa impidio reutilizarla
- normalmente precede a una request nueva o a la reutilizacion de una request en vuelo

`volt:cache-store`:

- indica que el runtime guardo una nueva respuesta en `navigationCache`
- suele dispararse despues de una navegacion o prefetch exitoso cuando la politica permite persistir la respuesta
- confirma que la entrada ya quedo disponible para futuros hits

`volt:cache-invalidate`:

- indica que una entrada concreta fue invalidada o eliminada
- puede dispararse por expiracion, por invalidacion explicita, por reemplazo de entrada o por una politica como `invalidate`
- sirve para seguir el ciclo de vida de una URL concreta dentro de la cache

`volt:cache-clear`:

- indica que se limpio toda la cache SPA actual
- suele usarse en limpiezas globales, demos manuales o futuras estrategias de reseteo de sesion
- a diferencia de `volt:cache-invalidate`, no se refiere a una sola URL sino al almacenamiento completo actual

### 4.2 Lectura Operativa Rapida

- `hit`: se reutilizo una entrada vigente
- `miss`: no habia entrada util para reutilizar
- `store`: se guardo una nueva respuesta
- `invalidate`: se elimino una entrada concreta
- `clear`: se vacio toda la cache

### 4.3 Tabla Rapida De Depuracion

| Evento | Cuando ocurre | Detalle tipico | Utilidad de depuracion |
| --- | --- | --- | --- |
| `volt:cache-hit` | cuando una entrada vigente puede reutilizarse | `url`, `finalUrl`, `source`, `mode` | confirmar reuse real del cache |
| `volt:cache-miss` | cuando no existe entrada util o no puede leerse | `url`, `source`, `mode` | entender por que el runtime fue a red |
| `volt:cache-store` | cuando se guarda una nueva respuesta | `url`, `finalUrl`, `ttl`, `source`, `mode` | validar que una ruta ya quedo cacheada |
| `volt:cache-invalidate` | cuando se elimina una entrada concreta | `url`, `aliases`, `reason`, `removed` | seguir limpiezas por URL, TTL o reemplazo |
| `volt:cache-clear` | cuando se limpia toda la cache SPA | `reason`, `removed` | verificar resets globales de cache |

### 4.4 Detalle Esperado En `event.detail`

Aunque el contenido puede variar segun el caso, normalmente estos eventos incluyen datos como:

- `url`: URL normalizada asociada al evento
- `finalUrl`: URL final resuelta si aplica
- `source`: origen del flujo (`prefetch`, `navigate`, `event`, etc.)
- `mode`: modo efectivo de cache (`default`, `reload`, `invalidate`, `no-store`)
- `reason`: motivo de invalidacion o limpieza cuando corresponde
- `removed`: cantidad de entradas eliminadas en invalidaciones o limpiezas
- `ttl`: TTL efectivo cuando se almacena una entrada

Ejemplo conceptual:

```js
document.addEventListener('volt:cache-store', function (event) {
  console.log(event.detail);
  // {
  //   url: 'http://127.0.0.1:8000/formExample',
  //   finalUrl: 'http://127.0.0.1:8000/formExample',
  //   source: 'prefetch',
  //   ttl: 5000,
  //   mode: 'default'
  // }
});
```

Uso de estos eventos:

- depuracion de navegacion SPA
- demos UI como `cacheExample`
- herramientas de inspeccion
- futura telemetria del runtime

## 5. Invalidacion Manual

### 5.1 Por Evento

Invalidar una URL concreta:

```js
document.dispatchEvent(new CustomEvent('volt:navigation-cache-invalidate', {
  detail: {
    url: '/formExample',
    reason: 'manual',
  },
}));
```

Limpiar toda la cache SPA actual:

```js
document.dispatchEvent(new CustomEvent('volt:navigation-cache-invalidate', {
  detail: {
    reason: 'manual',
  },
}));
```

### 5.2 Desde La Demo UI

Actualmente existe una demo interactiva en `/cacheExample` con:

- ejemplos de enlaces `volt:cache`
- tabla comparativa de modos
- monitor en vivo de eventos `volt:cache-*`
- botones reales para invalidar `/counterExample`
- botones reales para invalidar `/formExample`
- boton para limpiar toda la cache SPA

## 6. Fragment Cache SPA MVP

### 6.1 Objetivo

El MVP actual de fragment cache SPA no guarda fragmentos en un almacen separado.

Su objetivo inmediato es:

- reutilizar nodos vivos entre navegaciones SPA compatibles
- preservar formularios o componentes concretos sin depender del documento HTML completo
- reducir reconstrucciones innecesarias cuando una zona estable existe en origen y destino
- dejar una base declarativa para evolucionar hacia un fragment cache mas avanzado

### 6.2 Contrato Declarativo

Marcado soportado:

```html
<form data-volt-preserve="profile-form">
  ...
</form>

<section volt:preserve="counter-shell">
  ...
</section>
```

Reglas del contrato MVP:

- el origen y el destino deben declarar la misma clave de preservacion
- la clave puede declararse con `data-volt-preserve="clave"` o `volt:preserve="clave"`
- si el atributo existe sin valor, el runtime intenta usar `id` o `data-volt-target` como fallback de clave
- el tag del origen y el del destino deben coincidir
- la navegacion debe mantenerse dentro de un `layout` compatible; si hay fallback por cambio de layout, no se preserva el fragmento

### 6.3 Flujo Del Runtime

Durante `visit()` el runtime hace esto:

1. captura los fragmentos top-level preservables del `body` actual
2. reemplaza el `body` con el HTML del documento destino
3. busca placeholders compatibles en el nuevo `body`
4. si encuentra misma clave y mismo tag, reemplaza el nodo nuevo por el nodo vivo anterior
5. emite `volt:fragment-preserve` o `volt:fragment-discard` segun el resultado

### 6.4 Eventos Del MVP

Eventos emitidos por este bloque:

- `volt:fragment-preserve`: un fragmento anterior fue reutilizado en la pantalla nueva
- `volt:fragment-discard`: el runtime encontro un fragmento no reutilizable o una condicion insegura para preservarlo

Detalle tipico:

```js
{
  source: "navigate",
  url: "http://127.0.0.1:8000/formExample",
  finalUrl: "http://127.0.0.1:8000/formExample",
  key: "profile-form",
  tagName: "form"
}
```

Razones comunes de descarte:

- `missing-key`
- `duplicate-source`
- `missing-target-key`
- `duplicate-target`
- `missing-target`
- `tag-mismatch`

### 6.5 Limites Del MVP

- no existe aun un almacen separado de fragmentos fuera del DOM vivo
- no hay invalidacion por tags, scopes ni dependencias de datos
- el MVP solo preserva fragmentos top-level para evitar colisiones complejas entre nodos anidados
- no resuelve todavia politicas por ruta ni persistencia entre recargas completas
- no reemplaza el cache de datos ni la reconciliacion completa de componentes

## 7. Riesgos Y Limites Del Cache SPA

Riesgos actuales:

- usar HTML stale si el TTL es demasiado largo
- aumentar uso de memoria si se cachean demasiadas respuestas
- prefetchear rutas altamente dinamicas con poco valor real
- cachear vistas sensibles que deberian evitar persistencia

Limites actuales:

- solo cache de HTML/documento
- no hay persistencia por sesion
- no hay cache compartida entre tabs
- solo existe un `fragment preserve` opt-in basico; no hay fragment cache avanzado con almacenamiento independiente
- no existe aun una capa de invalidacion por tags, scopes o dependencias de datos

## 8. Reglas De Seguridad Y Consistencia

- no usar el cache como reemplazo de validaciones del backend
- no asumir que una respuesta prefetched sigue siendo valida logicamente solo por no haber expirado
- no mezclar cache de navegacion con snapshots reactivos de componentes
- no preservar fragmentos si el destino cambia de `layout` o ya no expone una clave compatible
- no saltarse el control de layout ni la reconciliacion segura del `head`
- usar `no-store` en rutas con informacion sensible o de frescura estricta

## 9. Checklist Tecnico Del Bloque SPA

- `[x]` `runtime.navigationCache`
- `[x]` `runtime.navigationInFlight`
- `[x]` URL normalizada por helper dedicado
- `[x]` lectura de cache con expiracion por TTL
- `[x]` almacenamiento con metadata y aliases
- `[x]` poda por expiracion y maximo de entradas
- `[x]` integracion en `prefetchPage()`
- `[x]` integracion en `visit()`
- `[x]` soporte declarativo inicial para `volt:cache`
- `[x]` control por meta tags del documento destino
- `[x]` invalidacion explicita por evento runtime
- `[x]` eventos `volt:cache-*`
- `[-]` preservacion declarativa inicial con `data-volt-preserve` / `volt:preserve`
- `[-]` eventos `volt:fragment-preserve` y `volt:fragment-discard`
- `[x]` demo interactiva de cache en UI

## 10. Validacion Ejecutada

- `[x]` `volt.js` sin errores de diagnostico despues de la capa de cache control
- `[x]` validacion de sintaxis con `node --check`
- `[x]` servidor local confirmado en `http://127.0.0.1:8000`
- `[x]` rutas demo `/`, `/counterExample`, `/formExample` y `/cacheExample` respondiendo `200`
- `[x]` shell compartida `app` confirmada
- `[x]` `visit()` enriquecido con preservacion opt-in de fragmentos compatibles en el runtime
- `[x]` demo UI de cache con tarjetas, tabla, monitor y acciones manuales
- `[-]` validacion visual/manual de red todavia pendiente para revisar en navegador el comportamiento fino de prefetch/preload

## 11. Futuro: Cache De Datos

Este apartado describe lo que deberia existir despues, pero **todavia no esta implementado**.

### 11.1 Objetivo Del Futuro Cache De Datos

Separar claramente:

- cache de documento HTML para navegacion SPA
- cache de datos para componentes, endpoints y recursos reutilizables

El futuro cache de datos deberia permitir:

- reutilizar payloads JSON o snapshots parciales
- invalidar por clave, tag o dependencia
- evitar roundtrips redundantes en acciones o loaders
- convivir con el cache SPA sin mezclar responsabilidades

### 11.2 Casos De Uso Futuros

- listas o dashboards con polling o refresco frecuente
- datos compartidos por varias pantallas
- respuestas de loaders o actions con forma cacheable
- hydration parcial de componentes
- stores persistentes por sesion o pestaña

### 11.3 Requisitos Deseables

- claves de cache estables
- invalidacion por tags o scopes
- TTL configurable por recurso
- politicas `stale-while-revalidate`
- revalidacion en background
- persistencia opcional en `sessionStorage`, `localStorage` o IndexedDB
- coordinacion entre tabs
- observabilidad y telemetria

### 11.4 Riesgos Del Futuro Cache De Datos

- incoherencia entre HTML cacheado y datos actualizados
- invalidacion compleja si no existe modelo de dependencias
- mayor acoplamiento entre backend y runtime si las claves no se definen bien
- posibilidad de fugas de datos sensibles si se persiste sin controles

### 11.5 Reglas De Diseno Recomendadas

- mantener separado el cache de datos del cache de navegacion
- no reutilizar automaticamente datos sensibles
- exponer una API publica clara para invalidar o revalidar
- permitir observabilidad similar a `volt:cache-*`
- documentar explicitamente el nivel de persistencia y su seguridad

### 11.6 Checklist Futuro

- `[ ]` definir modelo de clave para cache de datos
- `[ ]` definir API publica de lectura/escritura/invalidez
- `[ ]` definir persistencia opcional por capa
- `[ ]` definir integracion con loaders, actions y state runtime
- `[ ]` definir estrategia de consistencia con navegacion SPA
- `[ ]` definir telemetria y eventos del cache de datos

## 12. Como Mantener Este Documento

Regla practica:

- mover items de `[ ]` a `[-]` cuando empiece la implementacion real
- mover items de `[-]` a `[x]` cuando el bloque sea usable y validado
- registrar cambios de contrato si cambia `volt:cache`
- mantener siempre separado lo ya implementado del futuro cache de datos

