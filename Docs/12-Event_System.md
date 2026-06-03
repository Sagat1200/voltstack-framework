# VoltStack Event System

## Introducción

El Event System de VoltStack es la infraestructura encargada de la comunicación desacoplada entre:

- componentes reactivos
- runtime frontend
- runtime backend
- módulos Quantum
- servicios internos
- eventos de dominio
- navegación SPA
- lifecycle hooks

El sistema de eventos es uno de los pilares fundamentales de la arquitectura reactiva de VoltStack.

Permite construir aplicaciones altamente desacopladas, reactivas y extensibles sin crear dependencias rígidas entre componentes o módulos.

---

## Filosofía del Sistema de Eventos

### 1. Event-Driven Architecture

La comunicación debe realizarse preferentemente mediante eventos.

---

### 2. Loose Coupling

Los módulos no deben conocerse directamente cuando no sea necesario.

---

### 3. Reactive Native

Los eventos forman parte del runtime reactivo desde el núcleo.

---

### 4. Runtime Aware

El sistema debe funcionar correctamente en runtimes persistentes.

---

### 5. Frontend + Backend Unified Events

Frontend y backend deben compartir una filosofía común de eventos.

---

## Objetivos del Sistema

El sistema debe permitir:

- comunicación entre componentes
- lifecycle notifications
- runtime orchestration
- domain events
- frontend events
- SPA navigation events
- reactive synchronization
- broadcasting futuro
- realtime future support

---

## Arquitectura General

```txt
Frontend Runtime
        ↕
Volt Protocol
        ↕
Reactive Runtime
        ↕
Event Dispatcher
        ↕
Listeners / Components / Services
```

---

## Tipos de Eventos

VoltStack define múltiples categorías de eventos.

---

## 1. Runtime Events

Eventos internos del runtime.

---

## Ejemplos

```txt
runtime.booting
runtime.booted
runtime.request.started
runtime.request.finished
runtime.scope.reset
```

---

## 2. Component Events

Eventos relacionados con componentes reactivos.

---

## Ejemplos

```txt
component.mounted
component.hydrated
component.updated
component.rendered
component.destroyed
```

---

## 3. Lifecycle Events

Eventos del ciclo de vida reactivo.

---

## Ejemplos

```txt
hydration.started
hydration.finished
dehydration.started
dehydration.finished
```

---

## 4. Navigation Events

Eventos SPA.

---

## Ejemplos

```txt
navigation.started
navigation.finished
navigation.failed
navigation.cancelled
```

---

## 5. Protocol Events

Eventos relacionados con Volt Protocol.

---

## Ejemplos

```txt
protocol.request.received
protocol.response.generated
protocol.payload.invalid
```

---

## 6. Domain Events

Eventos de negocio.

---

## Ejemplos

```txt
user.created
invoice.paid
tenant.activated
subscription.cancelled
```

---

## 7. Frontend Runtime Events

Eventos del navegador/runtime frontend.

---

## Ejemplos

```txt
dom.updated
effect.executed
component.synced
snapshot.restored
```

---

## Event Dispatcher

El Event Dispatcher es el núcleo del sistema.

---

## Responsabilidades

- dispatch events
- register listeners
- execute listeners
- async dispatch futuro
- runtime-safe dispatching

---

## Arquitectura del Dispatcher

```txt
EventDispatcher
├── ListenerRegistry
├── EventPipeline
├── EventQueue
├── RuntimeContext
└── EventLogger
```

---

## Event Class Structure

Todos los eventos deben ser objetos explícitos.

---

## Ejemplo

```php
class UserCreatedEvent
{
    public function __construct(
        public readonly User $user
    ) {}
}
```

---

## Event Naming Convention

Eventos internos usan:

```txt
dot.notation
```

---

## Ejemplos

```txt
component.mounted
navigation.started
runtime.booted
```

---

## Domain Events

Los eventos de dominio usan clases explícitas.

---

## Ejemplo

```php
UserRegisteredEvent
InvoiceGeneratedEvent
```

---

## Event Dispatching

---

## Ejemplo Básico

```php
event(new UserCreatedEvent($user));
```

---

## Runtime Event Dispatch

```php
Runtime::dispatch('component.updated');
```

---

## Component Event Dispatch

Los componentes pueden emitir eventos reactivos.

---

## Ejemplo

```php
$this->emit('user.created');
```

---

## Event Listeners

Los listeners reaccionan a eventos.

---

## Ejemplo

```php
class SendWelcomeEmailListener
{
    public function handle(UserCreatedEvent $event): void
    {
        //
    }
}
```

---

## Listener Registration

---

## Ejemplo conceptual

```php
Event::listen(
    UserCreatedEvent::class,
    SendWelcomeEmailListener::class
);
```

---

## Component Listeners

Los componentes pueden escuchar eventos.

---

## Ejemplo

```php
protected array $listeners = [
    'user.created' => 'refreshUsers'
];
```

---

## Event Propagation

El sistema puede soportar propagación.

---

## Flujo conceptual

```txt
child component
↓
parent component
↓
runtime
↓
global listeners
```

---

## Scoped Events

Los eventos pueden limitarse a scopes específicos.

---

## Ejemplos

- component scope
- page scope
- runtime scope
- global scope

---

## Event Scopes

```txt
local
page
global
runtime
```

---

## Local Events

Solo visibles dentro del componente.

---

## Global Events

Disponibles en toda la aplicación.

---

## Runtime Events

Internos del runtime.

