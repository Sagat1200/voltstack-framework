# VoltStack Runtime

## Introducción

El Runtime de VoltStack es el núcleo reactivo encargado de ejecutar aplicaciones SPA impulsadas por PHP.

A diferencia de frameworks tradicionales basados únicamente en request/response, el runtime de VoltStack está diseñado para operar como un sistema reactivo persistente capaz de sincronizar frontend y backend mediante un protocolo optimizado llamado Volt Protocol.

El Runtime es responsable de:

- ejecutar componentes reactivos
- sincronizar estado
- manejar hydration/dehydration
- procesar acciones
- generar efectos
- coordinar navegación SPA
- administrar lifecycle hooks
- generar actualizaciones parciales del DOM
- optimizar ejecución en runtimes persistentes

---

## Objetivo del Runtime

El objetivo principal del runtime es permitir que el desarrollador construya aplicaciones SPA modernas utilizando PHP como lenguaje principal sin escribir manualmente grandes cantidades de JavaScript.

VoltStack Runtime debe ofrecer:

```txt
PHP Developer Experience
+
Reactive UI Runtime
+
SPA Navigation
+
Persistent Execution
```

---

## Filosofía del Runtime

### 1. Reactive by Default

Toda aplicación VoltStack es reactiva desde el inicio.

---

### 2. SPA Native

La navegación SPA forma parte del runtime principal.

---

### 3. Server-Driven UI

El backend controla el estado principal de la interfaz.

---

### 4. Smart Client Runtime

El frontend runtime puede resolver pequeñas interacciones localmente para minimizar requests innecesarios.

---

### 5. Persistent Runtime Aware

El runtime está diseñado para funcionar especialmente bien con FrankenPHP y runtimes persistentes.

---

## Arquitectura General del Runtime

```txt
Frontend Runtime
        ↕
Volt Protocol
        ↕
Reactive Runtime
        ├── Component Engine
        ├── State Engine
        ├── Hydration Engine
        ├── Diff Engine
        ├── Effect Engine
        ├── Lifecycle Engine
        ├── Navigation Engine
        └── Action Dispatcher
```

---

## Capas del Runtime

### Frontend Runtime

Responsabilidades:

- navegación SPA
- captura de eventos
- envío de acciones
- actualización parcial del DOM
- preservación de estado
- aplicación de efectos
- manejo de transitions
- hydration inicial

---

### Reactive Runtime

Responsabilidades:

- ejecución de componentes PHP
- resolución de acciones
- mutación de estado
- rendering reactivo
- generación de efectos
- hydration/dehydrate

---

## Component Engine

El Component Engine administra todos los componentes reactivos del sistema.

Responsabilidades:

- registro de componentes
- resolución de componentes
- mounting
- unmounting
- lifecycle hooks
- renderizado
- tracking de estado

---

## Component Registry

El registry mantiene un mapa global de componentes.

Ejemplo conceptual:

```php
ComponentRegistry::register(
    'counter',
    CounterComponent::class
);
```

En modo FrankenPHP el registry puede permanecer en memoria.

---

## Component Lifecycle

Lifecycle completo:

```txt
instantiate
↓
mount
↓
hydrate
↓
boot
↓
render
↓
interact
↓
update
↓
diff
↓
effects
↓
dehydrate
↓
destroy
```

---

## Hooks del Lifecycle

Ejemplos conceptuales:

```php
mount()
hydrate()
boot()
updating()
updated()
render()
dehydrate()
destroy()
```

---

## State Engine

El State Engine administra el estado reactivo del framework.

Responsabilidades:

- estado local
- estado compartido
- estado serializable
- dirty tracking
- validación de mutaciones
- persistencia temporal
- sincronización frontend/backend

---

## Tipos de Estado

### 1. Local State

Estado interno del componente.

```php
public int $count = 0;
```

---

### 2. Shared State

Estado compartido entre múltiples componentes.

```php
State::share('theme', 'dark');
```

---

### 3. Session State

Estado persistente por sesión.

---

### 4. Runtime State

Estado temporal mantenido por el runtime.

