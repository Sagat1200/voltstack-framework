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
- `[x]` directivas SPA avanzadas (`volt:text`, `volt:class`, `volt:attr`, `volt:style`, `volt:show`, `volt:if`, `volt:for`)
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

- `[x]` `volt:html`
- `[x]` `volt:bind`
- `[x]` `volt:model.local`
- `[x]` `volt:model.sync`
- `[x]` `volt:on`
- `[x]` `volt:dispatch`
- `[x]` `volt:focus`
- `[x]` `volt:portal`
- `[-]` `volt:preserve` (fragment preserve SPA opt-in actual)
- `[-]` `volt:persist`
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
- `[x]` directivas runtime mas expresivas (`volt:text`, `volt:class`, `volt:attr`, `volt:style`, `volt:show`, `volt:if`)
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

### I. Eficiencia Y Escalabilidad

Objetivo:

- detectar cuellos de botella reales en memoria, red, CPU, DOM e hidratacion
- medir degradacion progresiva del runtime bajo uso prolongado
- validar que SPA, acciones reactivas y cache sigan aportando valor bajo carga

Checklist sugerido:

- `[ ]` medir si el runtime consume demasiada memoria tras navegacion SPA prolongada
- `[ ]` medir si existen fugas de memoria por listeners, timers, snapshots o nodos huerfanos
- `[ ]` medir si los payloads de acciones y navegacion crecen demasiado con el tiempo
- `[ ]` medir tamaño de `snapshot`, `updates`, `effects` y HTML devuelto por request
- `[ ]` medir si el sistema hace demasiadas peticiones por una sola interaccion de usuario
- `[ ]` validar que no haya requests duplicadas entre `prefetch`, `navigate` y acciones reactivas
- `[ ]` medir latencia total de hidratacion/rehidratacion por componente y por pagina
- `[ ]` medir tiempo de bootstrap del runtime tras carga inicial del documento
- `[ ]` medir costo de patch DOM en componentes pequenos, medianos y grandes
- `[ ]` medir costo de reconciliacion de `head`, layout y fragment cache en navegaciones repetidas
- `[ ]` medir impacto de `volt:model.sync` bajo escritura rapida y multiples campos concurrentes
- `[ ]` medir frecuencia de `abort`, `stale request` y retrabajo de red bajo concurrencia
- `[ ]` medir crecimiento del numero de componentes activos, roots y snapshots registrados
- `[ ]` medir cantidad de nodos DOM afectados por patch frente al cambio visual real
- `[ ]` medir si hay layout thrashing o reflows costosos durante patch, focus o scroll restore
- `[ ]` medir tiempo de ejecucion de directivas runtime complejas (`volt:if`, `volt:for`, `volt:show`, `volt:class`, `volt:style`)
- `[ ]` medir costo de expresiones compuestas y multiples reglas declarativas sobre el mismo subarbol
- `[ ]` medir hit ratio real de cache SPA, `prefetch`, `preload` y fragment cache
- `[ ]` medir costo de invalidaciones frecuentes de cache y descarte de fragmentos preservados
- `[ ]` medir duplicacion de assets, metas o trabajo innecesario en `head`
- `[ ]` medir uso de CPU en navegacion continua, acciones encadenadas y formularios reactivos intensivos
- `[ ]` medir presencia de long tasks en navegador durante navegacion o acciones backend->frontend
- `[ ]` medir comportamiento con listas grandes, arboles DOM profundos y multiples componentes en pagina
- `[ ]` medir degradacion tras sesiones largas: 50, 100, 500 navegaciones o acciones consecutivas
- `[ ]` medir estabilidad de focus/scroll restore sin introducir costo excesivo de rehidratacion

Metricas sugeridas para registrar:

- memoria JS usada antes y despues de 10, 50 y 100 interacciones
- tamaño promedio/maximo de payload request y response
- cantidad de requests por flujo de usuario
- tiempo de TTFB, tiempo total de request y tiempo de patch visual
- tiempo de hidratacion por componente y tiempo total de rehidratacion por pagina
- numero de roots activos, listeners, timers y nodos preservados
- porcentaje de cache hit/miss/invalidate
- cantidad de efectos emitidos por accion y cuantas mutaciones DOM producen

Escenarios adicionales recomendados:

- pruebas con red lenta, alta latencia y perdida parcial de paquetes
- pruebas con CPU throttling y dispositivos de gama media/baja
- pruebas con multiples tabs abiertas compartiendo `shared state` o navegando en paralelo
- pruebas con paginas grandes que mezclen `volt:for`, `volt:model.sync`, `volt:portal` y fragment preserve
- pruebas de regresion tras dejar la app abierta durante largos periodos
- pruebas de stress sobre rutas con head dinamico, transitions y prefetch simultaneo
- pruebas de observabilidad para confirmar que hooks runtime permiten localizar el cuello de botella
- pruebas de costo del GC para detectar pausas visibles tras muchas acciones o navegaciones

Metodologia sugerida:

- definir un flujo fijo por escenario: carga inicial, 10 acciones, 10 navegaciones, 50 acciones, 100 navegaciones
- medir siempre en dos condiciones: ambiente normal y ambiente degradado con `CPU throttling` + red lenta
- capturar una linea base antes del cambio y otra despues del cambio para comparar tendencias
- repetir cada medicion al menos 3 veces y registrar promedio, maximo y desviacion visible
- separar mediciones de frontend, backend y red para no mezclar cuellos de botella
- distinguir tiempo de request, tiempo de hidratacion y tiempo de patch visual final

Herramientas sugeridas:

- Chrome DevTools `Performance` para CPU, long tasks, reflows y costo de scripting
- Chrome DevTools `Memory` para heap snapshots, detached nodes y crecimiento de memoria
- Chrome DevTools `Network` para conteo de requests, tamaño de payloads, waterfall y duplicaciones
- Lighthouse para una referencia rapida de performance general y main-thread blocking time
- Performance API (`performance.mark`, `performance.measure`) para instrumentacion puntual del runtime
- hooks runtime (`volt:request-*`, `volt:navigated`, `volt:fragment-*`, `volt:cache-*`) para correlacionar eventos
- logs del servidor o profiling backend para TTFB, serializacion y costo de render/hidratacion

Umbrales orientativos iniciales:

- carga inicial del runtime: objetivo `< 150ms` en desktop medio y `< 300ms` en CPU degradada
- navegacion SPA simple entre vistas compatibles: objetivo `< 120ms` de patch visual, alerta `> 250ms`
- accion reactiva comun: objetivo `< 180ms` total visible, alerta `> 350ms`
- rehidratacion de componente pequeno/mediano: objetivo `< 16ms` por componente visible, alerta `> 40ms`
- payload JSON de accion frecuente: objetivo `< 25KB`, alerta `> 80KB`
- HTML de respuesta para patch comun: objetivo `< 60KB`, alerta `> 150KB`
- requests por interaccion simple: objetivo `1`, alerta `> 2` sin justificacion clara
- crecimiento de memoria tras 100 interacciones repetibles: ideal `< 15%`, alerta `> 30%` sostenido
- long tasks en navegacion o accion: ideal `0`, alerta si aparecen tareas `> 50ms` de forma recurrente
- listeners/timers/roots activos: no deben crecer continuamente despues de volver al estado base

Presupuestos recomendados por categoria:

- memoria: heap estable tras volver al estado base y sin crecimiento monotono entre rondas
- red: sin duplicados entre `prefetch`, `navigate` y acciones; cache hit observable donde aplique
- DOM: mutaciones proporcionales al cambio real y sin reemplazos globales innecesarios
- runtime state: snapshots y `effects` con crecimiento controlado y sin campos redundantes
- head/layout: sin reinyeccion repetida de metas, estilos o scripts equivalentes

Formato sugerido de registro:

- escenario
- hardware/red usada
- numero de interacciones
- memoria inicial/final/maxima
- requests totales
- payload promedio/maximo
- tiempo promedio/maximo de request
- tiempo promedio/maximo de patch o hidratacion
- observaciones de GC, reflows, jank o duplicacion de trabajo

