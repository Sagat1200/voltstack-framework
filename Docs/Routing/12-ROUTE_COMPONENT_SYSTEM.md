# ROUTE_COMPONENT_SYSTEM.md

# VoltStack Route Component System

**Versión:** 1.0
**Estado:** Draft

---

# 1. Introducción

El Route Component System define la infraestructura que permite asociar componentes directamente a las rutas del framework.

En VoltStack, un componente constituye un Endpoint ejecutable de primera clase.

No depende de un controlador.

No requiere una vista intermediaria.

Puede representar directamente una página completa, una sección reactiva o cualquier unidad de interfaz compatible con el Runtime.

---

# 2. Filosofía

El sistema sigue cinco principios.

## Components First

Los componentes son ciudadanos de primera clase dentro del Router.

---

## Endpoint Oriented

Todo componente es un Endpoint.

---

## Runtime Independent

El Router no conoce React, Vue o el Runtime nativo.

Solo conoce Component Endpoints.

---

## Compile First

Toda la información del componente se resuelve durante la compilación.

---

## Declarative

Las rutas describen qué componente ejecutar.

Nunca cómo ejecutarlo.

---

# 3. Objetivos

El sistema busca.

* eliminar controladores innecesarios.
* simplificar páginas SPA.
* integrar Hydration.
* integrar SSR.
* reducir configuración.
* facilitar reutilización.

---

# 4. Arquitectura

```text id="zjlwm8"
ComponentRouting/

Contracts/
Endpoints/
Registry/
Discovery/
Compiler/
Dispatcher/
Metadata/
Resolvers/
Lifecycle/
Runtime/
Support/
Testing/
```

---

# 5. Flujo General

```text id="kjlwm5"
Incoming Request
        │
        ▼
Route Matcher
        │
        ▼
Component Endpoint
        │
        ▼
Component Dispatcher
        │
        ▼
Volt Runtime
        │
        ▼
Volt Protocol
        │
        ▼
Frontend Runtime
```

---

# 6. Component Endpoint

Cada componente registrado genera un.

```text id="3jlwm5"
ComponentEndpoint
```

Representa una definición compilada.

Nunca contiene lógica de navegación.

---

# 7. Registro

Los componentes pueden registrarse mediante.

* Fluent API
* Attributes
* Auto Discovery
* Providers
* Packages

Todos producen exactamente la misma definición.

---

# 8. Fluent API

Ejemplo.

```php id="84a8l9"
Route::component(
    '/dashboard',
    DashboardComponent::class
);
```

---

# 9. Attributes

Ejemplo.

```php id="pnlz6y"
#[Page('/dashboard')]
class DashboardComponent
{
}
```

---

# 10. Auto Discovery

El compilador puede localizar automáticamente.

* Pages
* Screens
* Layouts
* Components

Sin necesidad de registrarlos manualmente.

---

# 11. Component Registry

Todos los componentes registrados se almacenan en un Registry compilado.

El Runtime nunca realiza búsquedas dinámicas.

---

# 12. Metadata

Cada componente genera metadata.

Ejemplo.

* layout
* hydrate
* transition
* runtime
* cache
* permissions
* assets

---

# 13. Lifecycle

Cada componente puede implementar.

```text id="jlwmk9"
Boot

Mount

Hydrate

Render

Dehydrate

Destroy
```

El Runtime controla su ejecución.

---

# 14. Component Dispatcher

El Dispatcher recibe un Component Endpoint.

Resuelve.

* instancia
* dependencias
* estado
* bindings

Y delega al Runtime.

---

# 15. Integración con Routing

El Router trata un componente exactamente igual que.

* Controller
* Action
* Closure

Todos son Endpoints.

---

# 16. Integración con Volt Runtime

El Runtime interpreta.

* estado
* hidratación
* eventos
* navegación
* componentes hijos

El Router desconoce estos detalles.

---

# 17. Integración con Hydration

Cada componente puede declarar.

* hydrate
* lazy
* partial
* snapshot
* checksum

Toda esta información se encuentra compilada.

---

# 18. Integración con SSR

Un componente puede indicar.