---

## Dirty State Detection

El runtime debe detectar automáticamente propiedades modificadas.

Ejemplo:

```txt
before:
count = 1

after:
count = 2
```

Resultado:

```txt
dirty: count
```

Esto permite enviar payloads mínimos.

---

## Hydration Engine

El Hydration Engine reconstruye el componente entre requests reactivos.

Responsabilidades:

- restaurar estado
- restaurar metadata
- restaurar contexto
- validar integridad
- restaurar propiedades serializables

---

## Dehydration Engine

Convierte el estado del componente en un payload serializable.

Responsabilidades:

- serialización
- filtrado seguro
- reducción de payload
- protección de propiedades
- metadata snapshot

---

## Snapshot System

Cada componente reactivo mantiene un snapshot.

Ejemplo conceptual:

```json
{
  "component": "counter",
  "state": {
    "count": 5
  },
  "checksum": "runtime_hash"
}
```

---

## Diff Engine

El Diff Engine identifica cambios entre renders.

Responsabilidades:

- comparar fragmentos
- detectar cambios mínimos
- generar patches
- optimizar actualizaciones

---

## Estrategias de Diff

### 1. State Diff

Comparación de estado.

---

### 2. Fragment Diff

Comparación de fragmentos renderizados.

---

### 3. Effect Diff

Comparación de efectos reactivos.

---

## Effect Engine

El Effect Engine genera instrucciones para el frontend runtime.

Ejemplos de efectos:

```txt
text.update
dom.replace
dom.append
redirect
dispatch.event
show.modal
hide.modal
navigate
toast
focus
scroll
```

---

## Ejemplo conceptual

```json
{
  "effects": [
    {
      "type": "text.update",
      "target": "counter",
      "value": 10
    }
  ]
}
```

---

## Action Dispatcher

El dispatcher ejecuta acciones enviadas desde el frontend.

Ejemplo:

```php
public function increment()
{
    $this->count++;
}
```

Request:

```json
{
  "action": "increment"
}
```

---

## Action Pipeline

```txt
Request
↓
Hydrate
↓
Authorize
↓
Validate
↓
Execute Action
↓
Mutate State
↓
Render
↓
Generate Effects
↓
Dehydrate
↓
Response
```

---

## Navigation Engine

VoltStack implementa navegación SPA desde el runtime.

Responsabilidades:

- interceptar navegación
- preservar estado
- cargar páginas parcialmente
- manejar history API
- transitions
- prefetch
- replace navigation

---

## Navegación SPA

Ejemplo conceptual:

```php
return navigate('/dashboard');
```

O:

```php
Runtime::navigate('/dashboard');
```

---

## Partial Rendering

El runtime debe renderizar únicamente regiones necesarias.

Ejemplo:

```txt
Layout
├── Sidebar
├── Navbar
└── Content Area
```

Solo:

```txt
Content Area
```

debe actualizarse cuando sea posible.

---

## Frontend Runtime

El Frontend Runtime es un runtime JS mínimo.

No busca competir con React directamente.

Su objetivo es:

- ejecutar Volt Protocol
- aplicar efectos
- actualizar DOM
- mantener navegación SPA
- manejar estado local ligero

---

## Objetivos del Frontend Runtime

### 1. Tamaño pequeño

Runtime ligero.

---

### 2. Dependencia mínima

Sin requerir React/Vue.

---

### 3. Alta velocidad

DOM patching optimizado.

---

### 4. Invisible para el desarrollador

El desarrollador PHP no debería preocuparse por JS interno.

---

## Smart Local State

Algunas interacciones pueden resolverse sin request al servidor.

Ejemplos:

- dropdowns
- toggles
- tabs
- modals
- collapse
- visibility
- hover state

---

## Ejemplo conceptual

```php
#[ClientState]
public bool $open = false;
```

Esto puede resolverse localmente.

---

## Runtime Modes

VoltStack soporta múltiples modos.

---

## 1. Classic Mode

```txt
PHP-FPM
Request lifecycle tradicional
```

Características:

- bootstrap por request
- máxima compatibilidad
- menor rendimiento

