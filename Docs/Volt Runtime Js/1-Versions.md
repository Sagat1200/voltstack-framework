# Volt Runtime JS - Seguimiento De Desarrollo

## Objetivo

Este documento funciona como checklist viva del runtime SPA/reactivo de VoltStack.

Aqui se registra:

- lo que falta desarrollar
- lo que falta probar
- el estado actual de cada bloque
- el avance conforme se vaya implementando

## Convencion De Estado

- `[ ]` pendiente
- `[-]` en progreso
- `[x]` completado
- `[!]` pendiente critico o con riesgo

## Estado General Actual

Resumen del estado del runtime segun la documentacion y la implementacion observada actualmente:

- `[x]` base de acciones reactivas por protocolo
- `[x]` patching DOM base
- `[x]` navegacion SPA base
- `[x]` hooks runtime base para requests y navegacion
- `[x]` preservacion de foco y scroll basica
- `[-]` reconciliacion de `head` y manejo de layout
- `[x]` prefetch y preload SPA
- `[x]` client state real
- `[x]` shared state global real
- `[~]` directivas SPA avanzadas (`volt:text`, `volt:class`, `volt:attr`, `volt:style`, `volt:show`, `volt:if`, `volt:for`)
- `[ ]` effects de alto nivel (`toast`, `modal`)
- `[ ]` retry system
- `[ ]` offline mode
- `[ ]` extensibilidad formal del runtime
- `[ ]` transportes avanzados (`WebSocket`, `SSE`, `streaming`)

## Checklist De Desarrollo

### 1. Navigation Engine

- `[x]` interceptar enlaces con `volt:navigate`
- `[x]` usar `pushState`, `replaceState` y `popstate`
- `[x]` fallback a recarga completa ante error de navegacion
- `[x]` preservacion basica de scroll
- `[x]` preservacion basica de foco y seleccion
- `[x]` fallback por cambio de layout
- `[x]` reconciliacion basica/selectiva de `head`
- `[x]` prefetch por hover, viewport o heuristica
- `[x]` preload de assets asociados a la ruta destino
- `[x]` estrategia inicial de activacion para prefetch
- `[x]` cancelar prefetch obsoleto o redundante
- `[x]` reusar respuesta prefetched en `visit()`
- `[x]` evitar duplicar requests si la ruta ya esta en vuelo
- `[x]` registrar metadata basica de cache por URL
- `[x]` expirar entradas prefetched de forma segura
- `[x]` preload selectivo de `head` assets criticos
- `[x]` no reinyectar assets ya presentes en documento actual
- `[x]` soporte declarativo inicial para `volt:prefetch`
- `[-]` fragment cache SPA opt-in por clave declarativa
- `[-]` preservacion opt-in de formularios entre pantallas
- `[-]` preservacion opt-in de componentes vivos entre navegaciones
- `[-]` politicas configurables por ruta para SPA vs full reload
- `[x]` transiciones de pagina enter/leave reales
- `[x]` invalidacion/control de cache de navegacion

### 2. Protocol Client

- `[x]` envio de acciones por `fetch`
- `[x]` envio de navegacion por `fetch`
- `[x]` manejo de stale requests
- `[x]` abort de request anterior concurrente
- `[x]` manejo base de errores de request
- `[ ]` retry automatico para errores transitorios
- `[ ]` estrategia de timeout configurable
- `[ ]` clasificacion formal de errores de protocolo
- `[ ]` telemetria de latencia y payload
- `[ ]` serializacion incremental o streaming de responses

### 3. Component Runtime

- `[x]` descubrimiento base de roots por `data-volt-root`
- `[x]` registro de snapshots en atributos DOM
- `[x]` rehidratacion basica tras respuesta backend
- `[x]` sync de snapshot tras patch
- `[ ]` registro formal de componentes activos con API publica
- `[ ]` destruccion explicita de componentes desmontados
- `[ ]` cleanup agresivo de listeners huerfanos
- `[ ]` nested components complejos
- `[ ]` preservacion de componentes entre navegacion SPA

### 4. State Runtime

- `[x]` estados runtime internos: `loading`, `dirty`, `success`, `error`
- `[x]` politicas runtime por componente/target
- `[x]` client state real sin roundtrip al backend
- `[x]` shared state global entre componentes
- `[x]` API publica tipo `runtime.state`
- `[x]` sincronizacion selectiva frontend/backend
- `[ ]` stores persistentes por sesion o pestaña
- `[ ]` multi-tab synchronization

### 5. DOM Engine

- `[x]` `text.update`
- `[x]` `html.replace`
- `[x]` `dom.append`
- `[x]` `dom.insert`
- `[x]` `dom.remove`
- `[x]` `dom.move`
- `[x]` `attribute.set`
- `[x]` `class.toggle`
- `[x]` `style.set`
- `[x]` `focus`
- `[x]` `scroll`
- `[x]` fallback a reemplazo HTML del root
- `[ ]` patch parcial de arbol mas avanzado
- `[ ]` reconciliacion DOM mas granular
- `[ ]` manejo formal de teleports/portals
- `[ ]` persistencia de zonas globales (`toast-root`, `modal-root`, etc.)

### 6. Effect Engine