Matriz operativa sugerida:

| Escenario | Herramienta | Metrica principal | Umbral orientativo | Resultado | Observaciones |
| --- | --- | --- | --- | --- | --- |
| Carga inicial del documento con runtime | DevTools Performance + Network | tiempo de boot inicial, long tasks, requests iniciales | boot `< 150ms`, long tasks `0`, requests sin duplicados | `[ ]` | |
| Navegacion SPA entre dos vistas compatibles | DevTools Performance + Network | tiempo de patch visual, requests por click | patch `< 120ms`, requests `1` | `[ ]` | |
| Navegacion SPA repetida 50 veces | DevTools Memory + Network | crecimiento de heap, hit ratio cache, listeners/timers | memoria `< 15%`, sin crecimiento monotono | `[ ]` | |
| Navegacion con `prefetch` + `navigate` | DevTools Network | requests duplicadas, cache hit/miss, waterfall | sin duplicados, reuse observable | `[ ]` | |
| Accion reactiva simple | DevTools Performance + logs backend | tiempo total visible, TTFB, payload request/response | `< 180ms`, JSON `< 25KB` | `[ ]` | |
| `volt:model.sync` con escritura rapida | DevTools Network + Performance | requests por rafaga, debounce efectivo, stale/abort | sin tormenta de requests, abort/stale controlados | `[ ]` | |
| Rehidratacion de componente pequeno | Performance API + DevTools | tiempo de rehidratacion por componente | `< 16ms` | `[ ]` | |
| Rehidratacion de pagina con multiples componentes | DevTools Performance | tiempo total de hidratacion y patch | alerta `> 250ms` | `[ ]` | |
| Lista grande con `volt:for` | DevTools Performance + Memory | scripting, mutaciones DOM, heap | sin long tasks recurrentes `> 50ms` | `[ ]` | |
| Directivas complejas (`volt:if`, `volt:show`, `volt:class`, `volt:style`) | DevTools Performance | costo de expresiones y mutaciones | sin jank visible ni reflows excesivos | `[ ]` | |
| Fragment cache + preserve/reset | DevTools Memory + hooks runtime | reuso real, descarte correcto, memoria retenida | reuse correcto, sin nodos retenidos de mas | `[ ]` | |
| Reconciliacion de `head` y layout | DevTools Elements + Network | duplicacion de metas/assets, costo de swap | sin reinyecciones redundantes | `[ ]` | |
| Sesion larga de 100-500 interacciones | DevTools Memory + Performance | heap final, GC visible, estabilidad del runtime | sin degradacion sostenida ni fuga aparente | `[ ]` | |
| CPU degradada + red lenta | DevTools throttling | resiliencia del runtime bajo estres | degradacion controlada, sin bloqueo severo | `[ ]` | |
| Multiples tabs y estado compartido | DevTools + observacion funcional | consistencia, retrabajo de red, consumo extra | sin duplicacion injustificada ni drift de estado | `[ ]` | |

Prioridad sugerida de ejecucion:

1. carga inicial del documento con runtime
2. navegacion SPA entre vistas compatibles
3. accion reactiva simple
4. `volt:model.sync` con escritura rapida
5. navegacion con `prefetch` + `navigate`
6. sesion larga de 100-500 interacciones

Plantilla breve de resultado:

```md
- Escenario:
- Build/commit:
- Hardware:
- Red:
- Resultado:
- Metrica principal:
- Metrica maxima:
- Conclusion:
- Accion sugerida:
```

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
- `[x]` cobertura automatizada backend de `params + updates` para sincronizacion selectiva y mapeo explicito hacia `updates.*`
- `[x]` MVP de `volt:show` con expresiones `client:path` y `shared:path`
- `[x]` MVP de `volt:if` con mount/unmount por `client:path` y `shared:path`
- `[x]` MVP de `volt:for` con repeticion sobre arreglos `client/shared` y placeholders simples
- `[x]` MVP de `volt:text` para escribir texto desde `client/shared state`
- `[x]` MVP de `volt:class` para alternar clases CSS desde `client/shared state`
- `[x]` MVP de `volt:attr` para alternar atributos HTML desde `client/shared state`
- `[x]` MVP de `volt:style` para alternar estilos inline desde `client/shared state`
- `[x]` expresiones booleanas compuestas para `volt:show`, `volt:if`, `volt:class`, `volt:attr` y `volt:style`
- `[x]` multiples reglas declarativas en un mismo atributo para `volt:class`, `volt:attr` y `volt:style`
- `[x]` fallback declarativo con `??` para `volt:text`
- `[x]` comparaciones runtime flexibles y estrictas (`==`, `!=`, `===`, `!==`, `>`, `<`, `>=`, `<=`)
- `[x]` MVP de `volt:on` con `state:set`, `state:toggle`, `state:delete`, `dispatch:*` y modificadores `prevent`, `stop`, `once`, `self`
- `[x]` MVP de `volt:dispatch` con eventos simples, multiples y convivencia con `volt:on` en `/runtimeEvents`
- `[x]` MVP de `volt:html` con reemplazo completo de `innerHTML` y reescaneo del subarbol inyectado
- `[x]` MVP de `volt:bind` con reflejo DOM <- state y restauracion de baseline por propiedad
- `[x]` MVP de `volt:model.local` con binding bidireccional solo contra `window.Volt.state`
- `[x]` MVP de `volt:model.sync` con actualizacion optimista del store, debounce fijo y sync backend via accion interna `__volt_sync__`
- `[x]` MVP de `volt:focus` y `volt:autofocus.when` con enfoque reactivo por transicion `false -> true`
- `[x]` MVP de `volt:portal` con movimiento real del nodo hacia targets globales del layout
- `[-]` `volt:preserve` disponible como preserve opt-in de fragmentos top-level; `volt:persist` ya arranco como capa independiente sobre esa base
- `[-]` MVP inicial de `volt:persist` montado sobre la base de `fragment cache SPA`, con registro runtime propio entre navegaciones
- `[x]` demos `/runtimeHtml` y `/runtimeHtmlAlt` para validar contenido `client/shared`, reemplazo de subarbol y reactivacion de directivas internas
- `[x]` demos `/runtimeBind` y `/runtimeBindAlt` para validar `value`, `checked`, `disabled`, `href`, `src`, `title` y `placeholder`
- `[x]` demos `/runtimeModelLocal` y `/runtimeModelLocalAlt` para validar input, textarea, checkbox y select sin roundtrip backend
- `[x]` demos `/runtimeFocus` y `/runtimeFocusAlt` para validar foco reactivo y autofocus en navegacion SPA
- `[x]` demos `/runtimePortal` y `/runtimePortalAlt` para validar banner, modal y drawer portalizados
- `[x]` demos `/runtimePersist`, `/runtimePersistBridge` y `/runtimePersistAlt` para validar origen, pantalla intermedia sin target y reinyeccion final
- `[x]` pruebas automatizadas focalizadas del skeleton para contrato server-side de `fragment cache`, atributos declarativos de `prefetch/no-store` y estabilidad de `head/layout`
- `[x]` pruebas automatizadas del skeleton para el contrato declarativo de `/runtimeModelSync` y la sincronizacion selectiva `state -> params/updates`
- `[x]` validacion automatizada del subbloque de sincronizacion selectiva completada, ajustando expectativas de effects para no exigir `html.replace` cuando no existe diff HTML real
- `[x]` cobertura automatizada adicional de sincronizacion selectiva para `/runtimeModelSyncAlt` y para requests mixtos con `params + updates` de texto/booleanos en la misma accion
- `[x]` pruebas automatizadas del skeleton para `/runtimeAdvancedDirectives`, cubriendo `volt:text` con `??`, expresiones compuestas en `volt:show` y `volt:if`, reglas multiples en `volt:class`/`volt:attr`/`volt:style` y casos `null` vs `undefined`
- `[x]` validacion manual de `/runtimeAdvancedDirectives` cerrada con presets reproducibles, markers `data-runtime-check` y checklist dedicada en `6-Runtime-Advanced-Directives-Manual-Validation.md`
- `[x]` validacion manual de `fragment cache SPA`, `prefetch`/`preload`, `head` + layout fallback y politicas `reload` cerrada con la checklist `7-Fragment-Cache-Prefetch-Manual-Validation.md`
- `[x]` validacion manual de `politicas configurables por ruta para SPA vs full reload` cerrada con scaffolding en `NavigationPolicyPage`, `NavigationDocumentReloadPage` y la checklist `8-Navigation-Policy-Manual-Validation.md`

