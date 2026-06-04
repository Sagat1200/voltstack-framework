# VoltStack Fases And Versions

## Introducción

Este documento define las fases oficiales de desarrollo de VoltStack y las versiones asociadas a cada etapa de madurez del framework.

Su propósito es convertir la visión arquitectónica en un plan progresivo, medible y publicable, evitando saltos desordenados entre ideas, implementaciones parciales y releases sin utilidad real.

---

## Objetivo Principal

Definir:

- las fases reales del framework
- las versiones internas y públicas de cada fase
- los entregables mínimos por versión
- los criterios para avanzar de una fase a otra

---

## Filosofía De Fases

### 1. Cada fase entrega valor real

Una fase no existe para acumular código, sino para habilitar capacidades concretas.

---

### 2. Cada versión debe ser verificable

Toda versión debe poder validarse mediante:

- pruebas
- documentación
- demos funcionales
- criterios claros de salida

---

### 3. Foundation before scale

VoltStack no debe avanzar hacia:

- SPA avanzada
- runtime persistente complejo
- features empresariales
- runtime distribuido

sin un core estable previamente.

---

## Estrategia General De Versionado

```txt
0.1.x → Foundation bootstrap
0.2.x → HTTP and routing foundation
0.3.x → Views, controllers and actions
0.4.x → Reactive components alpha
0.5.x → Volt Protocol and SPA base
0.6.x → Runtime persistence preparation
0.7.x → Enterprise foundation
0.8.x → Advanced reactive preview
0.9.x → Release candidate line
1.0.0 → Stable production release
```

---

## Resumen Global

```txt
Phase 0 → Project foundation
Phase 1 → Core foundation
Phase 2 → HTTP application layer
Phase 3 → Rendering and application programming model
Phase 4 → Reactive runtime alpha
Phase 5 → SPA runtime beta
Phase 6 → Persistent runtime optimization
Phase 7 → Enterprise base
Phase 8 → Advanced reactive preview
Phase 9 → Release candidate
Phase 10 → Stable 1.0
```

---

## Phase 0 — Project Foundation

### Versiones

```txt
0.0.1
0.0.2
0.0.3
```

### Objetivo

Definir la visión, arquitectura, naming, estructura y roadmap del framework.

### Entregables

- documentación fundacional
- lineamientos de arquitectura
- estructura conceptual del proyecto
- roadmap inicial

### Estado esperado

```txt
Architectural foundation only
```

### Nota

Esta es la fase actual documentada del proyecto.

---

## Phase 1 — Core Foundation

### Version Range

```txt
0.1.0 → 0.1.x
```

### Objetivo

Construir la base ejecutable del framework.

### Módulos obligatorios

- bootstrap
- application core
- container
- configuration
- helpers base
- service providers base

### Entregables mínimos

- `Application`
- `Container`
- `ServiceProvider`
- `Bootstrapper`
- `ConfigRepository`
- helper `app()`

### Demo obligatoria

```txt
framework boot successful
```

### Criterio de salida

La aplicación arranca y resuelve servicios base.

---

## Phase 2 — HTTP Application Layer

### Version Range

```txt
0.2.0 → 0.2.x
```

### Objetivo

Construir el flujo HTTP tradicional mínimo.

### Módulos obligatorios

- http
- http kernel
- middleware pipeline
- routing

### Entregables mínimos

- `Request`
- `Response`
- `JsonResponse`
- `RedirectResponse`
- `HttpKernel`
- `Router`
- `Route`

### Demo obligatoria

```txt
GET /
↓
Route resolved
↓
Response sent
```

### Criterio de salida

Una request HTTP puede atravesar todo el pipeline y devolver respuesta.

---

## Phase 3 — Rendering And Programming Model

### Version Range

```txt
0.3.0 → 0.3.x
```

### Objetivo

Habilitar programación de aplicaciones reales sobre el framework.

### Módulos obligatorios

- controllers
- actions
- view system
- facades mínimas

### Entregables mínimos

- `Controller`
- `Action`
- `ViewFactory`
- `PhpViewEngine`
- helpers `view()` y `config()`
- facade `Route`

### Demo obligatoria

```txt
Route
↓
Controller
↓
View
↓
HTML response
```

### Criterio de salida

Ya pueden construirse páginas y endpoints no reactivos.

---

## Phase 4 — Reactive Runtime Alpha

### Version Range

```txt
0.4.0 → 0.4.x
```

### Objetivo

Introducir el modelo de componentes reactivos.

### Módulos obligatorios

- component system
- hydration base
- dehydration
- snapshot model
- action execution

### Entregables mínimos

- `Component`
- `ComponentManager`
- `Snapshot`
- `Hydrator`
- `Dehydrator`
- component rendering

### Demo obligatoria

```txt
Counter component
↓
mount
↓
render
↓
snapshot generated
```

### Criterio de salida

Un componente reactivo puede montarse, serializar estado y rerenderizarse.

---

## Phase 5 — SPA Runtime Beta

### Version Range

```txt
0.5.0 → 0.5.x
```

### Objetivo

Conectar el backend reactivo con un frontend runtime mínimo.

### Módulos obligatorios

- Volt Protocol mínimo
- protocol validation
- reactive endpoint
- frontend runtime base
- DOM replace simple

### Entregables mínimos

- `ProtocolController`
- `ActionRequest`
- `ActionResponse`
- `Checksum`
- JS runtime mínimo