- `[x]` `navigate`
- `[x]` `dispatch.event`
- `[x]` `runtime.policy`
- `[x]` transiciones por patch y update
- `[ ]` `toast`
- `[ ]` `modal`
- `[ ]` effects extensibles registrados por usuario
- `[ ]` middleware de effects
- `[ ]` cola de effects post-render configurable

### 7. Directives System

- `[x]` `volt:click`
- `[x]` `volt:model`
- `[x]` `volt:submit`
- `[x]` `volt:navigate`
- `[x]` `volt:loading`
- `[x]` `volt:dirty`
- `[x]` `volt:success`
- `[x]` `volt:error`
- `[x]` `volt:text`
- `[x]` `volt:class`
- `[x]` `volt:attr`
- `[x]` `volt:style`
- `[x]` `volt:show`
- `[x]` `volt:if`
- `[x]` `volt:for`
- `[~]` directivas runtime personalizadas (`volt:text`, `volt:class`, `volt:attr`, `volt:style`)
- `[ ]` parser extensible de directivas frontend

### 8. Transition Engine

- `[x]` transiciones basicas por fase
- `[x]` `loading delay`
- `[x]` `loading min-duration`
- `[x]` `success timeout`
- `[x]` `success min-duration`
- `[x]` `error timeout`
- `[x]` `dirty debounce`
- `[x]` transiciones de pagina SPA completas
- `[x]` leave transitions reales antes de navegar
- `[x]` coordinacion entre transition engine y navigation engine
- `[x]` perfiles de transicion reutilizables

### 9. Runtime Extensibility

- `[x]` hooks DOM/runtime basicos emitidos como eventos
- `[ ]` API publica `runtime.on(...)`
- `[ ]` plugins frontend
- `[ ]` custom effects
- `[ ]` runtime middleware
- `[ ]` navigation middleware
- `[ ]` hydration middleware
- `[ ]` effect middleware

### 10. Resilience Y Modo Offline

- `[ ]` retry system
- `[ ]` offline snapshots
- `[ ]` queued actions
- `[ ]` sync recovery
- `[ ]` deteccion de desconexion
- `[ ]` modo degradado con progressive enhancement mas formal

### 11. Transportes Futuros

- `[x]` HTTP como transporte inicial
- `[ ]` WebSocket transport
- `[ ]` SSE transport
- `[ ]` streaming UI
- `[ ]` concurrent rendering real

## Checklist De Pruebas

### A. Navegacion SPA

- `[ ]` navegar entre dos vistas con mismo layout sin recarga completa
- `[ ]` navegar entre layouts distintos y verificar fallback a full reload
- `[ ]` volver con `popstate` y validar contenido correcto
- `[ ]` validar preservacion de scroll normal
- `[ ]` validar `volt:preserve-scroll`
- `[ ]` validar reconciliacion de `head` con estilos y scripts
- `[ ]` validar que no se dupliquen scripts del `head`
- `[ ]` validar navegacion con error HTTP y fallback correcto

### B. Acciones Reactivas

- `[ ]` click simple con `volt:click`
- `[ ]` submit con `volt:submit`
- `[ ]` sincronizacion de `volt:model`
- `[ ]` actualizacion de snapshot tras response
- `[ ]` stale request descartada correctamente
- `[ ]` abort de request previa concurrente
- `[ ]` checksum invalido o payload roto manejado con error seguro

### C. Estados Runtime

- `[ ]` `loading` visible y oculto segun delay/min-duration
- `[ ]` `dirty` con debounce
- `[ ]` `success` con timeout y min-duration
- `[ ]` `error` con timeout
- `[ ]` filtros por `action`
- `[ ]` filtros por `target`

### D. DOM Y Effects

- `[ ]` `text.update`
- `[ ]` `html.replace`
- `[ ]` `dom.append`
- `[ ]` `dom.insert`
- `[ ]` `dom.remove`
- `[ ]` `dom.move`
- `[ ]` `class.toggle`
- `[ ]` `style.set`
- `[ ]` `focus`
- `[ ]` `scroll`
- `[ ]` `navigate`
- `[ ]` `dispatch.event`

### E. Foco, Seleccion Y Scroll

- `[ ]` preservar foco en input tras patch
- `[ ]` preservar seleccion en input/textarea
- `[ ]` preservar scroll interno en contenedores marcados
- `[ ]` restaurar scroll tras reemplazo HTML del root

### F. Layout Y Head

- `[ ]` inline page con `@extends('layouts.app')`
- `[ ]` retorno a home sin perder estilos
- `[ ]` cambio de layout con fallback automatico
- `[ ]` keys de `head` estables en assets de Vite
- `[ ]` no perder `meta charset` ni `viewport`

### G. Errores Y Seguridad

- `[ ]` error de navegacion SPA
- `[ ]` error de protocolo reactivo
- `[ ]` error de validacion backend
- `[ ]` CSRF invalido
- `[ ]` snapshot invalido
- `[ ]` accion no permitida

### H. Performance Basica

- `[ ]` medir tiempo de boot inicial
- `[ ]` medir costo de navegacion SPA entre vistas
- `[ ]` medir costo de patch en acciones frecuentes
- `[ ]` revisar crecimiento de listeners o timers tras muchas interacciones

## Bitacora De Avance

Usar esta seccion para marcar hitos reales conforme avancemos.

### 2026-06