---

## Frontend Event System

El frontend runtime también soporta eventos.

---

## Ejemplo conceptual

```js
runtime.emit('navigation.started');
```

---

## Frontend Event Listeners

```js
runtime.on('dom.updated', callback);
```

---

## Event Bridge

VoltStack debe incluir un puente frontend/backend.

---

## Arquitectura

```txt
Frontend Runtime
↕
Volt Protocol
↕
Backend Event System
```

---

## Cross Runtime Events

Objetivo:

Permitir que eventos backend generen efectos frontend.

---

## Ejemplo

```php
$this->dispatchBrowserEvent('toast', [
    'message' => 'Usuario creado.'
]);
```

---

## Frontend Effect Result

```txt
show toast
```

---

## Browser Events

Eventos ejecutados directamente en navegador.

---

## Ejemplo

```php
$this->browserEvent('modal.open');
```

---

## Event Payloads

Los eventos pueden transportar datos serializables.

---

## Ejemplo

```php
$this->emit('user.selected', [
    'id' => 1
]);
```

---

## Event Serialization

Payloads deben soportar:

- primitives
- arrays
- DTOs
- serializable objects

---

## Unsupported Payloads

Nunca serializar:

- closures
- resources
- connections
- runtime handlers

---

## Event Queue

Objetivo futuro.

---

## Características

- queued listeners
- delayed events
- retry system
- async execution

---

## Async Event Goals

```txt
background listeners
parallel listeners
distributed events
```

---

## Runtime Safety

Los eventos deben ser seguros para runtimes persistentes.

---

## Nunca persistir accidentalmente

```txt
request payloads
auth context
temporary state
```

---

## Scoped Listener Cleanup

Después de cada request:

```txt
remove temporary listeners
reset scoped listeners
clear request listeners
```

---

## Event Middleware

El sistema puede soportar middleware.

---

## Ejemplos

```txt
LoggingMiddleware
AuthorizationMiddleware
ValidationMiddleware
```

---

## Event Pipeline

```txt
dispatch
↓
middleware
↓
listeners
↓
effects
↓
complete
```

---

## Event Priorities

Objetivo futuro.

---

## Ejemplo conceptual

```php
Event::priority(100);
```

---

## Stop Propagation

Los eventos pueden detener propagación.

---

## Ejemplo conceptual

```php
$event->stopPropagation();
```

---

## Event Subscribers

Clases agrupadas para múltiples eventos.

---

## Ejemplo

```php
class UserSubscriber
{
    public function subscribe(EventDispatcher $events): void
    {
        //
    }
}
```

---

## Lifecycle Integration

El runtime debe emitir eventos automáticos.

---

## Ejemplos

```txt
component.mounting
component.mounted
hydration.started
hydration.finished
```

---

## Navigation Integration

Eventos SPA automáticos.

---

## Ejemplos

```txt
navigation.started
navigation.completed
navigation.failed
```

---

## Effect Events

Eventos del sistema de effects.

---

## Ejemplos

```txt
effect.generated
effect.executed
effect.failed
```

---

## Protocol Integration

Volt Protocol puede transportar eventos.

---

## Ejemplo

```json
{
  "event": {
    "name": "user.created",
    "payload": {
      "id": 1
    }
  }
}
```

---

## Event Logging

VoltStack debe poder registrar:

- dispatched events
- failed listeners
- execution time
- payload size

---

## Debug Mode

Modo debug debe mostrar:

- event timeline
- listener execution
- propagation chain
- runtime events
- frontend events

---

## Performance Goals

Objetivos:

- dispatch rápido
- listeners eficientes
- payloads mínimos
- runtime-safe listeners

---

## Security Goals

Objetivos:

- validar payloads
- prevenir event injection
- proteger eventos internos
- evitar serialization insegura

---

## Realtime Goals

Objetivos futuros:

- websocket broadcasting
- realtime sync
- multi-user events
- distributed runtime events

---

## Broadcast System

Objetivo futuro.

---

## Ejemplo conceptual

```php
broadcast(new UserCreatedEvent($user));
```

---

## Frontend Runtime Integration

El frontend runtime debe:

- escuchar effects
- emitir eventos
- sincronizar listeners
- manejar browser events

---

## Backend Responsibilities

El backend debe:

- dispatch seguro
- serialization correcta
- listener lifecycle
- runtime cleanup

---

## MVP Goals

La primera versión debe soportar:

- event dispatcher
- listeners
- component events
- runtime events
- browser events
- Volt Protocol event transport
- scoped listeners

---

## Ejemplo Completo

### Backend

```php
class Counter extends Component
{
    public int $count = 0;

    public function increment(): void
    {
        $this->count++;

        $this->emit('counter.updated', [
            'count' => $this->count
        ]);
    }
}
```

---

## Frontend Runtime

```txt
receive event
↓
update runtime
↓
trigger listeners
```

---

## Resultado

```txt
reactive synchronization
without page reload
```

---

## Future Goals

### Distributed Event Bus

### Edge Runtime Events

### Streaming Events

### Async Runtime Events

### Event Replay System

### Persistent Event Store

---

## Conclusión

El Event System de VoltStack debe convertirse en el sistema nervioso central de la arquitectura reactiva del framework.

Debe permitir comunicación desacoplada, reactiva y extensible entre:

- backend
- frontend
- runtime
- componentes
- módulos Quantum

manteniendo alto rendimiento y compatibilidad con runtimes persistentes modernos como FrankenPHP.
