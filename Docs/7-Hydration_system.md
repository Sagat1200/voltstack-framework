# VoltStack Hydration System

## Introducción

El Hydration System de VoltStack es responsable de reconstruir, sincronizar y serializar el estado reactivo de los componentes entre interacciones SPA.

Es uno de los sistemas más importantes del framework porque permite que los componentes PHP se comporten como interfaces reactivas persistentes dentro de una SPA moderna.

El sistema de hydration debe:

- restaurar componentes
- sincronizar estado
- validar integridad
- minimizar payloads
- proteger información sensible
- funcionar correctamente en runtimes persistentes

---

## Objetivo Principal

Permitir que un componente PHP pueda:

```php
class Counter extends Component
{
    public int $count = 0;
}
```

mantenga comportamiento reactivo entre requests SPA sin que el desarrollador administre manualmente:

- serialización
- reconstrucción
- snapshots
- sincronización frontend/backend
- payload management

---

## Filosofía del Sistema

### 1. State Driven

La interfaz debe reconstruirse a partir del estado serializado.

---

### 2. Minimal Payloads

Solo debe transportarse información necesaria.

---

### 3. Runtime Safe

Debe funcionar correctamente en:

- FrankenPHP
- PHP-FPM
- RoadRunner
- Swoole

---

### 4. Secure by Default

Nunca debe exponer información sensible accidentalmente.

---

### 5. Reactive Native

La hydration es parte del núcleo reactivo del framework.

---

## ¿Qué es Hydration?

Hydration es el proceso de:

```txt
restaurar un componente
a partir de un snapshot serializado
```

---

## ¿Qué es Dehydration?

Dehydration es el proceso contrario:

```txt
convertir un componente
en un snapshot serializable
```

---

## Flujo General

```txt
Component
↓
Dehydrate
↓
Snapshot
↓
Volt Protocol
↓
Frontend Runtime
↓
Request
↓
Hydrate
↓
Restored Component
```

---

## Ciclo Completo

```txt
mount
↓
render
↓
dehydrate
↓
snapshot transport
↓
hydrate
↓
action
↓
render
↓
dehydrate
```

---

## Snapshot System

El Snapshot System representa el estado serializado del componente.

---

## Objetivos del Snapshot

- restaurar componentes
- validar integridad
- detectar cambios
- reducir payloads
- mantener metadata reactiva

---

## Estructura Base del Snapshot

```json
{
  "component": {
    "id": "cmp_123",
    "name": "counter"
  },
  "state": {
    "count": 5
  },
  "memo": {},
  "checksum": "hash"
}
```

---

## Component Metadata

Información necesaria para reconstrucción.

---

## Ejemplo

```json
{
  "memo": {
    "path": "/dashboard",
    "locale": "es",
    "layout": "app"
  }
}
```

---

## Component Identity

Cada componente tiene:

- component id
- component class
- runtime metadata
- lifecycle context

---

## Hydration Pipeline

```txt
Receive Snapshot
↓
Validate Protocol
↓
Validate Checksum
↓
Resolve Component
↓
Instantiate Component
↓
Restore State
↓
Restore Metadata
↓
Hydrate Relations
↓
Execute Hooks
↓
Ready
```

---

## Dehydration Pipeline

```txt
Component
↓
Extract Serializable State
↓
Filter Protected State
↓
Normalize Values
↓
Generate Metadata
↓
Generate Checksum
↓
Serialize Snapshot
↓
Volt Protocol Response
```

---

## Serializable State

El sistema debe soportar:

- string
- integer
- float
- boolean
- array
- enums
- DTOs
- collections
- serializable objects

---

## Unsupported Types

Nunca serializar directamente:

- closures
- resources
- database connections
- runtime handlers
- streams
- sockets

---

## Public State

Las propiedades públicas son serializables automáticamente.

---

## Ejemplo

```php
public int $count = 0;
```

---

## Protected State

No debe serializarse.

---

## Ejemplo

```php
protected array $cache = [];
```

---

## Private State

Nunca debe exponerse.

---

## Ejemplo

```php
private string $secret;
```

---

## Protected Attribute

Ejemplo conceptual:

```php
#[Protected]
public string $token;
```

---

## Client State

Algunas propiedades viven únicamente en frontend.

---

## Ejemplo

```php
#[ClientState]
public bool $open = false;
```

---

## Dirty State Detection

El sistema debe detectar automáticamente propiedades modificadas.

---

## Ejemplo

```txt
before:
count = 1

after:
count = 2
```

Resultado:

```txt
dirty:
count
```

---

## Partial State Payloads

Solo propiedades modificadas deben enviarse.

---

## Ejemplo

```json
{
  "dirty": {
    "count": 2
  }
}
```

---

## State Normalization

Antes de serializar:

- enums → scalar
- collections → array
- DTOs → normalized structure

---

## Hydration Context

Cada hydration ocurre dentro de un contexto aislado.

---

## Runtime Context

Incluye:

- request
- auth
- session
- locale
- tenant
- runtime metadata

---

## Ejemplo conceptual

```php
RuntimeContext::current();
```

---

## Lifecycle Hooks

El Hydration System ejecuta hooks automáticos.

---

## hydrate()

Se ejecuta después de reconstruir estado.

```php
public function hydrate(): void
{
    //
}
```

---

