# ROUTE_CACHE_SYSTEM.md

# VoltStack Route Cache System

**Versión:** 1.0
**Estado:** Draft

---

# 1. Introducción

El Route Cache System es el subsistema responsable de almacenar todas las estructuras compiladas producidas por el Route Compiler.

Su objetivo es eliminar cualquier proceso de construcción durante el Runtime.

En producción el Router nunca analizará:

* archivos de rutas
* atributos
* providers
* grupos
* middleware
* metadata

Toda esta información ya existirá en estructuras optimizadas listas para ser utilizadas.

---

# 2. Filosofía

El sistema sigue cinco principios.

## Compiled Artifacts

El cache está compuesto por múltiples artefactos especializados.

---

## Immutable

Los artefactos nunca cambian durante la ejecución.

---

## Incremental

En desarrollo únicamente se regeneran los artefactos afectados.

---

## Runtime Optimized

Los archivos están diseñados para lectura directa.

---

## Independent

Cada artefacto posee una única responsabilidad.

---

# 3. Objetivos

El sistema busca:

* reducir tiempo de arranque.
* reducir consumo de CPU.
* eliminar reflexión.
* reducir uso de memoria.
* facilitar compilación incremental.
* reutilizar estructuras compartidas.

---

# 4. Arquitectura

```text id="vjlwm5"
Cache/

Contracts/
Artifacts/
Compiler/
Manifest/
Storage/
Versioning/
Validation/
Support/
Events/
Testing/
```

---

# 5. Flujo General

```text id="zjlwmk"
Route Definitions
        │
        ▼
Route Compiler
        │
        ▼
Artifact Generator
        │
        ▼
Artifact Validation
        │
        ▼
Artifact Storage
        │
        ▼
Runtime Loader
```

---

# 6. Concepto de Artifact

Un Artifact representa una estructura compilada con una única responsabilidad.

Nunca almacena información ajena a su propósito.

---

# 7. Tipos de Artifact

El compilador genera múltiples artefactos.

```text id="uychca"
RouteCollection

RouteTree

RouteMetadata

Bindings

Pipeline

Manifest

FrontendManifest

Statistics

Version

Checksums
```

Cada uno puede regenerarse de forma independiente.

---

# 8. Route Collection Artifact

Contiene la colección completa de rutas compiladas.

Es la estructura principal consumida por el Matcher.

---

# 9. Route Tree Artifact

Representa el árbol optimizado utilizado por el Route Matcher.

Puede utilizar.

* Radix Tree
* Trie
* Static Maps

Dependiendo del Driver seleccionado.

---

# 10. Metadata Artifact

Almacena toda la información declarativa.

El Runtime nunca realiza merges.

---

# 11. Pipeline Artifact

Contiene los Middleware ya resueltos.

Cada Pipeline se encuentra completamente compilado.

---

# 12. Binding Artifact

Almacena.

* modelos
* DTO
* enums
* resolvers
* value objects

Todo preprocesado.

---

# 13. Manifest Artifact

Describe el estado completo del sistema.

Incluye referencias a todos los artefactos.

---

# 14. Frontend Manifest

Genera información utilizada por.

* SPA Runtime
* Hydration
* TypeScript
* Navigation
* Prefetch

Sin exponer información privada.

---

# 15. Statistics Artifact

Contiene información de compilación.

Ejemplo.

```text id="89i39w"
Routes

Groups

Domains

Bindings

Components

Compilation Time

Memory Usage
```

---

# 16. Version Artifact

Cada compilación genera.

```text id="e7t5p6"
Compiler Version

Framework Version

Protocol Version

Manifest Version

Generated At
```

Esto facilita migraciones futuras.

---

# 17. Checksums

Cada Artifact posee un checksum.

Esto permite.

* validar integridad.
* detectar corrupción.
* evitar recompilaciones innecesarias.

---

# 18. Runtime Loader

El Runtime únicamente carga.

```text id="fx0fgk"
CompiledArtifacts
```