---

## 2. Persistent Mode

```txt
FrankenPHP
```

Características:

- workers persistentes
- container persistente
- component registry persistente
- reflection cache
- metadata cache
- menor tiempo de respuesta

---

## Runtime Driver Interface

Todos los drivers deben implementar contratos comunes.

Ejemplo conceptual:

```php
interface RuntimeDriver
{
    public function boot(): void;

    public function resetRequestScope(): void;

    public function terminate(): void;
}
```

---

## FrankenPHP Runtime

FrankenPHP es el runtime recomendado.

Ventajas:

- workers persistentes
- menor bootstrap
- memoria viva
- preload
- mejor rendimiento reactivo

---

## Elementos Persistentes

Elementos que pueden mantenerse vivos:

```txt
container base
service providers
route registry
component registry
compiled metadata
reflection cache
protocol serializers
```

---

## Elementos que deben resetearse

```txt
request
response
auth
session
csrf
validation
temporary state
user context
```

---

## Runtime Safety

La seguridad es crítica en runtimes persistentes.

Nunca debe filtrarse:

- estado entre usuarios
- sesiones
- payloads
- errores privados
- datos autenticados

---

## Runtime Context

Cada request debe ejecutarse dentro de un Runtime Context aislado.

Ejemplo conceptual:

```php
RuntimeContext::current();
```

Contiene:

- request
- auth
- session
- locale
- tenant
- runtime metadata

---

## Memory Management

El runtime debe monitorear:

- memory leaks
- instancias persistentes
- objetos huérfanos
- cache inválido
- acumulación de referencias

---

## Runtime Reset Strategy

Después de cada request reactivo:

```txt
flush request scope
clear temporary bindings
reset scoped services
reset auth
reset validation state
```

---

## Protocol Integration

El runtime nunca debe comunicarse directamente con el frontend.

Toda comunicación pasa por Volt Protocol.

---

## Runtime Performance Goals

Objetivos iniciales:

```txt
Cold boot:
< 100ms

Reactive request:
< 30ms

DOM patch:
mínimo posible

Payload:
optimizado
```

---

## Runtime Extensibility

El runtime debe ser extensible mediante:

- plugins
- drivers
- hooks
- runtime middleware
- lifecycle extensions
- protocol interceptors

---

## Runtime Middleware

VoltStack puede tener middleware específicos del runtime reactivo.

Ejemplo:

```txt
HydrationMiddleware
StateValidationMiddleware
ReactiveAuthMiddleware
ProtocolVersionMiddleware
```

---

## Render Pipeline

```txt
Component
↓
Render
↓
Fragment Tree
↓
Diff Engine
↓
Effect Builder
↓
Volt Protocol Payload
↓
Frontend Runtime
↓
DOM Patch
```

---

## Runtime Events

Eventos internos del runtime:

```txt
runtime.booted
component.mounted
component.hydrated
component.rendered
component.dehydrated
navigation.started
navigation.finished
effect.generated
```

---

## Runtime Logging

El runtime debe poder registrar:

- lifecycle
- hydration
- payloads
- effects
- navigation
- performance
- protocol activity

---

## Runtime Debugging

Modo debug reactivo:

```txt
- component tree
- state snapshots
- hydration logs
- protocol inspector
- effect inspector
- performance timeline
```

---

## Runtime Future Goals

Objetivos futuros:

- streaming UI
- realtime runtime
- websocket transport
- concurrent rendering
- fibers integration
- edge runtime
- distributed runtime
- AI-assisted rendering
- offline state sync

---

## MVP Runtime Goals

El primer MVP debe soportar:

- mounting de componentes
- hydration
- acciones reactivas
- estado básico
- Volt Protocol mínimo
- navegación SPA básica
- DOM patching simple
- integración con FrankenPHP

---

## Conclusión

VoltStack Runtime es el núcleo tecnológico del framework.

No debe comportarse como un simple sistema request/response tradicional, sino como un runtime reactivo persistente diseñado específicamente para aplicaciones SPA modernas impulsadas por PHP.
