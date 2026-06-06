# 04_CORE_DIRECTIVES_SPEC.md

# VoltStack Core Directives Specification (V1)

---

# 1. Introducción

Este documento define la especificación oficial de las directivas core del sistema de templates de VoltStack V1.

Las directivas V1 representan la capa fundamental del motor de renderizado y serán responsables de:

* control de flujo
* renderizado dinámico
* layouts
* includes
* output seguro
* estructuras base de templates

La V1 NO incluye:

* componentes
* reactividad
* eventos
* SPA
* hidratación
* runtime frontend

---

# 2. Filosofía de las Directivas

Las directivas deben ser:

* declarativas
* compilables
* consistentes
* predecibles
* extensibles
* de bajo overhead
* compatibles con SSR

---

# 3. Categorías de Directivas

| Categoría              | Descripción            |
| ---------------------- | ---------------------- |
| Conditional Directives | Control de flujo       |
| Loop Directives        | Iteraciones            |
| Echo Directives        | Renderizado dinámico   |
| Layout Directives      | Herencia de vistas     |
| Include Directives     | Inclusión de templates |
| PHP Directives         | Bloques PHP            |
| Comment Directives     | Comentarios internos   |

---

# 4. Conditional Directives

---

# 4.1 @if

---

## Sintaxis

```volt id="jlwm1d"
@if($condition)
@endif
```

---

## Ejemplo

```volt id="jlwm2f"
@if($user)
    Welcome
@endif
```

---

## Compilación

```php id="mgk7u0"
<?php if($user): ?>
    Welcome
<?php endif; ?>
```

---

# 4.2 @elseif

---

## Sintaxis

```volt id="jlwm4f"
@elseif($condition)
```

---

## Ejemplo

```volt id="jlwm6d"
@if($role === 'admin')
@elseif($role === 'user')
@endif
```

---

## Compilación

```php id="jlwm8k"
<?php elseif($role === 'user'): ?>
```

---

# 4.3 @else

---

## Sintaxis

```volt id="mjlwm2"
@else
```

---

## Compilación

```php id="mgk9u1"
<?php else: ?>
```

---

# 4.4 @endif

---

## Sintaxis

```volt id="mgk4u9"
@endif
```

---

## Compilación

```php id="mgk0u8"
<?php endif; ?>
```

---

# 4.5 @unless

---

## Sintaxis

```volt id="mgk9k3"
@unless($condition)
@endunless
```

---

## Compilación

```php id="mgk9t1"
<?php if(!($condition)): ?>
<?php endif; ?>
```

---

# 4.6 @isset

---

## Sintaxis

```volt id="mgk8m2"
@isset($user)
@endisset
```

---

## Compilación

```php id="mgk9v4"
<?php if(isset($user)): ?>
<?php endif; ?>
```

---

# 4.7 @empty

---

## Sintaxis

```volt id="mgk9a1"
@empty($users)
@endempty
```

---

## Compilación

```php id="mgk2t8"
<?php if(empty($users)): ?>
<?php endif; ?>
```

---

# 5. Loop Directives

---

# 5.1 @foreach

---

## Sintaxis

```volt id="mgk7l2"
@foreach($users as $user)
@endforeach
```

---

## Compilación

```php id="mgk6v9"
<?php foreach($users as $user): ?>
<?php endforeach; ?>
```

---

# 5.2 @forelse

---

## Sintaxis

```volt id="mgk5u3"
@forelse($users as $user)
@empty
@endforelse
```

---

## Compilación

```php id="mgk4v2"
<?php if(count($users) > 0): ?>
    <?php foreach($users as $user): ?>
    <?php endforeach; ?>
<?php else: ?>
<?php endif; ?>
```

---

# 5.3 @for

---

## Sintaxis

```volt id="mgk9x0"
@for($i = 0; $i < 10; $i++)
@endfor
```

---

## Compilación

```php id="mgk3y1"
<?php for($i = 0; $i < 10; $i++): ?>
<?php endfor; ?>
```

---

# 5.4 @while

---

## Sintaxis

```volt id="mgk6q2"
@while($condition)
@endwhile
```

---

## Compilación

```php id="mgk9e3"
<?php while($condition): ?>
<?php endwhile; ?>
```

---

# 6. Echo Directives

---

# 6.1 Escaped Echo

---

## Sintaxis

```volt id="mgk1f5"
{{ $name }}
```

