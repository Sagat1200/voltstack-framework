# ROUTE_METADATA_SYSTEM.md

# VoltStack Route Metadata System

**Versión:** 1.0
**Estado:** Draft

---

# 1. Introducción

El Route Metadata System es el subsistema responsable de almacenar, resolver y distribuir toda la información declarativa asociada a una ruta.

Mientras que el sistema de Routing determina qué endpoint debe ejecutarse, el sistema de Metadata describe cómo debe comportarse dicho endpoint dentro del ecosistema de VoltStack.

Toda la metadata será compilada previamente por el Route Compiler y consumida por los distintos módulos del framework durante el Runtime.

---

# 2. Filosofía

La metadata no contiene lógica.

Describe comportamiento.

Cada propiedad representa una característica declarativa que otros módulos podrán interpretar de forma independiente.

El sistema sigue cuatro principios:

* Declarativo
* Inmutable
* Compilable
* Extensible

---

# 3. Objetivos

El sistema busca:

* Centralizar la información de las rutas.
* Evitar configuraciones duplicadas.
* Compartir información entre módulos.
* Facilitar la compilación.
* Permitir extensiones mediante paquetes.
* Reducir el acoplamiento entre subsistemas.

---

# 4. Arquitectura

```text
Metadata/

Contracts/
Collection/
Registry/
Compiler/
Providers/
Resolvers/
Validators/
Attributes/
Support/
Events/
Cache/
Testing/
```

Cada componente posee una única responsabilidad.

---

# 5. Flujo General

```text
Route Definition
        │
        ▼
Metadata Providers
        │
        ▼
Metadata Compiler
        │
        ▼
Metadata Validation
        │
        ▼
Metadata Merge
        │
        ▼
Compiled Route Metadata
        │
        ▼
Runtime Consumers
```

---

# 6. Metadata Sources

La metadata puede provenir de múltiples orígenes.

* Fluent API
* Attributes
* Route Groups
* Packages
* Providers
* Plugins
* Runtime Extensions
* Auto Discovery

Toda la información se fusiona durante la compilación.

---

# 7. Route Metadata Object

Cada ruta compilada contiene un único objeto:

```text
RouteMetadata
```

Este objeto representa la descripción completa del endpoint.

Nunca contiene lógica de negocio.

---

# 8. Categorías

La metadata se organiza por categorías.

## Routing

* name
* methods
* domain
* prefix
* priority
* version

---

## Runtime

* runtime
* layout
* hydrate
* lazy
* prefetch
* keepAlive
* partialReload
* transition
* streaming

---

## Seguridad

* auth
* guest
* policies
* permissions
* csrf
* signed
* temporary
* throttle
* tenant
* scopes

---

## Renderizado

* ssr
* spa
* component
* renderer
* template
* responseType

---

## API

* apiVersion
* resource
* serialization
* negotiation
* openapi
* deprecation

---

## Cache

* cache
* cacheTags
* cacheTTL
* cacheDriver

---

## Internacionalización

* locale
* fallbackLocale
* translated

---

## Observabilidad

* metrics
* tracing
* logging
* profiling

---

## Documentación

* title
* description
* tags
* examples
* summary

---

## Personalizada

Los paquetes pueden añadir nuevas categorías.

---

# 9. Metadata Providers

Cada paquete puede registrar un Metadata Provider.

Ejemplo.

```text
SecurityMetadataProvider

SpaMetadataProvider

HydrationMetadataProvider

OpenApiMetadataProvider

TenantMetadataProvider
```

Cada Provider añade únicamente la metadata correspondiente a su módulo.

---

# 10. Metadata Compiler

El compilador es responsable de:

* validar propiedades.
* eliminar duplicados.
* fusionar categorías.
* resolver conflictos.
* optimizar referencias.
* generar estructuras inmutables.

---

# 11. Metadata Merge

Cuando una propiedad existe en varios niveles:

```text
Global

↓

Module

↓

Group

↓

Controller

↓

Endpoint
```

La prioridad siempre es determinística.

El endpoint tiene la mayor precedencia.

---

# 12. Metadata Validation

Cada categoría posee validadores independientes.

Ejemplo.

* tipo de dato
* compatibilidad
* versiones
* dependencias
* valores permitidos