- `[x]` navegacion SPA base funcional
- `[x]` hooks `volt:request-start`, `volt:request-finish`, `volt:request-stale`
- `[x]` reconciliacion selectiva del `head`
- `[x]` fallback por cambio de layout
- `[x]` soporte para layouts en single page components inline
- `[-]` bloque activo definido para `prefetch` y `preload` SPA
- `[x]` cache en memoria por URL implementada para navegacion SPA
- `[x]` reuso de requests en vuelo para navegacion
- `[x]` prefetch inicial por `pointerenter` y `focus`
- `[x]` preload selectivo inicial de assets criticos del `head` en respuestas prefetched
- `[x]` cancelacion de prefetch por perdida de interes (`pointerleave` y `focusout`)
- `[x]` prefetch por proximidad al viewport con `IntersectionObserver`
- `[x]` heuristica inicial en tiempo ocioso para prefetchear el enlace visible o cercano mas probable
- `[x]` control declarativo de cache SPA por enlace o documento
- `[x]` invalidacion explicita de cache por evento runtime
- `[x]` validacion tecnica HTTP del bloque de cache sobre rutas demo
- `[-]` MVP inicial de `fragment cache SPA` por clave declarativa
- `[x]` politica documental inicial para descartar fragmentos con `volt-fragment-control`
- `[x]` demo cruzada de formulario preservado + shell vivo preservado entre `/fragmentCache` y `/formExample`
- `[x]` ruta demo `/fragmentCacheReset` para validar descarte por politica de documento
- `[x]` contrato inicial de `auto`, `spa` y `reload` por enlace en `volt:navigate`
- `[x]` politica documental inicial con `<meta name="volt-navigation-mode" content="reload">`
- `[x]` laboratorio `/navigationPolicy` y destino `/navigationDocumentReload`
- `[x]` MVP inicial de `page transitions` SPA con `leave -> swap -> enter`
- `[x]` politica declarativa por enlace con `data-volt-page-transition`
- `[x]` politica documental con meta tags `volt-page-transition`
- `[x]` demo `/navigationTransition` y destino `/navigationTransitionAlt`
- `[x]` perfiles reutilizables `soft`, `gentle`, `crisp` y `classic` por enlace o documento
- `[x]` API publica `window.Volt.state` con stores `client` y `shared`
- `[x]` demo `/runtimeState` y `/runtimeStateAlt` para validar reset por URL y persistencia global
- `[x]` sincronizacion selectiva desde `client/shared state` hacia `params` o `updates`
- `[x]` effects backend -> frontend para `state.set`, `state.merge`, `state.delete` y `state.clear`
- `[x]` MVP de `volt:show` con expresiones `client:path` y `shared:path`
- `[x]` MVP de `volt:if` con mount/unmount por `client:path` y `shared:path`
- `[x]` MVP de `volt:for` con repeticion sobre arreglos `client/shared` y placeholders simples
- `[x]` MVP de `volt:text` para escribir texto desde `client/shared state`
- `[x]` MVP de `volt:class` para alternar clases CSS desde `client/shared state`
- `[x]` MVP de `volt:attr` para alternar atributos HTML desde `client/shared state`
- `[x]` MVP de `volt:style` para alternar estilos inline desde `client/shared state`

## Proximo Bloque Recomendado

Orden sugerido para seguir avanzando:

1. `fragment cache SPA`
2. pruebas manuales y automatizadas de `fragment cache SPA`, `prefetch`/`preload` y `head` + layout fallback
3. validacion manual fina de `politicas configurables por ruta para SPA vs full reload`
4. sincronizacion selectiva frontend/backend
5. expresiones compuestas para directivas runtime

## Bloque Cerrado Reciente

### Prefetch Y Preload SPA

Estado actual:

- `[x]` MVP implementado; validacion manual fina aun pendiente

Objetivo del bloque:

- anticipar navegaciones probables
- reducir latencia percibida
- reutilizar respuestas HTML ya obtenidas
- preparar assets criticos sin romper la coherencia del `head`

Checklist inmediato:

- `[x]` definir politica MVP de prefetch
- `[x]` implementar prefetch por `hover`
- `[x]` evaluar prefetch por `IntersectionObserver`
- `[x]` implementar heuristica de prefetch en tiempo ocioso para enlaces visibles o cercanos
- `[x]` agregar cache temporal en memoria por URL
- `[x]` integrar cache prefetched con `visit()`
- `[x]` evitar race conditions entre `prefetch` y `navigate`
- `[x]` implementar preload de assets criticos del documento destino
- `[-]` agregar pruebas manuales del flujo
- `[ ]` agregar pruebas automatizadas focalizadas si aportan valor

Resultado esperado del bloque:

- navegar con `volt:navigate` usando respuesta prefetched cuando exista
- reducir requests duplicadas
- preparar estilos/scripts criticos antes del patch del documento

Validacion tecnica ejecutada:

- `[x]` servidor local levantado en `http://127.0.0.1:8000`
- `[x]` respuestas `200` verificadas para `/`, `/counterExample` y `/formExample`
- `[x]` shell compartida confirmada con `data-volt-layout="app"` en las tres rutas
- `[x]` enlaces `volt:navigate` presentes en la home para disparar prefetch SPA real
- `[x]` `head` compatible verificado con `data-volt-head-key` y scripts modulo gestionados por layout
- `[x]` respuestas `200` reconfirmadas para `/`, `/counterExample` y `/formExample` despues del cambio de invalidacion/cache
- `[x]` `data-volt-layout="app"` y `data-volt-head-key` reconfirmados en las tres respuestas HTML tras el cambio
- `[x]` shell actual emitiendo assets frontend via Vite dev server en `http://127.0.0.1:5173`
- `[-]` validacion manual real de red/navegador pendiente para confirmar visualmente los hints `preload` y `modulepreload`

