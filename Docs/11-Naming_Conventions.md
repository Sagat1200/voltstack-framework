# VoltStack Naming Conventions

## Introducción

Este documento define las convenciones oficiales de nombres utilizadas en VoltStack.

Las convenciones tienen como objetivo:

- mantener consistencia
- mejorar legibilidad
- facilitar mantenimiento
- reducir ambigüedad
- mejorar DX (Developer Experience)
- facilitar escalabilidad empresarial

VoltStack adopta una filosofía de nombres inspirada parcialmente en:

- Laravel
- PSR Standards
- Domain Driven Design
- arquitecturas modulares modernas

pero adaptada a:

- runtimes reactivos
- SPA architecture
- persistent runtimes
- micro-modules internos

---

## Filosofía General

### 1. Consistency First

La consistencia es más importante que preferencias individuales.

---

### 2. Explicit Over Clever

Los nombres deben ser descriptivos y explícitos.

---

### 3. Domain-Oriented Naming

Los nombres deben representar intención y dominio.

---

### 4. Runtime Awareness

Los nombres deben reflejar el comportamiento reactivo cuando sea necesario.

---

### 5. Framework Coherence

Toda la arquitectura debe sentirse uniforme.

---

## Convenciones Generales

| Elemento        | Convención       |
| --------------- | ---------------- |
| Clases PHP      | PascalCase       |
| Interfaces      | PascalCase       |
| Traits          | PascalCase       |
| Métodos         | camelCase        |
| Variables       | camelCase        |
| Propiedades     | camelCase        |
| Constantes      | UPPER_SNAKE_CASE |
| Directorios PHP | PascalCase       |
| Archivos PHP    | PascalCase.php   |
| Archivos TS/JS  | kebab-case.ts    |
| Eventos         | dot.notation     |
| Configuración   | snake_case       |
| Routes Names    | dot.notation     |

---

## PHP Class Naming

Todas las clases deben usar:

```txt
PascalCase
```

---

## Correcto

```php
UserService
DashboardPage
ComponentRegistry
RuntimeManager
HydrationEngine
```

---

## Incorrecto

```php
userService
dashboard_page
runtime_manager
```

---

## Interfaces

Las interfaces deben terminar con:

```txt
Interface
```

---

## Ejemplos

```php
RuntimeDriverInterface
HydratorInterface
ProtocolSerializerInterface
CacheStoreInterface
```

---

## Traits

Los traits deben expresar comportamiento.

---

## Ejemplos

```php
InteractsWithState
HandlesHydration
DispatchesEffects
AuthorizesActions
```

---

## Abstract Classes

Las clases abstractas deben iniciar con:

```txt
Abstract
```

---

## Ejemplos

```php
AbstractComponent
AbstractDriver
AbstractHydrator
```

---

## Exceptions

Todas las excepciones deben terminar con:

```txt
Exception
```

---

## Ejemplos

```php
HydrationException
ProtocolException
RuntimeException
ValidationException
```

---

## Service Providers

Todos los providers deben terminar con:

```txt
ServiceProvider
```

---

## Ejemplos

```php
RuntimeServiceProvider
CacheServiceProvider
ProtocolServiceProvider
```

---

## Facades

Las fachadas deben tener nombres cortos y claros.

---

## Ejemplos

```php
Route
View
Cache
Config
Runtime
State
Event
```

---

## Controllers

Todos los controllers deben terminar con:

```txt
Controller
```

---

## Ejemplos

```php
UserController
WebhookController
AuthController
```

---

## Actions

Todas las acciones deben terminar con:

```txt
Action
```

---

## Ejemplos

```php
CreateUserAction
GenerateInvoiceAction
SyncTenantAction
```

---

## Middleware

Todos los middleware deben terminar con:

```txt
Middleware
```

---

## Ejemplos

```php
AuthMiddleware
HydrationMiddleware
ProtocolValidationMiddleware
```

---

## Events

Todos los eventos deben terminar con:

```txt
Event
```

---

## Ejemplos

```php
UserCreatedEvent
ComponentMountedEvent
NavigationStartedEvent
```

