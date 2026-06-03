# VoltStack Component System

## Introducción

El sistema de componentes de VoltStack es el núcleo de construcción de interfaces reactivas del framework.

Los componentes representan unidades encapsuladas de:

- UI
- estado
- comportamiento
- rendering
- interacción reactiva

El sistema está inspirado en:

- Livewire
- React
- Vue.js
- Phoenix LiveView

pero adaptado completamente a la filosofía PHP-first y SPA-native de VoltStack.

---

## Filosofía del Sistema de Componentes

### 1. PHP First

Los componentes deben poder desarrollarse principalmente en PHP.

---

### 2. Reactive Native

Todo componente es reactivo por defecto.

---

### 3. State Driven

La UI debe derivarse del estado del componente.

---

### 4. SPA Native

Los componentes viven dentro de una SPA persistente.

---

### 5. Runtime Optimized

El sistema debe funcionar eficientemente en:

- FrankenPHP
- PHP-FPM
- RoadRunner
- Swoole

---

## Objetivo Principal

Permitir construir interfaces modernas mediante:

```php
class Counter extends Component
{
    public int $count = 0;

    public function increment(): void
    {
        $this->count++;
    }

    public function render(): View
    {
        return view('counter');
    }
}
```

sin obligar al desarrollador a administrar:

- hydration manual
- DOM patching
- frontend state management
- SPA routing
- AJAX
- serialization
- client synchronization

---

## Arquitectura General

```txt
Component
    ↓
Reactive Runtime
    ↓
Volt Protocol
    ↓
Frontend Runtime
    ↓
DOM Patch
```

---

## Estructura Base de un Componente

```php
use VoltStack\Component\Component;

class Counter extends Component
{
    public int $count = 0;

    public function increment(): void
    {
        $this->count++;
    }

    public function render(): View
    {
        return view('counter');
    }
}
```

---

## Responsabilidades de un Componente

Un componente puede administrar:

- estado
- rendering
- acciones
- eventos
- navegación
- validación
- lifecycle hooks
- efectos reactivos

---

## Component Base Class

Clase conceptual base:

```txt
Component
├── state handling
├── hydration
├── rendering
├── events
├── validation
├── effects
├── lifecycle
└── navigation
```

---

## Estructura Interna Recomendada

```txt
Component
├── Contracts
├── Attributes
├── Concerns
├── Lifecycle
├── Rendering
├── Serialization
├── Validation
└── Effects
```

---

## Component Types

VoltStack soportará múltiples tipos de componentes.

---

## 1. Reactive Components

Componentes reactivos completos.

Ejemplo:

```php
class Counter extends Component
{
    public int $count = 0;
}
```

---

## 2. Page Components

Representan páginas SPA completas.

Ejemplo:

```php
Route::get('/dashboard', DashboardPage::class);
```

---

## 3. Layout Components

Layouts reutilizables.

Ejemplo:

```txt
AppLayout
├── Sidebar
├── Navbar
└── Content
```

---

## 4. Fragment Components

Componentes parciales optimizados para rendering incremental.

---

## 5. Client Components

Componentes manejados principalmente por el Frontend Runtime.

Ejemplo:

- dropdowns
- tooltips
- tabs
- modals

---

## Component Registration

Todos los componentes deben registrarse.

Ejemplo conceptual:

```php
ComponentRegistry::register(
    'counter',
    Counter::class
);
```

---

## Auto Discovery

VoltStack puede descubrir componentes automáticamente.

Ejemplo:

```txt
app/Components
app/Pages
app/Layouts
```

---

## Component Naming

### Convención recomendada

```txt
UserProfile
DashboardPage
AdminSidebar
CreateInvoiceModal
```

---

## Component ID

Cada instancia de componente posee un ID único.

Ejemplo:

```txt
cmp_84ad91
```

---

## Component State

El estado del componente se basa principalmente en propiedades públicas serializables.

---

## Estado Público

```php
public string $name = '';
public int $count = 0;
```

---

## Estado Protegido

Estado no serializable.

```php
protected array $cache = [];
```

---

## Estado Privado

Nunca expuesto al frontend.

```php
private string $secret;
```

---

## Protected Attributes

Ejemplo conceptual:

```php
#[Protected]
public string $token;
```

---

## Serializable State

