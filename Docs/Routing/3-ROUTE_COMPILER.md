# ROUTE_COMPILER.md

# VoltStack Route Compiler

**Versión:** 1.0
**Estado:** Draft

---

# 1. Introducción

El Route Compiler es el subsistema responsable de transformar las definiciones de rutas escritas por el desarrollador en estructuras optimizadas que puedan ejecutarse directamente durante el Runtime.

Su objetivo principal es eliminar cualquier procesamiento innecesario durante cada petición HTTP.

En modo producción, el Runtime nunca deberá:

* Analizar archivos de rutas.
* Ejecutar reflexión.
* Resolver atributos.
* Descubrir controladores.
* Analizar middlewares.
* Construir árboles de búsqueda.
* Resolver bindings dinámicamente.

Todo ese trabajo ocurre una única vez durante la compilación.

---

# 2. Filosofía

El compilador sigue cuatro principios.

## Ahead Of Time (AOT)

Toda la información posible será procesada antes del despliegue.

---

## Zero Reflection Runtime

El Runtime nunca utilizará Reflection.

Toda la información estará serializada.

---

## Immutable Routing

Las rutas compiladas son inmutables.

No pueden modificarse durante la ejecución.

---

## Multiple Artifacts

El compilador genera múltiples archivos optimizados.

No un único cache gigante.

---

# 3. Objetivos

El compilador deberá:

* Resolver atributos.
* Resolver Fluent API.
* Resolver archivos de rutas.
* Resolver auto-discovery.
* Resolver providers.
* Resolver grupos.
* Resolver middleware.
* Resolver constraints.
* Resolver bindings.
* Resolver metadata.
* Resolver componentes.
* Resolver runtime SPA.
* Optimizar árboles.
* Generar manifiestos.
* Generar caches.

---

# 4. Flujo General

```text
Route Files
Attributes
Packages
Modules
Providers
Discovery
        │
        ▼
Route Builder
        │
        ▼
Route Definitions
        │
        ▼
Compiler Pipeline
        │
        ├── Validation
        ├── Normalization
        ├── Binding Resolution
        ├── Constraint Resolution
        ├── Metadata Merge
        ├── Middleware Merge
        ├── Runtime Metadata
        ├── Optimization
        ├── Tree Builder
        ├── Cache Generator
        └── Manifest Generator
        │
        ▼
Compiled Routes
```

---

# 5. Pipeline del Compilador

El compilador estará dividido en etapas.

## Stage 1

Discovery

Encuentra automáticamente:

* Controllers
* Actions
* Components
* Attributes
* Plugins

---

## Stage 2

Validation

Verifica:

* rutas duplicadas
* nombres repetidos
* dominios inválidos
* conflictos
* middleware inexistente

---

## Stage 3

Normalization

Todas las rutas se convierten en una estructura uniforme.

No importa si provienen de:

* Attributes
* Fluent API
* YAML
* PHP
* Plugins

---

## Stage 4

Metadata Merge

Fusiona metadata proveniente de:

* grupos
* atributos
* providers
* plugins
* runtime

---

## Stage 5

Middleware Resolution

Genera el Pipeline definitivo.

No habrá resolución dinámica.

---

## Stage 6

Binding Resolution

Resuelve:

* Models
* DTO
* Enums
* Value Objects
* UUID
* Slugs

---

## Stage 7

Constraint Compilation

Compila todas las restricciones.

Ejemplo:

```
whereNumber()

whereUuid()

whereEnum()

whereRegex()

whereAlpha()
```

Todo queda preprocesado.

---

## Stage 8

Tree Builder

Construye la estructura de búsqueda.

No utiliza arrays planos.

Genera:

* Radix Tree
* Trie
* Static Prefix Tables

Dependiendo del Driver.

---

## Stage 9

Dispatcher Optimization

Genera referencias directas.

En producción no habrá búsqueda del controlador.

---

## Stage 10

Manifest Generation

Genera los distintos manifiestos.

---

# 6. Route Definition

Antes de compilar.

Cada ruta existe como un objeto:

```
RouteDefinition
```

Contiene únicamente información declarativa.

Nunca lógica.

---

# 7. Compiled Route

Después del compilador.

Cada ruta se convierte en:

```
CompiledRoute
```

Contiene:

* método
* path optimizado
* parámetros
* metadata
* dispatcher
* middleware
* bindings
* runtime

Todo listo para ejecutarse.

---

# 8. Compiler Pipeline

```text
Discovery

↓

Validation

↓

Normalization

↓

Metadata

↓

Bindings

↓

Constraints

↓

Middleware

↓

Optimization

↓

Dispatcher

↓

Manifest

↓

Cache
```