---

## Listeners

Todos los listeners deben terminar con:

```txt
Listener
```

---

## Ejemplos

```php
SendWelcomeEmailListener
RefreshDashboardListener
```

---

## Jobs

Todos los jobs deben terminar con:

```txt
Job
```

---

## Ejemplos

```php
GenerateReportJob
SyncStorageJob
```

---

## Commands

Todos los comandos deben terminar con:

```txt
Command
```

---

## Ejemplos

```php
ServeCommand
CacheClearCommand
RuntimeInspectCommand
```

---

## DTOs

Todos los DTOs deben terminar con:

```txt
Data
```

---

## Ejemplos

```php
UserData
InvoiceData
RuntimePayloadData
```

---

## Value Objects

Los Value Objects deben usar nombres de dominio.

---

## Ejemplos

```php
Email
Money
RuntimeId
ComponentId
```

---

## Enums

Todos los enums deben usar nombres descriptivos.

---

## Ejemplos

```php
RuntimeMode
ProtocolType
ComponentState
HydrationStrategy
```

---

## Component Naming

Los componentes deben usar nombres claros y semánticos.

---

## Ejemplos

```php
UserCard
DashboardStats
CreateInvoiceModal
SidebarMenu
```

---

## Page Components

Las páginas deben terminar con:

```txt
Page
```

---

## Ejemplos

```php
DashboardPage
UsersPage
SettingsPage
```

---

## Layout Components

Los layouts deben terminar con:

```txt
Layout
```

---

## Ejemplos

```php
AppLayout
AdminLayout
AuthLayout
```

---

## Fragment Components

Los fragmentos deben terminar con:

```txt
Fragment
```

---

## Ejemplos

```php
SidebarFragment
NavbarFragment
FooterFragment
```

---

## Runtime Classes

Las clases runtime deben reflejar su responsabilidad.

---

## Ejemplos

```php
RuntimeManager
RuntimeContext
RuntimeRegistry
RuntimeDispatcher
```

---

## Hydration Classes

Todas las clases relacionadas deben incluir:

```txt
Hydration
Hydrator
Dehydrator
Snapshot
```

---

## Ejemplos

```php
ComponentHydrator
SnapshotManager
DehydrationPipeline
HydrationContext
```

---

## Protocol Classes

Todas las clases protocol deben incluir:

```txt
Protocol
Payload
Serializer
Transport
```

---

## Ejemplos

```php
VoltProtocol
ProtocolSerializer
PayloadNormalizer
ProtocolTransport
```

---

## State Classes

Todas las clases state deben incluir:

```txt
State
Store
Signal
Watcher
```

---

## Ejemplos

```php
SharedState
StateStore
ReactiveSignal
StateWatcher
```

---

## Cache Classes

Todas las clases cache deben incluir:

```txt
Cache
Store
Repository
```

---

## Ejemplos

```php
RuntimeCache
RedisStore
MetadataRepository
```

---

## Driver Classes

Todos los drivers deben terminar con:

```txt
Driver
```

---

## Ejemplos

```php
FrankenPhpDriver
FpmDriver
RedisDriver
S3Driver
```

---

## Contracts Directory

Todas las interfaces deben vivir en:

```txt
Contracts/
```

---

## Exceptions Directory

Todas las excepciones deben vivir en:

```txt
Exceptions/
```

---

## Traits Directory

Todos los traits deben vivir en:

```txt
Traits/
```

---

## Providers Directory

Todos los providers deben vivir en:

```txt
Providers/
```

---

## Tests Directory

Todos los tests deben vivir en:

```txt
Tests/
```

---

## File Naming

Todos los archivos PHP:

```txt
PascalCase.php
```

---

## Correcto

```txt
RuntimeManager.php
HydrationEngine.php
UserController.php
```

---

## Incorrecto

```txt
runtime-manager.php
user_controller.php
```

---

## TypeScript / JavaScript Naming

Los archivos TS/JS deben usar:

```txt
kebab-case.ts
```

---

## Ejemplos