---

# 13. Metadata Registry

Todas las categorías registradas se almacenan en un Registry.

Esto permite que nuevos paquetes incorporen propiedades sin modificar el núcleo del framework.

---

# 14. Metadata Resolver

Durante el Runtime ningún módulo interpreta archivos de configuración.

Simplemente solicita la metadata correspondiente a la ruta compilada.

---

# 15. Integración con Quantum

Los siguientes módulos consumen Route Metadata.

* Quantum Runtime
* Quantum HTTP
* Quantum Security
* Quantum Authorization
* Quantum Components
* Quantum SPA
* Quantum SSR
* Quantum Cache
* Quantum Events
* Quantum Observability
* Quantum Tenant
* Quantum OpenAPI

---

# 16. Integración con SPA

El Runtime puede leer directamente.

* transition
* hydrate
* layout
* keepAlive
* prefetch
* lazy
* partialReload

Sin requerir configuraciones adicionales.

---

# 17. Integración con Seguridad

Quantum Security consume.

* auth
* guest
* permissions
* scopes
* csrf
* signed
* tenant
* throttle

El Router no necesita conocer el funcionamiento interno de estas propiedades.

---

# 18. Integración con OpenAPI

La metadata puede generar automáticamente.

* documentación
* esquemas
* ejemplos
* recursos
* tipos
* versiones

Sin duplicar información.

---

# 19. Integración con TypeScript

El compilador puede producir manifiestos para el frontend.

Ejemplo.

```text
routes.manifest.json

routes.types.ts

navigation.manifest.json
```

La metadata se reutiliza para generar estos artefactos.

---

# 20. Eventos

Durante la compilación se generan eventos.

```text
MetadataCollecting

MetadataMerging

MetadataCompiled

MetadataValidated

MetadataCached
```

---

# 21. Errores

El sistema puede producir.

* InvalidMetadata
* DuplicateMetadata
* MetadataConflict
* MetadataValidationException
* UnknownMetadataKey

---

# 22. Rendimiento

Toda la metadata se almacena como estructuras compiladas.

Durante el Runtime:

* no existe reflexión.
* no existen merges.
* no existen validaciones.
* no existen búsquedas dinámicas.

Toda la información se encuentra preparada para consumo inmediato.

---

# 23. Extensibilidad

Los paquetes pueden registrar:

* nuevas categorías.
* nuevos providers.
* nuevos validadores.
* nuevos compiladores.
* nuevos resolvers.
* nuevos serializadores.

Sin modificar el núcleo.

---

# 24. Compatibilidad

El sistema funciona sobre.

* HTTP
* API
* SPA
* SSR
* Streaming
* CLI
* Queue
* WebSocket
* Edge Runtime

---

# 25. Testing

Cada categoría deberá validar.

* compilación
* merge
* validación
* serialización
* compatibilidad
* rendimiento

---

# 26. Visión

El Route Metadata System transforma las rutas de VoltStack en entidades declarativas enriquecidas.

En lugar de limitarse a definir una URI y un controlador, cada ruta describe completamente su comportamiento dentro del framework, permitiendo que módulos como Seguridad, SPA Runtime, SSR, Cache, OpenAPI, Observabilidad y futuras extensiones compartan una única fuente de verdad compilada, consistente y altamente optimizada.

Mejora arquitectónica que propondría

Aquí incorporaría un concepto adicional que, en mi opinión, puede convertirse en una de las características más potentes de VoltStack: un Metadata Capability System.

En lugar de almacenar únicamente pares clave-valor, cada categoría de metadata declararía las capacidades que ofrece al framework.

Por ejemplo:

HydrationCapability
PrefetchCapability
TransitionCapability
AuthorizationCapability
CacheCapability
StreamingCapability
OpenApiCapability

De esta forma, el Runtime no tendría que preguntar si existe una clave como hydrate o prefetch; simplemente consultaría si la ruta implementa determinada capacidad. Esto desacopla completamente a los consumidores de los nombres concretos de las propiedades, facilita la evolución del sistema y permite que paquetes externos añadan nuevas capacidades sin modificar el núcleo de VoltStack. Es una aproximación muy alineada con la filosofía modular y orientada a compilación que estás construyendo.