El runtime debe soportar:

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
- database connections
- streams
- runtime handlers
- resources

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
dehydrate
↓
destroy
```

---

## Lifecycle Hooks

---

## mount()

Se ejecuta en el primer render.

```php
public function mount(): void
{
    //
}
```

---

## hydrate()

Reconstruye el componente.

```php
public function hydrate(): void
{
    //
}
```

---

## boot()

Inicialización general.

```php
public function boot(): void
{
    //
}
```

---

## updating()

Antes de modificar propiedades.

```php
public function updating(string $property): void
{
    //
}
```

---

## updated()

Después de modificar propiedades.

```php
public function updated(string $property): void
{
    //
}
```

---

## render()

Produce la vista.

```php
public function render(): View
{
    return view('counter');
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

## destroy()

Al destruir el componente.

```php
public function destroy(): void
{
    //
}
```

---

## Rendering System

El sistema de rendering debe soportar:

- templates PHP
- fragments
- layouts
- slots
- partial rendering
- incremental rendering

---

## Render Output

El render produce:

```txt
Fragment Tree
```

en lugar de simplemente HTML plano.

---

## Fragment Tree

Ejemplo conceptual:

```txt
DashboardPage
├── Sidebar
├── Navbar
└── Content
```

---

## Partial Rendering

Solo fragmentos modificados deben rerenderizarse.

---

## Dirty State Detection

El runtime detecta propiedades modificadas automáticamente.

Ejemplo:

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

## Actions

Las acciones representan métodos invocables desde el frontend.

---

## Ejemplo

```php
public function increment(): void
{
    $this->count++;
}
```

---

## Action Dispatch

```txt
Frontend Runtime
↓
Volt Protocol
↓
Reactive Runtime
↓
Component Action
```

---

## Action Parameters

```php
public function updateUser(int $id): void
{
    //
}
```

---

## Action Validation

Las acciones pueden validarse automáticamente.

---

## Events

Los componentes pueden emitir y escuchar eventos.

---

## Emit Event

```php
$this->emit('user.created');
```

---

## Listen Event

```php
protected array $listeners = [
    'user.created' => 'refresh'
];
```

---

## Navigation

Los componentes pueden iniciar navegación SPA.

---

## Ejemplo

```php
return navigate('/dashboard');
```

---

## Validation

Los componentes soportan validación integrada.

---

## Ejemplo

```php
$this->validate([
    'email' => ['required', 'email']
]);
```

---

## Reactive Validation

La validación puede ejecutarse:

- al enviar
- al escribir
- al modificar
- en tiempo real

---

## Component Effects

Los componentes pueden generar effects.

---

## Ejemplo conceptual

```php
$this->toast('Usuario creado.');
```

---

## Tipos de Effects

```txt
toast
modal
redirect
navigate
focus
scroll
event dispatch
```

---

## Client State

Algunas propiedades pueden manejarse únicamente en frontend.

---

## Ejemplo conceptual

```php
#[ClientState]
public bool $open = false;
```

---

## Smart Client Runtime

El Frontend Runtime puede resolver:

- dropdowns
- toggles
- tabs
- collapse
- modals

sin requests al servidor.

---

## Computed Properties

Propiedades derivadas.

---

## Ejemplo

```php
public function getFullNameProperty(): string
{
    return "{$this->name} {$this->last_name}";
}
```

---

## Watchers

Escuchar cambios de estado.

---

## Ejemplo conceptual

```php
public function watchCount($value): void
{
    //
}
```

---

## Async Actions

Objetivo futuro.

---

## Ejemplo conceptual

```php
#[Async]
public function generateReport(): void
{
    //
}
```

---

## Lazy Components

Componentes cargados bajo demanda.

---

## Deferred Rendering

Objetivo futuro:

```txt
stream rendering
lazy hydration
incremental hydration
```

---

## Nested Components

Los componentes pueden contener otros componentes.

---

## Ejemplo

```txt
DashboardPage
├── StatsWidget
├── SalesChart
└── ActivityFeed
```

---

## Component Communication

Métodos:

- events
- shared state
- parent-child communication
- reactive signals

---

## Shared State

Estado global compartido.

---

## Ejemplo

```php
State::share('theme', 'dark');
```

---

## Signals Integration

Objetivo futuro:

```php
$count = signal(0);
```

---

## Security Model

Los componentes deben proteger:

- propiedades privadas
- acciones sensibles
- estado interno
- snapshots
- hydration data

---

## Checksum Validation

Cada snapshot debe incluir checksum.

---

## Authorization

Las acciones pueden autorizarse.

---

## Ejemplo

```php
public function authorize(): bool
{
    return auth()->check();
}
```

---

## Runtime Awareness

Los componentes deben funcionar correctamente en:

- PHP-FPM
- FrankenPHP
- RoadRunner
- Swoole

---

## Persistent Runtime Safety

Nunca persistir accidentalmente:

- request
- auth
- session
- user data
- temporary payloads

---

## Performance Goals

Objetivos:

- payloads mínimos
- partial rendering
- fast hydration
- minimal DOM patching
- efficient serialization

---

## Frontend Runtime Responsibilities

El frontend debe:

- interpretar effects
- aplicar DOM patches
- manejar navegación SPA
- sincronizar snapshots
- manejar estado local

---

## Backend Responsibilities

El backend debe:

- controlar estado principal
- renderizar fragmentos
- validar acciones
- producir effects
- sincronizar snapshots

---

## Component File Structure

Ejemplo recomendado:

```txt
app
├── Components
├── Pages
├── Layouts
└── Fragments
```

---

## Future Goals

### Streaming Components

### Realtime Components

### Concurrent Rendering

### Offline State Sync

### Distributed Components

### Mobile Renderers

### Desktop Renderers

---

## MVP Goals

La primera versión debe soportar:

- mounting
- hydration
- rendering
- actions
- dirty state
- effects
- SPA navigation
- nested components
- validation

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

    public function decrement(): void
    {
        $this->count--;
    }

    public function render(): View
    {
        return view('counter');
    }
}
```

---

## Conclusión

El sistema de componentes de VoltStack representa la base principal para construir interfaces SPA modernas utilizando PHP como lenguaje principal.

Debe combinar:

- simplicidad
- reactividad
- rendimiento
- modularidad
- SPA nativa
- compatibilidad con runtimes persistentes

sin perder la experiencia elegante y productiva esperada por desarrolladores PHP.
