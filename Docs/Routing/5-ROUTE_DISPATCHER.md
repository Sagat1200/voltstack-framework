# ROUTE_DISPATCHER.md

# VoltStack Route Dispatcher

**Versión:** 1.0
**Estado:** Draft

---

# 1. Introducción

El Route Dispatcher es el componente responsable de ejecutar el endpoint asociado a una ruta previamente resuelta por el Route Matcher.

El Dispatcher representa el puente entre el sistema de Routing y el resto del framework.

Su responsabilidad consiste únicamente en invocar el ejecutor adecuado.

Nunca realiza matching.

Nunca construye rutas.

Nunca ejecuta búsquedas.

Nunca interpreta archivos de configuración.

---

# 2. Filosofía

El Dispatcher está basado en tres principios.

## Single Responsibility

Su única responsabilidad consiste en despachar.

---

## Polimorfismo

Una ruta puede representar distintos tipos de ejecutores.

No únicamente controladores.

---

## Runtime Independence

El Dispatcher desconoce el tipo de respuesta final.

Simplemente delega la ejecución.

---

# 3. Flujo General

```text
RouteMatch
      │
      ▼
Dispatcher Resolver
      │
      ▼
Endpoint Dispatcher
      │
      ▼
Endpoint Execution
      │
      ▼
Raw Result
      │
      ▼
Response Factory
      │
      ▼
HTTP Response
```

---

# 4. Responsabilidades

El Dispatcher será responsable de:

* Seleccionar el Dispatcher adecuado.
* Resolver dependencias.
* Ejecutar el endpoint.
* Obtener el resultado.
* Normalizar el resultado.
* Delegar la construcción de la respuesta.

No realiza ninguna otra tarea.

---

# 5. Arquitectura

```text
Dispatcher/

Contracts/
DispatcherResolver
ControllerDispatcher
ActionDispatcher
ComponentDispatcher
SpaDispatcher
SsrDispatcher
StreamDispatcher
ClosureDispatcher
ApiDispatcher
ResponseNormalizer
Exceptions/
Support/
```

Cada Dispatcher implementa un único contrato.

---

# 6. Dispatcher Resolver

Es el punto de entrada.

Recibe un RouteMatch.

Su trabajo consiste en determinar qué Dispatcher utilizar.

Ejemplo.

```text
Controller

↓

ControllerDispatcher
```

o

```text
Volt Component

↓

ComponentDispatcher
```

---

# 7. DispatcherInterface

Todos los Dispatchers implementarán:

```php
interface DispatcherInterface
{
    public function dispatch(
        RouteMatch $match,
        Request $request
    ): mixed;
}
```

Esto garantiza que cualquier tipo de endpoint pueda incorporarse sin modificar el Router.

---

# 8. Tipos de Dispatcher

VoltStack soportará de forma nativa:

* ControllerDispatcher
* ActionDispatcher
* ClosureDispatcher
* ComponentDispatcher
* ReactiveComponentDispatcher
* SpaDispatcher
* SsrDispatcher
* StreamDispatcher
* ApiDispatcher
* ResourceDispatcher

---

# 9. Controller Dispatcher

Ejecuta controladores tradicionales.

Ejemplo.

```php
UserController::show()
```

Resuelve automáticamente:

* Constructor
* Dependencias
* Parámetros
* Método

---

# 10. Action Dispatcher

Ejecuta clases de acción.

Ejemplo.

```php
ShowUserAction
```

Ideal para aplicaciones CQRS.

---

# 11. Closure Dispatcher

Permite despachar Closures.

Pensado principalmente para:

* prototipos
* testing
* rutas simples

No recomendado para producción masiva.

---

# 12. Component Dispatcher

Ejecuta componentes de VoltStack.

Ejemplo.

```php
DashboardComponent
```

El resultado puede ser:

* HTML
* Volt Protocol
* JSON
* SSR

---

# 13. Reactive Component Dispatcher

Especializado en componentes reactivos.

Integra automáticamente:

* Hydration
* State
* Events
* Runtime

No requiere configuración adicional.

---

# 14. SPA Dispatcher

Ejecuta páginas SPA.

Puede devolver:

* Volt Protocol
* Metadata
* Component Tree
* Navigation State

El Runtime interpreta el resultado.

---

# 15. SSR Dispatcher

Responsable de renderizar páginas del lado del servidor.

Puede trabajar con:

* Volt Runtime
* React Bridge
* Vue Bridge
* Otros adaptadores