## dehydrate()

Antes de serializar.

```php
public function dehydrate(): void
{
    //
}
```

---

## hydrateProperty()

Objetivo futuro.

```php
public function hydrateCount($value): void
{
    //
}
```

---

## dehydrateProperty()

Objetivo futuro.

```php
public function dehydrateCount($value): mixed
{
    return $value;
}
```

---

## Snapshot Integrity

Cada snapshot debe protegerse.

---

## Checksum System

Los snapshots deben incluir checksum.

---

## Ejemplo

```json
{
  "checksum": "secure_hash"
}
```

---

## Objetivos del Checksum

- evitar manipulación
- detectar corrupción
- proteger estado

---

## Payload Signing

Objetivo futuro:

```txt
signed payloads
encrypted payloads
```

---

## Nested Component Hydration

Los componentes hijos deben hidratarse independientemente.

---

## Ejemplo

```txt
DashboardPage
├── Sidebar
├── StatsWidget
└── ActivityFeed
```

---

## Fragment Hydration

El sistema debe hidratar fragmentos parciales.

---

## Objetivo

Evitar rerenderizar páginas completas.

---

## Lazy Hydration

Objetivo futuro.

---

## Casos de uso

- componentes debajo del viewport
- tabs ocultos
- modals
- widgets secundarios

---

## Incremental Hydration

Objetivo futuro:

```txt
hydrate progressively
```

---

## Smart Client Hydration

Pequeños estados pueden manejarse localmente.

---

## Ejemplos

- dropdowns
- toggles
- tabs
- modal visibility

---

## Hydration Middleware

El sistema puede incluir middleware.

---

## Ejemplos

```txt
SnapshotValidationMiddleware
ProtectedStateMiddleware
HydrationPerformanceMiddleware
```

---

## Serialization Engine

Responsable de:

- normalize values
- convert objects
- reduce payloads
- optimize serialization

---

## Serialization Strategies

### 1. Full Snapshot

Render inicial.

---

### 2. Dirty Snapshot

Solo propiedades modificadas.

---

### 3. Fragment Snapshot

Solo fragmentos específicos.

---

## Runtime Awareness

El Hydration System debe adaptarse al runtime.

---

## PHP-FPM Mode

```txt
stateless hydration
```

---

## FrankenPHP Mode

```txt
persistent metadata
persistent registries
cached reflection
optimized hydration
```

---

## Runtime Persistence

En FrankenPHP pueden mantenerse:

- reflection cache
- component metadata
- serializers
- hydration maps

---

## Runtime Reset

Nunca persistir entre requests:

- auth
- request
- session
- validation errors
- temporary state

---

## Memory Management

El sistema debe evitar:

- memory leaks
- stale snapshots
- orphaned references
- snapshot accumulation

---

## Component Reconciliation

El runtime debe reconciliar:

```txt
old snapshot
vs
new state
```

---

## DOM Synchronization

Hydration no actualiza directamente el DOM.

Produce:

```txt
effects
patches
fragments
```

que luego interpreta el Frontend Runtime.

---

## Error Handling

Errores posibles:

- invalid snapshot
- checksum mismatch
- hydration failure
- serialization failure
- invalid component state

---

## Error Response Example

```json
{
  "error": {
    "type": "hydration",
    "message": "Invalid snapshot."
  }
}
```

---

## Performance Goals

Objetivos:

- hydration rápida
- payload mínimo
- serialización eficiente
- partial hydration
- reflection cache
- minimal memory usage

---

## Security Goals

Objetivos:

- proteger estado privado
- prevenir payload tampering
- validar snapshots
- proteger propiedades sensibles

---

## Debugging

Modo debug debe permitir:

- hydration timeline
- snapshot inspector
- dirty state inspector
- serialization inspector

---

## Frontend Runtime Responsibilities

El frontend debe:

- almacenar snapshots
- reenviar snapshots
- manejar state patches
- sincronizar metadata

---

## Backend Responsibilities

El backend debe:

- validar snapshots
- reconstruir componentes
- serializar estado
- generar checksums
- producir payloads optimizados

---

## Future Goals

### Streaming Hydration

### Concurrent Hydration

### Offline Snapshot Recovery

### Distributed Runtime Hydration

### Edge Runtime Hydration

### Binary Payload Hydration

---

## MVP Goals

La primera versión debe soportar:

- snapshot básico
- hydration simple
- dehydration simple
- dirty detection
- checksum validation
- nested component hydration
- partial state updates

---

## Ejemplo Completo

### Estado inicial

```php
class Counter extends Component
{
    public int $count = 1;
}
```

---

## Snapshot generado

```json
{
  "component": {
    "id": "cmp_1",
    "name": "counter"
  },
  "state": {
    "count": 1
  },
  "checksum": "secure_hash"
}
```

---

## Acción recibida

```json
{
  "action": {
    "name": "increment"
  }
}
```

---

## Estado después

```json
{
  "dirty": {
    "count": 2
  }
}
```

---

## Conclusión

El Hydration System es uno de los pilares fundamentales de VoltStack.

Debe permitir que componentes PHP se comporten como interfaces SPA modernas, manteniendo:

- rendimiento
- seguridad
- reactividad
- payloads mínimos
- compatibilidad con runtimes persistentes

sin exponer complejidad innecesaria al desarrollador.