### Diseno MVP: Cache En Memoria Por URL

Objetivo:

- reutilizar respuestas de navegacion obtenidas por prefetch
- evitar requests duplicadas hacia la misma URL
- reducir la latencia percibida al hacer click en enlaces SPA

Alcance del MVP:

- solo memoria del navegador
- solo pestaña actual
- sin persistencia entre recargas completas
- sin compartir cache entre tabs

#### Estructura propuesta

Estado runtime nuevo:

```js
runtime.navigationCache = new Map();
runtime.navigationInFlight = new Map();
```

Clave de cache:

```txt
URL normalizada absoluta
```

Ejemplo:

```js
const key = new URL(link.href, window.location.href).toString();
```

#### Forma de una entrada cacheada

```js
{
  url: "https://app.test/formExample",
  finalUrl: "https://app.test/formExample",
  html: "<!doctype html>...</html>",
  document: parsedDocument,
  fetchedAt: 1718200000000,
  expiresAt: 1718200005000
}
```

Campos obligatorios del MVP:

- `url`
- `finalUrl`
- `document` o `html`
- `fetchedAt`
- `expiresAt`

Campos opcionales futuros:

- `headSummary`
- `redirected`
- `status`
- `source` (`prefetch` o `navigate`)

#### Politica inicial

- TTL recomendado: `5s`
- maximo de entradas: `10`
- si una entrada expira: eliminarla
- si una URL ya esta en vuelo: reutilizar esa promesa
- si una URL ya esta cacheada y vigente: no volver a prefetchear

#### Flujo propuesto

1. el usuario pone el cursor sobre un link con `volt:navigate`
2. el runtime dispara `prefetchPage(url)`
3. si la URL ya esta en cache y no expiro, no hace nada
4. si la URL ya tiene una request en vuelo, reutiliza la promesa
5. si no existe entrada, hace `requestPage(url)`
6. guarda la respuesta en `navigationCache`
7. cuando el usuario hace click:
   - si existe cache valida, `visit()` la usa
   - si no existe, `visit()` hace fetch normal

#### Integracion con `visit()`

Orden recomendado dentro de `visit(url, options)`:

1. normalizar URL
2. consultar `navigationCache`
3. si hay entrada valida, usarla como `payload`
4. si no hay entrada valida pero existe request en vuelo, esperarla
5. si no hay nada, ejecutar `requestPage()`
6. despues continuar con:
   - validacion de stale request
   - fallback por cambio de layout
   - reconciliacion de `head`
   - patch del `body`
   - `history.pushState` o `replaceState`

#### Integracion con prefetch

Triggers recomendados para el MVP:

- `pointerenter`
- `focus`

Triggers opcionales posteriores:

- `IntersectionObserver`
- heuristica por prioridad/ruta frecuente configurable
- prefetch programatico

#### Regla para evitar duplicados

Si la URL ya esta en `navigationInFlight`, el runtime no debe abrir otra request.

### Soporte Declarativo Inicial: `volt:prefetch`

Estado actual:

- `[x]` disponible en MVP inicial

Modos soportados por enlace:

- `volt:prefetch` o `volt:prefetch="auto"`: habilita las fuentes normales del runtime
- `volt:prefetch="hover"`: limita el prefetch a `pointerenter` y `focus`
- `volt:prefetch="viewport"`: limita el prefetch a `IntersectionObserver`
- `volt:prefetch="idle"`: limita el prefetch a la heuristica en tiempo ocioso
- `volt:prefetch="none"`: deshabilita el prefetch para ese enlace
- `volt:prefetch="hover viewport"`: permite combinar fuentes por lista simple

Alcance actual:

- funciona sobre enlaces same-origin
- puede convivir con `volt:navigate`
- no reemplaza aun una API declarativa mas rica por politica, prioridad o TTL

### Control E Invalidacion De Cache De Navegacion

Estado actual:

- `[x]` disponible en MVP actual

Controles soportados por enlace:

- `volt:cache="reload"`: omite la entrada cacheada actual y fuerza lectura desde red; la nueva respuesta puede quedar cacheada
- `volt:cache="invalidate"`: invalida primero la URL objetivo y luego vuelve a resolverla normalmente
- `volt:cache="no-store"`: omite cache de lectura y almacenamiento para esa navegacion
- `volt:cache="ttl=15s"` o `volt:cache="max-age=15s"`: redefine el TTL de la entrada almacenada para esa ruta
- `volt:cache="reload ttl=15s"`: permite combinar modo y TTL en la misma directiva

Control soportado por documento destino:

- `<meta name="volt-cache-control" content="no-store">`
- `<meta name="volt-cache-control" content="reload">`
- `<meta name="volt-cache-control" content="ttl=15s">`
- `<meta name="volt-cache-control" content="reload ttl=15s">`
- `<meta name="volt:navigation-cache" content="...">`: alias equivalente para el runtime

Comportamiento implementado:

- el runtime guarda aliases por URL solicitada y `finalUrl`, por lo que redirects o URLs canonicas invalidan/reusan la misma entrada
- el runtime invalida entradas expiradas por TTL y tambien permite invalidacion explicita antes de reutilizar una respuesta
- si una navegacion real llega con `reload`, `invalidate` o `no-store`, puede abortar un `prefetch` anterior incompatible y resolver una respuesta nueva
- `prefetch` respeta `volt:cache`; en `no-store` no precalienta cache persistente y en `reload`/`invalidate` refresca la entrada

Eventos emitidos:

- `volt:cache-hit`
- `volt:cache-miss`
- `volt:cache-store`
- `volt:cache-invalidate`
- `volt:cache-clear`

Invalidacion explicita desde frontend:

```js
document.dispatchEvent(new CustomEvent('volt:navigation-cache-invalidate', {
  detail: {
    url: '/formExample',
    reason: 'manual',
  },
}));
```

Para limpiar toda la cache SPA actual:

```js
document.dispatchEvent(new CustomEvent('volt:navigation-cache-invalidate', {
  detail: {
    reason: 'manual',
  },
}));
```

Validacion tecnica ejecutada para este bloque:

- `[x]` `volt.js` sin errores de diagnostico tras introducir la capa de cache control
- `[x]` validacion de sintaxis con `node --check`
- `[x]` servidor local confirmado en `http://127.0.0.1:8000`
- `[x]` rutas demo `/`, `/counterExample` y `/formExample` respondiendo `200`
- `[x]` shell compartida `app` conservada en las tres rutas
- `[-]` validacion visual/manual pendiente para confirmar eventos `volt:cache-hit|miss|store|invalidate` desde el navegador

Debe reutilizar la promesa existente:

```js
if (runtime.navigationInFlight.has(url)) {
  return runtime.navigationInFlight.get(url);
}
```

#### Regla de expiracion

Al leer una entrada:

- si `Date.now() > expiresAt`, eliminar y tratar como miss

Al insertar una entrada:

- si el cache supera `10` entradas, eliminar la mas antigua

#### Estrategia de almacenamiento recomendada

MVP:

- guardar `html`
- parsear a `Document` al usarla o al guardarla

Alternativa:

- guardar directamente `document`

Decision sugerida para VoltStack:

- guardar `html` y `finalUrl`
- regenerar `Document` con `DOMParser` cuando haga falta

Motivo:

- reduce acoplamiento con nodos DOM vivos
- hace mas simple la expiracion
- minimiza problemas por referencias mutables

#### Riesgos conocidos

- usar HTML stale si el TTL es demasiado largo
- aumentar consumo de memoria si se guardan muchas respuestas
- prefetchear rutas altamente dinamicas puede traer poco valor
- cachear documentos con estado muy sensible puede causar percepcion de desactualizacion

#### Reglas de seguridad y consistencia

- no usar cache si la navegacion real detecta cambio de layout incompatible y requiere fallback
- no asumir que una respuesta prefetched evita validaciones posteriores
- no reinyectar assets del `head` ya presentes en el documento actual
- no mezclar cache de navegacion con snapshots reactivos de componentes

#### Checklist tecnico del MVP

- `[x]` agregar `runtime.navigationCache`
- `[x]` agregar `runtime.navigationInFlight`
- `[x]` agregar helper `normalizeNavigationUrl(url)`
- `[x]` agregar helper `getCachedNavigation(url)`
- `[x]` agregar helper `setCachedNavigation(url, entry)`
- `[x]` agregar helper `pruneNavigationCache()`
- `[x]` agregar `prefetchPage(url, options)`
- `[x]` integrar cache dentro de `visit()`
- `[x]` integrar triggers `pointerenter` y `focus`
- `[x]` registrar invalidacion por TTL
- `[x]` verificar que no se dupliquen requests
- `[x]` probar reuso real del payload prefetched

## Bloque Activo Actual

### Fragment Cache SPA

Estado actual:

- `[-]` MVP inicial implementado; contrato de preserve/reset ya aterrizado y pendiente de validacion manual final
- `[x]` dependencias previas cubiertas: cache de navegacion, invalidacion explicita y demo UI de observabilidad

Objetivo del bloque:

- reutilizar partes estables de pantalla sin depender solo del documento HTML completo
- preservar zonas o fragmentos entre navegaciones SPA compatibles
- reducir trabajo de render y patch cuando solo cambian regiones concretas
- preparar la base para preservacion opt-in de formularios y componentes vivos

Checklist inmediato:

- `[x]` definir el alcance MVP de `fragment cache SPA`
- `[x]` decidir un primer nivel por fragmento con clave declarativa compartida
- `[x]` definir reglas de invalidez cuando cambie `layout`, `head` o politica de ruta
- `[x]` definir una convencion declarativa para preservar fragmentos
- `[x]` implementar una primera preservacion opt-in de formularios entre pantallas
- `[x]` implementar una primera preservacion opt-in de componentes vivos entre navegaciones
- `[ ]` agregar pruebas manuales focalizadas para reuse, invalidez y fallback seguro

Resultado esperado del bloque:

- conservar fragmentos compatibles al navegar entre vistas afines
- evitar reconstrucciones completas cuando no aportan valor
- dejar una base clara para politicas de preservacion mas avanzadas

