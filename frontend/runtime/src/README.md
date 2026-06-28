# Volt Runtime Source

Este directorio contiene la fuente modular del runtime frontend de VoltStack.

## Regla Operativa

- `frontend/runtime/volt.js` es un archivo generado
- no se edita directamente
- cualquier cambio debe hacerse en los archivos `*.js` de este directorio
- despues de editar, reconstruir con:

```bash
php tools/build-runtime.php
```

## Orden Actual

- `00-bootstrap.js`
  - arranque del IIFE, estado global `runtime`, helpers base, telemetria, componentes activos y APIs publicas
- `10-directive-expression-utils.js`
  - nombres de directivas, parsing/evaluacion de expresiones store y utilidades base compartidas
- `11-dom-model-directives.js`
  - directivas `bind`, `model.local`, `model.sync`, `portal`, `html` y `focus`
- `12-store-render-directives.js`
  - directivas declarativas de render como `if`, `for`, `show`, `text`, `class`, `attr` y `style`
- `13-state-sync-navigation.js`
  - sincronizacion selectiva de estado y contrato declarativo de navegacion/cache/page transitions
- `20-navigation-cache.js`
  - cache SPA, payloads de navegacion, preload de assets criticos y reuso de requests en vuelo
- `21-navigation-prefetch.js`
  - prefetch por intent/viewport/idle, observer de viewport y cleanup de handles huérfanos relacionados con navegacion
- `30-state-directives-core.js`
  - helpers base de directivas de estado, scopes, policies y parsing/aplicacion declarativa
- `31-state-runtime-sync.js`
  - timers, durations y pipeline de sincronizacion runtime para `loading`, `error`, `dirty` y `success`
- `32-ui-preservation-hooks.js`
  - descriptores estables, preservacion de focus/scroll y emision central de hooks runtime
- `40-patch-transitions.js`
  - helpers de timing/transicion, hooks de patch y preservacion de UI durante mutaciones
- `41-request-state.js`
  - helpers compartidos de request/action y estados `loading`, `error`, `dirty`, `success`
- `42-navigation-document.js`
  - helpers SPA de documento, fragmentos preservados/persistidos, `head` y payload documental
- `43-effects-patch.js`
  - resolucion y aplicacion de `effects`, inserciones DOM y fallback de patch
- `44-navigation-visit.js`
  - request y pipeline de `visit()` para navegacion SPA
- `45-action-dispatch.js`
  - pipeline de `dispatchAction()` para acciones reactivas
- `50-events-and-boot.js`
  - listeners globales, exposicion `window.Volt.*`, contrato publico `window.Volt.contract` y boot final del documento

## Nota

La fragmentacion actual conserva el runtime como un solo asset servido por `/_volt/runtime.js`, pero ya no obliga a mantener toda la logica en un unico archivo gigante.

## Contrato Publico

- el runtime publica `window.Volt.contract` como descriptor estable del asset servido por `/_volt/runtime.js`
- el contrato actual expone `version` y la lista formal de APIs publicas: `visit`, `prefetch`, `state`, `components` y `telemetry`
- cuando se cambie este contrato, debe actualizarse primero la fuente en `frontend/runtime/src` y luego regenerar `frontend/runtime/volt.js`
