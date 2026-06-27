# MIDDLEWARE_PIPELINE.md

# VoltStack Middleware Pipeline

**Versión:** 1.0
**Estado:** Draft

---

# 1. Introducción

El Middleware Pipeline es el subsistema responsable de procesar todas las solicitudes antes y después de la ejecución del endpoint.

Representa una cadena de procesamiento donde cada Middleware recibe una solicitud, puede modificarla, detener la ejecución o delegar el control al siguiente elemento del Pipeline.

En VoltStack, el Pipeline no se construye dinámicamente durante cada petición.

Toda la estructura será compilada previamente por el Route Compiler para minimizar el trabajo del Runtime.

---

# 2. Filosofía

El Pipeline sigue cinco principios fundamentales.

## Compiled First

Los pipelines son compilados durante la construcción del proyecto.

---

## Context Aware

Un Middleware puede ejecutarse únicamente en determinados contextos.

---

## Immutable

Los Pipelines compilados no cambian durante la ejecución.

---

## Ordered

El orden siempre es determinístico.

---

## Extensible

Nuevos Middleware y nuevas etapas pueden registrarse mediante contratos públicos.

---

# 3. Objetivos

El sistema busca:

* minimizar la resolución dinámica.
* evitar reflexión.
* reducir llamadas al Container.
* reutilizar pipelines.
* soportar múltiples runtimes.
* facilitar la extensión mediante paquetes.

---

# 4. Flujo General

```text
Incoming Request
        │
        ▼
Global Pipeline
        │
        ▼
Runtime Pipeline
        │
        ▼
Group Pipeline
        │
        ▼
Route Pipeline
        │
        ▼
Controller Pipeline
        │
        ▼
Endpoint Dispatcher
        │
        ▼
After Pipeline
        │
        ▼
Response
```

---

# 5. Arquitectura

```text
Middleware/

Contracts/
Pipeline/
Registry/
Resolver/
Compiler/
Context/
Stages/
Aliases/
Priority/
Runtime/
Events/
Exceptions/
Support/
Testing/
```

---

# 6. Pipeline Compiler

El Pipeline es construido por el Route Compiler.

Durante la compilación se resuelven:

* aliases
* grupos
* prioridades
* exclusiones
* middleware duplicados
* metadata
* contextos

El Runtime únicamente carga el Pipeline resultante.

---

# 7. Middleware Registry

Todos los Middleware se registran en un único Registry.

Ejemplo.

```text
AuthMiddleware

ThrottleMiddleware

CSRFMiddleware

CacheMiddleware

LocaleMiddleware

TenantMiddleware
```

Cada Middleware posee un identificador único.

---

# 8. Alias

Los aliases permiten una configuración más sencilla.

Ejemplo.

```text
auth

guest

verified

admin

tenant

csrf

throttle
```

Durante la compilación los aliases son reemplazados por referencias directas.

---

# 9. Tipos de Middleware

VoltStack reconoce diferentes niveles.

## Global

Siempre se ejecutan.

---

## Runtime

Dependen del Runtime activo.

Ejemplo.

SPA

SSR

API

CLI

Streaming

---

## Group

Aplicados a grupos de rutas.

---

## Route

Aplicados únicamente a una ruta.

---

## Controller

Declarados sobre controladores o componentes.

---

## Endpoint

Aplicados directamente al endpoint.

---

## After

Se ejecutan después de generar la respuesta.

---

# 10. Contextos

Cada Middleware puede declarar dónde es válido.

Ejemplo.

```text
HTTP

API

SPA

SSR

CLI

QUEUE

STREAM

WEBSOCKET

EDGE
```

El compilador elimina automáticamente los Middleware incompatibles.

---

# 11. Prioridad

El Pipeline utiliza prioridades explícitas.

Ejemplo.

```text
Highest

High

Normal

Low

Lowest
```

Nunca depende únicamente del orden de registro.

---

# 12. Middleware Interface

Todos implementan:

```php
interface MiddlewareInterface
{
    public function handle(
        Request $request,
        Next $next
    ): Response;
}
```

Para Middleware posteriores a la respuesta se utilizará un contrato independiente.

---

# 13. Before Middleware

