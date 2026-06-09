# 03_DIRECTIVE_REGISTRY.md

# VoltStack Directive Registry System

---

# Estado Actual Implementado

Este documento describe una arquitectura objetivo más amplia que la implementación real actual. Hoy el framework ya tiene un registry funcional y extensible, pero todavía no un subsistema completo con resolver, manager, aliases y lazy loading.

Estado real actual:

* existe un `DirectiveRegistry` único y centralizado
* el registro interno usa lookup O(1) mediante array asociativo
* las directivas core se registran automáticamente en el constructor
* las directivas custom pueden registrarse en runtime
* existe soporte de overwrite manual mediante `register(..., overwrite: true)`
* el compiler y el node compiler resuelven directivas a través del registry actual
* las directivas core se implementan principalmente con `CallbackDirective`
* ya existen directivas estructurales, de control de flujo, e includes/layouts
* el sistema soporta nombres con guiones, por ejemplo `tailwind-vite`

Lo que NO existe todavía:

* `DirectiveResolver` como objeto separado
* `DirectiveManager`
* aliases de directivas
* namespaces tipo `ui:button`
* lazy loading de directivas
* clases dedicadas por cada directiva core como requisito general

API real actual del registry:

* `register(string $name, DirectiveContract $directive, bool $overwrite = false): void`
* `has(string $name): bool`
* `resolve(string $name): ?DirectiveContract`

Directivas core registradas hoy:

* condicionales: `if`, `elseif`, `else`, `endif`, `unless`, `endunless`, `isset`, `endisset`, `empty`, `endempty`
* loops: `foreach`, `endforeach`, `for`, `endfor`, `while`, `endwhile`, `forelse`, `empty`, `endforelse`
* layouts: `extends`, `section`, `endsection`, `yield`
* includes: `include`
* php: `php`, `endphp`

Referencia real del código actual:

* [DirectiveRegistry.php](file:///c:/W4/Packages/VoltStack/voltstack-framework/src/Quantum/View/Directives/DirectiveRegistry.php)
* [CallbackDirective.php](file:///c:/W4/Packages/VoltStack/voltstack-framework/src/Quantum/View/Directives/Support/CallbackDirective.php)
* [TemplateDirectiveCompiler.php](file:///c:/W4/Packages/VoltStack/voltstack-framework/src/Quantum/View/Compilers/TemplateDirectiveCompiler.php)

---

# 1. Introducción

El Directive Registry es el sistema encargado de registrar, resolver y administrar todas las directivas del motor de templates de VoltStack.

Representa uno de los componentes centrales de la arquitectura del compiler, ya que permite desacoplar:

* parsing
* resolución
* compilación
* extensibilidad

El registry permitirá que VoltStack soporte:

* directivas core
* directivas personalizadas
* plugins
* extensiones enterprise
* futuras transformaciones runtime

---

# 2. Objetivos del Sistema

El Directive Registry debe:

* registrar directivas dinámicamente
* resolver directivas eficientemente
* soportar extensibilidad
* evitar colisiones
* soportar namespaces futuros
* permitir overrides
* mantener alto rendimiento
* desacoplar compiler y directivas

---

# 3. Filosofía Arquitectónica

Las directivas NO estarán hardcodeadas dentro del compiler.

El compiler únicamente:

* detecta tokens/directivas
* delega resolución al registry
* recibe compiladores/directivas

Esto permite:

* extensibilidad total
* arquitectura modular
* plugins
* compilación desacoplada

---

# 4. Flujo General

```text id="zr8n0q"
Template
   ↓
Lexer
   ↓
Parser
   ↓
Directive Resolver
   ↓
Directive Registry
   ↓
Directive Compiler
   ↓
Compiled PHP
```

---

# 5. Arquitectura General

---

# 5.1 Componentes Principales

```text id="g2z9lr"
DirectiveRegistry
DirectiveResolver
DirectiveCompiler
DirectiveContract
DirectiveManager
```

---

# 5.2 Responsabilidades

| Componente        | Responsabilidad           |
| ----------------- | ------------------------- |
| DirectiveRegistry | Registrar directivas      |
| DirectiveResolver | Resolver directivas       |
| DirectiveCompiler | Compilar directivas       |
| DirectiveManager  | Administrar ciclo de vida |
| DirectiveContract | Contrato base             |

---

# 6. Directive Registry

El registry almacena todas las directivas disponibles.

---

# 6.1 Ejemplo de Registro

```php id="kxjlwm"
Directive::register(
    'if',
    IfDirective::class
);
```

---

# 6.2 Registro Interno

```php id="1vfkl6"
[
    'if' => IfDirective::class,
    'foreach' => ForeachDirective::class,
]
```

---

# 6.3 Responsabilidades

El registry debe:

* almacenar mappings
* validar duplicados
* permitir overrides
* resolver aliases
* soportar carga lazy

---

# 7. Directive Resolver

Responsable de localizar la directiva correcta.

---

# 7.1 Flujo

```text id="ym4mxh"
@if($user)
   ↓
Resolver
   ↓
IfDirective
```

---

# 7.2 Resolución

```php id="5ynh4w"
$directive = $registry->resolve('if');
```

---

# 7.3 Validaciones

Debe detectar:

* directivas inexistentes
* conflictos
* namespaces inválidos

---

# 8. Directive Contract

Todas las directivas deben implementar un contrato base.

---

# 8.1 Contrato Base

```php id="f8dclu"
interface DirectiveContract
{
    public function compile(
        string $expression
    ): string;
}
```

---

# 8.2 Objetivos

El contrato garantiza:

* consistencia
* compilación uniforme
* extensibilidad
* testing sencillo

---

# 9. Tipos de Directivas

La arquitectura debe soportar múltiples tipos.

---

# 9.1 Block Directives

Ejemplo:

```volt id="mkpq1k"
@if()
@endif
```

---

# 9.2 Inline Directives

Ejemplo:

```volt id="s8l4fr"
@include('header')
```

---

# 9.3 Echo Directives

Ejemplo:

```volt id="wx5c5u"
{{ $name }}
```

---

# 9.4 Raw Directives

Ejemplo:

```volt id="eb55vh"
{!! $html !!}
```

---

# 9.5 Structural Directives

Ejemplo:

```volt id="jlwm1g"
@extends()
@section()
@yield()
```

---

# 10. Arquitectura de Compilación

---

# 10.1 Flujo

```text id="kuo5uk"
Directive Token
    ↓
Resolver
    ↓
Directive Instance
    ↓
compile()
    ↓
Compiled PHP
```

---

# 10.2 Ejemplo

Entrada:

```volt id="fp9l55"
@if($user)
```

Salida:

```php id="jlwmqe"
<?php if($user): ?>
```

---

# 11. Directivas Core V1

---

# 11.1 Condicionales

```text id="jlwmiv"
if
elseif
else
endif
unless
endunless
isset
endisset
empty
endempty
```

---

# 11.2 Loops

```text id="mgk9m1"
foreach
endforeach
forelse
empty
endforelse
for
endfor
while
endwhile
```

---

# 11.3 Layouts

```text id="jlwm2k"
extends
section
endsection
yield
```

---

# 11.4 Includes

```text id="jlwm34"
include
```

---

# 11.5 PHP

```text id="jlwm9t"
php
endphp
```

---

# 12. Directivas Custom

El sistema debe permitir registrar directivas del usuario.

---

# 12.1 Ejemplo

```php id="jlwmvv"
Directive::register(
    'datetime',
    DateTimeDirective::class
);
```

---

# 12.2 Uso

```volt id="v4d27r"
@datetime($date)
```

---

# 13. Namespaces Futuros

La arquitectura debe prepararse para:

```volt id="mjlwm8"
@ui:button
@spa:navigate
@auth:can
```

---

# 14. Lazy Directive Loading

El sistema debe soportar carga lazy.

---

# Objetivos

* menor memoria
* startup rápido
* plugins dinámicos

---

# Ejemplo

```text id="jlwmf5"
Directive Requested?
    ↓
Load Directive Class
```

---

# 15. Overrides

Debe ser posible sobrescribir directivas.

---

# Ejemplo

```php id="jlwm93"
Directive::override(
    'if',
    CustomIfDirective::class
);
```

---

# 16. Alias System

El sistema puede soportar aliases futuros.

---

# Ejemplo

```php id="jlwm8f"
Directive::alias(
    'auth',
    'can'
);
```

---

# 17. Arquitectura de Performance

---

# 17.1 Cached Registry

El registry debe cachearse.

---

# 17.2 Fast Lookup

La resolución debe ser O(1).

---

# 17.3 Immutable Definitions

Las definiciones deben ser preferiblemente inmutables.

---

# 18. Error Handling

---

# 18.1 Directiva No Encontrada

```text id="jlwm92"
Unknown directive [customDirective]
```

---

# 18.2 Directiva Inválida

```text id="jlwmv2"
Directive must implement DirectiveContract
```

---

# 18.3 Duplicados

```text id="mgk8o0"
Directive [if] already registered
```

---

# 19. Extensibilidad Futura

La arquitectura debe prepararse para:

* directives macros
* runtime directives
* reactive directives
* hydration directives
* SPA directives
* async directives
* AST transforms
* compile hooks

---

# 20. Estructura Recomendada

```text id="jlwm1v"
src/
└── Quantum/
    └── View/
        ├── Directives/
        │   ├── Core/
        │   ├── Contracts/
        │   ├── Registry/
        │   ├── Resolver/
        │   ├── Manager/
        │   ├── Exceptions/
        │   └── Support/
```

---

# 21. Ejemplo Arquitectónico Completo

---

# Registro

```php id="jlwm6h"
Directive::register(
    'if',
    IfDirective::class
);
```

---

# Resolución

```php id="mjlwm4"
$resolver->resolve('if');
```

---

# Compilación

```php id="jlwmxl"
$directive->compile('$user');
```

---

# Resultado

```php id="jlwm2s"
<?php if($user): ?>
```

---

# 22. Objetivo Estratégico

El Directive Registry representa el punto de extensibilidad central del motor de templates de VoltStack.

Toda evolución futura dependerá de este sistema:

* componentes
* reactividad
* SPA
* hidratación
* runtime frontend
* enterprise features

Por ello, el registry debe ser:

* modular
* desacoplado
* extensible
* altamente optimizable
* enterprise-ready