```txt
runtime-manager.ts
dom-patcher.ts
protocol-client.ts
navigation-engine.ts
```

---

## CSS Naming

Las clases CSS deben usar:

```txt
kebab-case
```

---

## Ejemplos

```css
.volt-button
.runtime-loading
.navigation-transition
```

---

## Route Naming

Las rutas deben usar:

```txt
dot.notation
```

---

## Ejemplos

```php
dashboard.index
users.show
settings.profile
```

---

## Event Naming

Los eventos runtime deben usar:

```txt
dot.notation
```

---

## Ejemplos

```txt
component.mounted
runtime.booted
navigation.started
hydration.failed
```

---

## Config Naming

Configuración:

```txt
snake_case
```

---

## Ejemplos

```php
runtime_driver
protocol_version
cache_store
```

---

## Environment Variables

Variables de entorno:

```txt
UPPER_SNAKE_CASE
```

---

## Ejemplos

```env
VOLT_RUNTIME=frankenphp
APP_ENV=local
CACHE_STORE=redis
```

---

## Database Naming

---

## Tables

```txt
snake_case plural
```

---

## Ejemplos

```txt
users
runtime_logs
protocol_snapshots
```

---

## Columns

```txt
snake_case
```

---

## Ejemplos

```txt
created_at
runtime_mode
component_id
```

---

## Boolean Naming

Los booleanos deben leerse naturalmente.

---

## Correcto

```php
$isActive
$shouldRender
$hasAccess
```

---

## Incorrecto

```php
$activeFlag
$renderValue
```

---

## Method Naming

Los métodos deben expresar intención.

---

## Correcto

```php
hydrateComponent()
generateEffects()
dispatchAction()
```

---

## Incorrecto

```php
doStuff()
run()
handle()
```

---

## Action Methods

Las acciones deben usar verbos claros.

---

## Ejemplos

```php
increment()
decrement()
createUser()
sendEmail()
```

---

## Query Methods

Los métodos booleanos deben comenzar con:

```txt
is
has
can
should
```

---

## Ejemplos

```php
isDirty()
hasAccess()
canRender()
shouldHydrate()
```

---

## Constant Naming

Las constantes deben usar:

```txt
UPPER_SNAKE_CASE
```

---

## Ejemplos

```php
DEFAULT_RUNTIME
MAX_PAYLOAD_SIZE
PROTOCOL_VERSION
```

---

## Namespace Structure

Namespaces deben reflejar estructura real.

---

## Ejemplo

```php
VoltStack\Quantum\Routing\RouteRegistry
```

---

## Frontend Runtime Naming

Todos los módulos frontend deben expresar claramente su responsabilidad.

---

## Ejemplos

```txt
effect-engine.ts
dom-patcher.ts
navigation-runtime.ts
```

---

## Runtime Safety Naming

Clases runtime-sensitive deben incluir:

```txt
Runtime
Scope
Context
Worker
```

---

## Ejemplos

```php
RequestScope
RuntimeContext
WorkerManager
```

---

## Future Naming Guidelines

VoltStack debe mantener coherencia futura en:

- distributed runtime
- streaming runtime
- edge runtime
- realtime systems
- concurrent rendering

---

## Naming Anti-Patterns

Evitar:

- nombres genéricos
- abreviaciones innecesarias
- acrónimos ambiguos
- nombres demasiado cortos
- nombres sin intención

---

## Incorrecto

```php
Mgr
Util
DataHelper
StuffHandler
```

---

## Correcto

```php
RuntimeManager
ProtocolSerializer
ComponentHydrator
```

---

## Objetivo Final

Las convenciones de nombres de VoltStack deben lograr:

- legibilidad inmediata
- arquitectura consistente
- mantenibilidad empresarial
- DX elegante
- crecimiento escalable
- claridad arquitectónica

---

## Conclusión

La consistencia de nombres es uno de los pilares fundamentales para mantener VoltStack como un framework moderno, mantenible y empresarial a largo plazo.

Todas las nuevas características y módulos deben seguir estrictamente estas convenciones para preservar coherencia en todo el ecosistema.