### Demo obligatoria

```txt
click event
↓
POST /_volt/action
↓
hydrate
↓
execute action
↓
render
↓
html patch
```

### Criterio de salida

Una interacción reactiva completa funciona sin recarga de página.

---

## Phase 6 — Persistent Runtime Optimization

### Version Range

```txt
0.6.0 → 0.6.x
```

### Objetivo

Preparar el framework para runtimes persistentes seguros.

### Módulos obligatorios

- runtime context
- scoped services base
- request scope reset
- metadata persistence preparation
- runtime safety guards

### Entregables mínimos

- `RuntimeContext`
- `ScopeManager`
- reset hooks
- runtime-safe service rules

### Demo obligatoria

```txt
multiple requests
↓
scope reset
↓
no state leakage
```

### Criterio de salida

El diseño base ya evita contaminación entre requests.

---

## Phase 7 — Enterprise Foundation

### Version Range

```txt
0.7.0 → 0.7.x
```

### Objetivo

Agregar capacidades base necesarias para adopción empresarial inicial.

### Módulos candidatos

- auth base
- validation
- cache
- events
- security middleware

### Entregables mínimos

- validación backend-first
- CSRF estable
- policies o authorization base
- cache simple

### Criterio de salida

El framework soporta aplicaciones de negocio con seguridad mínima sólida.

---

## Phase 8 — Advanced Reactive Preview

### Version Range

```txt
0.8.0 → 0.8.x
```

### Objetivo

Explorar características reactivas avanzadas sin comprometer estabilidad.

### Módulos candidatos

- signals
- optimistic UI
- lazy hydration
- transitions
- partial navigation avanzada

### Criterio de salida

Existe preview funcional con APIs marcadas como experimentales.

---

## Phase 9 — Release Candidate Line

### Version Range

```txt
0.9.0 → 0.9.x
```

### Objetivo

Congelar APIs principales y preparar la línea estable.

### Requisitos

- documentación alineada al código
- tests del core estables
- demos funcionales consistentes
- seguridad mínima validada
- performance básica medida

### Criterio de salida

Las APIs públicas principales dejan de cambiar de forma agresiva.

---

## Phase 10 — Stable 1.0

### Version

```txt
1.0.0
```

### Objetivo

Publicar la primera versión estable general de VoltStack.

### Debe incluir

- foundation core estable
- HTTP layer estable
- controllers y actions estables
- view system estable
- component system estable
- Volt Protocol funcional
- frontend runtime mínimo estable
- seguridad mínima
- documentación oficial suficiente

### Criterio de salida

VoltStack puede ser usado en proyectos reales con expectativas razonables de estabilidad.

---

## Tabla De Versiones

```txt
0.0.x = arquitectura y documentos base
0.1.x = bootstrap, application, container, config
0.2.x = request, response, kernel, router
0.3.x = controllers, actions, views, helpers
0.4.x = components, hydration, snapshots
0.5.x = protocol, reactive endpoint, frontend runtime base
0.6.x = request scope, runtime context, persistence safety
0.7.x = security, validation, auth base, cache base
0.8.x = features reactivas avanzadas preview
0.9.x = release candidate y estabilización
1.0.0 = release estable
```

---

## Orden Recomendado De Implementación

```txt
0.1.x
↓
0.2.x
↓
0.3.x
↓
0.4.x
↓
0.5.x
↓
0.6.x
↓
0.7.x
↓
0.8.x
↓
0.9.x
↓
1.0.0
```

---

## Versiones Que No Deben Saltarse

Existen fases que no deben comprimirse ni omitirse:

- `0.1.x`
- `0.2.x`
- `0.4.x`
- `0.5.x`
- `0.6.x`

Estas fases forman el núcleo real del framework.

---

## Reglas De Publicación

### Se puede publicar una nueva versión menor cuando:

- existe una capacidad nueva completa
- hay documentación asociada
- existen tests mínimos
- la demo obligatoria funciona

---

### No se debe publicar una nueva versión menor cuando:

- solo existen stubs vacíos
- la arquitectura no está validada en ejecución
- la documentación promete más de lo implementado
- la funcionalidad no puede probarse end-to-end

---

## Criterios Para Moverse Entre Fases

VoltStack solo debe avanzar a la siguiente fase cuando:

- la fase anterior funciona realmente
- los contratos base están claros
- el flujo principal está testeado
- la documentación refleja el estado exacto del repositorio

---

## Primer Objetivo Operativo Recomendado

La primera meta técnica concreta debe ser:

```txt
0.1.x + 0.2.x + 0.3.x
```

Resultado:

```txt
framework bootable
+ routing
+ controllers
+ views
```

Después:

```txt
0.4.x + 0.5.x
```

Resultado:

```txt
reactive component MVP
```

---

## Meta De La Primera Demo Pública

La primera demo que justifica continuidad del desarrollo debe mostrar:

- una aplicación arrancando
- rutas funcionando
- una vista renderizada
- un componente `Counter`
- una acción reactiva
- actualización parcial sin recarga

---

## Conclusión

Las fases y versiones de VoltStack deben expresar una evolución técnica progresiva, realista y verificable.

El framework debe pasar primero por una línea sólida `0.1.x` a `0.6.x`, donde se construya el corazón del sistema, antes de aspirar a características empresariales, runtime avanzado o capacidades distribuidas.