Nunca recompila.

Nunca modifica.

---

# 19. Desarrollo

Durante el desarrollo.

El sistema detecta.

* rutas modificadas.
* archivos eliminados.
* nuevos módulos.
* nuevos paquetes.

Solo recompila los artefactos afectados.

---

# 20. Producción

En producción.

Todos los Artifacts son considerados inmutables.

Si alguno falta.

El Runtime puede:

* lanzar excepción.
* activar modo seguro.
* solicitar recompilación.

Configuración dependiente del entorno.

---

# 21. Almacenamiento

Por defecto.

```text id="mjlwm2"
bootstrap/cache/routes/
```

Ejemplo.

```text id="6pxb6q"
collection.php

tree.php

metadata.php

bindings.php

pipeline.php

manifest.json

frontend.json

statistics.json

version.json

checksums.json
```

Cada archivo posee un propósito específico.

---

# 22. Validación

Antes de utilizar un Artifact.

El Runtime puede validar.

* checksum
* versión
* compatibilidad
* integridad

La validación puede deshabilitarse en producción.

---

# 23. Integración con Quantum

Participan.

* Quantum Routing
* Quantum Runtime
* Quantum Cache
* Quantum Compiler
* Quantum SPA
* Quantum Components
* Quantum Security

---

# 24. Eventos

Durante la compilación.

```text id="1jlwm5"
ArtifactGenerating

ArtifactGenerated

ArtifactValidated

ArtifactStored

ArtifactLoaded

ArtifactInvalidated
```

---

# 25. Invalidación

El sistema puede invalidar.

* un Artifact.
* una categoría.
* un módulo.
* una colección completa.

No siempre es necesario reconstruir todo el cache.

---

# 26. Compatibilidad

El sistema funciona correctamente sobre.

* FrankenPHP
* PHP-FPM
* RoadRunner
* Swoole
* OpenSwoole
* Docker
* Kubernetes

---

# 27. Rendimiento

El Runtime nunca deberá.

* ordenar rutas.
* construir árboles.
* fusionar metadata.
* resolver aliases.
* descubrir middleware.
* generar manifiestos.

Toda esta información ya está disponible.

---

# 28. Extensibilidad

Los paquetes pueden registrar nuevos Artifacts.

Ejemplos.

* OpenAPI
* GraphQL
* AI
* Workflow
* Analytics

Sin modificar el núcleo.

---

# 29. Testing

Cada Artifact debe validar.

* generación.
* lectura.
* integridad.
* versionado.
* compatibilidad.
* rendimiento.

---

# 30. Visión

El Route Cache System transforma el tradicional concepto de "cache de rutas" en una infraestructura de artefactos compilados especializados.

Gracias a esta arquitectura, VoltStack reduce el trabajo del Runtime al mínimo, facilita la recompilación incremental, optimiza el consumo de memoria y proporciona una base sólida para servidores persistentes y aplicaciones empresariales de gran escala.

Una mejora que añadiría para VoltStack

Hay una característica que considero muy interesante y que encaja con la filosofía del framework: un Artifact Dependency Graph.

En lugar de que los artefactos sean independientes sin relación, el compilador mantendría un grafo de dependencias entre ellos.

Por ejemplo:

RouteDefinition
        │
        ├──────────────► RouteTree
        │
        ├──────────────► Metadata
        │
        ├──────────────► Pipeline
        │
        ├──────────────► FrontendManifest
        │
        └──────────────► TypeScript Manifest

Si únicamente cambia un middleware, el sistema sabría que necesita regenerar Pipeline, Manifest y, si corresponde, FrontendManifest, pero no RouteTree ni Bindings.

Esto permitiría una compilación incremental extremadamente rápida, muy útil durante el desarrollo y especialmente beneficiosa en proyectos empresariales con miles de rutas y múltiples paquetes Quantum. Creo que sería una de las características más diferenciadoras de VoltStack respecto a cualquier otro framework PHP actual.
