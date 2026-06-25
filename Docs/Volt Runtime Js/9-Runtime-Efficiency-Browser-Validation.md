# Runtime Efficiency - Browser Validation

## Objetivo

Validar en navegador real la pasada inicial de eficiencia del runtime usando:

- `window.Volt.telemetry`
- `window.Volt.components`
- `performance.getEntriesByType('navigation')`
- `performance.getEntriesByType('resource')`
- el laboratorio `/runtimeEvents`

## Preparacion

1. levantar la aplicacion cliente con assets frontend actualizados
2. abrir `/runtimeEvents`
3. abrir DevTools en `Network` y `Performance`
4. activar `Preserve log` en `Network`
5. confirmar que el panel `data-runtime-efficiency-demo` aparece visible

## Pantalla Relevante

- `/runtimeEvents`
  - laboratorio de hooks y eficiencia
  - markers estables:
    - `data-runtime-events-demo`
    - `data-runtime-efficiency-demo`
    - `data-runtime-check="efficiency-navigation-performance"`
    - `data-runtime-check="efficiency-runtime-asset"`
    - `data-runtime-check="efficiency-runtime-overview"`
    - `data-runtime-check="efficiency-summary-json"`
    - `data-runtime-check="efficiency-components-detail"`

## Checklist Manual

### 1. Baseline Inicial Del Documento

Accion:

- abrir `/runtimeEvents` por carga documental normal
- pulsar `Refrescar metricas`

Esperado:

- el bloque `efficiency-navigation-performance` muestra valores reales de `type`, `duration`, `domInteractive`, `DCL end` y `load end`
- `type` normalmente arranca como `navigate` o `reload`
- el bloque `efficiency-runtime-asset` muestra el recurso `/_volt/runtime.js`
- `efficiency-runtime-overview` muestra conteos de telemetry y roots activos

### 2. Confirmar Runtime Externo Y Cacheable

Accion:

- revisar `Network`
- recargar la pagina una vez

Esperado:

- aparece una request hacia `/_volt/runtime.js`
- el runtime ya no viaja inline dentro del HTML principal
- la respuesta del runtime expone headers cacheables (`Cache-Control`, `ETag`, `Last-Modified`)

### 3. Generar Telemetria De Accion Y Patch

Accion:

- en la misma pantalla, interactuar con el bloque de `volt:on`:
  - escribir en el input de draft
  - alternar el panel con `click`
  - disparar `click.once`
  - usar el input con `keydown.enter.prevent`

Esperado:

- el card `Telemetry patch` incrementa su `count`
- `Latest patch entry` deja de estar vacio
- `efficiency-summary-json` refleja nuevos datos en `telemetrySummary.patch`
- `Active components summary` sigue mostrando roots coherentes y no crece sin motivo

### 4. Generar Telemetria De Navegacion SPA

Accion:

- desde `/runtimeEvents`, navegar por SPA a:
  - `/runtimeModelSync`
  - `/runtimeState`
- volver a `/runtimeEvents` por SPA

Esperado:

- el card `Telemetry navigation` incrementa su `count`
- `Latest navigation entry` muestra `finalUrl`, `outcome`, payload y duraciones
- el panel de eficiencia sigue disponible al regresar
- el runtime asset no necesita volver a inyectarse inline en el HTML

### 5. Generar Telemetria De Payload Real

Accion:

- navegar a `/runtimeModelSync`
- editar varios campos con `volt:model.sync`
- volver a `/runtimeEvents`

Esperado:

- `Telemetry action` refleja `avg request`, `max request`, `avg response` y `avg patch`
- `Latest action entry` muestra `requestPayloadBytes`, `responsePayloadBytes` y `patchDurationMs`
- en `Network` no debe verse tormenta de requests injustificada para una sola interaccion simple

### 6. Reset Y Refresco Operativo

Accion:

- pulsar `Resetear telemetria`
- despues pulsar `Refrescar roots`

Esperado:

- `telemetry entries` vuelve a `0`
- los cards de navigation/action/patch reinician sus conteos
- `total roots` y `unique components` siguen reflejando el estado actual del DOM conectado

### 7. Verificacion Basica Con DevTools Performance

Accion:

- grabar una sesion corta en `Performance`
- repetir una navegacion SPA y una accion reactiva

Esperado:

- no aparecen long tasks recurrentes obvias durante una interaccion simple
- el costo principal observable debe concentrarse en request/patch real, no en reinyeccion completa del runtime
- el timeline debe ser consistente con las duraciones visibles en el panel de eficiencia

## Criterio De Cierre

Se puede marcar esta pasada como util cuando:

- `/runtimeEvents` permite ver `navigation`, `action` y `patch` sin abrir consola
- la request a `/_volt/runtime.js` queda observable y separada del HTML
- el panel permite detectar payloads altos, patches lentos o crecimiento raro de roots
- los datos del laboratorio son suficientes para decidir el siguiente cuello de botella real antes de optimizar a ciegas
