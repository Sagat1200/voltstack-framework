# VoltStack Routing - Version Development

**Version:** 1.0  
**Estado:** Working Draft  
**Objetivo:** traducir la vision de `Docs/Routing` a un plan ejecutable de implementacion por versiones, separando claramente `MVP`, `V2`, `Postergado` y `Vision futura`.

---

## 1. Proposito

La carpeta `Docs/Routing` define una arquitectura amplia, modular y muy bien orientada a futuro.

Sin embargo, no todo debe implementarse al mismo tiempo.

Este documento existe para responder una pregunta practica:

> Que parte del sistema de Routing debe construirse primero para habilitar el framework y evitar sobredisenar el core antes de tiempo.

La respuesta operativa es:

- primero cerrar un `Core Routing V1` pequeno, solido y compilable
- despues abrir `SPA Native V2` sobre ese contrato ya estable
- dejar fuera del primer ciclo todo lo que sea enterprise, distributed, adaptive, negotiation o AI

---

## 2. Regla De Prioridad

Cada pieza del sistema cae en una de estas categorias:

- `MVP V1`: imprescindible para que el router funcione como infraestructura base del framework
- `V2 SPA`: importante para integrar de forma limpia el runtime SPA reactivo, pero no bloquea el core HTTP
- `Postergado`: util, pero no debe entrar antes de estabilizar el core
- `Vision`: idea valiosa de largo plazo; no debe convertirse en deuda de implementacion inmediata

Regla general:

- si una capacidad cambia el contrato HTTP base del framework, debe resolverse en `V1`
- si una capacidad depende del runtime SPA o solo agrega ergonomia, puede ir a `V2`
- si una capacidad introduce complejidad estructural sin desbloquear valor inmediato, se posterga

---

## 3. Contrato Minimo De V1

Antes de seguir expandiendo el runtime SPA, el sistema de Routing deberia cerrar este contrato tecnico minimo:

- `RouteDefinition` inmutable como forma normalizada de una ruta
- `CompiledRoute` como artefacto ejecutable
- `CompiledRouteCollection` como conjunto principal consumido por matcher y URL generator
- `RouteMatcher` por `metodo + dominio + path + constraints`
- `RouteDispatcher` desacoplado del tipo de endpoint
- `Middleware Pipeline` compilado
- `Route Metadata` minima y publica para consumers internos
- `Route Cache / Artifacts` para eliminar trabajo en runtime
- `URL Generator` basado en nombres de ruta y metadata compilada

Semantica HTTP minima que debe quedar resuelta en `V1`:

- soporte para `GET`, `POST`, `PUT`, `PATCH`, `DELETE`, `HEAD`, `OPTIONS`, `ANY`
- distincion formal entre `404 Not Found` y `405 Method Not Allowed`
- generacion de cabecera `Allow` cuando corresponda
- politica clara para `HEAD` sobre rutas `GET`
- decision explicita sobre `method override`
- punto de integracion limpio con `CSRF`, auth, throttle y seguridad por metadata

---

## 4. Matriz Ejecutiva

