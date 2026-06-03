# VoltStack Architecture

## Propósito

Este documento define la arquitectura técnica base de VoltStack, un framework PHP fullstack, SPA y reactivo, inspirado en la productividad de Laravel, la experiencia server-driven de Livewire y el rendimiento de runtimes persistentes como FrankenPHP.

VoltStack no debe entenderse como un framework MVC tradicional con una capa reactiva agregada posteriormente. Su arquitectura nace desde el inicio como un runtime de aplicaciones reactivas impulsado por PHP.

---

## Visión Arquitectónica

VoltStack está diseñado bajo una arquitectura por capas:

```txt
Frontend Runtime
        ↕
Volt Protocol
        ↕
Reactive Runtime
        ↕
Application Core
        ↕
Quantum Modules
        ↕
Runtime Driver
```

Cada capa tiene una responsabilidad clara y desacoplada.

---

## Capas Principales

### 1. Frontend Runtime

El Frontend Runtime es el runtime JavaScript interno de VoltStack.

Su responsabilidad es ejecutar la experiencia SPA en el navegador sin que el desarrollador tenga que escribir JavaScript manualmente.

Responsabilidades principales:

- navegación SPA
- escucha de eventos DOM
- envío de acciones al backend
- recepción de respuestas reactivas
- aplicación de efectos
- actualización parcial del DOM
- preservación de estado
- manejo de transiciones
- sincronización con el backend

---

### 2. Volt Protocol

Volt Protocol es el contrato de comunicación entre el frontend y el backend.

No debe enviar páginas completas cuando no sea necesario. Su objetivo es transportar instrucciones reactivas optimizadas.

Ejemplo conceptual:

```json
{
  "component": "counter",
  "state": {
    "count": 2
  },
  "effects": [
    {
      "type": "text.update",
      "target": "counter-value",
      "value": 2
    }
  ]
}
```

Responsabilidades:

- transportar estado
- transportar acciones
- transportar eventos
- transportar efectos
- transportar errores
- transportar navegación
- transportar instrucciones de renderizado parcial

---

### 3. Reactive Runtime

El Reactive Runtime es el núcleo diferencial de VoltStack.

Es responsable de ejecutar componentes, sincronizar estado, hidratar información, procesar acciones y generar respuestas reactivas.

Responsabilidades:

- montar componentes
- hidratar estado
- ejecutar acciones
- validar mutaciones
- renderizar componentes
- generar efectos
- deshidratar estado
- producir respuestas compatibles con Volt Protocol

---

### 4. Application Core

Application Core contiene la infraestructura principal del framework.

Incluye:

- Application
- Kernel
- Container
- Service Providers
- Configuration
- Events
- Routing
- HTTP layer
- Exception handling

Esta capa se inspira en la experiencia de Laravel, pero se adapta a un entorno reactivo y persistente.

---

### 5. Quantum Modules

Quantum es el sistema modular interno del framework.

Cada módulo Quantum representa un micro-paquete del framework.

Ejemplos:

```txt
Quantum
├── Bootstrap
├── Cache
├── Config
├── Container
├── Http
├── HttpKernel
├── Routing
├── Middlewares
├── Actions
├── Controllers
├── Events
├── Reactive
├── State
├── Signals
├── Protocol
├── View
└── Concurrency
```

Cada módulo debe poder evolucionar con bajo acoplamiento.

---

### 6. Runtime Driver

Runtime Driver permite que VoltStack pueda ejecutarse sobre diferentes entornos PHP.

Drivers iniciales:

```txt
Runtime Drivers
├── FrankenPHP
├── PHP-FPM
├── RoadRunner
└── Swoole
```

FrankenPHP será el runtime recomendado para máximo rendimiento.

---

## Flujo General de Request

### Flujo inicial de una página

```txt
Browser
↓
HTTP Request
↓
Runtime Driver
↓
HttpKernel
↓
Router
↓
Controller / Component Entry
↓
Reactive Runtime
↓
Render inicial
↓
Volt Protocol / HTML inicial
↓
Frontend Runtime
↓
Hydration SPA
```

---

### Flujo reactivo posterior

```txt
Usuario interactúa
↓
Frontend Runtime captura evento
↓
Volt Protocol envía acción
↓
Reactive Runtime hidrata componente
↓
Se ejecuta método PHP
↓
Se actualiza estado
↓
Se genera diff/effects
↓
Frontend Runtime aplica cambios
↓
DOM actualizado sin recarga
```

---

## Estructura Base del src

```txt
src
├── Platform
├── Facades
├── Helper
├── Support
├── Testing
└── Quantum
```

---

## Platform

`Platform` contiene las clases principales del framework.

Responsabilidades:

- inicialización de la aplicación
- coordinación del runtime
- gestión de drivers
- registro de módulos
- ciclo de vida principal
- integración entre capas

Clases conceptuales:

```txt
Platform
├── Application.php
├── Kernel.php
├── RuntimeManager.php
├── RuntimeDriverManager.php
├── ServiceProvider.php
├── ModuleRegistry.php
└── Environment.php
```

