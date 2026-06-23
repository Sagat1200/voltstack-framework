# Runtime Advanced Directives - Validacion Manual

## Objetivo

Validar en navegador que la demo `/runtimeAdvancedDirectives` expone un flujo manual rapido y repetible para revisar:

- `volt:text` con fallback `??`
- expresiones compuestas en `volt:show`
- expresiones compuestas en `volt:if`
- reglas multiples en `volt:class`, `volt:attr` y `volt:style`
- comparaciones relacionales entre refs y literales
- bordes `null` vs `undefined`

## Preparacion

1. levantar la aplicacion cliente con Vite y servidor local activos
2. abrir `/runtimeAdvancedDirectives`
3. confirmar que la pagina muestra el bloque `Presets de validacion manual`
4. usar `Reset demo` antes de empezar cada pasada si vienes de otro estado

## Marcadores Estables

Los siguientes targets ayudan a inspeccionar la demo de forma consistente:

- `data-runtime-preset-status`
- `data-runtime-check="text-fallback-result"`
- `data-runtime-check="show-compound-panel"`
- `data-runtime-check="if-compound-panel"`
- `data-runtime-check="relational-threshold-panel"`
- `data-runtime-check="relational-ref-panel"`
- `data-runtime-check="null-undefined-flex-panel"`
- `data-runtime-check="null-undefined-strict-panel"`
- `data-runtime-check="class-multi-card"`
- `data-runtime-check="attr-multi-button"`
- `data-runtime-check="style-multi-card"`

## Checklist Manual

### 1. Fallback shared

Accion:

- pulsar `Fallback shared`

Esperado:

- `data-runtime-preset-status` describe el preset de fallback
- `text-fallback-result` muestra `Nota shared visible`
- `client snapshot` queda sin `draft.note`
- `shared snapshot` contiene `draft.note = "Nota shared visible"`

### 2. Prioridad client

Accion:

- pulsar `Prioridad client`

Esperado:

- `text-fallback-result` muestra `Nota client prioritaria`
- `shared snapshot` conserva `Nota shared secundaria`
- el resultado final sigue favoreciendo `client`

### 3. Condicion true

Accion:

- pulsar `Condicion true`

Esperado:

- `show-compound-panel` queda visible
- `if-compound-panel` queda montado
- los snapshots reflejan:
  - `client.ui.showClientPanel = true`
  - `client.ui.mountClientPanel = true`
  - `shared.ui.showSharedPanel = false`
  - `shared.ui.mountSharedPanel = false`

### 4. Condicion false

Accion:

- pulsar `Condicion false`

Esperado:

- `show-compound-panel` deja de verse
- `if-compound-panel` deja de estar montado
- los snapshots reflejan:
  - `client.ui.showClientPanel = true`
  - `client.ui.mountClientPanel = false`
  - `shared.ui.showSharedPanel = true`
  - `shared.ui.mountSharedPanel = false`

### 5. Umbral relacional

Accion:

- pulsar `Umbral relacional`

Esperado:

- `relational-threshold-panel` queda visible
- `relational-ref-panel` queda visible
- `client.counter = 2`
- `shared.counter = 1`
- en el inspector, `client:counter >= 2 && shared:counter < 3` y `client:counter >= shared:counter` se muestran como `true`

### 6. Null vs undefined

Accion:

- pulsar `Null vs undefined`

Esperado:

- `null-undefined-flex-panel` queda visible
- `null-undefined-strict-panel` muestra la variante estricta como falsa
- los snapshots contienen `nullValue`, `emptyString`, `zeroValue` y `falseValue`
- `undefinedValue` no aparece serializado en ninguno de los scopes
- en el inspector:
  - `==` entre `null` y `undefined` da `true`
  - `===` entre `null` y `undefined` da `false`

### 7. Reglas client

Accion:

- pulsar `Reglas client`

Esperado:

- `class-multi-card` recibe la rama client de clases
- `attr-multi-button` queda `disabled` con `data-lock="client-only"`
- `style-multi-card` recibe la rama client de estilos
- las ramas shared quedan inactivas

### 8. Reglas shared

Accion:

- pulsar `Reglas shared`

Esperado:

- `class-multi-card` muestra tambien la rama shared
- `attr-multi-button` queda `disabled` con `data-lock="shared"` y `title="Bloqueado por shared"`
- `style-multi-card` usa la rama shared de estilos
- `shared snapshot` refleja `highlightSharedCard`, `lockSharedAction` y `softenSharedCard` en `true`

### 9. Reset

Accion:

- pulsar `Reset demo`

Esperado:

- `data-runtime-preset-status` indica baseline limpio
- los inputs de notas quedan vacios
- los snapshots `client` y `shared` vuelven a un estado base sin residuos de presets anteriores

## Cierre Del Bloque

Se puede marcar esta validacion como completada cuando:

- todos los presets dejan la pantalla en un estado coherente
- los markers anteriores se pueden revisar sin ambiguedad
- los snapshots coinciden con el comportamiento visual
- no aparecen errores de consola al alternar entre presets
