# ROUTING_ARCHITECTURE.md

# VoltStack Routing Architecture

**Versión:** 1.0
**Estado:** Draft
**Framework:** VoltStack Framework
**Módulo:** Quantum Routing

---

# 1. Introducción

El sistema de Routing de VoltStack ha sido diseñado bajo una arquitectura completamente modular, compilable y orientada a servicios.

A diferencia de los routers tradicionales, donde todas las responsabilidades se concentran en una única clase, VoltStack divide el proceso completo de resolución de rutas en múltiples subsistemas especializados que colaboran mediante contratos bien definidos.

El objetivo es obtener:

* Alta mantenibilidad.
* Alto rendimiento.
* Bajo acoplamiento.
* Fácil extensión.
* Compatibilidad con múltiples runtimes.

---

# 2. Principios Arquitectónicos

Toda la arquitectura se basa en los siguientes principios.

## Single Responsibility

Cada componente realiza únicamente una función.

Nunca existirá una clase "Router" gigante que resuelva todo.

---

## Pipeline Driven

Cada petición atraviesa una secuencia de etapas independientes.

Cada etapa puede extenderse sin modificar las demás.

---

## Compile First

Toda la información posible será compilada durante el proceso de construcción del proyecto.

El runtime solamente ejecutará estructuras optimizadas.

---

## Runtime Agnostic

La arquitectura funciona sobre:

* HTTP
* SPA
* SSR
* CLI
* Testing
* Edge Runtime

---

## Metadata Driven

Las rutas son objetos enriquecidos con metadatos.

El Router nunca dependerá únicamente del método HTTP y la URI.

---

# 3. Arquitectura General

El flujo completo del sistema será:

```text
Incoming Request
        │
        ▼
Kernel
        │
        ▼
Routing Runtime
        │
        ▼
Route Matcher
        │
        ▼
Route Resolver
        │
        ▼
Route Metadata
        │
        ▼
Middleware Pipeline
        │
        ▼
Dispatcher
        │
        ▼
Controller / Component / Action
        │
        ▼
Response
```

---

# 4. Componentes Principales

El módulo estará dividido en los siguientes subsistemas.

```text
Routing/

Contracts/
Attributes/
Builders/
Registry/
Collection/
Compiler/
Matcher/
Resolver/
Dispatcher/
Middleware/
Pipeline/
Metadata/
Constraints/
Groups/
Binding/
Generators/
Cache/
Manifest/
Runtime/
Discovery/
Security/
Support/
Exceptions/
Events/
Testing/
```

---

# 5. Contracts

Define todas las interfaces públicas.

Ejemplos:

```text
RouteInterface

RouteCollectionInterface

RouteMatcherInterface

DispatcherInterface

RouteCompilerInterface

RouteRegistryInterface

UrlGeneratorInterface

ConstraintInterface

RouteCacheInterface

RouteResolverInterface
```

Todo el sistema dependerá exclusivamente de contratos.

---

# 6. Registry

Responsable del registro de rutas.

Funciones:

* Registrar rutas.
* Registrar grupos.
* Registrar atributos.
* Registrar providers.
* Registrar plugins.

Nunca realizará matching.

---

# 7. Route Collection

Representa la colección completa de rutas del proyecto.

Responsabilidades:

* almacenamiento
* búsqueda
* ordenamiento
* compilación

Será completamente inmutable una vez compilada.

---

# 8. Discovery

Responsable del descubrimiento automático.

Podrá localizar:

* Controllers
* Actions
* Attributes
* Modules
* Packages
* Plugins

Generando automáticamente nuevas rutas.

---

# 9. Compiler

Uno de los componentes más importantes.

Responsabilidades:

* Compilar atributos.
* Resolver middleware.
* Optimizar constraints.
* Generar árboles.
* Generar cache.
* Eliminar reflexión.
* Generar manifiestos.
* Optimizar bindings.

Todo el trabajo pesado ocurre aquí.

---

# 10. Matcher

Su única responsabilidad consiste en encontrar la ruta adecuada.

Entrada:

Request

Salida:

RouteDefinition

Nunca ejecuta controladores.

Nunca ejecuta middleware.

---

# 11. Resolver

Una vez encontrada la ruta:

El Resolver prepara toda la información necesaria.

Ejemplo:

* parámetros
* bindings
* metadata
* controller
* componente
* layouts
* runtime

---

# 12. Metadata System

Cada ruta contiene un objeto Metadata.

Ejemplo:

```text
RouteMetadata

• nombre

• métodos

• dominio

• middleware

• layout

• transition

• hydrate

• cache

• throttling

• policies

• permisos

• runtime

• versión

• tags

• tenant

• csrf

• signed

• locale

• prefetch
```

El Runtime consumirá esta información.

---

# 13. Dispatcher

Su responsabilidad es ejecutar la acción correspondiente.

Puede despachar hacia:

* Controller
* Closure
* Component
* Action
* SPA Component
* SSR Renderer
* Stream
* Future Dispatchers

---

# 14. Middleware Pipeline

VoltStack utilizará un Pipeline compilado.

```text
Global

↓

Group

↓

Route

↓

Controller

↓

Action

↓

After Middleware
```