---

## Facades

`Facades` proporciona una API estática elegante para acceder a servicios internos del container.

Ejemplos:

```txt
Facades
├── App.php
├── Route.php
├── Config.php
├── Cache.php
├── Event.php
├── Runtime.php
├── State.php
└── View.php
```

Objetivo:

```php
Route::get('/dashboard', Dashboard::class);
Cache::put('key', 'value');
State::set('counter', 1);
```

---

## Helper

`Helper` contiene funciones globales del framework.

Ejemplos:

```php
app();
config();
route();
runtime();
state();
base_path();
public_path();
storage_path();
```

Los helpers deben ser opcionales y no reemplazar la arquitectura principal.

---

## Support

`Support` contiene utilidades reutilizables.

Ejemplos:

```txt
Support
├── Arr.php
├── Str.php
├── Collection.php
├── AttributeBag.php
├── MetadataBag.php
├── ReflectionHelper.php
├── RuntimePayload.php
└── SerializableState.php
```

---

## Testing

`Testing` contiene herramientas para probar aplicaciones VoltStack.

Responsabilidades:

- pruebas HTTP
- pruebas de componentes
- pruebas de estado reactivo
- pruebas de navegación SPA
- pruebas del protocolo
- assertions del runtime

Ejemplos:

```txt
Testing
├── TestCase.php
├── ComponentTestCase.php
├── MakesHttpRequests.php
├── InteractsWithRuntime.php
├── AssertsVoltProtocol.php
└── AssertsReactiveState.php
```

---

## Quantum

`Quantum` contiene los micro-paquetes internos del framework.

La idea es que el framework no sea un bloque monolítico, sino una composición de piezas pequeñas.

---

## Quantum/Bootstrap

Responsable del proceso de arranque.

Incluye:

- carga de configuración
- carga de providers
- inicialización del container
- inicialización del runtime
- preparación del kernel

---

## Quantum/Container

Contenedor de dependencias del framework.

Debe soportar:

- bindings
- singletons
- scoped instances
- contextual binding
- auto-wiring
- service providers
- reset de instancias en runtimes persistentes

---

## Quantum/Config

Sistema de configuración.

Debe soportar:

- archivos PHP de configuración
- variables de entorno
- cache de configuración
- configuración mutable controlada
- configuración segura para runtime persistente

---

## Quantum/Http

Capa HTTP base.

Incluye:

- Request
- Response
- RedirectResponse
- JsonResponse
- UploadedFile
- Headers
- Cookies

---

## Quantum/HttpKernel

Kernel HTTP principal.

Responsabilidades:

- recibir request
- ejecutar middleware stack
- resolver rutas
- despachar controllers/componentes
- manejar errores
- devolver response

---

## Quantum/Routing

Sistema de rutas.

Debe soportar:

- rutas HTTP
- grupos
- middlewares
- nombres de rutas
- parámetros
- constraints
- rutas hacia componentes reactivos
- rutas SPA

Ejemplo:

```php
Route::get('/dashboard', DashboardPage::class)
    ->name('dashboard');
```

---

## Quantum/Middlewares

Sistema de middlewares.

Responsabilidades:

- autenticación
- autorización
- CSRF
- sesiones
- throttling
- headers
- seguridad
- preparación del runtime reactivo

---

## Quantum/Actions

Clases de acción reutilizables.

Sirven para encapsular lógica de aplicación fuera de controllers o componentes.

Ejemplo:

```php
CreateUserAction::run($data);
```

---

## Quantum/Controllers

Capa de controllers tradicional.

Debe coexistir con componentes reactivos.

Uso recomendado:

- APIs
- endpoints clásicos
- acciones HTTP no reactivas
- integraciones externas

---

## Quantum/Reactive

Módulo principal de reactividad.

Responsabilidades:

- componentes reactivos
- lifecycle
- hydration
- dehydrate
- action dispatch
- dirty state detection
- diff generation
- effect generation

---

## Quantum/State

Sistema de estado.

Debe manejar:

- estado local de componente
- estado compartido
- estado persistente
- estado de sesión
- estado serializable
- validación de mutaciones

---

## Quantum/Signals

Sistema de señales.

Inspirado en modelos reactivos modernos.

Responsabilidades:

- valores observables
- computed values
- watchers
- effects
- dependencias reactivas

---

## Quantum/Protocol

Implementa Volt Protocol.

Responsabilidades:

- encode payload
- decode payload
- validar payloads
- generar responses reactivas
- normalizar errores
- transportar efectos
- versionar protocolo

---

## Quantum/View

Sistema de vistas y rendering.

Debe soportar:

- templates PHP
- componentes
- layouts
- slots
- fragments
- partial rendering
- SSR inicial
- render reactivo posterior

---

## Quantum/Cache

Sistema de cache.

Debe soportar:

- array cache
- file cache
- redis
- memory cache
- runtime cache
- cache compatible con FrankenPHP

---

## Quantum/Concurrency

Módulo para concurrencia y ejecución paralela cuando el runtime lo permita.