```text id="jlwmq1"
SSR Enabled
```

El Runtime seleccionará el Renderer adecuado.

---

# 19. Integración con SPA

Los Component Endpoints generan directamente.

* Volt Protocol
* Navigation Metadata
* Component Tree

---

# 20. Layout

Cada componente puede definir.

```text id="jlwmq2"
Layout

Transition

KeepAlive

Prefetch
```

Sin necesidad de un controlador.

---

# 21. Component Tree

El Runtime recibe un árbol.

```text id="jlwmq3"
Dashboard

├── Sidebar

├── Navigation

├── Statistics

└── Activity
```

La estructura se genera durante la compilación.

---

# 22. Component Discovery

El compilador descubre.

* namespaces
* atributos
* providers
* módulos

Generando automáticamente nuevos Endpoints.

---

# 23. Component Resolver

El Resolver obtiene.

* metadata
* bindings
* assets
* runtime

Todo utilizando estructuras compiladas.

---

# 24. Integración con Quantum

Participan.

* Quantum Routing
* Quantum Components
* Quantum Runtime
* Quantum Hydration
* Quantum Events
* Quantum Assets
* Quantum Security
* Quantum Cache

---

# 25. Eventos

Durante el ciclo de vida.

```text id="jlwmq4"
ComponentDiscovered

ComponentCompiled

ComponentResolved

ComponentMounted

ComponentRendered

ComponentDestroyed
```

---

# 26. Errores

Puede generar.

* ComponentNotFound
* InvalidComponent
* InvalidLayout
* InvalidRuntime
* HydrationException

---

# 27. Compatibilidad

El sistema soporta.

* Runtime nativo
* React Bridge
* Vue Bridge
* Svelte Bridge
* Solid Bridge
* SSR
* SPA

Todos utilizan la misma definición.

---

# 28. Rendimiento

Toda la información del componente.

* metadata
* bindings
* lifecycle
* runtime
* layout

Se encuentra compilada.

El Runtime nunca analiza clases.

---

# 29. Extensibilidad

Los paquetes pueden registrar.

* nuevos Component Types.
* nuevos Runtime Adapters.
* nuevos Lifecycle Hooks.
* nuevos Metadata Providers.
* nuevos Renderers.

Sin modificar el núcleo.

---

# 30. Testing

Cada componente deberá validar.

* compilación.
* metadata.
* hidratación.
* render.
* runtime.
* SSR.
* SPA.
* rendimiento.

---

# 31. Visión

El Route Component System convierte a los componentes en Endpoints de primera clase dentro del ecosistema de VoltStack.

Gracias a esta arquitectura, las rutas pueden representar directamente páginas, pantallas y componentes reactivos sin depender de controladores tradicionales, proporcionando una infraestructura unificada para HTTP, SPA, SSR y futuras tecnologías de renderizado, manteniendo el Router completamente desacoplado del frontend y permitiendo que el Runtime interprete la ejecución mediante el Volt Protocol.

Una propuesta que considero una de las mayores innovaciones para VoltStack

Aquí introduciría el concepto de Universal Component Endpoint (UCE).

En lugar de que una ruta apunte directamente a una clase PHP, apuntaría a una definición compilada del componente.

Por ejemplo:

DashboardComponent
        │
        ▼
UniversalComponentEndpoint
        │
        ├── PHP Runtime
        ├── Volt Runtime
        ├── React Adapter
        ├── Vue Adapter
        ├── SSR Adapter
        └── Static Renderer

De esta forma, el Router nunca despacha una clase, sino un Endpoint Universal.

Ese endpoint puede ejecutarse mediante distintos adaptadores sin modificar la ruta.

Esto encaja perfectamente con la visión que has definido para VoltStack:

un Runtime SPA propio,
un puente hacia React (voltstack/react),
futuros adaptadores para Vue o Svelte,
SSR,
NativePHP,
renderizado estático,
e incluso ejecución en Edge.

En mi opinión, este concepto de Universal Component Endpoint puede convertirse en una de las características arquitectónicas más distintivas de VoltStack frente al resto de frameworks PHP.