| Documento | Rol real en el sistema | Prioridad | Decision | Resultado esperado |
| --- | --- | --- | --- | --- |
| `1-ROUTING_PROJECT_CONTEXT.md` | contexto, filosofia y alcance | Baja | `Referencia` | mantener como marco conceptual |
| `2-ROUTING_ARCHITECTURE.md` | arquitectura madre del modulo | Alta | `MVP V1` | usar como contrato de capas y responsabilidades |
| `3-ROUTE_COMPILER.md` | compilacion AOT de rutas | Alta | `MVP V1` | implementar compilador minimo con artefactos esenciales |
| `4-ROUTE_MATCHER.md` | matching por metodo, dominio y path | Alta | `MVP V1` | implementar matcher HTTP real multi-metodo |
| `5-ROUTE_DISPATCHER.md` | ejecucion del endpoint resuelto | Alta | `MVP V1` | implementar resolver + dispatch basico |
| `6-MIDDLEWARE_PIPELINE.md` | pipeline compilado por contexto | Alta | `MVP V1` | soportar global, group y route como minimo |
| `7-ROUTE_METADATA_SYSTEM.md` | fuente de verdad declarativa | Alta | `MVP V1` | implementar metadata minima, no el universo completo |
| `8-SPA_ROUTING_PROTOCOL.md` | contrato de navegacion SPA | Media | `V2 SPA` | aterrizar payload minimo para navegacion reactiva |
| `9-ROUTE_CACHE_SYSTEM.md` | artefactos compilados y runtime loader | Alta | `MVP V1` | generar y cargar artefactos estables |
| `10-URL_GENERATOR.md` | generacion de URLs por nombre | Alta | `MVP V1` | `route()`, dominios, query y signed mas adelante |
| `11-ROUTE_ATTRIBUTES.md` | definicion por atributos PHP | Media | `V1.1` | soportar despues del flujo por route files/fluent API |
| `12-ROUTE_COMPONENT_SYSTEM.md` | componentes como endpoints | Media | `V2/V3` | no bloquear V1 con este sistema |
| `13-FRONTEND_ROUTE_MANIFEST.md` | manifiesto publico para frontend | Media | `V2 SPA` | exponer solo rutas y capacidades publicas |
| `14-MULTI_TENANT_ROUTING.md` | tenant-aware routing | Baja | `Postergado` | entrar cuando exista modulo tenant estable |
| `15-ROUTE_SECURITY_MODEL.md` | seguridad por metadata | Alta | `MVP V1 parcial` | integrar auth/csrf/throttle como metadata minima |
| `16-ROUTING_PERFORMANCE_MODEL.md` | presupuestos y principios de rendimiento | Media | `MVP V1 parcial` | usar como criterio, no como subsistema grande |
| `17-ROUTING_ROADMAP.md` | roadmap de evolucion | Baja | `Referencia` | conservar como vision por fases |

---

## 5. Implementacion Recomendada Por Documento

### `1-ROUTING_PROJECT_CONTEXT.md`

Decision:

- mantenerlo como documento de norte arquitectonico
- no convertir su alcance completo en backlog inmediato

Tomar ahora:

- `compiled by default`
- `runtime aware`
- separacion de responsabilidades

Postergar:

- edge runtime
- microservicios
- streaming generalizado
- plataforma universal completa

### `2-ROUTING_ARCHITECTURE.md`

Decision:

- usarlo como contrato base de `V1`

Implementar ahora:

- `Registry`
- `RouteCollection`
- `Compiler`
- `Matcher`
- `Dispatcher`
- `Pipeline`
- `Metadata`
- `Cache/Artifacts`

No implementar aun:

- gran cantidad de subsistemas vacios solo por simetria documental
- resolver universal demasiado abstracto
- eventos exhaustivos del ciclo si todavia no aportan trazabilidad real

### `3-ROUTE_COMPILER.md`

Decision:

- es una pieza fundacional de `V1`

Implementar ahora:

- normalizacion de rutas provenientes de route files o Fluent API
- validacion de duplicados
- merge minimo de metadata
- compilacion de middleware
- compilacion de constraints
- generacion de `collection`, `tree`, `metadata`, `pipeline`

Postergar:

- plugin system completo del compilador
- drivers avanzados si no hay necesidad real
- estadisticas complejas mas alla de lo util para debug

### `4-ROUTE_MATCHER.md`

Decision:

- es uno de los documentos mas importantes para ejecutar primero

Implementar ahora:

- seleccion por metodo HTTP
- soporte para `GET`, `POST`, `PUT`, `PATCH`, `DELETE`, `HEAD`, `OPTIONS`, `ANY`
- dominio opcional
- rutas estaticas
- rutas dinamicas con parametros
- constraints basicos
- errores `RouteNotFound` y `MethodNotAllowed`

Aterrizar explicitamente:

- `405` con `Allow`
- estrategia `HEAD -> GET`
- si `OPTIONS` sera automatico o explicito

Postergar:

- adaptive matching engine
- multiple indices avanzados
- optimizaciones exoticas prematuras

### `5-ROUTE_DISPATCHER.md`

Decision:

- implementar solo el nucleo necesario para ejecutar endpoints en `V1`

Implementar ahora:

- `DispatcherResolver`
- `ControllerDispatcher`
- `ClosureDispatcher`
- `ActionDispatcher` si el framework ya usa ese modelo
- `ResponseNormalizer` basico

Postergar:

- `SpaDispatcher`
- `SsrDispatcher`
- `StreamDispatcher`
- `ApiDispatcher` especializado
- `ResourceDispatcher`
- endpoint abstraction total si aun no existe contrato estable

### `6-MIDDLEWARE_PIPELINE.md`

Decision:

- pieza critica de `V1`, pero con alcance controlado

Implementar ahora:

- pipeline compilado con niveles `global`, `group`, `route`
- alias minimos
- prioridades deterministicas
- contextos `HTTP` y luego `SPA/API` cuando realmente existan

Postergar:

- around middleware sofisticado
- optimizer de pipeline
- contextos mas exoticos como `EDGE`, `QUEUE`, `WEBSOCKET`

### `7-ROUTE_METADATA_SYSTEM.md`

Decision:

- implementar metadata minima, no el catalogo total

Metadata minima sugerida para `V1`:

- `name`
- `methods`
- `domain`
- `middleware`
- `csrf`
- `auth`
- `throttle`
- `runtime` basico
- `layout` solo si ya tiene consumer real

Postergar:

- observabilidad extensa
- OpenAPI
- i18n avanzada
- documentation metadata
- capability system completo

### `8-SPA_ROUTING_PROTOCOL.md`

Decision:

- no debe bloquear el core routing
- entra en `V2 SPA`

Implementar primero un payload SPA minimo:

- `target`
- `layout`
- `transition`
- `hydrate`
- `metadata` publica
- `redirect`
- `error`

No implementar aun:

- navigation intent system
- streaming generalizado
- partial reload complejo
- events declarativos amplios

### `9-ROUTE_CACHE_SYSTEM.md`

Decision:

- parte esencial de `V1`

Artefactos minimos:

- `collection.php`
- `tree.php`
- `metadata.php`
- `pipeline.php`
- `version.json` o equivalente

Opcionales despues:

- `frontend.json`
- `statistics.json`
- `checksums.json` avanzados
- invalidacion incremental fina

### `10-URL_GENERATOR.md`

Decision:

- debe existir en `V1`, pero con alcance pequeno

Implementar ahora:

- `route(name, parameters)`
- rutas nombradas
- query string
- fragment
- absoluto/relativo

Postergar:

- `spa()`
- `component()`
- `api()` especializado
- signed y temporary si aun no hay seguridad cerrada
- URL intent system

### `11-ROUTE_ATTRIBUTES.md`

Decision:

- muy util, pero no debe ser la primera puerta de entrada

Implementar despues de estabilizar:

- route files
- fluent API
- route definition
- compiler

Prioridad real:

- `V1.1`

Implementar primero:

- atributos HTTP basicos
- `Name`
- `Middleware`
- `Domain`

Postergar:

- macros de atributos
- catalogo grande de atributos SPA/API/Docs

### `12-ROUTE_COMPONENT_SYSTEM.md`

Decision:

- no debe entrar en el core routing inicial

Motivo:

- introduce un modelo de endpoint de mayor nivel que depende de runtime, hydration y componentes

Prioridad real:

- `V2` si solo habilita SPA pages
- `V3` si se adopta como estrategia general del framework

### `13-FRONTEND_ROUTE_MANIFEST.md`

Decision:

- importante para `V2 SPA`
- no bloquear `V1` por esto

Manifest minimo recomendado:

- `protocol`
- `version`
- `routes`
- `methods`
- `path`
- `public capabilities`

No incluir aun:

- negotiation por adapter
- manifests multiples por runtime
- TypeScript complejo si aun no existe consumer real

### `14-MULTI_TENANT_ROUTING.md`

Decision:

- postergado hasta que `Quantum Tenant` tenga contrato estable

Lo unico que conviene prever en `V1`:

- soporte de `domain`
- soporte de `TenantContext` opcional en metadata

No implementar aun:

- overlays por tenant
- estrategias multiples completas
- branding, capability overlays y reglas por plan

### `15-ROUTE_SECURITY_MODEL.md`

Decision:

- aplicar un corte parcial en `V1`

Implementar ahora:

- metadata de `auth`
- metadata de `guest`
- metadata de `csrf`
- metadata de `signed` solo si ya existe URL signing
- metadata de `throttle`

Postergar:

- MFA
- OAuth
- policy graph
- scopes complejos
- capability graph de seguridad

### `16-ROUTING_PERFORMANCE_MODEL.md`

Decision:

- tomarlo como criterio de aceptacion, no como subsistema pesado

Aplicar desde `V1`:

- sin reflection en runtime
- sin merge dinamico de metadata
- sin compilacion en request
- matcher por estructuras compiladas
- pipelines compartidos

Postergar:

- performance budget system automatico
- analisis profundo de presupuestos por artefacto

### `17-ROUTING_ROADMAP.md`

Decision:

- mantenerlo como mapa estrategico
- no usarlo como backlog lineal de implementacion inmediata

Uso recomendado:

- revisar al cerrar cada version
- reordenar segun dependencias reales del framework

---

## 6. MVP Real De Quantum Routing

Si el objetivo es desbloquear el framework y luego volver al runtime SPA reactivo, el `MVP real` deberia verse asi:

### Bloque 1 - Definicion

- `RouteDefinition`
- `CompiledRoute`
- `RouteBuilder`
- `RouteCollection`

### Bloque 2 - Matching

- rutas estaticas
- rutas dinamicas con parametros
- soporte multi-metodo
- dominio opcional
- constraints basicos
- `404` vs `405`

### Bloque 3 - Dispatch

- resolver de dispatcher
- controller/closure/action dispatcher
- response normalizer basico

### Bloque 4 - Pipeline

- middleware global
- middleware por grupo
- middleware por ruta
- orden deterministico

### Bloque 5 - Metadata

- metadata minima
- merge compilado
- acceso simple desde runtime y seguridad

### Bloque 6 - Artifacts

- compilacion
- carga desde cache
- invalidacion basica en desarrollo

### Bloque 7 - URL Generator

- rutas nombradas
- parametros
- query y fragment

---

## 7. Lo Que No Debe Bloquear V1

Estas piezas pueden ser valiosas, pero no deben retrasar el primer bloque funcional:

- component endpoints universales
- SPA protocol completo
- frontend manifest avanzado
- multi-tenant overlays
- security graphs
- compiler plugin ecosystem completo
- adaptive matching engine
- pipeline optimizer
- performance budget system
- manifests por runtime adapter
- AI, distributed, edge o universal runtime

---

## 8. Orden Recomendado De Implementacion

Orden corto y seguro:

1. `RouteDefinition + RouteCollection + Fluent API`
2. `Matcher` multi-metodo con `404/405/Allow`
3. `Dispatcher` basico
4. `Middleware Pipeline` compilado
5. `Metadata` minima
6. `Route Cache / Artifacts`
7. `URL Generator`
8. `Route Attributes`
9. `Frontend Manifest` minimo
10. `SPA Routing Protocol` minimo

---

## 9. Decision Sobre El Runtime SPA Reactivo

Con base en esta documentacion, la recomendacion para el runtime SPA reactivo es:

- no seguir ampliando el runtime sobre un router aun incompleto
- cerrar primero el contrato HTTP multi-metodo del framework
- mantener por ahora el runtime con:
  - navegacion SPA por `GET`
  - acciones reactivas internas por `POST`
- dejar preparada la infraestructura del router para `PUT`, `PATCH`, `DELETE`, `HEAD`, `OPTIONS`
- decidir despues, ya con el core estable, si el protocolo reactivo consumira otros verbos o si seguira centralizado en `POST`

Esto reduce deuda tecnica y evita acoplar el runtime a una semantica HTTP incompleta.

---

## 10. Criterio De Cierre Para V1

`V1 Core Routing` puede considerarse cerrado cuando existan estas garantias:

- las rutas se definen y compilan a una estructura unica y estable
- el matcher resuelve correctamente por metodo y path
- el framework responde formalmente `404` y `405`
- el dispatcher ejecuta endpoints HTTP basicos
- el pipeline de middleware funciona sin resolucion dinamica excesiva
- la metadata minima ya es consumible por seguridad y runtime
- el URL generator funciona por nombre de ruta
- el runtime no necesita interpretar archivos de rutas

Cuando eso exista, ya tiene sentido volver a expandir `SPA Native`.

---

## 11. Criterio De Cierre Para V2 SPA

`V2 SPA` deberia comenzar solo despues de cerrar `V1`.

Meta minima de `V2`:

- frontend manifest publico
- metadata SPA publica y estable
- protocolo minimo de navegacion
- integracion limpia con layout, hydrate y transition
- contrato de errores consistente entre router, dispatcher y runtime

---

## 12. Resumen Ejecutivo

La documentacion de `Docs/Routing` define una vision correcta, pero su implementacion debe recortarse agresivamente por fases.

Decision final:

- `V1`: core routing compilable, multi-metodo, dispatcher, pipeline, metadata minima, cache y URL generator
- `V2`: runtime SPA y manifiestos publicos sobre el contrato ya estable
- `V3+`: componentes como endpoints de primera clase
- `Postergado`: tenant avanzado, negotiation, adaptive engines, overlays, AI, edge, distributed

En otras palabras:

> primero infraestructura base del framework; despues inteligencia SPA; al final expansion arquitectonica.
