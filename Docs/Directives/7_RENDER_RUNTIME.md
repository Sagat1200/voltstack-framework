# 06_RENDER_RUNTIME.md

# VoltStack Render Runtime

---

# Estado Actual Implementado

Este documento describe una arquitectura de runtime más amplia que la implementación real actual. Hoy el framework ya tiene un runtime funcional para vistas compiladas, pero no todavía un subsistema separado con `ViewRenderer`, `ViewContext`, `SectionManager`, `LayoutManager`, `IncludeManager` y `BufferManager` como objetos independientes.

Estado real actual:

* `ViewFactory` localiza vistas y prioriza `*.volt.php` sobre `*.php`
* `PhpViewEngine` garantiza la compilación en cache, inyecta variables y ejecuta la vista compilada
* `ViewRuntime` concentra el estado de runtime para layouts y secciones
* el render usa `extract($data, EXTR_SKIP)` y expone el runtime como `$__volt`
* layouts e includes anidados reutilizan el mismo runtime o clones controlados del runtime
* el runtime preserva `TemplateCompilerException` y también `ViewRenderException` en renderizados anidados

Capacidades reales actuales:

* render de vistas compiladas
* includes
* `@extends`
* `@section` / `@endsection`
* `@yield`
* render anidado mediante `ViewRuntime::render()`
* resolución preferente de `.volt.php`

Lo que NO existe todavía como subsistema separado:

* `ViewRenderer` como clase dedicada
* `ViewContext` como objeto formal independiente
* managers separados para sections, layouts o includes
* streaming runtime
* un cache runtime distinto del cache de vistas compiladas

Referencia real del código actual:

* [ViewFactory.php](file:///c:/W4/Packages/VoltStack/voltstack-framework/src/Quantum/View/ViewFactory.php)
* [PhpViewEngine.php](file:///c:/W4/Packages/VoltStack/voltstack-framework/src/Quantum/View/PhpViewEngine.php)
* [ViewRuntime.php](file:///c:/W4/Packages/VoltStack/voltstack-framework/src/Quantum/View/Runtime/ViewRuntime.php)
* [CompiledViewRenderingTest.php](file:///c:/W4/Packages/VoltStack/voltstack-framework/tests/Feature/CompiledViewRenderingTest.php)
* [ViewRenderingTest.php](file:///c:/W4/Packages/VoltStack/voltstack-framework/tests/Feature/ViewRenderingTest.php)

---

# 1. Introducción

El Render Runtime de VoltStack es el sistema responsable de ejecutar templates compilados y producir la salida final HTML.

El runtime representa la capa de ejecución del motor de vistas y opera únicamente sobre código PHP previamente compilado.

El runtime NO interpreta templates.

Toda interpretación ocurre previamente en:

* Lexer
* Parser
* AST
* Compiler

El runtime únicamente:

* carga vistas compiladas
* inyecta variables
* ejecuta templates
* maneja layouts
* maneja secciones
* maneja includes
* controla output buffering

---

# 2. Objetivos del Runtime

El runtime debe:

* ejecutar vistas compiladas eficientemente
* minimizar overhead
* soportar layouts
* soportar includes
* soportar sections/yields
* manejar output buffering
* soportar SSR
* soportar streaming futuro
* permitir render nested
* mantener desacoplamiento del compiler

---

# 3. Filosofía Arquitectónica

VoltStack separa completamente:

| Sistema  | Responsabilidad    |
| -------- | ------------------ |
| Compiler | Compilar templates |
| Runtime  | Ejecutar templates |

---

# Beneficios

* rendimiento superior
* menor complejidad runtime
* mejor cache
* SSR más rápido
* extensibilidad futura

---

# 4. Flujo General

```text id="mgk8a1"
Template
   ↓
Compiler
   ↓
Compiled PHP
   ↓
Runtime Renderer
   ↓
HTML Final
```

---

# 5. Responsabilidades del Runtime

---

# 5.1 Renderizado

Ejecutar vistas compiladas.

---

# 5.2 Context Injection

Inyectar variables del template.

---

# 5.3 Layout System

Administrar layouts y herencia.

---

# 5.4 Section System

Administrar secciones.

---

# 5.5 Include System

Renderizar vistas parciales.

---

# 5.6 Output Buffering

Controlar buffers de render.

---

# 5.7 Cache Integration

Utilizar vistas compiladas cacheadas.

---

# 6. Arquitectura General

```text id="mgk4u3"
RenderRuntime
 ├── ViewFactory
 ├── ViewRenderer
 ├── ViewContext
 ├── SectionManager
 ├── LayoutManager
 ├── IncludeManager
 ├── BufferManager
 └── RuntimeCache
```

---

# 7. View Factory

Responsable de crear instancias de vistas.

---

# Responsabilidades

* localizar vistas
* resolver paths
* resolver namespaces
* preparar contexto

---

# Ejemplo

```php id="mgk1b7"
$view = $factory->make(
    'home',
    ['user' => $user]
);
```

---

# 8. View Renderer

Responsable de ejecutar vistas compiladas.

---

# Flujo

```text id="mgk9k2"
Compiled PHP
    ↓
Extract Variables
    ↓
Include Template
    ↓
Capture Output
```

---

# Ejemplo Conceptual

```php id="mgk6v4"
extract($data);

include $compiledPath;
```

---

# 9. View Context

Representa el contexto compartido del render.

---

# Responsabilidades

* variables
* shared data
* runtime metadata
* stacks internos

---

# Ejemplo

```php id="mgk8q1"
[
    'user' => $user,
    'title' => 'Dashboard'
]
```

---

# 10. Variable Injection

Las variables deben inyectarse mediante:

```php id="mgk4d8"
extract($data, EXTR_SKIP);
```

---

# Objetivos

* acceso simple
* performance
* aislamiento

---

# 11. Layout System

El runtime debe soportar layouts jerárquicos.

---

# Ejemplo

```volt id="mgk7y5"
@extends('layouts.app')
```

---

# Flujo

```text id="mgk3m0"
Child View
    ↓
Sections
    ↓
Parent Layout
    ↓
Final HTML
```

---

# 12. Section System

Las secciones utilizan buffering.

---

# Ejemplo

```volt id="mgk9c3"
@section('content')
@endsection
```

---

# Flujo Interno

```php id="mgk1t6"
startSection('content');

ob_start();

endSection();
```

---

# 13. Yield System

Permite insertar contenido dinámico.

---

# Ejemplo

```volt id="mgk6x2"
@yield('content')
```

---

# Compilación Conceptual

```php id="mgk8h4"
echo $this->yieldContent('content');
```

---

# 14. Include System

Permite renderizar sub-vistas.

---

# Ejemplo

```volt id="mgk4w5"
@include('partials.header')
```

---

# Flujo

```text id="mgk7g0"
Parent View
    ↓
Include Renderer
    ↓
Child View
```

---

# 15. Nested Rendering

El runtime debe soportar renderizado anidado.

---

# Ejemplo

```text id="mgk9j7"
Layout
 └── Section
      └── Include
           └── Include
```

---

# 16. Output Buffering

VoltStack utilizará output buffering controlado.

---

# Objetivos

* layouts
* sections
* nested views
* SSR streaming futuro

---

# Ejemplo

```php id="mgk2z9"
ob_start();

$content = ob_get_clean();
```

---

# 17. Runtime Cache

El runtime debe usar vistas compiladas cacheadas.

---

# Flujo

```text id="mgk5s2"
Template Changed?
    ↓
YES → Recompile
NO  → Use Cached PHP
```

---

# 18. Runtime Metadata

El runtime puede almacenar metadata futura.

---

# Ejemplo

```text id="mgk7p4"
renderTime
templatePath
layoutPath
sections
hydrationMetadata
```

---

# 19. Render Pipeline

---

# Pipeline Completo

```text id="mgk4n1"
View Request
    ↓
Resolve View
    ↓
Compile Check
    ↓
Load Compiled PHP
    ↓
Inject Context
    ↓
Execute View
    ↓
Capture Output
    ↓
Return HTML
```

---

# 20. Runtime Isolation

Cada render debe estar aislado.

---

# Objetivos

* evitar leaks
* evitar colisiones
* permitir concurrencia futura

---

# 21. Runtime Lifecycle

---

# Inicio

```text id="mgk6m3"
make()
```

---

# Render

```text id="mgk8u6"
render()
```

---

# Finalización

```text id="mgk9l8"
flush()
```

---

# 22. Error Handling

---

# 22.1 View Not Found

```text id="mgk3o5"
View [dashboard] not found.
```

---

# 22.2 Compiled File Missing

```text id="mgk5k9"
Compiled template missing.
```

---

# 22.3 Runtime Exception

```text id="mgk1n2"
Error rendering template.
```

---

# 23. Runtime Security

---

# 23.1 Escaped Output

`{{ }}` siempre escapado.

---

# 23.2 Raw Output

`{!! !!}` bajo responsabilidad del desarrollador.

---

# 23.3 Context Isolation

Evitar sobrescritura accidental.

---

# 24. Performance Goals

---

# 24.1 Low Runtime Overhead

El runtime debe ser extremadamente ligero.

---

# 24.2 Cached Templates

Nunca interpretar templates en runtime.

---

# 24.3 Fast Includes

Includes optimizados.

---

# 24.4 Minimal Memory Usage

Buffers eficientes.

---

# 25. Compatibilidad Futura

El runtime debe prepararse para:

* Component Runtime
* Reactive Runtime
* SPA Runtime
* Hydration Runtime
* Streaming SSR
* Islands Rendering
* Async Rendering
* Concurrent Rendering

---

# 26. Estructura Recomendada

```text id="mgk7r1"
src/
└── Quantum/
    └── View/
        ├── Runtime/
        │   ├── Factory/
        │   ├── Renderer/
        │   ├── Context/
        │   ├── Sections/
        │   ├── Layouts/
        │   ├── Includes/
        │   ├── Buffer/
        │   ├── Cache/
        │   ├── Contracts/
        │   ├── Exceptions/
        │   └── Support/
```

---

# 27. Ejemplo Completo

---

# Template

```volt id="mgk8z5"
@extends('layouts.app')

@section('content')
    Hello {{ $user->name }}
@endsection
```

---

# Render Pipeline

```text id="mgk2g4"
Resolve Layout
    ↓
Render Section
    ↓
Inject Section Into Layout
    ↓
Generate Final HTML
```

---

# HTML Final

```html id="mgk5e7"
<html>
<body>
    Hello Francisco
</body>
</html>
```

---

# 28. Objetivo Estratégico

El Render Runtime representa la capa de ejecución de todo el motor de vistas de VoltStack.

Toda funcionalidad futura dependerá de este sistema:

* componentes
* SPA
* reactividad
* hidratación
* SSR avanzado
* streaming
* islands architecture

Por ello, el runtime debe diseñarse como una arquitectura:

* desacoplada
* ligera
* extensible
* optimizable
* enterprise-ready
