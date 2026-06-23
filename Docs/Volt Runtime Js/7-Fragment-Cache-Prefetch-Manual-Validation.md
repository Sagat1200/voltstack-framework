# Fragment Cache + Prefetch - Validacion Manual

## Objetivo

Validar en navegador el comportamiento real del runtime SPA para:

- `fragment cache SPA` con preservacion y descarte seguro
- `prefetch` documental por hover y su reutilizacion al navegar
- hints `preload` y `modulepreload` sobre assets del destino
- fallback seguro cuando cambia el contrato documental o la politica de cache

## Preparacion

1. levantar la aplicacion cliente con servidor local y assets frontend actualizados
2. abrir DevTools en las pestañas `Network` y `Elements`
3. activar `Preserve log` en `Network`
4. abrir primero `/fragmentCache`
5. tener a mano estas rutas:
   - `/fragmentCache`
   - `/formExample`
   - `/fragmentCacheReset`
   - `/cacheExample`
   - `/navigationPolicy`
   - `/navigationDocumentReload`

## Pantallas Y Monitores Utiles

- `/fragmentCache`
  - formularios de comparacion entre fragmento preservado y control
  - shell vivo preservado y shell control
  - monitor embebido de `volt:fragment-preserve` y `volt:fragment-discard`
- `/formExample`
  - destino compatible para reutilizar `draft-fragment` y `live-shell`
  - monitor rapido de eventos de fragmento
- `/fragmentCacheReset`
  - destino con `<meta name="volt-fragment-control" content="reset">`
  - HTML base distinto para confirmar descarte por politica documental
- `/cacheExample`
  - monitor de `volt:cache-hit`, `volt:cache-miss`, `volt:cache-store`, `volt:cache-invalidate` y `volt:cache-clear`
  - enlaces con `volt:cache="reload"`, `no-store`, `invalidate` y `ttl=15s`
  - enlaces `Hover + prefetch` para inducir prefetch real
- `/navigationPolicy`
  - laboratorio documental para `spa`, `reload` y `auto`
- `/navigationDocumentReload`
  - destino `reload-only` con `request marker` cambiante

## Checklist Manual

### 1. Reuse Basico De Fragmentos

Accion:

- abrir `/fragmentCache`
- editar el formulario con `data-volt-preserve="draft-fragment"`
- cambiar el `textarea`, el checkbox y el `input`
- editar tambien el formulario control sin `data-volt-preserve`
- modificar el shell con `data-volt-preserve="live-shell"`:
  - cambiar el texto editable
  - mover el rango
  - abrir o cerrar el `details`
- navegar a `/formExample` usando `volt:navigate`

Esperado:

- el formulario preservado aparece con exactamente los cambios previos
- el formulario control vuelve a su HTML base
- el shell preservado mantiene texto, rango y estado del `details`
- el shell control vuelve a su estado inicial
- el monitor de `/formExample` registra `volt:fragment-preserve`
- el detalle del evento incluye claves compatibles como `draft-fragment` o `live-shell`

### 2. Reuse De Vuelta Al Origen

Accion:

- desde `/formExample`, hacer mas cambios en el fragmento preservado
- volver a `/fragmentCache` con `volt:navigate`

Esperado:

- el nodo vivo vuelve a reutilizarse al regresar
- los cambios mas recientes viajan de vuelta al origen
- el monitor de `/fragmentCache` incrementa `volt:fragment-preserve`
- no debe aparecer `volt:fragment-discard` salvo por una razon justificada

### 3. Descarte Por Politica Documental

Accion:

- partir desde `/fragmentCache` o `/formExample` con cambios vivos en los fragmentos
- navegar a `/fragmentCacheReset` usando el enlace rojo con `volt:cache="no-store"` y `volt:prefetch="none"`

Esperado:

- el formulario `draft-fragment` muestra el HTML base del destino:
  - `Nombre del borrador = HTML nuevo del destino`
  - `textarea` con el texto del destino
- el shell `live-shell` muestra el contenido base de `/fragmentCacheReset`
- el monitor registra `volt:fragment-discard`
- el detalle del descarte incluye razon `document-policy` o `navigation-policy` segun el origen efectivo

### 4. Reuse Seguro Tras Reset

Accion:

- desde `/fragmentCacheReset`, volver a `/fragmentCache`