Casos de uso:

- tareas paralelas
- procesos async
- prefetch
- background jobs controlados
- integración futura con fibers

---

## Modelo de Componentes

Los componentes son unidades reactivas controladas por PHP.

Un componente puede tener:

- propiedades públicas
- estado interno
- acciones
- lifecycle hooks
- eventos
- validaciones
- render method

Ejemplo conceptual:

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

---

## Lifecycle Reactivo

```txt
mount
↓
hydrate
↓
boot
↓
action
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

## Navegación SPA

VoltStack debe ofrecer navegación SPA desde el núcleo.

Características:

- navegación sin recarga
- preserve scroll
- preserve state
- replace navigation
- prefetch
- transición entre páginas
- rutas reactivas
- fallback HTTP tradicional

---

## FrankenPHP Mode

En modo FrankenPHP, VoltStack debe aprovechar workers persistentes.

Elementos persistentes:

- container base
- route registry
- component registry
- metadata cache
- compiled views
- reflection cache
- protocol serializers
- runtime configuration

Elementos que deben resetearse por request:

- request
- response
- usuario autenticado
- sesión activa
- datos temporales
- errores de validación
- estado mutable no persistente

---

## PHP-FPM Mode

En modo PHP-FPM, VoltStack debe funcionar de forma tradicional.

Características:

- bootstrap por request
- compatibilidad amplia
- menor rendimiento
- sin memoria persistente
- ideal para hosting convencional

---

## Principio de Seguridad en Runtime Persistente

Todo dato sensible asociado a un request debe limpiarse al finalizar la ejecución.

Nunca deben persistir entre requests:

- usuario autenticado
- tokens
- payloads
- inputs
- headers
- cookies
- errores
- datos privados de componente
- datos de sesión no controlados

---

## Render Pipeline

```txt
Component
↓
View Renderer
↓
Fragment Tree
↓
Diff Engine
↓
Effect Builder
↓
Volt Protocol Response
↓
Frontend Runtime
↓
DOM Patch
```

---

## Error Handling

VoltStack debe tener un sistema de manejo de errores capaz de responder en dos formatos:

### 1. HTTP tradicional

Para páginas normales, APIs o errores del servidor.

### 2. Volt Protocol Error

Para interacciones reactivas.

Ejemplo conceptual:

```json
{
  "error": {
    "type": "ValidationException",
    "message": "El campo email es obligatorio.",
    "fields": {
      "email": ["El campo email es obligatorio."]
    }
  }
}
```

---

## Principios Arquitectónicos

### 1. Modularidad

Cada pieza del framework debe tener límites claros.

### 2. Bajo acoplamiento

Los módulos Quantum deben comunicarse mediante contratos.

### 3. Runtime awareness

El framework debe conocer el tipo de runtime donde se ejecuta.

### 4. Seguridad por request

El modo persistente nunca debe filtrar estado entre usuarios.

### 5. Reactividad como núcleo

La reactividad no debe ser un paquete externo.

### 6. SPA por defecto

La navegación SPA debe existir desde el inicio.

### 7. PHP como experiencia principal

El desarrollador debe construir la mayor parte de la aplicación en PHP.

---

## Diagrama General

```txt
┌──────────────────────────────┐
│        Browser / Client       │
└──────────────┬───────────────┘
               │
               ▼
┌──────────────────────────────┐
│      Frontend Runtime         │
│  Navigation / Effects / DOM   │
└──────────────┬───────────────┘
               │ Volt Protocol
               ▼
┌──────────────────────────────┐
│      Reactive Runtime         │
│ Hydration / Actions / State   │
└──────────────┬───────────────┘
               │
               ▼
┌──────────────────────────────┐
│       Application Core        │
│ Kernel / Container / Routing  │
└──────────────┬───────────────┘
               │
               ▼
┌──────────────────────────────┐
│       Quantum Modules         │
│ Http / View / Cache / Events  │
└──────────────┬───────────────┘
               │
               ▼
┌──────────────────────────────┐
│       Runtime Driver          │
│ FrankenPHP / FPM / Swoole     │
└──────────────────────────────┘
```

---

## Primera Meta Técnica

La primera meta técnica de VoltStack será construir un MVP compuesto por:

```txt
Platform/Application
Quantum/Container
Quantum/Config
Quantum/Http
Quantum/Routing
Quantum/HttpKernel
Quantum/View
Quantum/Reactive
Quantum/Protocol
Frontend Runtime mínimo
```

Este MVP debe permitir:

- iniciar aplicación
- definir rutas
- renderizar una página
- montar un componente
- ejecutar una acción reactiva
- devolver respuesta Volt Protocol
- actualizar una zona del DOM sin recarga

---

## Conclusión

VoltStack debe construirse como un framework PHP moderno orientado a runtime reactivo.

Su arquitectura debe evitar convertirse en una copia directa de Laravel o Livewire. En cambio, debe tomar sus mejores principios y evolucionarlos hacia una plataforma SPA nativa, persistente y altamente productiva para desarrolladores PHP.