## Proximo Bloque Recomendado

Orden sugerido para seguir avanzando:

1. endurecer y ampliar la cobertura de `sincronizacion selectiva frontend/backend`
2. revisar si hace falta cobertura adicional para escenarios borde de `fragment cache SPA`
3. revisar si hace falta cobertura adicional para `prefetch`/`preload` en navegador real
4. consolidar el bloque activo y preparar el siguiente tramo del roadmap
5. revisar si conviene cerrar o reorganizar el roadmap de demos manuales ya completadas

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
- `[x]` agregar pruebas automatizadas focalizadas si aportan valor

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
- `[x]` agregar pruebas manuales focalizadas para reuse, invalidez y fallback seguro; validadas con la checklist `7-Fragment-Cache-Prefetch-Manual-Validation.md`

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

- acepta expresiones simples o compuestas con `client:path` y `shared:path`
- soporta `!`, `&&`, `||`, parentesis y comparaciones relacionales flexibles o estrictas
- ejemplo: `volt:show="client:counter >= 2 && shared:counter < 3"`
- tambien permite comparar una ref contra otra, por ejemplo `volt:show="client:counter >= shared:counter"`
- en comparacion flexible, una ref con `null` y otra ausente (`undefined`) coinciden con `==`; con `===` ya no
- el `path` soporta acceso con punto como `draft.note` o `ui.showSharedPanel`
- un valor truthy muestra el elemento en `volt:show`
- un valor truthy oculta el elemento en `volt:show.hide`
- el DOM se resincroniza al mutar `window.Volt.state`, al aplicar effects y despues de navegar por SPA

Limitaciones actuales:

- no soporta todavia lectura directa de snapshot backend
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

- acepta expresiones simples o compuestas con `client:path` y `shared:path`
- soporta `!`, `&&`, `||`, parentesis y comparaciones relacionales flexibles o estrictas
- ejemplo: `volt:if="shared:draft.note == 'activar' || client:counter > 3"`
- tambien permite comparar una ref contra otra, por ejemplo `volt:if="client:counter >= shared:counter"`
- el laboratorio `/runtimeAdvancedDirectives` incluye el caso borde `client:edge.nullValue == shared:edge.undefinedValue`
- el mismo laboratorio incluye el caso inverso `client:edge.undefinedValue == shared:edge.nullValue`
- tambien incluye una tabla rapida para contrastar `null`, `undefined`, `''`, `0` y `false` con `==` frente a `===`
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

## Contrato Actual: Volt Html

Estado actual:

- `[x]` MVP inicial implementado en runtime
- `[x]` demo dedicada creada en el skeleton
- `[-]` pendiente validacion manual fina de comportamiento en navegador

Declaracion actual:

```html
<div volt:html="shared:preview.html"></div>
<section volt:html="client:editor.renderedHtml"></section>
<article volt:html="shared:cms.fragment"></article>
```

Gramatica actual:

- formato base: `volt:html="origen"`
- `origen` acepta refs runtime del store; el MVP actual reutiliza el mismo resolvedor base de `volt:text`
- el `path` usa notacion con punto como `preview.html`, `editor.renderedHtml` o `cms.fragment`
- cada nodo puede declarar un solo `volt:html`
- `volt:html` reemplaza el contenido interno del nodo destino, no el nodo contenedor

Reglas actuales:

- `volt:html` es una directiva de lectura DOM <- state; no escribe al store
- al montar o resincronizar el DOM, el runtime resuelve la ref y la escribe en `innerHTML`
- cuando cambia `window.Volt.state`, se reevalua cada `volt:html` registrado
- al aplicar effects o navegar por SPA, el runtime vuelve a resincronizar los nodos con `volt:html` dentro del arbol afectado
- si el valor resuelto es `null`, `undefined` o la ref no existe, el runtime vacia el contenido interno del nodo
- cuando el valor cambia, el runtime reemplaza el subarbol interno completo del nodo destino
- despues de escribir `innerHTML`, el runtime debe volver a escanear el subarbol insertado para activar directivas runtime soportadas en el contenido nuevo
- para evitar trabajo innecesario, el runtime conserva el ultimo HTML aplicado y no reescribe si el string no cambio
- una declaracion invalida no rompe otras directivas del nodo; el runtime la ignora

Semantica actual de tipos:

- si el valor es string, se inserta tal cual en `innerHTML`
- si el valor es numero o booleano, se convierte con `String(valor)` antes de insertarlo
- si el valor es un arreglo u objeto, el MVP lo serializa con `JSON.stringify` antes de insertarlo
- si el valor es `null` o `undefined`, el contenido queda vacio

Seguridad actual del MVP:

- `volt:html` se considera una directiva de contenido confiable
- el MVP no incluye sanitizacion automatica
- la fuente ideal para `volt:html` es backend controlado, HTML renderizado por el framework o contenido ya saneado
- la documentacion debe advertir explicitamente que no se debe usar con input arbitrario de usuario sin sanitizacion previa
- un paso futuro puede agregar `volt:html:safe` o una politica configurable de sanitizacion, pero no forma parte del primer contrato

Interaccion actual con otras directivas:

- `volt:html` no reemplaza a `volt:text`; cuando solo se necesita texto, `volt:text` sigue siendo la opcion recomendada
- `volt:html` puede convivir con directivas en el nodo contenedor como `volt:show`, `volt:if`, `volt:class`, `volt:attr` o `volt:style`
- el contenido inyectado puede incluir directivas runtime, siempre que el runtime reescanee el subarbol despues del reemplazo
- si el contenido inyectado contiene formularios, listeners o nodos interactivos, esos recursos se consideran efimeros y pueden perder estado al siguiente reemplazo completo

Limitaciones actuales del MVP:

- no soporta todavia politicas de sanitizacion integradas
- no hace diff granular del HTML interno; siempre reemplaza el subarbol completo
- no preserva foco, seleccion ni estado efimero dentro del contenido reemplazado
- no soporta todavia modos especiales como `morph`, `fragment` o `append`
- aunque el contrato preferido es una ref simple, el MVP actual hereda el resolvedor base de contenido del runtime

Rutas demo actuales:

- `/runtimeHtml`: origen para probar preview HTML desde `client/shared state`, incluyendo contenido enriquecido y bloques con directivas internas
- `/runtimeHtmlAlt`: destino para validar reinicio de contenido `client`, persistencia de `shared` y reactivacion del subarbol tras navegacion SPA

## Contrato Actual: Volt Bind

Estado actual:

- `[x]` MVP inicial implementado en runtime
- `[x]` demo dedicada creada en el skeleton
- `[-]` pendiente validacion manual fina de comportamiento en navegador

Declaracion actual:

```html
<input volt:bind:value="client:draft.note">
<input type="checkbox" volt:bind:checked="shared:ui.enabled">
<button volt:bind:disabled="shared:ui.busy"></button>
<a volt:bind:href="shared:links.detailsUrl"></a>
<img volt:bind:src="shared:media.previewUrl">
```

Gramatica actual:

- formato base: `volt:bind:propiedad="origen"`
- `propiedad` es el nombre de una propiedad DOM o de un atributo reflejado como `value`, `checked`, `disabled`, `hidden`, `href`, `src`, `title`
- el MVP actual acepta `volt:bind:propiedad`, `volt-bind-propiedad` y `data-volt-bind-propiedad`
- `origen` reutiliza el resolvedor base de contenido del runtime
- el `path` usa notacion con punto como `draft.note`, `ui.enabled` o `media.previewUrl`
- un mismo nodo puede declarar multiples bindings en atributos distintos, por ejemplo `volt:bind:value` y `volt:bind:disabled`
- cada binding es independiente y se resincroniza por separado

Reglas actuales:

- `volt:bind` es una directiva de lectura DOM <- state; no escribe al store por si sola
- al montar o resincronizar el DOM, el runtime resuelve la ref y actualiza la propiedad destino
- cuando cambia `window.Volt.state`, se reevalua cada binding registrado
- al aplicar effects o navegar por SPA, el runtime vuelve a resincronizar los bindings activos del arbol afectado
- para propiedades textuales como `value`, `title`, `href` o `src`, el runtime asigna el valor resuelto convertido a string si no es `null` ni `undefined`
- para propiedades booleanas como `checked`, `disabled`, `hidden`, `required` o `readonly`, el runtime aplica coercion booleana estandar
- si el valor resuelto es `null`, `undefined` o la ref no existe, el runtime restaura un valor seguro por defecto segun la propiedad
- `value` usa string vacio como valor por defecto
- `checked`, `disabled`, `hidden`, `required` y `readonly` usan `false` como valor por defecto
- `href`, `src`, `title` y otras propiedades textuales eliminan el atributo reflejado si no habia valor inicial o restauran el original si existia
- si el nodo ya tenia un valor inicial renderizado por servidor, el runtime lo considera baseline para restauracion cuando el binding queda vacio
- una declaracion invalida no rompe otros bindings del nodo; el runtime la ignora

Semantica actual por tipo de propiedad:

- `value`: escribe sobre `element.value` sin disparar eventos sinteticos
- `checked`: escribe sobre `element.checked`
- `disabled`, `hidden`, `required`, `readonly`: escriben sobre la propiedad booleana y mantienen consistente el atributo reflejado
- `href`, `src`, `title`, `id`, `name`, `placeholder`: escriben sobre la propiedad si existe y sincronizan el atributo reflejado cuando aplique
- si una propiedad no existe en el elemento destino, el runtime cae a `setAttribute` en este MVP

Semantica actual de tipos:

- strings se escriben tal cual
- numeros se convierten con `String(valor)` para propiedades textuales
- booleanos se escriben solo en propiedades booleanas; en propiedades textuales se convierten a `true` o `false`
- objetos o arreglos no son un target valido del MVP; el runtime los serializa con `JSON.stringify` solo como fallback documental

Interaccion actual con otras directivas:

- `volt:bind` no reemplaza en el MVP a `volt:text`, `volt:attr`, `volt:class`, `volt:style` ni `volt:model`
- `volt:text` sigue siendo la opcion declarativa para `textContent`
- `volt:attr` sigue siendo la opcion declarativa para reglas condicionales sobre atributos
- `volt:model` sigue siendo la opcion bidireccional o sincronizada con backend
- `volt:bind` cubre el caso directo y uniforme de reflejar state en propiedades DOM

Limitaciones actuales del MVP:

- aunque el contrato objetivo es simple, el MVP actual reutiliza el resolvedor base del runtime
- no soporta modificadores tipo `.number`, `.trim` o `.lazy`
- no escribe al store; para eso siguen existiendo `volt:model` o `volt:on`
- no hace diff profundo de objetos ni bindings a subpropiedades complejas del DOM
- no resuelve todavia `style`, `class` o `dataset` como namespaces especiales dentro de `volt:bind`

Rutas demo actuales:

- `/runtimeBind`: origen para probar `value`, `checked`, `disabled`, `href` y `src` desde `client/shared state`
- `/runtimeBindAlt`: destino para validar restauracion de baseline y reinicio de `client scope` en navegacion SPA

## Contrato Actual: Volt On

Estado actual:

- `[x]` disponible en MVP actual

Declaracion actual:

```html
<button volt:on="click -> dispatch:menu:toggle"></button>
<form volt:on="submit.prevent -> dispatch:form:submitted"></form>
<input volt:on="input -> state:set client:draft.note = $event.target.value"></input>
<button volt:on="click.once -> state:toggle client:ui.open"></button>
<div volt:on="click.self -> state:toggle client:ui.open"></div>
<button volt:on="click.stop -> state:set client:events.lastNested = 'inner-button'"></button>
<input volt:on="keydown.enter.prevent -> dispatch:demo.events.enter"></input>
```

Gramatica actual:

- formato base: `evento[.modificador[.modificador...]] -> accion`
- un mismo atributo puede declarar multiples reglas separadas por `|`
- cada regla se evalua de izquierda a derecha en el orden declarado
- `evento` es un nombre DOM simple como `click`, `input`, `change`, `submit`, `focus`, `blur`, `keydown`, `keyup`
- los eventos de teclado pueden usar una tecla concreta con sintaxis `keydown.escape`, `keydown.enter`, `keyup.tab`
- los modificadores reservados del MVP son `prevent`, `stop`, `once`, `self`
- una accion valida del MVP es una de estas:
- `dispatch:nombre`
- `state:set scope:path = valor`
- `state:toggle scope:path`
- `state:delete scope:path`
- `scope` acepta `client` o `shared`
- `path` usa la misma notacion con punto ya soportada por el runtime, por ejemplo `ui.open` o `draft.note`
- `valor` acepta `true`, `false`, `null`, numeros, strings con comillas simples y `$event.target.value`
- `valor` tambien acepta `$event.target.checked` para controles booleanos

Reglas actuales:

- el runtime usa delegacion global de eventos sobre `document` y resuelve el nodo objetivo mas cercano con `volt:on`
- al no montar listeners por nodo, evita duplicaciones al resincronizar el DOM o al navegar por SPA
- si una regla usa `prevent`, el runtime ejecuta `event.preventDefault()` antes de resolver la accion
- si una regla usa `stop`, el runtime ejecuta `event.stopPropagation()` antes de resolver la accion
- si una regla usa `self`, la accion solo corre cuando `event.target === element`
- si una regla usa `once`, la regla queda consumida por nodo despues de la primera ejecucion exitosa y se rearma al remount de la pagina o del elemento
- si una regla usa `keydown.<tecla>` o `keyup.<tecla>`, la accion solo corre cuando `event.key` coincide con la tecla declarada normalizada en minusculas
- `dispatch:nombre` emite un `CustomEvent` con `bubbles: true` y `detail` minimo formado por `originalEvent`, `scopeId`, `element` y `directive`
- `state:set scope:path = valor` escribe el valor resuelto en `window.Volt.state`
- `state:toggle scope:path` invierte el valor truthy actual del path objetivo
- `state:delete scope:path` elimina la clave objetivo del store si existe
- despues de una accion `state:*`, el runtime debe disparar el mismo ciclo de resincronizacion que ya usa para cambios manuales de `window.Volt.state`
- una regla invalida no rompe el resto del atributo; el runtime la ignora
- si varias reglas escuchan el mismo evento en el mismo atributo, se ejecutan en el orden declarado

Semantica actual de acciones:

- `dispatch:nombre` no modifica estado por si sola; sirve para componer interaccion entre componentes frontend
- `state:set` permite escribir en `client` o `shared` sin roundtrip al backend
- `state:toggle` esta limitado a valores pensados como booleanos; si el path no existe, el primer toggle lo crea como `true`
- `state:delete` fuerza el caso `undefined` para pruebas y escenarios de limpieza local
- `$event.target.value` y `$event.target.checked` solo se resuelven si el evento trae `target`; en otro caso la accion no se considera valida
- `dispatch:*` desde `volt:on` reutiliza el mismo contrato base de `volt:dispatch`, pero con el nombre del evento definido en la accion
- el laboratorio actual tambien confirma que `volt:on` puede convivir con `volt:dispatch` en el mismo nodo

