# VoltStack FrankenPHP Integration

## Introducción

VoltStack está diseñado desde su arquitectura principal para integrarse profundamente con FrankenPHP.

A diferencia de frameworks PHP tradicionales construidos alrededor del modelo request/response efímero de PHP-FPM, VoltStack busca aprovechar runtimes persistentes para ofrecer:

- menor latencia
- menor bootstrap cost
- runtime reactivo persistente
- SPA más fluida
- mejor rendimiento
- menor consumo de CPU
- mejor reutilización de memoria

FrankenPHP representa el runtime recomendado oficialmente para VoltStack.

---

## Objetivo Principal

Combinar:

```txt
VoltStack Reactive Runtime
+
FrankenPHP Persistent Workers
```

para crear un entorno SPA reactivo moderno impulsado por PHP.

---

## Filosofía de Integración

### 1. Runtime Persistent First

VoltStack debe aprovechar workers persistentes desde el núcleo.

---

### 2. PHP Native Runtime

No depender de servidores externos complejos.

---

### 3. Minimal Bootstrap

El framework debe inicializarse una sola vez cuando sea posible.

---

### 4. Runtime Safe

La persistencia nunca debe provocar filtración de estado entre requests.

---

### 5. Reactive Optimized

El runtime persistente debe acelerar interacciones SPA reactivas.

---

## Problema del Modelo Tradicional

PHP-FPM funciona bajo:

```txt
request
↓
boot framework
↓
execute
↓
destroy memory
```

Esto provoca:

- bootstrap repetitivo
- reconstrucción constante del container
- recarga de metadata
- reflection repetida
- recreación de servicios

---

## FrankenPHP cambia el paradigma

Con FrankenPHP:

```txt
boot once
↓
persistent worker
↓
handle multiple requests
```

---

## Beneficios Directos para VoltStack

### 1. Menor tiempo de respuesta

---

### 2. Mejor experiencia SPA

---

### 3. Mejor hydration performance

---

### 4. Menor costo de serialización

---

### 5. Registries persistentes

---

### 6. Metadata cache persistente

---

### 7. Reflection cache persistente

---

## Arquitectura General

```txt
Browser
↓
Volt Frontend Runtime
↓
Volt Protocol
↓
FrankenPHP Worker
↓
VoltStack Runtime
↓
Reactive Components
```

---

## Runtime Layers

```txt
FrankenPHP
└── VoltStack Runtime
    ├── Application Container
    ├── Reactive Runtime
    ├── Component Registry
    ├── Hydration System
    ├── Route Registry
    ├── Metadata Cache
    └── Protocol Runtime
```

---

## Worker Lifecycle

### Startup

```txt
worker boot
↓
load framework
↓
initialize container
↓
load routes
↓
load providers
↓
initialize runtime
↓
ready
```

---

## Request Lifecycle

```txt
receive request
↓
create request scope
↓
hydrate runtime context
↓
execute request
↓
send response
↓
reset request scope
↓
worker remains alive
```

---

## Shutdown

```txt
flush resources
↓
terminate worker
```

---

## Runtime Modes

VoltStack debe soportar múltiples modos.

---

## 1. FrankenPHP Mode

Modo optimizado y recomendado.

---

## 2. PHP-FPM Mode

Compatibilidad universal.

---

## 3. RoadRunner Mode

Objetivo futuro.

---

## 4. Swoole Mode

Objetivo futuro.

---

## Runtime Driver System

VoltStack debe abstraer runtimes mediante drivers.

---

## RuntimeDriverInterface

Ejemplo conceptual:

```php
interface RuntimeDriverInterface
{
    public function boot(): void;

    public function handle(Request $request): Response;

    public function resetScope(): void;

    public function terminate(): void;
}
```

---

## FrankenPHP Driver

Ejemplo conceptual:

```txt
FrankenPhpDriver
├── WorkerBootstrap
├── RuntimeManager
├── ScopeResetter
├── MemoryMonitor
└── RequestDispatcher
```

---

## Persistent Application Container

Uno de los mayores beneficios.

---

## Objetivo

Evitar reconstrucción constante del container.

---

## Persisted Services

Pueden permanecer vivos:

```txt
container
route registry
component registry
metadata registry
reflection cache
compiled config
compiled views
protocol serializers
```

---

## Scoped Services

Servicios que deben resetearse por request.

---

## Ejemplo conceptual

```php
$app->scoped(AuthContext::class);
```

---

## Scoped Elements

Nunca deben persistir:

```txt
request
response
auth
session
csrf
validation state
temporary runtime state
tenant context
```

---

## Request Scope System

Cada request debe tener un scope aislado.

---

## Request Scope Lifecycle

```txt
create scope
↓
bind request services
↓
execute request
↓
flush scope
```

---

## Scope Reset Strategy

Después de cada request:

```txt
clear scoped bindings
reset auth
reset session
clear validation errors
clear temporary caches
flush request state
```

---

## Runtime Context Isolation

Cada request debe ejecutarse dentro de un contexto aislado.

---

## Ejemplo conceptual