Cada etapa podrá extenderse mediante Providers.

---

# 15. URL Generator

Genera:

* URLs
* URLs firmadas
* URLs temporales
* URLs SPA
* URLs SSR
* Assets
* API Links

Todo mediante un único contrato.

---

# 16. Constraint System

Sistema independiente para validar parámetros.

Ejemplos:

* números
* UUID
* slug
* enum
* expresiones regulares
* clases personalizadas

Los constraints se compilarán previamente.

---

# 17. Route Groups

Un grupo representa una configuración compartida.

Puede incluir:

* prefijo
* dominio
* middleware
* namespace
* versión
* layout
* metadata
* runtime
* tenant

Los grupos pueden anidarse.

---

# 18. Binding System

Sistema responsable de resolver automáticamente:

* modelos
* enums
* DTO
* Value Objects
* recursos

Podrá ser extendido mediante nuevos resolvers.

---

# 19. Manifest Generator

Durante la compilación se generarán distintos manifiestos.

Ejemplos:

```text
routes.php

routes.cache

routes.manifest.json

routes.frontend.json

routes.metadata

routes.bindings
```

El Runtime utilizará estos archivos directamente.

---

# 20. Runtime

El Runtime consume las rutas compiladas.

Nunca analiza archivos PHP.

Nunca realiza reflexión.

Nunca reconstruye colecciones.

Simplemente ejecuta.

---

# 21. Seguridad

El sistema de Routing soportará:

* CSRF
* Signed URLs
* Temporary URLs
* Rate Limiting
* Policies
* Permissions
* Tenant Isolation
* Domain Validation
* Origin Validation
* Security Metadata

Todo mediante Metadata.

---

# 22. Integración con Volt Runtime

Una ruta podrá contener información específica para el runtime SPA.

Ejemplo:

* hydrate
* lazy
* prefetch
* transition
* layout
* keepAlive
* partialReload
* streaming

El Runtime interpretará estas propiedades automáticamente.

---

# 23. Integración con Componentes

VoltStack no despacha únicamente controladores.

También podrá resolver:

```text
Controller

↓

Volt Component

↓

Reactive Component

↓

SPA Page

↓

Server Component

↓

API Resource
```

Todos implementan un contrato común de despacho.

---

# 24. Eventos del Sistema

Todo el ciclo de vida emitirá eventos.

Ejemplos:

```text
RouteRegistering

RouteRegistered

RouteMatched

RouteResolved

RouteDispatching

RouteDispatched

RouteNotFound

RouteCached

RouteCompiled

RouteManifestGenerated
```

Estos eventos permiten extender el framework sin modificar el núcleo.

---

# 25. Extensibilidad

El sistema podrá ampliarse mediante:

* Drivers
* Providers
* Plugins
* Compilers
* Matchers
* Dispatchers
* Constraints
* Metadata Providers
* URL Generators
* Runtime Adapters

Toda extensión utilizará contratos públicos.

---

# 26. Integración con Quantum

El Router mantiene integración directa con los siguientes módulos del framework:

```text
Quantum HTTP

Quantum Kernel

Quantum Middleware

Quantum Container

Quantum Events

Quantum Security

Quantum Controllers

Quantum Actions

Quantum Components

Quantum SPA Runtime

Quantum Hydration

Quantum Configuration

Quantum Cache

Quantum Logging

Quantum Testing
```

---

# 27. Objetivos de Rendimiento

La arquitectura ha sido diseñada para:

* Compilar todas las rutas antes de producción.
* Eliminar reflexión en tiempo de ejecución.
* Reducir el uso de memoria.
* Optimizar el matching mediante estructuras especializadas (como árboles Radix o Trie).
* Precompilar el pipeline de middleware.
* Reducir el número de asignaciones de objetos durante cada petición.
* Mantener compatibilidad con servidores persistentes como FrankenPHP, RoadRunner y Swoole.

---

# 28. Visión Arquitectónica

VoltStack Routing no debe entenderse como un simple Router HTTP.

Representa una plataforma de resolución de navegación capaz de conectar todos los subsistemas del framework mediante una arquitectura modular, compilable y extensible.

Cada solicitud es tratada como un flujo compuesto por múltiples etapas especializadas, donde cada componente tiene una responsabilidad claramente definida y puede evolucionar de forma independiente sin comprometer el rendimiento ni la estabilidad del sistema.

Recomendación de arquitectura

Hay una mejora importante que añadiría respecto a Laravel y Symfony: separar completamente el registro de rutas de su representación compilada.

En lugar de que Route::get() construya directamente objetos pesados, propondría un flujo en tres fases:

Route Builder: API fluida para que el desarrollador defina las rutas.
Route Definition: Objeto inmutable y normalizado con toda la información de la ruta.
Compiled Route: Versión optimizada para producción, sin reflexión ni procesamiento adicional.

Esta separación hace que el sistema sea mucho más fácil de optimizar, permite generar manifiestos para el runtime SPA, producir tipados para TypeScript, soportar AOT (Ahead-of-Time Compilation) y adaptar el router a distintos runtimes sin duplicar lógica. Creo que sería una de las diferencias más sólidas de VoltStack frente a los routers actuales del ecosistema PHP.
