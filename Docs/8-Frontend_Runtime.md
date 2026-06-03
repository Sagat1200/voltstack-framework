# VoltStack Frontend Runtime

## Introducción

El Frontend Runtime de VoltStack es el runtime JavaScript interno responsable de ejecutar la experiencia SPA reactiva dentro del navegador.

A diferencia de frameworks frontend tradicionales como:

- React
- Vue.js
- Svelte

el runtime frontend de VoltStack no busca convertirse en un framework UI independiente para desarrolladores frontend.

Su propósito es actuar como un runtime invisible que permita que componentes PHP funcionen como interfaces SPA modernas y reactivas.

---

## Filosofía

### 1. Invisible JavaScript

El desarrollador PHP no debe administrar directamente el runtime frontend.

---

### 2. SPA Native

La navegación SPA forma parte del núcleo.

---

### 3. Reactive Native

El runtime debe reaccionar automáticamente a cambios provenientes del backend.

---

### 4. Minimal Runtime

El runtime debe ser ligero y eficiente.

---

### 5. Protocol Driven

Todo el runtime gira alrededor de Volt Protocol.

---

## Objetivo Principal

Permitir que aplicaciones PHP se comporten como SPAs modernas mediante:

```txt
Frontend Runtime
+
Volt Protocol
+
Reactive Backend Runtime
```

sin requerir que el desarrollador implemente manualmente:

- AJAX
- state management
- DOM diffing
- hydration
- SPA routing
- DOM patching
- event synchronization

---

## Arquitectura General

```txt
Frontend Runtime
├── Boot Engine
├── Navigation Engine
├── Protocol Client
├── Component Runtime
├── State Runtime
├── DOM Engine
├── Effect Engine
├── Event Engine
├── Hydration Runtime
└── Transition Engine
```

---

## Runtime Boot Process

### Flujo inicial

```txt
HTML Initial Render
↓
Frontend Runtime Boot
↓
Component Discovery
↓
Snapshot Registration
↓
Hydration
↓
Event Binding
↓
SPA Ready
```

---

## Boot Engine

Responsable de iniciar el runtime.

---

## Funciones

- cargar runtime
- detectar componentes
- registrar snapshots
- inicializar navegación SPA
- preparar event listeners
- iniciar hydration

---

## Component Discovery

El runtime debe detectar automáticamente componentes renderizados.

---

## Ejemplo conceptual

```html
<div
    data-volt-component="counter"
    data-volt-id="cmp_1"
>
</div>
```

---

## Component Runtime

Responsable de administrar componentes en frontend.

---

## Funciones

- registrar componentes activos
- mantener snapshots
- manejar estado local
- ejecutar hydration
- aplicar patches

---

## Component Registry

Mapa de componentes activos.

---

## Ejemplo conceptual

```js
runtime.components.set('cmp_1', component);
```

---

## Snapshot Runtime

El frontend mantiene snapshots reactivos.

---

## Responsabilidades

- almacenar snapshots
- reenviar snapshots
- sincronizar estado
- detectar inconsistencias

---

## Ejemplo conceptual

```js
snapshot = {
    state: {
        count: 1
    }
};
```

---

## Protocol Client

Responsable de comunicarse con el backend mediante Volt Protocol.

---

## Funciones

- enviar actions
- enviar navegación
- enviar eventos
- recibir effects
- manejar responses
- retry requests
- protocol validation

---

## Ejemplo conceptual

```js
protocol.send({
    type: 'action',
    action: 'increment'
});
```

---

## Transport Layer

Inicialmente:

```txt
HTTP
```

Futuro:

```txt
WebSockets
SSE
Streaming
```

---

## Request Pipeline

```txt
User Interaction
↓
Event Engine
↓
Protocol Client
↓
Volt Protocol Request
↓
Backend
↓
Volt Protocol Response
↓
Effect Engine
↓
DOM Engine
```

---

## Event Engine

Responsable de capturar interacciones.

---

## Eventos soportados

- click
- input
- change
- submit
- blur
- focus
- keydown
- custom events

---

## Event Binding

Ejemplo conceptual:

```html
<button volt:click="increment">
```

---

## Runtime Parsing

El runtime interpreta:

```txt
volt:click
volt:model
volt:submit
volt:show
volt:if
volt:for
```

---

## State Runtime

Responsable del estado frontend.

---

## Tipos de Estado

### 1. Server State

Sincronizado con backend.

---

### 2. Client State

Estado únicamente frontend.

---

### 3. Shared State

Estado global compartido.

---

## Client State

Ejemplo:

```php
#[ClientState]
public bool $open = false;
```

---

## Objetivo

Evitar requests innecesarios para:

- dropdowns
- modals
- tabs
- collapse
- toggles

---

## State Synchronization

El runtime debe sincronizar:

```txt
Frontend State
↕
Backend State
```

---

## DOM Engine

Responsable de actualizar el DOM.

---

## Objetivos

- minimal DOM patching
- fragment replacement
- targeted updates
- preserve focus
- preserve transitions

---

## Estrategias

### 1. Fragment Replace

---

### 2. Attribute Patch

---

### 3. Text Patch

---

### 4. Partial Tree Patch

---

## Ejemplo conceptual

```json
{
  "effects": [
    {
      "type": "text.update",
      "target": "counter",
      "value": 5
    }
  ]
}
```

---

## DOM Patch Pipeline

```txt
Effects
↓
Effect Engine
↓
DOM Engine
↓
DOM Patch
↓
UI Updated
```

---

## Effect Engine