```php
RuntimeContext::current();
```

---

## Runtime Context incluye

- request
- auth
- session
- locale
- tenant
- runtime metadata

---

## Component Registry Persistence

El Component Registry puede permanecer vivo.

---

## Beneficios

- menor reflection
- menor discovery cost
- mejor hydration speed

---

## Route Registry Persistence

Las rutas pueden permanecer cargadas.

---

## Beneficios

- route resolution más rápida
- menor bootstrap

---

## Reflection Cache

VoltStack debe cachear reflection.

---

## Objetivos

- reducir introspection cost
- acelerar hydration
- acelerar serialization

---

## Metadata Cache

El framework debe cachear metadata de:

- componentes
- propiedades
- atributos
- lifecycle hooks
- serializers

---

## Compiled Configuration

La configuración puede permanecer compilada.

---

## Benefits

```txt
faster boot
less IO
less parsing
```

---

## Hydration Optimization

FrankenPHP permite hydration más eficiente.

---

## Beneficios

- serializers persistentes
- metadata viva
- menos reconstrucción

---

## Volt Protocol Optimization

El protocolo puede reutilizar:

- serializers
- encoders
- normalizers
- validators

---

## Runtime Memory Management

La persistencia requiere administración de memoria.

---

## Objetivos

- prevenir memory leaks
- detectar referencias huérfanas
- liberar recursos temporales

---

## Memory Monitor

VoltStack debe incluir monitoreo de memoria.

---

## Métricas

- worker memory
- active components
- cached registries
- request growth
- leak detection

---

## Worker Recycling

Workers deben reciclarse cuando:

- memoria excede límites
- leak detection activa
- runtime corruption
- demasiados requests

---

## Ejemplo conceptual

```txt
max_requests = 10000
max_memory = 256MB
```

---

## Runtime Safety

La seguridad es crítica.

---

## Nunca persistir accidentalmente

```txt
authenticated users
sessions
request payloads
temporary files
headers
cookies
private runtime state
```

---

## Dangerous Patterns

Evitar:

```php
static $user;
```

o:

```php
singleton user state
```

---

## Safe Singleton Strategy

Los singletons deben ser:

- stateless
- immutable
- runtime-safe

---

## Runtime Aware Services

Los servicios deben conocer el runtime activo.

---

## Ejemplo conceptual

```php
if (runtime()->persistent()) {
    //
}
```

---

## Development Mode

Modo desarrollo debe soportar:

- hot reload
- worker reload
- debug hydration
- runtime inspection

---

## Debugging Tools

VoltStack debe incluir:

```txt
worker inspector
memory inspector
scope inspector
runtime timeline
hydration profiler
protocol profiler
```

---

## Runtime Metrics

Métricas importantes:

- request time
- hydration time
- serialization time
- worker memory
- protocol payload size
- DOM patch size

---

## Runtime Logging

Logs específicos:

- worker lifecycle
- scope resets
- memory warnings
- protocol failures
- hydration failures

---

## Concurrency Future

Objetivos futuros:

- fibers
- async tasks
- parallel hydration
- concurrent rendering

---

## Frontend Runtime Synergy

FrankenPHP mejora especialmente:

- SPA navigation
- hydration speed
- action latency
- partial rendering
- component interaction

---

## Expected Performance Improvements

Comparado con PHP-FPM:

```txt
lower latency
lower bootstrap cost
better throughput
better SPA responsiveness
```

---

## Performance Targets

Objetivos iniciales:

```txt
reactive requests < 30ms
worker boot once
minimal memory growth
fast hydration
```

---

## Fallback Compatibility

VoltStack debe seguir funcionando sobre:

```txt
PHP-FPM
```

sin requerir FrankenPHP obligatoriamente.

---

## Runtime Adapter Strategy

Arquitectura recomendada:

```txt
RuntimeManager
├── FrankenPhpDriver
├── FpmDriver
├── RoadRunnerDriver
└── SwooleDriver
```

---

## Configuration Example

Ejemplo conceptual:

```php
return [

    'runtime' => [

        'driver' => env('VOLT_RUNTIME', 'frankenphp'),

    ],

];
```

---

## Production Recommendations

Producción recomendada:

```txt
FrankenPHP
+
VoltStack Runtime
+
Redis
+
OPcache
```

---

## Deployment Philosophy

VoltStack debe facilitar:

- deployment simple
- runtime persistente
- cloud deployment
- container deployment
- edge deployment futuro

---

## Future Goals

### Distributed Runtime

### Runtime Clustering

### Streaming Runtime

### WebSocket Native Runtime

### Edge Runtime

### Cloud Runtime Orchestration

---

## MVP Goals

La primera integración debe soportar:

- persistent workers
- request scopes
- scoped services
- runtime reset
- route persistence
- component registry persistence
- hydration optimization

---

## Conclusión

La integración profunda con FrankenPHP es uno de los pilares tecnológicos más importantes de VoltStack.

Permite transformar PHP desde un modelo tradicional request/response hacia un runtime reactivo persistente optimizado para aplicaciones SPA modernas.