Eventos iniciales soportados por el contrato:

- `click`
- `input`
- `change`
- `submit`
- `focus`
- `blur`
- `keydown`
- `keyup`

Teclas iniciales soportadas por el contrato:

- `enter`
- `escape`
- `tab`
- `space`

Limitaciones actuales:

- no evalua expresiones arbitrarias del tipo `state:set client:count = client:count + 1`
- no expone todavia payloads declarativos complejos para `dispatch`
- no soporta todavia modificadores temporales tipo `debounce`, `throttle` o `outside`
- no reemplaza a `volt:click`, `volt:model` ni `volt:submit` en la primera iteracion; convive con ellas
- no ejecuta codigo JavaScript libre ni `eval`

Rutas demo:

- `/runtimeEvents`: origen para probar `click`, `input`, `change` y `dispatch:*` contra `window.Volt.state`
- la misma demo cubre `click.once`, `click.self`, `click.stop`, `keydown.enter.prevent` y `submit.prevent`

## Comparativa: Volt On Vs Volt Dispatch

| Aspecto | `volt:on` | `volt:dispatch` |
| --- | --- | --- |
| Objetivo principal | orquestar eventos DOM y resolver acciones frontend declarativas | emitir `CustomEvent` declarativos desde markup |
| Disparador MVP | explicito por evento, por ejemplo `click`, `input`, `keydown.escape` | implicito por `click` |
| Acciones del MVP | `dispatch:*`, `state:set`, `state:toggle`, `state:delete` | solo `dispatch` de uno o varios eventos |
| Modificadores | si, `prevent`, `stop`, `once`, `self` | no en el MVP |
| Mutacion de state | si, mediante `state:*` | no |
| Complejidad | mas alta; sirve como DSL general de eventos | mas baja; sirve como atajo de emision |
| Caso ideal | cuando necesitas reaccionar al evento y decidir que hacer | cuando solo quieres avisar a otros listeners frontend |
| Relacion recomendada | directiva principal de eventos | azucar declarativa para el caso comun `click -> dispatch:nombre` |

## Comparativa: Volt On / Volt Dispatch / Volt Click / Volt Submit

| Aspecto | `volt:on` | `volt:dispatch` | `volt:click` | `volt:submit` |
| --- | --- | --- | --- | --- |
| Rol principal | DSL general de eventos frontend | emitir `CustomEvent` frontend | disparar accion reactiva backend desde click | enviar formulario/reactive action al backend |
| Disparador | explicito, por ejemplo `click`, `input`, `keydown.escape` | implicito por `click` | `click` | `submit` |
| Toca backend | opcionalmente no | no | si | si |
| Puede mutar state local | si, con `state:*` | no | indirectamente via respuesta backend | indirectamente via respuesta backend |
| Modificadores MVP | si | no | propios del flujo reactivo existente | propios del flujo reactivo existente |
| Mejor caso de uso | interaccion declarativa rica sin roundtrip | notificar listeners frontend | botones de accion de componente | formularios y validaciones |
| Complejidad | alta | baja | media | media |
| Recomendacion | usar cuando necesitas control fino del evento | usar cuando solo quieres emitir un evento | usar para acciones backend simples | usar para formularios y submits reactivos |

## Contrato Actual: Volt Dispatch

Estado actual:

- `[x]` disponible en MVP actual

Declaracion actual:

```html
<button volt:dispatch="menu:toggle"></button>
<button volt:dispatch="toast:show"></button>
<button volt:dispatch="dialog:close | analytics:cta-click"></button>
<button volt:dispatch="filters:changed"></button>
```

Gramatica actual:

- formato base: `volt:dispatch="evento"`
- un mismo atributo puede declarar multiples eventos separados por `|`
- cada evento se evalua de izquierda a derecha en el orden declarado
- `evento` es un nombre logico de `CustomEvent`, como `menu:toggle`, `toast:show`, `dialog:close` o `filters:changed`
- el nombre del evento no incluye JavaScript libre ni payloads arbitrarios en la primera version

Reglas actuales:

- `volt:dispatch` se resuelve desde un listener global delegado y en el MVP se activa por `click`
- en el MVP, el disparador implicito es `click`
- al activar el nodo, el runtime emite un `CustomEvent` por cada nombre declarado en el atributo
- los eventos se emiten con `bubbles: true` y `cancelable: true`
- el `detail` emitido incluye `sourceElement`, `directive`, `scopeId`, `clientScope`, `sharedScope`, `component` y `originalEvent`
- si el nodo esta deshabilitado o no puede recibir activacion, el runtime no debe disparar eventos
- al usar delegacion global, no necesita montar ni limpiar listeners por cada nodo individual
- si una declaracion es invalida, el runtime la ignora sin romper otras directivas del nodo

Semantica actual:

- `volt:dispatch` no modifica `window.Volt.state` por si sola
- su objetivo es emitir eventos frontend declarativos para que otras partes del runtime o la aplicacion reaccionen
- si hay multiples eventos en el atributo, todos se emiten en el orden declarado dentro de la misma activacion
- el runtime no debe asumir listeners existentes; despachar un evento sin consumidores sigue siendo valido
- la demo actual muestra su convivencia con `volt:on`, donde primero corre `volt:on` y despues `volt:dispatch` en el listener global actual

Interaccion actual con otras directivas:

- con `volt:on`, `volt:dispatch` sirve como version abreviada y declarativa para el caso comun `click -> dispatch:nombre`
- con `volt:show` y `volt:if`, solo puede disparar eventos si el nodo esta visible o montado realmente
- con `volt:loading`, `volt:dirty`, `volt:success` y `volt:error`, puede servir para notificar estados UI a listeners frontend sin roundtrip
- con un futuro `runtime.on(...)`, estos eventos deberian poder observarse desde una API publica coherente

Limitaciones actuales:

- no soporta todavia payload declarativo, por ejemplo objetos o expresiones
- no soporta todavia cambiar el evento disparador; el MVP se limita a `click`
- no soporta modificadores como `prevent`, `stop`, `once` o `self`; esos casos corresponden a `volt:on`
- no sustituye a `volt:click` ni a acciones backend; es solo un canal frontend de eventos
- no ejecuta codigo JavaScript libre ni `eval`

Rutas demo:

- `/runtimeEvents`: origen para probar `volt:dispatch` simple, multiple y combinado con `volt:on`
- la demo actual incluye puente minimo hacia `window.Volt.state` para reflejar los `CustomEvent` emitidos

## Contrato Actual: Volt Focus

Estado actual:

- `[x]` MVP inicial implementado en runtime
- `[x]` demo dedicada creada en el skeleton
- `[-]` pendiente validacion manual fina de comportamiento en navegador

Declaracion actual:

```html
<input volt:focus="client:ui.focusTitle">
<textarea volt:autofocus.when="shared:form.showErrors"></textarea>
<button volt:focus="client:ui.returnFocusToAction"></button>
```

Gramatica actual:

- formato base para foco reactivo: `volt:focus="condicion"`
- formato base para foco al montar o al pasar a truthy: `volt:autofocus.when="condicion"`
- `condicion` acepta una ref simple `client:path` o `shared:path`
- el `path` usa notacion con punto como `ui.focusTitle`, `form.showErrors` o `ui.returnFocusToAction`
- un mismo nodo puede declarar `volt:focus`, `volt:autofocus.when` o ambos, pero cada directiva se evalua por separado

Reglas actuales:

- `volt:focus` y `volt:autofocus.when` son directivas de lectura DOM <- state; no escriben al store
- al montar o resincronizar el DOM, el runtime evalua la condicion declarada
- si la condicion de `volt:focus` pasa de falsy a truthy o entra ya truthy en el primer montaje, el runtime ejecuta `element.focus()`
- si la condicion de `volt:autofocus.when` pasa de falsy a truthy o el nodo entra ya truthy en el primer montaje, el runtime ejecuta `element.focus()`
- si la condicion sigue en truthy pero el elemento ya es `document.activeElement`, el runtime no debe reenfocar innecesariamente
- si el elemento esta deshabilitado, oculto, desconectado del DOM o no es focuseable, el runtime no debe lanzar error; solo omite el intento
- al aplicar effects o navegar por SPA, el runtime vuelve a evaluar estas directivas dentro del arbol afectado
- si varias directivas de foco quedan truthy en la misma pasada, gana la ultima resuelta en orden de escaneo del DOM
- una declaracion invalida no rompe otras directivas del nodo; el runtime la ignora

Semantica actual:

- `volt:focus` esta pensada para reenfocar o mover el foco reactivamente cuando el state cambia
- `volt:autofocus.when` esta pensada para el primer foco util al montar un bloque, mostrar errores o abrir un panel
- ninguna de las dos directivas selecciona texto ni mueve el cursor; el MVP solo garantiza `focus()`
- el runtime no debe disparar eventos sinteticos adicionales mas alla de los normales que el navegador emite al enfocar

Interaccion actual con otras directivas:

- puede convivir con `volt:show` y `volt:if`, pero solo intenta enfocar cuando el nodo existe realmente en el DOM
- con `volt:html`, cualquier foco interno dentro del contenido reemplazado se considera efimero y puede perderse al siguiente reemplazo
- con `volt:on`, un listener frontend puede alternar la condicion que luego active `volt:focus`
- con `volt:model`, el foco no debe interferir con la escritura normal del input; solo evita reenfoques redundantes

Limitaciones actuales del MVP:

- no soporta todavia modificadores como `.select`, `.prevent-scroll` o `.delay`
- no preserva seleccion de texto ni posicion del cursor como hace la capa de restore de patch
- no soporta prioridad explicita entre multiples nodos candidatos al foco
- no usa comparaciones ni expresiones compuestas en la primera version; solo refs simples
- no intenta enfocar nodos dentro de shadow DOM ni portales futuros

Rutas demo actuales:

- `/runtimeFocus`: origen para probar foco condicional en inputs, textareas, botones de retorno y paneles con error
- `/runtimeFocusAlt`: destino para validar reenfoque, `shared state` y comportamiento tras navegacion SPA

## Contrato Actual: Volt Portal

Estado actual:

- `[x]` MVP inicial implementado en runtime
- `[x]` demo dedicada creada en el skeleton
- `[-]` pendiente validacion manual fina de comportamiento en navegador

Declaracion actual:

```html
<div volt:portal="#modals-root">
  <section class="modal-shell">...</section>
</div>

<aside volt:portal="#drawer-root">
  <nav class="mobile-drawer">...</nav>
</aside>
```

Gramatica actual:

- formato base: `volt:portal="selector"`
- `selector` es un selector CSS simple resuelto con `document.querySelector`
- cada nodo puede declarar un solo `volt:portal`
- `volt:portal` mueve o proyecta el subarbol del nodo hacia un target externo, manteniendo el nodo origen como ancla logica del runtime

Reglas actuales:

- al montar o resincronizar el DOM, el runtime resuelve el target del portal
- si el target existe, el contenido portalizado se inserta dentro de ese contenedor destino
- si el target no existe, el runtime conserva el contenido en su posicion original
- el runtime debe asociar el portal con una ancla estable para poder limpiarlo, reubicarlo o desmontarlo correctamente
- al desmontar el nodo origen por `volt:if`, navegacion SPA o reemplazo DOM, el runtime debe desmontar tambien el contenido portalizado
- al aplicar effects o navegar por SPA, el runtime debe evitar duplicar la misma instancia portalizada
- si el nodo portalizado contiene directivas runtime, estas deben seguir activas y resincronizarse con normalidad
- si varias instancias portalizan al mismo target, el orden visual sigue el orden de montaje
- una declaracion invalida no rompe otras directivas del nodo; el runtime la ignora

Semantica actual:

- `volt:portal` no cambia el estado ni el snapshot del componente; solo cambia la ubicacion fisica del DOM renderizado
- el contenido portalizado sigue perteneciendo logicamente al componente o al arbol donde fue declarado
- los eventos del DOM portalizado siguen pudiendo burbujear hacia `document`, pero no deben depender de la jerarquia visual original para funcionar
- el MVP actual implementa portal como movimiento real del nodo, no como clonacion, para evitar divergencias de estado local del DOM

Interaccion actual con otras directivas:

- con `volt:show` y `volt:if`, el portal solo existe mientras el nodo origen este visible o montado segun corresponda
- con `volt:focus`, el foco debe dirigirse al contenido ya portalizado si ese es el nodo realmente activo
- con `volt:html`, el contenido interno portalizado puede reemplazarse, pero el contenedor portalizado debe mantenerse estable
- con `volt:on` y `volt:dispatch`, los listeners deben seguir funcionando sobre el contenido ya proyectado

Limitaciones actuales del MVP:

- no soporta todavia targets multiples ni expresiones dinamicas para el selector
- no soporta todavia portales anidados con reglas especiales de prioridad
- no resuelve por si sola problemas de scroll lock, backdrop o focus trap; eso queda para una capa superior
- no preserva todavia el orden relativo si el mismo portal se remonta varias veces en ciclos complejos
- no soporta todavia shadow DOM ni targets fuera del `document` principal

Rutas demo actuales:

- `/runtimePortal`: origen para probar modales, drawers y banners portalizados desde `client/shared state`
- `/runtimePortalAlt`: destino para validar limpieza, remount y comportamiento del portal tras navegacion SPA

## Contrato Actual: Volt Persist

Estado actual:

- `[-]` MVP inicial implementado en runtime
- `[-]` demo dedicada creada en el skeleton; pendiente validacion manual fina
- `[x]` reutiliza la base existente de `data-volt-preserve="clave"` y `volt:preserve="clave"` dentro de `fragment cache SPA`

Nota de alcance actual:

- `volt:persist` ya existe como directiva independiente en `volt.js`
- el runtime acepta `data-volt-persist`, `volt-persist` y `volt:persist`
- la base de captura/restauracion se apoya en el mismo enfoque usado por `fragment cache SPA`
- a diferencia de `volt:preserve`, el registro de `volt:persist` puede sobrevivir a una pantalla intermedia que no exponga el target y reinyectarse cuando aparezca una clave compatible otra vez
- la politica documental `reset` sigue pudiendo descartar el registro persistido actual

Declaracion actual:

```html
<aside volt:persist="app-sidebar"></aside>
<section volt:persist="global-player"></section>
<div volt:persist="search-panel"></div>
```

Gramatica actual:

- formato base: `volt:persist="clave"`
- `clave` es un identificador estable y unico dentro de la pagina, por ejemplo `app-sidebar`, `global-player` o `search-panel`
- cada nodo puede declarar un solo `volt:persist`
- el nodo persistido se identifica por su clave logica, no por su posicion exacta en el DOM

Reglas actuales:

- al navegar por SPA, si la nueva vista contiene un nodo con la misma clave de `volt:persist`, el runtime reutiliza la instancia DOM ya existente en lugar de crear una nueva
- si la nueva vista no contiene esa clave, el runtime puede conservar temporalmente la instancia persistida fuera del arbol principal hasta una siguiente navegacion compatible o descartarla segun politica documental
- al reutilizar una instancia persistida, el runtime debe volver a asociarla a su nuevo ancla logica sin duplicarla
- el runtime debe evitar que una misma clave genere dos instancias activas al mismo tiempo
- si una clave aparece mas de una vez en la misma captura o en el target actual, el MVP conserva la primera coincidencia valida y descarta el resto
- una instancia persistida conserva estado efimero del DOM como scroll interno, foco potencial, valor local no controlado y listeners ya montados, salvo que otra directiva o patch lo reemplace explicitamente
- si la navegacion fuerza recarga completa o cambia a un layout incompatible, el runtime puede descartar las instancias persistidas
- la politica documental `reset` descarta el registro persistido para evitar reinyectar nodos en destinos que exigen arranque limpio
- en el MVP actual, la restauracion exige coincidencia de clave y de `tagName`

