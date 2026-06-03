# VoltStack Render Pipeline

## Introducción

El Render Pipeline de VoltStack es el sistema encargado de transformar:

```txt
State
+
Components
+
Reactive Changes
```

en:

```txt
UI Updates
+
Effects
+
DOM Patches
+
SPA Responses
```

El pipeline de rendering representa uno de los núcleos fundamentales del framework porque coordina:

- rendering reactivo
- hydration
- diffing
- partial rendering
- effect generation
- Volt Protocol responses
- DOM synchronization

VoltStack adopta una arquitectura de rendering reactivo server-driven optimizada para runtimes persistentes como FrankenPHP.

---

## Filosofía del Render Pipeline

### 1. State Driven Rendering

La interfaz debe derivarse completamente del estado.

---

### 2. Minimal Rendering

Solo debe renderizarse lo necesario.

---

### 3. Partial Rendering

Evitar rerenderizar páginas completas.

---

### 4. Reactive Native

El rendering debe responder automáticamente a cambios reactivos.

---

### 5. Runtime Optimized

El pipeline debe aprovechar runtimes persistentes.

---

## Objetivo Principal

Convertir:

```php
$this->count++;
```

en:

```txt
minimal DOM update
without full reload
```

---

## Arquitectura General

```txt
Component State Mutation
        ↓
Dirty Detection
        ↓
Render Pipeline
        ├── Component Renderer
        ├── Fragment Builder
        ├── Diff Engine
        ├── Effect Generator
        ├── Protocol Serializer
        └── Response Builder
        ↓
Volt Protocol
        ↓
Frontend Runtime
        ↓
DOM Patch
```

---

## Render Lifecycle

```txt
mount
↓
render
↓
fragment generation
↓
diff detection
↓
effect generation
↓
protocol serialization
↓
frontend patch
```

---

## Pipeline Stages

VoltStack divide el rendering en múltiples etapas.

---

## 1. State Mutation Stage

Una acción modifica estado.

---

## Ejemplo

```php
$this->count++;
```

---

## Resultado

```txt
dirty state detected
```

---

## 2. Dirty Detection Stage

El runtime detecta cambios.

---

## Objetivo

Evitar rendering innecesario.

---

## Ejemplo

```txt
before:
count = 1

after:
count = 2
```

Resultado:

```txt
dirty:
count
```

---

## 3. Component Rendering Stage

El componente vuelve a renderizarse.

---

## Ejemplo

```php
public function render(): View
{
    return view('counter');
}
```

---

## Resultado

```txt
fragment tree
```

---

## 4. Fragment Generation Stage

El renderer genera fragmentos renderizables.

---

## Objetivo

Separar regiones actualizables.

---

## Ejemplo conceptual

```txt
DashboardPage
├── Sidebar
├── Navbar
└── Content
```

---

## Fragment Structure

Cada fragmento debe tener:

- fragment id
- html
- metadata
- checksum
- dependencies

---

## Ejemplo conceptual

```json
{
  "fragment": {
    "id": "content",
    "html": "<div>...</div>"
  }
}
```

---

## 5. Diff Stage

El Diff Engine compara:

```txt
previous render
vs
current render
```

---

## Objetivo

Generar únicamente cambios mínimos.

---

## Tipos de Diff

### State Diff

---

### Fragment Diff

---

### Effect Diff

---

### DOM Patch Diff

---

## Diff Output

```txt
minimal changes
```

---

## 6. Effect Generation Stage

El backend produce effects para frontend runtime.

---

## Ejemplo

```json
{
  "effects": [
    {
      "type": "text.update",
      "target": "counter",
      "value": 2
    }
  ]
}
```

---

## Effect Types

```txt
text.update
html.replace
dom.append
dom.remove
class.toggle
style.set
focus
scroll
navigate
toast
modal
```

---

## 7. Protocol Serialization Stage

El pipeline convierte resultado en Volt Protocol.

---

## Ejemplo

```json
{
  "state": {},
  "effects": [],
  "fragments": []
}
```

---

## 8. Frontend Patch Stage

El frontend runtime aplica cambios.

---

## Flujo

```txt
effects
↓
DOM engine
↓
DOM patch
↓
UI updated
```

---

## Rendering Modes

VoltStack soporta múltiples estrategias.

---

## 1. Initial Render

Primer render SSR.

---

## Objetivo

Generar HTML inicial.

---

## Flujo

```txt
request
↓
render page
↓
generate snapshots
↓
hydrate frontend
```

---

## 2. Reactive Render

Render reactivo parcial.

---

## Objetivo

Actualizar solo fragmentos necesarios.

---

## 3. Partial Render

Solo ciertas regiones se rerenderizan.

---

## Ejemplo

```txt
Sidebar stays alive
Content rerenders
```

---

## 4. Fragment Render

Renderización individual de fragmentos.

---

## 5. Deferred Render

Objetivo futuro.

---

## 6. Streaming Render

Objetivo futuro.

---

## Render Engine

El Render Engine coordina todo el pipeline.

---

## Arquitectura

```txt
RenderEngine
├── ComponentRenderer
├── FragmentBuilder
├── DiffEngine
├── EffectBuilder
├── SnapshotGenerator
└── ResponseSerializer
```

