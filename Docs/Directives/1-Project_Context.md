PROJECT_CONTEXT.md
VoltStack Directive System — V1 Core Template Engine

1. Introducción

El sistema de directivas de VoltStack representa el núcleo del motor de renderizado del framework.

Su propósito es proporcionar una sintaxis moderna, limpia, extensible y compilable que permita construir interfaces dinámicas utilizando PHP como lenguaje principal, eliminando la necesidad de escribir PHP embebido directamente dentro de las vistas.

El sistema está inspirado parcialmente en:

Blade
Twig
JSX
Astro
Qwik

Sin embargo, el objetivo de VoltStack no es replicar estos sistemas, sino crear una arquitectura de renderizado fullstack diseñada específicamente para:

aplicaciones PHP modernas
renderizado SSR
componentes desacoplados
hidratación progresiva
SPA híbridas
sistemas reactivos futuros
runtime extensible
compilación incremental
compatibilidad empresarial
2. Objetivo General

Diseñar e implementar un sistema de directivas compilables que funcione como base del motor de vistas de VoltStack.

El sistema debe:

transformar templates en PHP optimizado
compilar vistas automáticamente
soportar cache inteligente
generar AST interno
permitir futuras capacidades reactivas
ser extensible mediante plugins/directivas
mantener alto rendimiento
minimizar runtime overhead
3. Objetivos Técnicos
3.1 Construir un Lexer

Responsable de tokenizar el contenido del template.

Ejemplo:

@if($user)
    {{ $user->name }}
@endif

Debe convertirse en tokens internos:

T_DIRECTIVE_IF
T_EXPRESSION
T_ECHO
T_BLOCK_END
3.2 Construir un Parser

Convertirá tokens en un AST estructurado.

Ejemplo conceptual:

TemplateNode
 ├── IfNode
 │    └── EchoNode
3.3 Construir un AST (Abstract Syntax Tree)

El AST será la base para:

compilación
optimización
análisis estático
futuras transformaciones reactivas
SSR avanzado
hydration metadata
3.4 Construir un Compiler

Responsable de convertir el AST en PHP compilado.

Ejemplo:

@if($user)

↓

<?php if($user): ?>
3.5 Construir un Runtime Renderer

Responsable de:

cargar vistas compiladas
renderizar templates
manejar contexto
administrar variables
manejar layouts
manejar includes
administrar cache
4. Alcance de la V1

La V1 se enfocará únicamente en el Core Template Engine.

NO incluirá:

componentes
reactividad
eventos
hidratación
runtime frontend
SPA navigation
virtual DOM
signals
stores
websocket integration

Estas funcionalidades serán introducidas progresivamente en futuras versiones.

1. Directivas Incluidas en V1
5.1 Directivas Condicionales
@if()
@elseif()
@else
@endif
@unless()
@endunless
@isset()
@endisset
@empty()
@endempty
5.2 Directivas de Loops
@foreach()
@endforeach
@forelse()
@empty
@endforelse
@for()
@endfor
@while()
@endwhile
5.3 Directivas Switch
@switch()
@case()
@break
@default
@endswitch
5.4 Directiva PHP Inline
@php
@endphp
5.5 Directivas Echo

Escape automático:

{{ $name }}

Salida raw:

{!! $html !!}
5.6 Directivas de Includes
@include()
5.7 Directivas de Layouts
@extends()
@section()
@endsection
@yield()
5.8 Comentarios
{{-- comentario --}}
6. Arquitectura General
Quantum/
└── View
    ├── Compiler
    │   ├── Lexer
    │   ├── Parser
    │   ├── AST
    │   ├── Compiler
    │   ├── Visitors
    │   └── Tokens
    │
    ├── Directives
    │   └── Core
    │
    ├── Runtime
    │
    ├── Cache
    │
    ├── Contracts
    │
    ├── Exceptions
    │
    └── Support
7. Arquitectura del Compiler
Pipeline
Template
   ↓
Lexer
   ↓
Tokens
   ↓
Parser
   ↓
AST
   ↓
Compiler
   ↓
Compiled PHP
   ↓
Runtime Renderer
8. Sistema de Directivas

Las directivas serán registradas mediante un registry centralizado.

Ejemplo:

Directive::register(
    'if',
    IfDirective::class
);
9. Contrato de Directivas
interface DirectiveContract
{
    public function compile(
        string $expression
    ): string;
}
10. Ejemplo de Directiva
class IfDirective implements DirectiveContract
{
    public function compile(
        string $expression
    ): string {
        return "<?php if ({$expression}): ?>";
    }
}
11. Objetivos de Rendimiento

El sistema debe:

minimizar regex complejos
soportar cache persistente
evitar recompilaciones innecesarias
soportar templates grandes
permitir lazy compilation
permitir precompilación futura
12. Sistema de Cache

Las vistas compiladas deberán almacenarse en:

storage/framework/views

Cada template compilado debe incluir:

hash del archivo
checksum
metadata
timestamps
invalidación automática
13. Sistema de Errores

El sistema debe proporcionar errores claros para:

directivas inválidas
bloques sin cerrar
sintaxis incorrecta
includes inexistentes
layouts inválidos

Ejemplo:

Unclosed @if directive found in:
resources/views/home.volt
Line: 45
14. Compatibilidad Futura

La arquitectura debe prepararse para:

V2 Component System
V3 Reactivity
V4 Event System
V5 SPA Navigation
V6 Hydration Engine
SSR streaming
partial hydration
islands architecture
async rendering
runtime hooks
template transforms
15. Filosofía del Sistema

El sistema de directivas de VoltStack debe ser:

declarativo
extensible
compilable
escalable
altamente optimizable
desacoplado
enterprise-ready
16. Objetivo Estratégico

VoltStack no busca ser únicamente otro motor de templates PHP.

El objetivo es construir:

un runtime fullstack moderno
un sistema SSR avanzado
una arquitectura híbrida SPA/MPA
un motor reactivo progresivo
una plataforma UI desacoplada
un ecosistema PHP moderno y escalable

El sistema de directivas V1 representa la base fundamental sobre la cual evolucionará todo el runtime del framework.