Semantica actual:

- `volt:persist` no preserva estado del store; preserva una instancia fisica del DOM entre navegaciones SPA compatibles
- la clave representa identidad visual/estructural estable, no contenido reactivo serializado
- esta directiva esta pensada para sidebar, reproductores, shells flotantes, buscadores globales y paneles que no deberian reinicializarse en cada cambio de pagina
- el contenido persistido puede seguir recibiendo resincronizacion runtime si contiene otras directivas activas
- el MVP actual opera sobre fragmentos top-level, alineado con la base existente de `fragment cache SPA`

Interaccion actual con otras directivas:

- con `volt:portal`, un nodo puede ser persistido y ademas portalizado, pero el runtime debe resolver primero identidad persistida y luego su ubicacion visual
- con `volt:focus`, el foco de un nodo persistido puede sobrevivir a la navegacion si el navegador mantiene el elemento activo
- con `volt:html`, si una directiva reemplaza el contenido interno del nodo persistido, solo persiste la instancia contenedora, no el subarbol reemplazado previo
- con `volt:on` y `volt:dispatch`, los listeners ya asociados a la instancia persistida deben seguir funcionando al reutilizarla

Limitaciones actuales del MVP:

- no soporta todavia estrategias configurables como `keep-alive`, `discard-on-leave` o TTL
- no soporta todavia persistencia entre recargas completas de pagina ni entre pestañas
- no resuelve conflictos complejos entre persistencia y reconciliacion profunda del DOM
- no soporta todavia persistencia parcial declarativa dentro de una misma clave
- no expone todavia hooks publicos tipo `persist:attached` o `persist:discarded`
- aun no tiene hooks publicos especificos para persistencia; la observabilidad actual se apoya en `volt:navigated`

Rutas demo actuales:

- `/runtimePersist`: origen para editar nodos con `volt:persist`
- `/runtimePersistBridge`: pantalla intermedia sin targets persistidos para comprobar que el registro sobrevive fuera del DOM visible
- `/runtimePersistAlt`: destino final para validar reinyeccion real de la instancia viva tras una pantalla intermedia

## Contrato Actual: Volt Model Local

Estado actual:

- `[x]` MVP inicial implementado en runtime
- `[x]` demo dedicada creada en el skeleton
- `[-]` pendiente validacion manual fina de comportamiento en navegador

Declaracion actual:

```html
<input volt:model.local="client:draft.note">
<textarea volt:model.local="client:draft.body"></textarea>
<input type="checkbox" volt:model.local="client:ui.enabled">
<select volt:model.local="shared:filters.category"></select>
```

Gramatica actual:

- formato base: `volt:model.local="origen"`
- `origen` acepta una ref simple `client:path` o `shared:path`
- el `path` usa notacion con punto como `draft.note`, `draft.body`, `ui.enabled` o `filters.category`
- el binding es bidireccional entre control DOM y `window.Volt.state`, pero solo en el runtime frontend
- cada control puede declarar una sola directiva `volt:model.local`
- el MVP actual acepta `volt:model.local`, `volt-model-local` y `data-volt-model-local`

Reglas actuales:

- al montar el nodo, el runtime lee el valor actual del store y lo refleja en el control correspondiente
- cuando el usuario modifica el control, el runtime escribe el nuevo valor inmediatamente en `window.Volt.state`
- `input`, `textarea` y `select` de texto usan el valor de `event.target.value`
- `checkbox` usa `event.target.checked`
- `radio` usa `event.target.value` solo cuando queda seleccionado
- al cambiar `window.Volt.state` por otras vias, el control vuelve a resincronizarse con el valor actual del store
- esta directiva no dispara roundtrip al backend por si sola
- al aplicar effects o navegar por SPA, el runtime vuelve a enlazar correctamente el control si sigue existiendo
- cuando el path aun no existe, el control conserva su baseline SSR hasta que llegue un valor desde state o desde la propia interaccion del usuario
- una declaracion invalida no rompe otras directivas del nodo; el runtime la ignora

Semantica actual:

- `volt:model.local` es la opcion para formularios puramente frontend o borradores temporales
- la fuente de verdad durante la interaccion es `window.Volt.state`
- si el path no existe, el runtime puede inicializarlo con el primer valor emitido por el control
- el valor queda disponible inmediatamente para `volt:text`, `volt:show`, `volt:bind`, `volt:on` y otras directivas runtime

Interaccion actual con otras directivas:

- con `volt:text`, `volt:bind` o `volt:show`, los cambios del usuario deben reflejarse sin roundtrip
- con `volt:focus`, el reenfoque no debe romper el valor local del control
- con `volt:submit`, el formulario puede enviar despues el state local acumulado si el componente decide leerlo
- con `volt:model.sync`, no deben coexistir ambas directivas en el mismo nodo

Limitaciones actuales del MVP:

- no soporta todavia modificadores como `.trim`, `.number`, `.lazy` o `.debounce`
- no resuelve todavia colecciones complejas, arrays de checkboxes ni archivos
- no sincroniza automaticamente con backend
- no implementa validacion declarativa propia; depende de otras capas

Rutas demo actuales:

- `/runtimeModelLocal`: origen para probar inputs, textarea, checkbox y select ligados solo al store frontend
- `/runtimeModelLocalAlt`: destino para validar reinicio de `client scope`, persistencia de `shared` y resincronizacion SPA

## Contrato Actual: Volt Model Sync

Estado actual:

- `[x]` MVP inicial implementado en runtime
- `[x]` demos dedicadas creadas en el skeleton
- `[-]` pendiente validacion manual fina de comportamiento en navegador

Declaracion actual:

```html
<input volt:model.sync="client:draft.note">
<textarea volt:model.sync="client:draft.body"></textarea>
<select volt:model.sync="shared:filters.category"></select>
```

Gramatica actual:

- formato base: `volt:model.sync="origen"`
- `origen` acepta una ref simple `client:path` o `shared:path`
- el `path` usa notacion con punto como `draft.note`, `draft.body` o `filters.category`
- el binding es bidireccional: actualiza `window.Volt.state` y ademas agenda sincronizacion con backend segun la politica del runtime
- cada control puede declarar una sola directiva `volt:model.sync`
- el MVP actual acepta `volt:model.sync`, `volt-model-sync` y `data-volt-model-sync`

Reglas actuales:

- al montar el nodo, el runtime refleja el valor actual del store en el control
- cuando el usuario modifica el control, el runtime actualiza primero `window.Volt.state`
- despues de actualizar el state local, el runtime agenda una sincronizacion con backend para el path afectado
- la politica actual del MVP usa un debounce fijo de `220ms`
- la sincronizacion backend se envia mediante la accion interna `__volt_sync__`, sin exigir un metodo publico del componente por campo
- para el destino backend, el MVP usa primero las reglas declaradas en `data-volt-state-sync`, `volt-state-sync` o `volt:state-sync`
- si no hay reglas explicitas de `state-sync`, el runtime usa el atributo `name` del control como fallback hacia `updates.<name>`
- opcionalmente, el campo puede declarar `data-volt-model-sync-update`, `volt-model-sync-update` o `volt:model.sync.update` para definir el nombre exacto del update backend sin depender de `name`
- si hay una sincronizacion pendiente del mismo control, el runtime colapsa el timer anterior y conserva solo el ultimo valor conocido
- al recibir respuesta backend, el runtime resincroniza snapshot, state y UI con el valor confirmado
- si la sincronizacion falla, el runtime conserva el valor local optimista y expone el error a las capas `volt:error` o estado equivalente
- una declaracion invalida no rompe otras directivas del nodo; el runtime la ignora

Semantica actual:

- `volt:model.sync` es la opcion para campos que deben sentirse reactivos pero tambien vivir respaldados por el backend
- el usuario percibe actualizacion local inmediata, pero el sistema conserva una ruta clara de confirmacion server-driven
- el store local actua como estado optimista hasta que llega la respuesta del backend

Interaccion actual con otras directivas:

- con `volt:dirty`, el cambio local debe marcar el control o formulario como sucio antes de la confirmacion del backend
- con `volt:success` y `volt:error`, la respuesta de la sincronizacion puede alimentar feedback visual
- con `volt:submit`, un formulario puede mezclar `model.sync` y submit explicito, pero el contrato debe evitar dobles envios accidentales
- con `volt:model.local`, no deben coexistir ambas directivas en el mismo nodo
- con `data-volt-state-sync`, un mismo control puede mapear el valor optimista a `params` o `updates` del protocolo reactivo existente

Limitaciones actuales del MVP:

- no soporta todavia politicas configurables como `.lazy`, `.blur`, `.debounce(300)` o `.defer`
- no soporta todavia uploads, archivos ni estructuras complejas
- no resuelve conflictos avanzados entre valor optimista local y respuesta server si cambia el mismo campo desde otra fuente concurrente
- aborta la request reactiva anterior del mismo componente cuando entra una nueva sincronizacion, igual que otras acciones del runtime
- depende de una capa de transporte reactivo ya estable para funcionar bien

Rutas demo actuales:

- `/runtimeModelSync`: origen para probar sincronizacion optimista de inputs y selects con feedback visual
- `/runtimeModelSyncAlt`: destino para validar resincronizacion, errores y comportamiento tras navegacion SPA

## Comparativa: Volt Text / Volt Html / Volt Bind / Volt Model

| Aspecto | `volt:text` | `volt:html` | `volt:bind` | `volt:model` |
| --- | --- | --- | --- | --- |
| Direccion principal | state -> DOM | state -> DOM | state -> DOM | DOM <-> state y opcional backend |
| Target | `textContent` | `innerHTML` | propiedad DOM especifica | valor interactivo de input/control |
| Tipo de uso ideal | texto plano visible | contenido enriquecido confiable | reflejar propiedades como `value`, `checked`, `disabled`, `href` | formularios e inputs con sincronizacion |
| Soporta HTML | no | si | no, salvo propiedades textuales del DOM | no como objetivo principal |
| Soporta binding de propiedades | no | no | si | si, pero orientado a entrada de usuario |
| Escribe al store | no | no | no | si |
| Riesgo principal | bajo | XSS y reemplazo completo de subarbol | conflicto semantico con otras directivas si se abusa | sincronizacion, latencia y control de entrada |
| Caso recomendado | etiquetas, badges, textos auxiliares | previews, fragmentos CMS, HTML renderizado por backend | checkbox, disabled, href, src, value reflejado | inputs, textareas, selects, formularios |

## Contrato Actual: Volt Text

Estado actual:

- `[x]` disponible en MVP inicial

Declaracion actual:

```html
<span volt:text="client:draft.note"></span>
<span volt:text="shared:draft.note"></span>
<span volt:text="shared:serverSync.syncedAt"></span>
<span volt:text="client:draft.note ?? shared:draft.note ?? 'Sin nota'"></span>
```

Reglas actuales:

- acepta expresiones simples con `client:path` o `shared:path`
- soporta fallback declarativo con `??`, por ejemplo `client:draft.note ?? shared:draft.note ?? 'Sin nota'`
- el `path` soporta acceso con punto como `draft.note` o `serverSync.syncedAt`
- escribe el resultado en `textContent` del nodo destino
- si el valor es `null`, `undefined` o no existe, el texto queda vacio
- si el valor es un objeto o arreglo, el MVP lo serializa con `JSON.stringify`
- el DOM se resincroniza al mutar `window.Volt.state`, al aplicar effects y despues de navegar por SPA

Limitaciones actuales:

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
<article volt:class="client:ui.ready && !shared:ui.blocked -> ring-2 ring-emerald-400 | shared:ui.highlightSharedCard -> shadow-xl"></article>
<article volt:class="client:counter >= 2 && shared:counter < 3 -> ring-4 ring-sky-400"></article>
<article volt:class="client:counter >= shared:counter -> ring-2 ring-violet-400"></article>
```

Reglas actuales:

- acepta expresiones con formato `condicion -> clases`
- la condicion puede usar `client:path`, `shared:path`, `!`, `&&`, `||`, parentesis y comparaciones relacionales flexibles o estrictas
- soporta multiples reglas en un mismo atributo separadas por `|`
- la lista de clases se separa por espacios y se aplica con `classList`
- si la condicion pasa a falsy, el runtime quita solo las clases controladas por esa directiva
- si el elemento ya tenia una clase originalmente, el runtime la restaura al desactivar la directiva
- el DOM se resincroniza al mutar `window.Volt.state`, al aplicar effects y despues de navegar por SPA

Limitaciones actuales:

- no evalua expresiones arbitrarias ni objetos estilo `{ active: condition }`
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
<div volt:attr="client:ui.ready && !shared:ui.busy -> data-state=ready, aria-busy=false | shared:ui.busy -> data-state=busy, aria-busy=true"></div>
<div volt:attr="client:counter >= 2 -> data-threshold=ready, aria-live=polite"></div>
<div volt:attr="client:counter >= shared:counter -> data-balance=client-dominant"></div>
```

Reglas actuales:

- acepta expresiones con formato `condicion -> atributo=valor, otro=valor`
- la condicion puede usar `client:path`, `shared:path`, `!`, `&&`, `||`, parentesis y comparaciones relacionales flexibles o estrictas
- soporta multiples reglas en un mismo atributo separadas por `|`
- la lista de atributos se separa por comas
- si la condicion es truthy, el runtime aplica cada atributo con `setAttribute`
- si la condicion vuelve a falsy, el runtime restaura el valor original de cada atributo o lo elimina si no existia
- el DOM se resincroniza al mutar `window.Volt.state`, al aplicar effects y despues de navegar por SPA

Limitaciones actuales:

- no soporta todavia sintaxis booleana especial distinta de la presencia normal del atributo
- no evalua expresiones arbitrarias ni objetos tipo `{ disabled: condition }`

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
<div volt:style="client:ui.ready && !shared:ui.busy -> opacity:1; transform:scale(1) | shared:ui.busy -> opacity:0.55; pointer-events:none"></div>
<div volt:style="shared:counter >= 3 -> opacity:0.45; filter:saturate(0.7)"></div>
<div volt:style="client:counter >= shared:counter -> outline:1px solid rgba(139,92,246,0.45)"></div>
```

Reglas actuales:

- acepta expresiones con formato `condicion -> propiedad:valor; otra-propiedad:valor`
- la condicion puede usar `client:path`, `shared:path`, `!`, `&&`, `||`, parentesis y comparaciones relacionales flexibles o estrictas
- soporta multiples reglas en un mismo atributo separadas por `|`
- la lista de declaraciones se separa por `;`
- si la condicion es truthy, el runtime aplica cada declaracion con `style.setProperty`
- si la condicion vuelve a falsy, el runtime restaura el valor inline original de cada propiedad o la elimina si no existia
- el DOM se resincroniza al mutar `window.Volt.state`, al aplicar effects y despues de navegar por SPA

Limitaciones actuales:

- no soporta todavia objetos estilo `{ opacity: condition }`
- no evalua expresiones arbitrarias ni calculos dinamicos dentro del valor

Rutas demo:

- `/runtimeState`: origen para alternar estilos inline cliente y compartidos
- `/runtimeStateAlt`: destino para validar que los estilos `client` se reinician por URL y los `shared` persisten

## Como Actualizar Este Archivo

Regla simple de trabajo:

- cuando algo se empiece, cambiar `[ ]` por `[-]`
- cuando quede usable y validado, cambiar por `[x]`
- si aparece un bloqueo o riesgo importante, marcar `[!]`
- registrar el hito en `Bitacora De Avance`