Contrato MVP actual:

- marcar el fragmento con `data-volt-preserve="clave"` o `volt:preserve="clave"`
- renderizar la misma clave en la pantalla destino
- el runtime reutiliza el nodo anterior si el `layout` sigue siendo compatible y el tag coincide
- el destino puede forzar descarte global con `<meta name="volt-fragment-control" content="reset">`
- si la navegacion usa una politica `no-store`, el runtime no reutiliza fragmentos preservados del origen
- si no hay clave compatible o el fragmento no es reutilizable, se descarta con fallback seguro al HTML nuevo

Reglas MVP de invalidez ya implementadas:

- cambio de `layout`: se mantiene el fallback a full reload y no se intenta preservar
- politica documental `reset`: el runtime emite `volt:fragment-discard` con razon `document-policy`
- politica de navegacion `no-store`: el runtime emite `volt:fragment-discard` con razon `navigation-policy`
- mismatch de clave o `tag`: el runtime descarta el fragmento con razones como `missing-target` o `tag-mismatch`

Rutas demo actuales:

- `/fragmentCache`: origen principal para editar formulario y shell vivo preservables
- `/formExample`: destino compatible que reutiliza `draft-fragment` y `live-shell`
- `/fragmentCacheReset`: destino con descarte forzado por politica documental

Nota:

- aun falta la validacion manual fina en navegador para cerrar `fragment cache SPA` y revisar en una sola pasada `preload`, `modulepreload`, `volt:fragment-preserve` y `volt:fragment-discard` en condiciones reales.

## Contrato Actual: Perfiles De Transicion Reutilizables

Estado actual:

- `[x]` disponible en MVP actual

Perfiles iniciales soportados:

- `soft`: resuelve `fade`, `220ms`, `out-in`
- `gentle`: resuelve `fade`, `320ms`, `out-in`
- `crisp`: resuelve `fade`, `160ms`, `out-in`
- `classic`: resuelve `default`, `180ms`, `out-in`

Declaracion por enlace:

```html
<a
  href="/navigationTransitionProfile"
  volt:navigate
  data-volt-page-transition-profile="soft"
>
```

Declaracion por documento destino:

```html
<meta name="volt-page-transition-profile" content="gentle">
```

Reglas actuales:

- el perfil aporta `name`, `duration` y `mode` por defecto
- `data-volt-page-transition`, `data-volt-page-transition-duration` y `data-volt-page-transition-mode` pueden seguir sobrescribiendo partes del perfil
- el runtime expone `pageTransitionProfile`, `pageTransitionSource`, `pageTransitionMode` y `pageTransitionDuration` en `volt:before-navigate`, `volt:navigated`, `volt:before-enter` y `volt:after-enter` cuando aplica
- la demo separa un destino neutro (`/navigationTransitionProfile`) para validar perfil por enlace y un destino documental (`/navigationTransitionAlt`) para validar perfil por meta

## Contrato Actual: Client State Y Shared State

Estado actual:

- `[x]` disponible en MVP actual

API publica:

```js
window.Volt.state.get(key, { scope: 'client' | 'shared' })
window.Volt.state.set(key, value, { scope: 'client' | 'shared' })
window.Volt.state.merge(key, partial, { scope: 'client' | 'shared' })
window.Volt.state.update(key, updater, { scope: 'client' | 'shared' })
window.Volt.state.delete(key, { scope: 'client' | 'shared' })
window.Volt.state.clear({ scope: 'client' | 'shared', reason: 'manual' })
window.Volt.state.snapshot({ scope: 'client' | 'shared' })
window.Volt.state.subscribe(key, listener, { scope: 'client' | 'shared' })
window.Volt.state.currentScope()
```

Reglas actuales:

- `scope: 'client'` vive en memoria del runtime y queda ligado a la URL SPA actual
- al navegar a otra pantalla SPA compatible, el runtime cambia el `currentScope()` del cliente y limpia el store `client`
- `scope: 'shared'` permanece disponible entre pantallas SPA mientras la pestaña siga viva
- ambos stores son puramente frontend; no hacen roundtrip al backend ni persisten todavia entre recargas completas

Eventos emitidos:

- `volt:state-changed`
- `volt:state-cleared`
- `volt:state-scope-changed`

Rutas demo:

- `/runtimeState`: origen para mutar `client` y `shared`
- `/runtimeStateAlt`: destino para confirmar que `client` se reinicia y `shared` persiste

## Contrato Actual: Sincronizacion Selectiva Frontend/Backend

Estado actual:

- `[x]` disponible en MVP actual

Declaracion en el trigger o formulario:

```html
<form
  volt-submit="captureSelectiveSync"
  data-volt-state-sync="client:draft.note->params.clientNote, shared:draft.note->params.sharedNote, shared:counter->updates.sharedCounterMirror">
</form>
```

Reglas actuales:

- cada regla usa el formato `scope:path->destination.field`
- `scope` acepta `client` o `shared`
- `destination` acepta `params` o `updates`
- la lectura del origen soporta rutas con punto como `draft.note`
- el destino actual es plano, por ejemplo `params.clientNote` o `updates.title`
- solo las claves declaradas viajan al backend en la accion reactiva

Backend -> frontend:

- `ActionEffectOptions` y `ActionManualEffectBuilder` soportan:
  - `stateSet(scope, key, value)`
  - `stateMerge(scope, key, value)`
  - `stateDelete(scope, key)`
  - `stateClear(scope, reason)`

Eventos runtime:

- `volt:state-sync`
- `volt:state-changed`
- `volt:state-cleared`
- `volt:state-scope-changed`

Rutas demo:

- `/runtimeState`: muta stores, envia sync selectivo y recibe `shared.serverSync` desde el backend

## Contrato Actual: Volt Show

Estado actual:

- `[x]` disponible en MVP inicial

Declaracion actual:

```html
<section volt:show="client:ui.showClientPanel"></section>
<section volt:show="shared:ui.showSharedPanel"></section>
<section volt:show.hide="shared:ui.showSharedPanel"></section>
```

Reglas actuales:

- acepta expresiones simples con `client:path` o `shared:path`
- soporta negacion con `!`, por ejemplo `volt:show="!shared:ui.showSharedPanel"`
- el `path` soporta acceso con punto como `draft.note` o `ui.showSharedPanel`
- un valor truthy muestra el elemento en `volt:show`
- un valor truthy oculta el elemento en `volt:show.hide`
- el DOM se resincroniza al mutar `window.Volt.state`, al aplicar effects y despues de navegar por SPA

Limitaciones actuales:

- no evalua expresiones arbitrarias
- no soporta todavia comparaciones, operadores logicos ni lectura directa de snapshot backend
- `volt:show` ya cubre visibilidad basada en state; los siguientes pasos quedan en directivas mas expresivas

Rutas demo:

- `/runtimeState`: origen para alternar paneles visibles por `client` y `shared`
- `/runtimeStateAlt`: destino para validar que `client` se limpia por URL y `shared` persiste

## Contrato Actual: Volt If

Estado actual:

- `[x]` disponible en MVP inicial

Declaracion actual:

```html
<section volt:if="client:ui.mountClientPanel"></section>
<section volt:if="shared:ui.mountSharedPanel"></section>
```

Reglas actuales:

- acepta expresiones simples con `client:path` o `shared:path`
- soporta negacion con `!`, por ejemplo `volt:if="!shared:ui.mountSharedPanel"`
- el `path` soporta acceso con punto como `ui.mountSharedPanel`
- cuando la expresion es falsy, el nodo se desmonta del DOM
- cuando la expresion vuelve a ser truthy, el runtime vuelve a montar una clonacion fresca del nodo original
- el DOM se resincroniza al mutar `window.Volt.state`, al aplicar effects y despues de navegar por SPA

Limitaciones actuales:

- no preserva estado interno efimero del nodo montado al desmontar; al volver a montar se crea desde el markup original
- no soporta todavia bloques `else`
- `volt:for` ya cubre el siguiente MVP estructural del bloque

Rutas demo:

- `/runtimeState`: origen para alternar nodos montados por `client` y `shared`
- `/runtimeStateAlt`: destino para validar que `client` se desmonta por URL y `shared` puede seguir montado

## Contrato Actual: Volt For

Estado actual:

- `[x]` disponible en MVP inicial

Declaracion actual:

```html
<article volt:for="card, index in client:list.items">
  <strong>{{ index }}. {{ card.title }}</strong>
  <p>{{ card.detail }}</p>
</article>

<article volt:for="card, index in shared:list.items">
  <strong>{{ index }}. {{ card.title }}</strong>
</article>
```

Reglas actuales:

- acepta expresiones con formato `alias in scope:path` o `alias, index in scope:path`
- `scope` puede ser `client` o `shared`
- el `path` soporta acceso con punto y debe resolver a un arreglo
- en el clon se soportan placeholders simples `{{ alias }}`, `{{ alias.prop }}` y `{{ index }}`
- el runtime vuelve a renderizar la lista completa al mutar `window.Volt.state`, al aplicar effects y despues de navegar por SPA
- la lista `client` cambia de scope con la URL; la lista `shared` permanece en memoria de la pestaña

Limitaciones actuales:

- solo soporta arreglos; no itera todavia objetos o rangos
- no hay diff granular por item; el MVP vuelve a renderizar la lista completa
- no soporta todavia `key`, reordenamiento optimizado ni plantillas condicionales por item
- no evalua expresiones arbitrarias dentro de `{{ }}`

Rutas demo:

- `/runtimeState`: origen para agregar y quitar items en `client:list.items` y `shared:list.items`
- `/runtimeStateAlt`: destino para validar que la lista `client` se reinicia por URL y la `shared` persiste

## Contrato Actual: Volt Text

Estado actual:

- `[x]` disponible en MVP inicial

Declaracion actual:

```html
<span volt:text="client:draft.note"></span>
<span volt:text="shared:draft.note"></span>
<span volt:text="shared:serverSync.syncedAt"></span>
```

Reglas actuales:

- acepta expresiones simples con `client:path` o `shared:path`
- el `path` soporta acceso con punto como `draft.note` o `serverSync.syncedAt`
- escribe el resultado en `textContent` del nodo destino
- si el valor es `null`, `undefined` o no existe, el texto queda vacio
- si el valor es un objeto o arreglo, el MVP lo serializa con `JSON.stringify`
- el DOM se resincroniza al mutar `window.Volt.state`, al aplicar effects y despues de navegar por SPA

Limitaciones actuales:

- no soporta todavia fallback textual declarativo
- no evalua expresiones arbitrarias ni concatenaciones
- no distingue todavia entre `textContent` y `innerText`; siempre usa `textContent`

Rutas demo:

- `/runtimeState`: origen para ver texto desde `client.draft.note`, `shared.draft.note` y `shared.serverSync.syncedAt`
- `/runtimeStateAlt`: destino para validar que el texto `client` se reinicia por URL y el `shared` persiste

## Contrato Actual: Volt Class

Estado actual:

- `[x]` disponible en MVP inicial

Declaracion actual:

```html
<article volt:class="client:ui.highlightClientCard -> ring-4 ring-cyan-400"></article>
<article volt:class="shared:ui.highlightSharedCard -> ring-4 ring-fuchsia-400"></article>
<article volt:class="!shared:ui.highlightSharedCard -> opacity-60"></article>
```

Reglas actuales:

- acepta expresiones con formato `scope:path -> clases`
- `scope` puede ser `client` o `shared`
- soporta negacion con `!`, por ejemplo `!shared:ui.highlightSharedCard -> opacity-60`
- la lista de clases se separa por espacios y se aplica con `classList`
- si la condicion pasa a falsy, el runtime quita solo las clases controladas por esa directiva
- si el elemento ya tenia una clase originalmente, el runtime la restaura al desactivar la directiva
- el DOM se resincroniza al mutar `window.Volt.state`, al aplicar effects y despues de navegar por SPA

Limitaciones actuales:

- no evalua expresiones arbitrarias ni objetos estilo `{ active: condition }`
- no soporta todavia combinaciones declarativas con multiples reglas en un mismo atributo
- no hace diff semantico de utilidades CSS; solo alterna la lista literal declarada

Rutas demo:

- `/runtimeState`: origen para alternar resaltado en tarjetas cliente y compartida
- `/runtimeStateAlt`: destino para validar que el resaltado `client` se reinicia por URL y el `shared` persiste

## Contrato Actual: Volt Attr

Estado actual:

- `[x]` disponible en MVP inicial

Declaracion actual:

```html
<button volt:attr="client:ui.lockClientAction -> disabled=disabled, aria-disabled=true"></button>
<button volt:attr="shared:ui.lockSharedAction -> disabled=disabled, data-lock=shared"></button>
<div volt:attr="!shared:ui.lockSharedAction -> data-state=ready"></div>
```

Reglas actuales:

- acepta expresiones con formato `scope:path -> atributo=valor, otro=valor`
- `scope` puede ser `client` o `shared`
- soporta negacion con `!`, por ejemplo `!shared:ui.lockSharedAction -> data-state=ready`
- la lista de atributos se separa por comas
- si la condicion es truthy, el runtime aplica cada atributo con `setAttribute`
- si la condicion vuelve a falsy, el runtime restaura el valor original de cada atributo o lo elimina si no existia
- el DOM se resincroniza al mutar `window.Volt.state`, al aplicar effects y despues de navegar por SPA

Limitaciones actuales:

- no soporta todavia sintaxis booleana especial distinta de la presencia normal del atributo
- no evalua expresiones arbitrarias ni objetos tipo `{ disabled: condition }`
- no soporta multiples reglas independientes dentro del mismo atributo `volt:attr`

Rutas demo:

- `/runtimeState`: origen para bloquear acciones cliente y compartida desde atributos
- `/runtimeStateAlt`: destino para validar que los atributos `client` se reinician por URL y los `shared` persisten

## Contrato Actual: Volt Style

Estado actual:

- `[x]` disponible en MVP inicial

Declaracion actual:

```html
<article volt:style="client:ui.softenClientCard -> opacity:0.55; transform:scale(0.98)"></article>
<article volt:style="shared:ui.softenSharedCard -> opacity:0.7; box-shadow:0 18px 40px rgba(217,70,239,0.22)"></article>
<div volt:style="!shared:ui.softenSharedCard -> opacity:1"></div>
```

Reglas actuales:

- acepta expresiones con formato `scope:path -> propiedad:valor; otra-propiedad:valor`
- `scope` puede ser `client` o `shared`
- soporta negacion con `!`, por ejemplo `!shared:ui.softenSharedCard -> opacity:1`
- la lista de declaraciones se separa por `;`
- si la condicion es truthy, el runtime aplica cada declaracion con `style.setProperty`
- si la condicion vuelve a falsy, el runtime restaura el valor inline original de cada propiedad o la elimina si no existia
- el DOM se resincroniza al mutar `window.Volt.state`, al aplicar effects y despues de navegar por SPA

Limitaciones actuales:

- no soporta todavia objetos estilo `{ opacity: condition }`
- no evalua expresiones arbitrarias ni calculos dinamicos dentro del valor
- no soporta multiples reglas independientes dentro del mismo atributo `volt:style`

Rutas demo:

- `/runtimeState`: origen para alternar estilos inline cliente y compartidos
- `/runtimeStateAlt`: destino para validar que los estilos `client` se reinician por URL y los `shared` persisten

## Como Actualizar Este Archivo

Regla simple de trabajo:

- cuando algo se empiece, cambiar `[ ]` por `[-]`
- cuando quede usable y validado, cambiar por `[x]`
- si aparece un bloqueo o riesgo importante, marcar `[!]`
- registrar el hito en `Bitacora De Avance`