---

## Objetivo

Render seguro con escape automático.

---

## Compilación

```php id="mgk8z2"
<?= e($name) ?>
```

---

# 6.2 Raw Echo

---

## Sintaxis

```volt id="mgk7r4"
{!! $html !!}
```

---

## Objetivo

Renderizar contenido sin escape.

---

## Compilación

```php id="mgk6w5"
<?= $html ?>
```

---

# 7. Include Directives

---

# 7.1 @include

---

## Sintaxis

```volt id="mgk3h8"
@include('partials.header')
```

---

## Compilación

```php id="mgk1j6"
<?= $__volt->render('partials.header') ?>
```

---

# 8. Layout Directives

---

# 8.1 @extends

---

## Sintaxis

```volt id="mgk4n9"
@extends('layouts.app')
```

---

## Objetivo

Definir layout padre.

---

# 8.2 @section

---

## Sintaxis

```volt id="mgk7b2"
@section('content')
@endsection
```

---

## Compilación Conceptual

```php id="mgk5p1"
$__volt->startSection('content');

$__volt->endSection();
```

---

# 8.3 @yield

---

## Sintaxis

```volt id="mgk2c7"
@yield('content')
```

---

## Compilación

```php id="mgk0m4"
<?= $__volt->yieldContent('content') ?>
```

---

# 9. PHP Directives

---

# 9.1 @php

---

## Sintaxis

```volt id="mgk5u0"
@php
    $name = 'VoltStack';
@endphp
```

---

## Compilación

```php id="mgk8d3"
<?php
    $name = 'VoltStack';
?>
```

---

# 10. Comment Directives

---

# 10.1 Blade-style Comments

---

## Sintaxis

```volt id="mgk1o7"
{{-- comentario --}}
```

---

## Resultado

No debe renderizarse.

---

# 11. Directivas Reservadas

Las siguientes palabras quedan reservadas:

```text id="mgk9s0"
if
elseif
else
endif
foreach
endforeach
for
endfor
while
endwhile
extends
section
yield
include
php
endphp
```

---

# 12. Validaciones

---

# 12.1 Bloques No Cerrados

Ejemplo:

```volt id="mgk7g4"
@if($user)
```

---

## Error

```text id="mgk4j1"
Unclosed @if directive.
```

---

# 12.2 Directivas Inválidas

Ejemplo:

```volt id="mgk3n8"
@invalidDirective()
```

---

## Error

```text id="mgk1v2"
Unknown directive [invalidDirective]
```

---

# 13. Reglas de Compilación

---

# 13.1 No Runtime Parsing

Las directivas deben compilarse previamente.

---

# 13.2 Salida PHP Optimizada

El compiler debe generar:

* PHP limpio
* mínimo overhead
* estructuras nativas

---

# 13.3 Line Mapping

Las líneas originales deben preservarse para debugging.

---

# 14. Performance Rules

---

# 14.1 Cache Obligatorio

Todas las vistas compiladas deben cachearse.

---

# 14.2 Recompilación Inteligente

Solo recompilar templates modificados.

---

# 14.3 Lazy Compilation

Compilar únicamente vistas utilizadas.

---

# 15. Seguridad

---

# 15.1 Escape Automático

`{{ }}` siempre debe escapar HTML.

---

# 15.2 Raw Output

`{!! !!}` será responsabilidad del desarrollador.

---

# 16. Compatibilidad Futura

La arquitectura V1 debe prepararse para:

* componentes
* slots
* props
* directivas reactivas
* SPA navigation
* hydration
* SSR streaming
* islands
* async rendering

---

# 17. Estructura Recomendada

```text id="mgk7p0"
src/
└── Quantum/
    └── View/
        ├── Directives/
        │   ├── Core/
        │   │   ├── Conditional/
        │   │   ├── Loops/
        │   │   ├── Layouts/
        │   │   ├── Includes/
        │   │   ├── Echo/
        │   │   └── PHP/
```

---

# 18. Objetivo Estratégico

Las directivas core V1 representan la base del runtime de VoltStack.

Toda futura capacidad del framework dependerá de estas estructuras:

* Component System
* Reactivity Engine
* SPA Runtime
* Hydration Engine
* Enterprise Features

Por ello, las directivas deben mantenerse:

* simples
* optimizables
* desacopladas
* extensibles
* enterprise-ready