---

# 16. Stream Dispatcher

Permite endpoints de larga duración.

Ejemplos.

* Event Streams
* Server Sent Events
* Descargas
* Streaming de archivos
* Streaming de IA

---

# 17. API Dispatcher

Especializado en APIs.

Puede integrar automáticamente:

* serialización
* versionado
* resources
* negociación de contenido

---

# 18. Resource Dispatcher

Pensado para recursos REST.

Puede trabajar directamente con:

* Resource Objects
* DTO
* Collections

---

# 19. Resolver de Dependencias

Antes de ejecutar un endpoint.

El Dispatcher solicitará al Container:

* controlador
* acción
* componente

Nunca construirá objetos manualmente.

---

# 20. Resolución de Parámetros

El Dispatcher recibe parámetros ya procesados por el sistema de Binding.

No realiza conversiones.

Simplemente los inyecta.

---

# 21. Integración con Middleware

El Dispatcher siempre se ejecuta después del Middleware Pipeline.

Nunca invoca middleware directamente.

---

# 22. Response Normalizer

Los distintos endpoints pueden devolver:

```text
string

array

Response

JsonResponse

View

Component

Stream

Resource

DTO
```

El Response Normalizer convierte todos los resultados a una respuesta uniforme antes de entregarlos al sistema HTTP.

---

# 23. Integración con Volt Runtime

Cuando el Dispatcher detecta un endpoint reactivo.

Puede devolver:

* Volt Protocol
* Hydration Payload
* Component Snapshot
* Runtime Metadata
* Event Queue

El Runtime será quien interprete estos datos.

---

# 24. Integración con Quantum

El Dispatcher interactúa con:

* Quantum Container
* Quantum HTTP
* Quantum Components
* Quantum Actions
* Quantum Events
* Quantum Runtime
* Quantum Security

No depende directamente del Router.

---

# 25. Eventos

Durante el despacho se emitirán eventos.

```text
DispatchStarting

DispatcherResolved

EndpointResolving

EndpointResolved

DispatchCompleted

DispatchFailed
```

Estos eventos permiten extender el comportamiento del framework.

---

# 26. Errores

El Dispatcher puede generar:

* EndpointNotFound
* InvalidDispatcher
* InvalidResponse
* DispatchException
* DependencyResolutionException

La gestión final corresponde al Exception Handler del Kernel.

---

# 27. Rendimiento

El Dispatcher está optimizado para:

* Resolver dependencias una sola vez.
* Evitar reflexión repetitiva.
* Utilizar referencias compiladas.
* Reutilizar información del Route Compiler.
* Minimizar la creación de objetos.

---

# 28. Extensibilidad

Los desarrolladores podrán registrar nuevos Dispatchers.

Ejemplo.

* GraphQLDispatcher
* RpcDispatcher
* CliDispatcher
* QueueDispatcher
* WorkflowDispatcher
* AIAgentDispatcher

Todos implementarán el contrato DispatcherInterface.

---

# 29. Testing

Cada Dispatcher deberá contar con pruebas para:

* resolución
* ejecución
* errores
* integración con Container
* integración con Runtime
* rendimiento
* concurrencia

---

# 30. Visión

El Route Dispatcher constituye la capa de ejecución del sistema de Routing de VoltStack.

Su arquitectura desacoplada y polimórfica permite que el framework trate controladores tradicionales, componentes reactivos, páginas SPA, renderizado SSR, APIs y futuros tipos de endpoints como ciudadanos de primera clase, manteniendo una interfaz uniforme, altamente extensible y optimizada para entornos modernos de alta concurrencia.

Propuesta para diferenciar a VoltStack

Creo que el Dispatcher puede convertirse en una de las piezas más innovadoras del framework si se introduce el concepto de Endpoint como abstracción única.

En lugar de que las rutas conozcan si apuntan a un controlador, un componente o una acción, todas las rutas apuntarían a un objeto EndpointDefinition. El DispatcherResolver decidiría cómo ejecutarlo.

Esto permitiría que paquetes como:

Quantum\Components
Quantum\React
Quantum\Vue
Quantum\GraphQL
Quantum\AI
Quantum\Workflow

registren nuevos tipos de endpoints sin modificar el núcleo del Router.

Con este enfoque, VoltStack no tendría un "Dispatcher de controladores", sino una plataforma de ejecución de endpoints, mucho más flexible y preparada para evolucionar durante las próximas versiones del framework.
