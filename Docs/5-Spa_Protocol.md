# VoltStack SPA Protocol

## Introducción

Volt Protocol es el protocolo oficial de comunicación entre el Frontend Runtime y el Reactive Runtime de VoltStack.

Este protocolo define cómo el frontend y backend intercambian:

- estado
- acciones
- eventos
- navegación
- efectos
- errores
- metadata reactiva

Volt Protocol es uno de los núcleos tecnológicos más importantes de VoltStack.

El protocolo está diseñado específicamente para:

- aplicaciones SPA
- rendering reactivo
- runtime persistente
- payloads mínimos
- sincronización eficiente
- partial rendering
- comunicación optimizada

---

## Filosofía del Protocolo

### 1. Minimal Payloads

El protocolo debe enviar únicamente información necesaria.

---

### 2. Reactive Native

Volt Protocol está diseñado para interfaces reactivas desde el núcleo.

---

### 3. Transport Agnostic

El protocolo debe poder funcionar sobre:

- HTTP
- WebSockets
- SSE
- HTTP/3
- runtimes distribuidos futuros

---

### 4. State Driven

El protocolo está basado en sincronización de estado y efectos.

---

### 5. Runtime Optimized

El protocolo debe minimizar:

- serialización
- hydration cost
- DOM updates
- bandwidth
- latency

---

## Arquitectura General

```txt
Frontend Runtime
        ↕
Volt Protocol
        ↕
Reactive Runtime
```

---

## Objetivos del Protocolo

Volt Protocol debe permitir:

- navegación SPA
- acciones reactivas
- sincronización de estado
- actualización parcial
- hydration eficiente
- rendering incremental
- dispatch de eventos
- manejo de errores
- streaming futuro

---

## Tipos de Comunicación

Volt Protocol define dos tipos principales:

### 1. Client → Server

El frontend envía:

- acciones
- eventos
- navegación
- snapshots
- metadata

---

### 2. Server → Client

El backend responde con:

- effects
- state patches
- navigation updates
- errors
- fragments
- metadata

---

## Flujo Reactivo

```txt
User Interaction
↓
Frontend Runtime
↓
Volt Protocol Request
↓
Reactive Runtime
↓
Component Execution
↓
Diff Generation
↓
Volt Protocol Response
↓
Frontend Runtime
↓
DOM Patch
```

---

## Estructura General del Payload

```json
{
  "protocol": "volt",
  "version": "1.0",
  "type": "action",
  "component": {},
  "state": {},
  "effects": [],
  "metadata": {}
}
```

---

## Estructura Base del Request

```json
{
  "protocol": "volt",
  "version": "1.0",
  "request_id": "uuid",
  "type": "action",
  "component": {
    "id": "cmp_123",
    "name": "counter"
  },
  "action": {
    "name": "increment",
    "params": []
  },
  "snapshot": {},
  "metadata": {}
}
```

---

## Estructura Base del Response

```json
{
  "protocol": "volt",
  "version": "1.0",
  "response_id": "uuid",
  "type": "update",
  "component": {
    "id": "cmp_123"
  },
  "state": {},
  "effects": [],
  "metadata": {}
}
```

---

## Component Payload

### Objetivo

Identificar el componente reactivo.

### Ejemplo

```json
{
  "component": {
    "id": "cmp_123",
    "name": "counter",
    "checksum": "hash"
  }
}
```

---

## Component ID

Cada componente tiene un ID único runtime.

Ejemplo:

```txt
cmp_123
```

---

## Snapshot System

Cada request reactivo incluye un snapshot serializado.

### Objetivos

- hydration
- validación
- protección de integridad
- dirty detection

### Ejemplo

```json
{
  "snapshot": {
    "state": {
      "count": 5
    },
    "memo": {
      "path": "/counter"
    },
    "checksum": "secure_hash"
  }
}
```

---

## State Payload

Contiene estado serializable del componente.

### Ejemplo

```json
{
  "state": {
    "count": 10,
    "loading": false
  }
}
```

---

## Dirty State Payload

El protocolo debe enviar únicamente propiedades modificadas.

Ejemplo:

```json
{
  "dirty": {
    "count": 10
  }
}
```

---

## Actions

Representan métodos ejecutables del componente.

### Ejemplo

```json
{
  "action": {
    "name": "increment",
    "params": []
  }
}
```

---

## Action Lifecycle

```txt
dispatch
↓
hydrate
↓
authorize
↓
validate
↓
execute
↓
mutate state
↓
render
↓
generate effects
↓
response
```

---

## Effects System

Los effects son instrucciones enviadas al Frontend Runtime.

El backend no manipula directamente el DOM.

El backend genera effects.

---

## Tipos de Effects

### DOM Effects

```txt
text.update
html.replace
dom.append
dom.remove
dom.move
attribute.set
class.toggle
style.set
```

---

### Navigation Effects

```txt
navigate
redirect
reload
history.replace
history.push
```

---

### UI Effects

```txt
show.modal
hide.modal
toast
focus
blur
scroll
```

---

### Event Effects

```txt
dispatch.event
emit
broadcast
```

---

## Ejemplo de Effects

```json
{
  "effects": [
    {
      "type": "text.update",
      "target": "counter",
      "value": 11
    }
  ]
}
```

---

## Partial Rendering

Volt Protocol debe soportar actualizaciones parciales.

### Objetivo

Evitar rerenderizar páginas completas.

---

## Fragment System