Cada etapa implementa:

```
CompilerStageInterface
```

---

# 9. Compiler Drivers

El compilador podrá utilizar distintos algoritmos.

Ejemplo:

```
RadixCompiler

TrieCompiler

StaticCompiler

RegexCompiler
```

El Driver será configurable.

---

# 10. Compiler Passes

Al igual que un compilador moderno.

Cada optimización será un Pass.

Ejemplo:

```
MergeGroupPass

NormalizePathPass

ResolveMiddlewarePass

ResolveBindingsPass

CollapsePrefixesPass

OptimizeRegexPass

StaticRoutePass

SortPriorityPass

GenerateManifestPass
```

Cada Pass es independiente.

---

# 11. Optimizaciones

El compilador podrá realizar:

* eliminación de duplicados
* prefijos comunes
* agrupación de dominios
* agrupación por método HTTP
* compresión de metadata
* cache de parámetros
* reducción de memoria
* referencias compartidas

---

# 12. Archivos Generados

El compilador genera múltiples artefactos.

```
bootstrap/cache/

routes.php

routes.metadata.php

routes.tree.php

routes.bindings.php

routes.pipeline.php

routes.manifest.json

routes.frontend.json

routes.statistics.json
```

Cada archivo tiene una responsabilidad.

---

# 13. Frontend Manifest

El compilador produce un manifiesto para:

* SPA Runtime
* TypeScript
* Prefetch
* Navigation
* Components

Sin exponer información sensible.

---

# 14. Estadísticas

El compilador podrá generar información útil.

Ejemplo:

```
Total Routes

Static Routes

Dynamic Routes

Regex Routes

Compiled Middleware

Groups

Domains

Bindings

Components

Controllers

Compilation Time
```

Esto ayuda a detectar cuellos de botella.

---

# 15. Desarrollo

En modo desarrollo.

El compilador será incremental.

Únicamente recompilará las rutas modificadas.

---

# 16. Producción

En producción.

Todo estará completamente compilado.

El Runtime únicamente cargará:

```
CompiledRouteCollection
```

No ejecutará ningún compilador.

---

# 17. Integración con Volt Runtime

El compilador añadirá metadata utilizada por el Runtime.

Ejemplo:

```
hydrate

layout

transition

prefetch

lazy

partialReload

stream

keepAlive

component

runtimeAdapter
```

El Router no necesita conocer su significado.

Solo transporta la información.

---

# 18. Integración con Quantum

El compilador interactúa con:

* Quantum HTTP
* Quantum Container
* Quantum Middleware
* Quantum Security
* Quantum Events
* Quantum Components
* Quantum SPA Runtime
* Quantum Hydration
* Quantum Cache

---

# 19. Eventos

Durante la compilación se emitirán eventos.

```
CompilationStarted

RoutesDiscovered

RoutesValidated

MetadataResolved

BindingsResolved

TreeGenerated

ManifestGenerated

CacheGenerated

CompilationFinished
```

Esto permitirá que otros paquetes agreguen información al proceso de compilación.

---

# 20. Objetivos de Rendimiento

El compilador busca que el Runtime:

* No utilice Reflection.
* No recorra archivos.
* No construya colecciones.
* No procese atributos.
* No genere árboles.
* No resuelva middleware.
* No descubra bindings.

Todo estará preparado antes del primer request.

---

# 21. Visión

El Route Compiler representa el motor de optimización de VoltStack.

Su misión es convertir un conjunto de definiciones declarativas escritas por el desarrollador en una infraestructura de navegación altamente optimizada, lista para ejecutarse con el menor consumo posible de CPU y memoria.

Gracias a esta arquitectura basada en compilación AOT, VoltStack podrá ofrecer tiempos de resolución consistentes, una integración profunda con el Runtime SPA y un rendimiento superior en entornos persistentes como FrankenPHP, RoadRunner y Swoole.

Propuesta para diferenciar aún más a VoltStack

Creo que aquí hay una oportunidad de crear una característica que no existe integrada en ningún framework PHP importante: un Compiler Plugin System.

En lugar de que el compilador tenga una lista fija de etapas, cualquier paquete podría registrar sus propios Compiler Passes. Por ejemplo:

Quantum\Security podría añadir automáticamente metadatos de políticas.
Quantum\Tenant podría inyectar reglas de aislamiento por tenant.
Quantum\SPA podría enriquecer las rutas con información de hidratación y transiciones.
Quantum\React o futuros adaptadores podrían generar manifiestos específicos para sus runtimes.

Esto convertiría al compilador en una plataforma de optimización extensible, no solo en un generador de caché de rutas, y reforzaría la arquitectura modular que estás construyendo para VoltStack.