Procesan la Request.

Pueden:

* modificar datos.
* autenticar.
* validar.
* cancelar la petición.
* registrar información.

---

# 14. After Middleware

Procesan la Response.

Ejemplos.

* compresión
* logging
* métricas
* cache
* auditoría
* headers
* cookies

---

# 15. Around Middleware

Opcionalmente un Middleware podrá envolver toda la ejecución.

Ejemplo.

```text
Before

↓

Endpoint

↓

After
```

Ideal para:

* profiling
* tracing
* transacciones
* medición de rendimiento

---

# 16. Pipeline Resolver

El Resolver obtiene el Pipeline compilado asociado a la ruta.

No construye Middleware.

No consulta configuración.

Simplemente recupera la referencia correspondiente.

---

# 17. Pipeline Cache

Cada Pipeline compilado tendrá un identificador.

Varias rutas podrán compartir exactamente el mismo Pipeline.

Esto evita duplicación de memoria.

---

# 18. Context Resolution

Antes de ejecutar el Pipeline se determina el contexto.

Ejemplo.

```text
SPA

↓

Pipeline SPA
```

o

```text
API

↓

Pipeline API
```

---

# 19. Integración con Quantum

El Pipeline interactúa con:

* Quantum HTTP
* Quantum Security
* Quantum Events
* Quantum Authentication
* Quantum Authorization
* Quantum Tenant
* Quantum Components
* Quantum Runtime

---

# 20. Middleware Metadata

Cada Middleware puede declarar:

* prioridad
* contexto
* dependencias
* exclusiones
* etiquetas
* versión
* compatibilidad

Toda esta información se procesa durante la compilación.

---

# 21. Eventos

El sistema emitirá eventos.

```text
PipelineBuilding

PipelineCompiled

MiddlewareStarting

MiddlewareCompleted

MiddlewareSkipped

PipelineFinished

PipelineFailed
```

---

# 22. Errores

Puede generar.

* MiddlewareNotFound
* InvalidMiddleware
* CircularDependency
* InvalidContext
* PipelineCompilationException

---

# 23. Optimizaciones

El compilador podrá realizar.

* eliminación de duplicados.
* inlining.
* ordenamiento por prioridad.
* agrupación por contexto.
* referencias compartidas.
* pre-resolución de aliases.
* eliminación de Middleware inalcanzables.

---

# 24. Testing

Cada Pipeline deberá validar.

* orden de ejecución.
* prioridades.
* exclusiones.
* contexto.
* cancelación.
* concurrencia.
* rendimiento.

---

# 25. Compatibilidad

El sistema funcionará sobre.

* FrankenPHP
* PHP-FPM
* RoadRunner
* Swoole
* OpenSwoole
* CLI
* Docker
* Kubernetes

---

# 26. Rendimiento

El Runtime nunca deberá:

* resolver aliases.
* ordenar Middleware.
* construir el Pipeline.
* detectar duplicados.
* analizar prioridades.

Todo ese trabajo pertenece al compilador.

---

# 27. Visión

El Middleware Pipeline de VoltStack representa una infraestructura de procesamiento compilada, desacoplada y consciente del contexto de ejecución.

Su diseño permite reutilizar pipelines, eliminar trabajo repetitivo en tiempo de ejecución y adaptarse de forma transparente a aplicaciones HTTP tradicionales, APIs, runtimes SPA, SSR y futuros entornos distribuidos, proporcionando un equilibrio entre flexibilidad, extensibilidad y rendimiento de nivel empresarial.

Propuesta para hacer a VoltStack único

Aquí incorporaría una característica inspirada en compiladores modernos: un Middleware Optimization Pass.

Durante la compilación, el sistema podría:

Fusionar middleware compatibles en una sola unidad ejecutable.
Eliminar middleware redundantes o anulados por otros.
Detectar dependencias y reordenar automáticamente cuando sea seguro.
Generar una representación optimizada del pipeline (similar a un árbol de llamadas) para reducir invocaciones y creación de objetos.

Esto convertiría al Middleware Pipeline en un sistema optimizable, no simplemente en una lista de clases, y encaja perfectamente con la filosofía AOT que estás definiendo para VoltStack.