La UI se divide en fragmentos renderizables.

Ejemplo:

```txt
layout
├── sidebar
├── navbar
└── content
```

---

## Fragment Payload

```json
{
  "fragments": [
    {
      "id": "content",
      "html": "<div>...</div>"
    }
  ]
}
```

---

## Navigation Protocol

Volt Protocol soporta navegación SPA nativa.

---

## Navigation Request

```json
{
  "type": "navigate",
  "target": "/dashboard"
}
```

---

## Navigation Response

```json
{
  "navigation": {
    "url": "/dashboard",
    "replace": false,
    "preserveScroll": true,
    "preserveState": false
  }
}
```

---

## State Preservation

El protocolo puede preservar:

- scroll
- formularios
- estado compartido
- estado temporal

---

## Event System

Volt Protocol puede transportar eventos.

---

## Client Events

```json
{
  "event": {
    "name": "user.selected",
    "payload": {
      "id": 1
    }
  }
}
```

---

## Server Events

```json
{
  "effects": [
    {
      "type": "dispatch.event",
      "name": "notification.created"
    }
  ]
}
```

---

## Error Handling

Volt Protocol define errores estructurados.

---

## Validation Error

```json
{
  "error": {
    "type": "validation",
    "message": "Validation failed.",
    "fields": {
      "email": [
        "El campo email es obligatorio."
      ]
    }
  }
}
```

---

## Runtime Error

```json
{
  "error": {
    "type": "runtime",
    "message": "Component action failed."
  }
}
```

---

## Security Model

Volt Protocol debe implementar múltiples mecanismos de seguridad.

---

## Checksum Validation

Cada snapshot debe incluir checksum.

---

## Payload Signing

Los payloads críticos deben poder firmarse.

---

## Protected State

Algunas propiedades nunca deben exponerse al frontend.

Ejemplo:

```php
#[Protected]
private string $secret;
```

---

## Protocol Versioning

El protocolo debe ser versionable.

Ejemplo:

```json
{
  "protocol": "volt",
  "version": "1.0"
}
```

---

## Serialization Rules

El protocolo debe soportar:

- primitives
- arrays
- collections
- DTOs
- enums
- serializable objects

---

## Unsupported Types

Nunca serializar directamente:

- closures
- resources
- database connections
- runtime handlers

---

## Metadata System

Metadata adicional del runtime.

Ejemplo:

```json
{
  "metadata": {
    "locale": "es",
    "timezone": "UTC",
    "runtime": "frankenphp"
  }
}
```

---

## Runtime Modes

Volt Protocol debe adaptarse según el runtime.

---

## Classic Mode

```txt
HTTP request/response
```

---

## Persistent Mode

```txt
persistent workers
reduced payloads
runtime caching
```

---

## Transport Layer

Volt Protocol es independiente del transporte.

---

## HTTP Transport

Modo inicial oficial.

---

## WebSocket Transport

Objetivo futuro.

---

## SSE Transport

Objetivo futuro.

---

## Streaming UI

Objetivo futuro del protocolo.

Ejemplo conceptual:

```txt
stream partial UI
incremental rendering
```

---

## Protocol Compression

El protocolo debe permitir:

- payload compression
- fragment deduplication
- state optimization

---

## Frontend Runtime Responsibilities

El Frontend Runtime debe:

- interpretar Volt Protocol
- ejecutar effects
- aplicar patches
- manejar navegación
- actualizar estado local

---

## Backend Responsibilities

El backend debe:

- validar requests
- ejecutar acciones
- sincronizar estado
- producir effects
- serializar snapshots

---

## Lifecycle Integrado

```txt
mount
↓
hydrate
↓
execute
↓
render
↓
diff
↓
effects
↓
dehydrate
↓
response
```

---

## Protocol Goals

### 1. Small Payloads

### 2. Fast Hydration

### 3. Minimal DOM Updates

### 4. Runtime Compatibility

### 5. Reactive Performance

### 6. SPA Native Experience

---

## Debug Mode

Volt Protocol debe tener modo debug.

Información adicional:

- payload inspector
- hydration timing
- effects inspector
- component tree
- render timing

---

## Protocol Middleware

El protocolo puede tener middleware.

Ejemplos:

```txt
ProtocolValidationMiddleware
PayloadSigningMiddleware
HydrationMiddleware
CompressionMiddleware
```

---

## Future Protocol Goals

### Streaming Rendering

### Real-time Synchronization

### Distributed Runtime

### Edge Runtime

### Offline Sync

### Concurrent Rendering

### Server Push

---

## MVP del Protocolo

La primera versión debe soportar:

- component hydration
- action dispatch
- dirty state
- effects
- partial rendering básico
- SPA navigation básica
- structured errors

---

## Ejemplo Completo

### Request

```json
{
  "protocol": "volt",
  "version": "1.0",
  "type": "action",
  "component": {
    "id": "cmp_1",
    "name": "counter"
  },
  "action": {
    "name": "increment"
  },
  "snapshot": {
    "state": {
      "count": 1
    }
  }
}
```

---

### Response

```json
{
  "protocol": "volt",
  "version": "1.0",
  "type": "update",
  "state": {
    "count": 2
  },
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

## Conclusión

Volt Protocol representa el puente central entre PHP y la experiencia SPA reactiva de VoltStack.

El protocolo debe evolucionar como un sistema altamente optimizado, seguro y preparado para runtimes persistentes modernos como FrankenPHP.
