# VoltStack State System

## Introducción

El State System de VoltStack es la infraestructura encargada de administrar el estado reactivo de la aplicación.

El sistema de estado es uno de los pilares fundamentales del framework porque permite que:

- componentes PHP
- frontend runtime
- SPA navigation
- hydration system
- Volt Protocol
- runtime persistente

permanezcan sincronizados de manera eficiente y reactiva.

VoltStack adopta una arquitectura state-driven donde la interfaz es consecuencia directa del estado actual de la aplicación.

---

## Filosofía del Sistema de Estado

### 1. State Driven UI

La interfaz debe derivarse completamente del estado.

---

### 2. Reactive Native

El estado debe reaccionar automáticamente a cambios.

---

### 3. PHP First

El estado principal debe vivir en PHP.

---

### 4. Smart Client State

Pequeños estados UI pueden manejarse localmente en frontend.

---

### 5. Runtime Safe

El estado debe ser seguro para runtimes persistentes.

---

## Objetivo Principal

Permitir que aplicaciones SPA modernas puedan desarrollarse mediante:

```php
class Counter extends Component
{
    public int $count = 0;
}
```

sin administrar manualmente:

- sincronización
- serialization
- hydration
- DOM updates
- frontend stores
- snapshots
- reactive subscriptions

---

## Arquitectura General

```txt
Frontend Runtime
        ↕
Volt Protocol
        ↕
Reactive Runtime
        ↕
State Engine
        ├── Local State
        ├── Shared State
        ├── Client State
        ├── Runtime State
        └── Session State
```

---

## Tipos de Estado

VoltStack define múltiples categorías de estado.

---

## 1. Local State

Estado interno de un componente.

---

## Ejemplo

```php
public int $count = 0;
```

---

## Características

- serializable
- reactivo
- scoped al componente
- sincronizado automáticamente

---

## 2. Shared State

Estado compartido entre múltiples componentes.

---

## Ejemplo

```php
State::share('theme', 'dark');
```

---

## Casos de uso

- tema global
- usuario autenticado
- locale
- preferencias UI
- runtime metadata

---

## 3. Client State

Estado únicamente frontend.

---

## Ejemplo

```php
#[ClientState]
public bool $open = false;
```

---

## Objetivo

Evitar requests innecesarios.

---

## Casos comunes

- dropdowns
- modals
- collapse
- tabs
- hover state
- animations

---

## 4. Session State

Estado persistente por sesión.

---

## Ejemplo

```php
SessionState::put('cart', $cart);
```

---

## 5. Runtime State

Estado interno temporal del runtime.

---

## Ejemplo

```txt
active components
navigation state
hydration maps
protocol metadata
```

---

## Arquitectura del State Engine

```txt
State Engine
├── State Manager
├── State Store
├── Signal Engine
├── Watchers
├── Dirty Tracker
├── Serialization Engine
├── Hydration Integration
└── Runtime Synchronization
```

---

## State Manager

Responsable principal del sistema.

---

## Funciones

- register state
- mutate state
- synchronize state
- validate mutations
- track changes

---

## State Store

Contenedor interno del estado.

---

## Ejemplo conceptual

```php
StateStore::set('theme', 'dark');
```

---

## Reactive State

Todo estado serializable es reactivo por defecto.

---

## Flujo Reactivo

```txt
state mutation
↓
dirty detection
↓
render
↓
effects
↓
Volt Protocol
↓
DOM patch
```

---

## Dirty State Detection

El runtime debe detectar cambios automáticamente.

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

## Dirty Payloads

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

## State Synchronization

El runtime sincroniza automáticamente:

```txt
Frontend State
↕
Backend State
```

---

## State Lifecycle

```txt
initialize
↓
hydrate
↓
mutate
↓
track dirty
↓
render
↓
serialize
↓
dehydrate
```

---

## State Serialization

El sistema debe soportar:

- primitives
- arrays
- collections
- enums
- DTOs
- serializable objects

---

## Unsupported State

Nunca serializar:

- closures
- resources
- streams
- connections
- runtime handlers

---

## State Normalization

Antes de serializar:

- collections → arrays
- enums → scalar
- DTOs → normalized objects

---

## Protected State

Algunas propiedades nunca deben exponerse.

---

## Ejemplo

```php
#[Protected]
public string $token;
```

---

## Protected State Rules

- nunca serializar
- nunca enviar al frontend
- nunca incluir en snapshots

---

## Computed State

Valores derivados automáticamente.

---

## Ejemplo

```php
public function getFullNameProperty(): string
{
    return "{$this->name} {$this->last_name}";
}
```

---

## Características

- readonly
- recalculado automáticamente
- no persistido

---

## Signal System

VoltStack incluirá un sistema de señales reactivas.

---

## Objetivo

Permitir dependencias reactivas.

---

## Ejemplo conceptual

```php
$count = signal(0);
```

---

## Signal Features

- dependency tracking
- computed values
- watchers
- effects
- subscriptions

---

## Watchers

Los watchers reaccionan a cambios de estado.

---

## Ejemplo