Esperado:

- no reaparecen los cambios antiguos descartados
- la pantalla vuelve con su HTML base o con un nuevo estado vivo creado despues del reset
- el descarte previo no deja residuos visuales

### 5. Prefetch Por Hover Y Reuso De Payload

Accion:

- abrir `/cacheExample`
- en `Network`, filtrar por `/counterExample` o `/formExample`
- hacer hover sobre uno de los botones `Hover + prefetch`
- esperar a que aparezca el request
- luego hacer click en ese mismo enlace

Esperado:

- el hover dispara una solicitud previa marcada como origen `prefetch`
- el monitor de hooks incrementa `volt:cache-miss` y luego `volt:cache-store` al prefetchear una URL nueva
- al hacer click, la navegacion reutiliza el payload ya obtenido o entra como `volt:cache-hit`
- no se duplica una segunda solicitud innecesaria para la misma URL si la entrada sigue vigente
- el badge `source` del monitor ayuda a distinguir `prefetch` vs `navigate`

### 6. Politicas De Cache En Navegacion Real

Accion:

- en `/cacheExample`, probar uno por uno:
  - `reload`
  - `no-store`
  - `invalidate`
  - `ttl=15s`

Esperado:

- `reload`: invalida o fuerza lectura fresca antes de guardar una nueva entrada
- `no-store`: evita lectura y escritura persistente en cache SPA
- `invalidate`: limpia la URL y luego repuebla con respuesta nueva
- `ttl=15s`: permite reutilizacion dentro de la ventana sin nuevo fetch completo
- el monitor embebido refleja `volt:cache-hit`, `volt:cache-miss`, `volt:cache-store` e `volt:cache-invalidate` de forma coherente

### 7. Hints De Preload Y Modulepreload

Accion:

- seguir en `/cacheExample`
- hacer hover sobre un enlace `Hover + prefetch`
- en `Elements`, inspeccionar el `head`
- en `Network`, observar entradas de tipo `preload`, `script` o assets asociados al destino

Esperado:

- aparecen hints `preload` o `modulepreload` para assets criticos del documento destino cuando corresponda
- no se insertan hints duplicados para la misma URL/asset durante la misma ventana de reutilizacion
- tras navegar, esos hints ya no provocan una segunda cascada innecesaria si el asset estaba listo

Nota:

- dependiendo del navegador, algunos hints pueden verse mejor en `Elements` que en la lista resumida de `Network`

### 8. Fallback Por Politica Reload Del Documento

Accion:

- abrir `/navigationPolicy`
- hacer click en `Probar politica documental reload`
- anotar el `request marker` de `/navigationDocumentReload`
- volver al laboratorio y repetir la accion

Esperado:

- la visita empieza desde un enlace SPA pero termina en carga completa del navegador
- el `request marker` cambia en cada entrada completa al documento `reload`
- no hay patch SPA estable del body final porque el destino se declara `reload-only`

### 9. Full Reload Por Politica Del Enlace

Accion:

- en `/navigationPolicy`, hacer click en `volt:navigate="reload"`

Esperado:

- el navegador realiza recarga completa directa a `/counterExample`
- no depende de leer primero el documento destino para decidir el fallback

### 10. Integridad De Head Y Layout

Accion:

- navegar entre `/fragmentCache`, `/formExample` y `/cacheExample`
- inspeccionar `head` y `body`
- revisar metas con `data-volt-head-key` y el atributo `data-volt-layout`

Esperado:

- las pantallas compatibles conservan un contrato de layout estable
- el `head` se actualiza sin duplicar metas clave ni romper el documento
- si una navegacion futura encontrara un layout incompatible, el contrato esperado sigue siendo fallback seguro en lugar de reuse parcial inconsistente

## Criterio De Cierre

Se puede cerrar este bloque cuando:

- `fragment cache` preserva solo los nodos declarados y descarta correctamente al entrar a `/fragmentCacheReset`
- `prefetch` por hover muestra reuse observable del payload o al menos evita fetch duplicado innecesario
- `preload` y `modulepreload` se observan de forma consistente en `head` o `Network`
- las politicas `reload`, `no-store`, `invalidate` y `ttl=15s` producen hooks coherentes
- `head` y `layout` permanecen estables en rutas compatibles y hacen fallback seguro en escenarios no compatibles