Responsable de interpretar effects.

---

## Effects soportados

```txt
text.update
html.replace
dom.append
dom.remove
class.toggle
style.set
focus
scroll
toast
modal
navigate
```

---

## Navigation Engine

Responsable de la navegación SPA.

---

## Funciones

- interceptar links
- history API
- preserve state
- transitions
- preload
- prefetch

---

## SPA Navigation Flow

```txt
Click Link
↓
Prevent Default
↓
Volt Navigation Request
↓
Backend Render
↓
Fragments
↓
DOM Patch
↓
History Update
```

---

## Link Interception

Ejemplo conceptual:

```html
<a href="/dashboard" volt:navigate>
```

---

## History API

El runtime utiliza:

```txt
pushState
replaceState
popstate
```

---

## Preserve State

Puede preservar:

- formularios
- scroll
- componentes vivos
- shared state

---

## Transition Engine

Responsable de transiciones SPA.

---

## Funciones

- page transitions
- loading states
- enter animations
- leave animations

---

## Loading States

Ejemplo conceptual:

```html
<button volt:loading>
```

---

## Optimistic UI

Objetivo futuro.

---

## Ejemplo

Actualizar UI antes de respuesta backend.

---

## Hydration Runtime

Responsable de:

- hydration inicial
- rehydration
- snapshot sync
- lifecycle frontend

---

## Frontend Lifecycle

```txt
discover
↓
hydrate
↓
bind events
↓
ready
↓
interact
↓
patch
↓
destroy
```

---

## Runtime Hooks

Objetivo futuro.

---

## Ejemplo conceptual

```js
runtime.on('component.updated', callback);
```

---

## Shared Runtime State

El runtime puede mantener estado global.

---

## Ejemplo conceptual

```js
runtime.state.set('theme', 'dark');
```

---

## Runtime Context

Información global del runtime.

---

## Ejemplo

```js
runtime.context = {
    locale: 'es',
    csrf: 'token',
    runtime: 'frankenphp'
};
```

---

## Runtime Memory

El runtime debe liberar:

- listeners huérfanos
- componentes destruidos
- snapshots obsoletos
- transitions viejas

---

## Error Handling

El runtime debe manejar:

- protocol errors
- hydration failures
- navigation failures
- timeout errors
- malformed payloads

---

## Error Example

```json
{
  "error": {
    "type": "protocol",
    "message": "Invalid snapshot."
  }
}
```

---

## Retry System

Objetivo futuro.

---

## Casos

- network failure
- timeout
- temporary disconnect

---

## Offline Mode

Objetivo futuro.

---

## Características futuras

- offline snapshots
- queued actions
- sync recovery

---

## Runtime Security

El runtime nunca debe:

- exponer estado protegido
- ejecutar código arbitrario
- confiar completamente en frontend
- modificar snapshots sensibles

---

## CSP Compatibility

Debe funcionar correctamente con CSP modernas.

---

## Runtime Extensibility

El runtime debe soportar:

- plugins
- directives
- custom effects
- middleware
- runtime hooks

---

## Directives System

Ejemplos conceptuales:

```txt
volt:click
volt:model
volt:show
volt:if
volt:for
volt:loading
volt:navigate
```

---

## Runtime Middleware

Objetivo futuro.

---

## Ejemplos

```txt
NavigationMiddleware
EffectMiddleware
HydrationMiddleware
```

---

## Rendering Strategy

VoltStack utiliza:

```txt
Server Driven Rendering
+
Client Side DOM Patching
```

---

## Runtime Goals

### 1. Small Runtime Size

---

### 2. Fast DOM Updates

---

### 3. SPA Native Feel

---

### 4. Minimal Network Usage

---

### 5. Invisible Complexity

---

## Performance Goals

Objetivos iniciales:

```txt
boot < 20ms
minimal payloads
fast patching
low memory usage
```

---

## Runtime Modes

---

## Standard Browser Mode

Modo principal.

---

## Progressive Enhancement Mode

Fallback para navegación clásica.

---

## Streaming Mode

Objetivo futuro.

---

## Runtime Compatibility

Compatible con:

- Chrome
- Firefox
- Safari
- Edge

---

## Future Goals

### Streaming UI

### WebSocket Runtime

### Concurrent Rendering

### Edge Runtime

### Offline Runtime

### Shared Worker Runtime

### Multi-tab Synchronization

---

## MVP Goals

La primera versión debe soportar:

- component discovery
- protocol requests
- effect handling
- DOM patching básico
- SPA navigation
- hydration básica
- loading states
- event binding

---

## Ejemplo Completo

### HTML

```html
<div
    data-volt-component="counter"
    data-volt-id="cmp_1"
>
    <span id="counter">1</span>

    <button volt:click="increment">
        Increment
    </button>
</div>
```

---

## Frontend Runtime

```txt
click
↓
send protocol action
↓
receive effects
↓
patch DOM
```

---

## Backend Response

```json
{
  "effects": [
    {
      "type": "text.update",
      "target": "counter",
      "value": 2
    }
  ]
}
```

---

## Resultado

```txt
DOM actualizado
sin recarga
```

---

## Conclusión

El Frontend Runtime de VoltStack es el puente que permite que PHP controle interfaces SPA modernas sin obligar al desarrollador a trabajar directamente con frameworks frontend complejos.

Debe mantenerse:

- ligero
- reactivo
- extensible
- seguro
- optimizado
- invisible para el desarrollador PHP

mientras ofrece una experiencia comparable a aplicaciones SPA modernas.