```php
public function watchCount($value): void
{
    //
}
```

---

## Watch Lifecycle

```txt
state mutation
↓
watch trigger
↓
effect generation
```

---

## Shared State System

Estado global compartido.

---

## Ejemplo

```php
State::share('locale', 'es');
```

---

## Shared State Goals

- evitar prop drilling
- sincronización global
- runtime metadata
- global UI state

---

## State Persistence

El sistema puede persistir estado.

---

## Estrategias

### Session Persistence

---

### Cache Persistence

---

### Database Persistence

---

## State Snapshots

El estado forma parte del snapshot de hydration.

---

## Ejemplo

```json
{
  "state": {
    "count": 5
  }
}
```

---

## Snapshot Goals

- hydration
- restore state
- dirty comparison
- runtime synchronization

---

## Frontend Runtime State

El frontend mantiene:

- snapshots
- local state
- shared state
- navigation state

---

## Frontend State Responsibilities

- reenviar snapshots
- manejar client state
- sincronizar dirty state
- ejecutar effects

---

## SPA Navigation State

La navegación SPA puede preservar estado.

---

## Preserve State

Ejemplo conceptual:

```php
navigate('/dashboard')
    ->preserveState();
```

---

## State Isolation

Cada componente debe aislar su estado.

---

## Nested Components

Cada componente hijo posee:

- state independiente
- dirty tracking independiente
- hydration independiente

---

## State Context

Cada request se ejecuta dentro de un contexto aislado.

---

## Runtime Context

Incluye:

- auth
- request
- tenant
- locale
- runtime metadata

---

## Runtime Safety

El estado nunca debe filtrarse entre requests.

---

## Especialmente importante en

- FrankenPHP
- RoadRunner
- Swoole

---

## Runtime Reset Strategy

Después de cada request:

```txt
clear request state
reset scoped stores
clear temporary references
flush auth state
```

---

## State Mutation Rules

El runtime debe controlar mutaciones.

---

## Objetivos

- evitar corrupción
- validar tipos
- proteger propiedades

---

## Immutable Goals

Objetivo futuro:

```txt
immutable snapshots
```

---

## Optimistic UI

Objetivo futuro.

---

## Flujo conceptual

```txt
frontend updates immediately
↓
backend confirms
↓
reconcile state
```

---

## Async State

Objetivo futuro.

---

## Ejemplo conceptual

```php
#[AsyncState]
public array $report;
```

---

## Distributed State

Objetivo futuro:

- multi-worker state
- distributed runtime
- realtime sync

---

## State Middleware

El sistema puede incluir middleware.

---

## Ejemplos

```txt
StateValidationMiddleware
ProtectedStateMiddleware
SerializationMiddleware
```

---

## State Validation

El sistema debe validar:

- tipos
- serialization safety
- mutation integrity
- protocol compatibility

---

## Error Handling

Errores posibles:

- invalid mutation
- serialization failure
- protected state access
- snapshot mismatch

---

## Error Example

```json
{
  "error": {
    "type": "state",
    "message": "Invalid state mutation."
  }
}
```

---

## State Performance Goals

Objetivos:

- payloads mínimos
- dirty updates
- fast serialization
- minimal memory usage
- optimized hydration

---

## Runtime Persistence Optimization

FrankenPHP permite:

- state metadata cache
- serializer reuse
- reflection persistence
- hydration optimization

---

## Memory Management

El sistema debe prevenir:

- stale references
- orphaned snapshots
- memory leaks
- oversized payloads

---

## Debug Mode

Modo debug debe incluir:

- state inspector
- dirty tracker
- snapshot viewer
- mutation logs
- watcher logs

---

## State Logging

Registrar:

- mutations
- dirty state
- snapshot generation
- synchronization failures

---

## Security Goals

Objetivos:

- proteger propiedades sensibles
- prevenir state tampering
- validar snapshots
- secure serialization

---

## Future Goals

### Distributed State

### Realtime Synchronization

### Offline State Recovery

### Shared Workers

### Concurrent State Updates

### Streaming State

---

## MVP Goals

La primera versión debe soportar:

- local state
- dirty tracking
- state hydration
- shared state básico
- computed properties
- watchers básicos
- protected state
- snapshot synchronization

---

## Ejemplo Completo

```php
class Counter extends Component
{
    public int $count = 0;

    public function increment(): void
    {
        $this->count++;
    }

    public function getDoubleProperty(): int
    {
        return $this->count * 2;
    }
}
```

---

## Flujo

```txt
increment()
↓
state mutation
↓
dirty detection
↓
render
↓
effects
↓
Volt Protocol
↓
frontend patch
```

---

## Resultado

```txt
reactive SPA update
without full reload
```

---

## Conclusión

El State System de VoltStack representa la base reactiva que permite que PHP controle interfaces SPA modernas de forma eficiente, segura y altamente productiva.

Debe combinar:

- simplicidad
- reactividad
- rendimiento
- sincronización automática
- runtime safety
- SPA fluida

sin obligar al desarrollador PHP a administrar manualmente complejidad frontend avanzada.