---

## Component Renderer

Responsable de ejecutar:

```php
render()
```

---

## Objetivos

- render eficiente
- partial rendering
- nested rendering
- fragment rendering

---

## Fragment Builder

Convierte output renderizado en fragment tree.

---

## Ejemplo conceptual

```txt
FragmentTree
├── layout
├── sidebar
├── content
└── footer
```

---

## Nested Components

Los componentes hijos deben renderizarse independientemente.

---

## Ejemplo

```txt
DashboardPage
├── StatsWidget
├── RevenueChart
└── ActivityFeed
```

---

## Independent Rendering

Cada componente debe poder:

- renderizarse
- hidratarse
- diffearse
- actualizarse

de manera independiente.

---

## Smart Rendering

El runtime debe evitar renders innecesarios.

---

## Estrategias

- dirty tracking
- dependency tracking
- fragment reuse
- cached rendering

---

## Cached Rendering

Objetivo futuro.

---

## Ejemplo conceptual

```txt
reuse unchanged fragments
```

---

## Rendering Metadata

Cada render debe incluir metadata.

---

## Ejemplo

```json
{
  "metadata": {
    "component": "counter",
    "render_time": 2
  }
}
```

---

## Render Context

Cada render ocurre dentro de un contexto aislado.

---

## Incluye

- request
- auth
- locale
- tenant
- runtime metadata

---

## Runtime Context

Ejemplo conceptual:

```php
RuntimeContext::current();
```

---

## Runtime Awareness

El pipeline debe adaptarse según runtime.

---

## PHP-FPM Mode

```txt
stateless rendering
```

---

## FrankenPHP Mode

```txt
persistent metadata
reflection cache
compiled render cache
```

---

## Persistent Runtime Benefits

FrankenPHP permite mantener:

- render metadata
- reflection
- serializers
- compiled templates
- fragment caches

---

## Render Cache

VoltStack debe soportar:

- fragment cache
- component cache
- metadata cache
- render cache

---

## Cache Goals

Reducir:

- rendering cost
- serialization cost
- hydration cost

---

## DOM Synchronization

El backend NO manipula DOM directamente.

Produce:

- fragments
- effects
- patches

---

## Frontend Responsibilities

El frontend runtime debe:

- aplicar effects
- ejecutar patches
- actualizar DOM
- preservar focus
- preservar scroll

---

## Backend Responsibilities

El backend debe:

- renderizar
- generar fragments
- detectar diffs
- producir effects
- serializar payloads

---

## SPA Navigation Rendering

La navegación SPA también utiliza el pipeline.

---

## Flujo

```txt
navigate
↓
backend render
↓
fragment generation
↓
frontend patch
```

---

## Preserve State Rendering

El pipeline debe permitir:

- preserve forms
- preserve scroll
- preserve components
- preserve shared state

---

## Hydration Integration

El rendering trabaja junto con hydration.

---

## Flujo

```txt
hydrate
↓
render
↓
dehydrate
```

---

## Rendering Security

El pipeline debe proteger:

- protected state
- private properties
- sensitive metadata

---

## Rendering Validation

Validar:

- fragment integrity
- snapshot integrity
- protocol compatibility

---

## Error Handling

Errores posibles:

- render failure
- invalid fragment
- serialization failure
- diff corruption

---

## Error Example

```json
{
  "error": {
    "type": "render",
    "message": "Render pipeline failed."
  }
}
```

---

## Performance Goals

Objetivos:

- minimal rendering
- minimal DOM patches
- fast fragment generation
- optimized serialization
- low memory usage

---

## Memory Management

El pipeline debe evitar:

- stale fragments
- orphaned snapshots
- oversized payloads
- unnecessary rerenders

---

## Debug Mode

Modo debug debe incluir:

- render timeline
- diff inspector
- fragment inspector
- effect inspector
- render profiler

---

## Render Logging

Registrar:

- render time
- fragment count
- patch size
- payload size
- rerender count

---

## Future Goals

### Streaming Rendering

### Concurrent Rendering

### Incremental Rendering

### Edge Rendering

### Distributed Rendering

### AI-Assisted Rendering

---

## MVP Goals

La primera versión debe soportar:

- component rendering
- fragment generation
- dirty detection
- partial rendering
- effects
- Volt Protocol responses
- DOM patch synchronization

---

## Ejemplo Completo

### Backend

```php
class Counter extends Component
{
    public int $count = 1;

    public function increment(): void
    {
        $this->count++;
    }

    public function render(): View
    {
        return view('counter');
    }
}
```

---

## Render Pipeline

```txt
increment()
↓
dirty detection
↓
render()
↓
fragment generation
↓
effect generation
↓
Volt Protocol
↓
frontend patch
```

---

## Resultado

```txt
counter updated
without full reload
```

---

## Conclusión

El Render Pipeline de VoltStack representa el núcleo que conecta:

- estado
- componentes
- runtime reactivo
- Volt Protocol
- frontend runtime

para producir una experiencia SPA moderna impulsada completamente por PHP.

Debe mantenerse:

- rápido
- reactivo
- modular
- seguro
- optimizado para runtimes persistentes
- orientado a rendering incremental

sin perder simplicidad para el desarrollador PHP.
