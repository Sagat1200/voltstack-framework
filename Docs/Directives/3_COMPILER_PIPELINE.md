# 02_COMPILER_PIPELINE.md

# VoltStack Compiler Pipeline

---

# Estado Actual Implementado

Este documento describe la arquitectura objetivo del pipeline. El estado implementado hoy en el framework es más acotado, pero ya es modular y funcional.

Pipeline real actual:

```text
Template Source
    ↓
TemplateSourceTokenizer
    ↓
TemplateSourceToken(PHP | INLINE_HTML)
    ↓
TemplateTokenizer
    ↓
TemplateToken(TEXT | COMMENT | ECHO | RAW_ECHO | DIRECTIVE)
    ↓
TemplateParser
    ↓
TemplateNode
    ↓
TemplateBlockParser
    ↓
AST minimo especializado
    ↓
TemplateNodeCompiler
    ↓
Compiled PHP
    ↓
CompiledViewStore
    ↓
PhpViewEngine / ViewRuntime
```

Capas reales implementadas:

* `TemplateSourceTokenizer` separa PHP inline vs HTML inline
* `TemplateTokenizer` tokeniza comentarios, echos, raw echos y directivas
* `TemplateParser` transforma tokens en nodos
* `TemplateBlockParser` eleva secuencias planas a nodos jerárquicos
* `TemplateNodeCompiler` compila el AST mínimo a PHP
* `CompiledViewStore` persiste y reutiliza vistas compiladas
* `PhpViewEngine` y `ViewRuntime` ejecutan layouts, sections, yields e includes

AST mínimo real actual:

* `IfNode`
* `ForelseNode`
* `SimpleBlockNode`
* `SectionNode`
* `IncludeNode`
* `ExtendsNode`
* `YieldNode`

Metadata y errores reales actuales:

* tokens y nodos conservan `line` y `column`
* errores estructurales incluyen ubicación real
* el compilador usa `TemplateParseException` y `DirectiveBalanceException`
* el runtime usa `ViewRenderException`

Pendiente respecto al diseño objetivo:

* lexer formal separado del tokenizer actual
* visitors dedicados por nodo
* transforms AST
* line mapping completo hacia el PHP compilado
* jerarquía AST más completa y especializada

---

# 1. Introducción

El Compiler Pipeline de VoltStack es el sistema encargado de transformar templates declarativos escritos en sintaxis Volt en código PHP compilado y optimizado.

El pipeline ha sido diseñado siguiendo principios de compiladores modernos utilizados en:

* compiladores de lenguajes
* motores SSR
* frameworks reactivos
* motores de templates
* sistemas AST-based

El objetivo es proporcionar:

* alto rendimiento
* extensibilidad
* transformaciones futuras
* soporte SSR
* análisis estructural
* optimización progresiva

---

# 2. Filosofía del Pipeline

VoltStack NO interpreta templates dinámicamente.

VoltStack compila templates previamente.

Esto permite:

* menor costo runtime
* renderizado más rápido
* cache inteligente
* optimización estática
* extensibilidad AST
* futuras capacidades reactivas

---

# 3. Flujo General

```text id="8k7wph"
Template (.volt)
    ↓
Loader
    ↓
Lexer
    ↓
Tokens
    ↓
Parser
    ↓
AST
    ↓
AST Visitors
    ↓
Compiler
    ↓
Compiled PHP
    ↓
Cache Writer
    ↓
Runtime Renderer
```

---

# 4. Etapas del Pipeline

---

# 4.1 Template Loader

Responsable de:

* localizar templates
* cargar contenido
* resolver namespaces
* validar existencia
* resolver layouts/includes

Entrada:

```text id="yjmm5e"
resources/views/home.volt
```

Salida:

```text id="mgmx4h"
Contenido raw del template
```

---

# 4.2 Lexer Stage

Responsable de tokenizar el template.

---

## Objetivos

* identificar directivas
* identificar echos
* identificar bloques
* identificar expresiones
* identificar texto plano

---

## Ejemplo

Entrada:

```volt id="p4b7gh"
@if($user)
    {{ $user->name }}
@endif
```

Salida:

```text id="n7l4zy"
T_IF
T_EXPRESSION($user)

T_ECHO_OPEN
T_VARIABLE($user->name)
T_ECHO_CLOSE

T_ENDIF
```

---

## Responsabilidades

El lexer NO debe:

* validar lógica
* interpretar expresiones
* compilar código

El lexer únicamente tokeniza.

---

# 4.3 Parser Stage

Responsable de transformar tokens en AST.

---

## Objetivos

* validar estructura
* construir nodos
* validar nesting
* detectar errores sintácticos

---

## Ejemplo

Entrada:

```text id="7w29iz"
T_IF
T_ECHO
T_ENDIF
```

Salida:

```text id="gsdzsl"
TemplateNode
 └── IfNode
      └── EchoNode
```

---

## Validaciones

El parser debe detectar:

* bloques sin cerrar
* directivas inválidas
* nesting incorrecto
* expresiones corruptas

---

# 4.4 AST Stage

Representa la estructura semántica completa del template.

---

## Objetivos

* desacoplar parsing y compilación
* permitir transforms
* permitir visitors
* preparar futuras optimizaciones

---

## Ejemplo AST

```text id="g7e91w"
TemplateNode
 ├── TextNode
 ├── IfNode
 │    └── EchoNode
 └── IncludeNode
```

---

## Futuras capacidades AST

La arquitectura AST debe permitir:

* hydration metadata
* islands architecture
* reactividad
* SSR transforms
* tree shaking
* partial compilation
* lazy boundaries

---

# 4.5 AST Visitors

Los visitors recorren el AST para transformaciones y compilación.

---

## Objetivos

* separar responsabilidades
* modularizar compilación
* permitir transforms futuros

---

## Ejemplo

```text id="fq8cf0"
IfNodeVisitor
EchoNodeVisitor
LoopNodeVisitor
```

---

## Flujo

```text id="mjlwm3"
AST
 ↓
Visitors
 ↓
PHP Fragments
```

---

# 4.6 Compiler Stage

Responsable de generar PHP compilado.

---

## Ejemplo

Entrada:

```volt id="mgj8r7"
@if($user)
```

Salida:

```php id="0hys6n"
<?php if($user): ?>
```

---

## Objetivos

El compiler debe:

* generar PHP limpio
* minimizar overhead
* preservar line mapping
* soportar debugging
* evitar código redundante

---

# 4.7 PHP Generator

Une todos los fragmentos generados.

---

## Resultado

```php id="axj9vn"
<?php if($user): ?>
    <?= e($user->name) ?>
<?php endif; ?>
```

---

# 4.8 Cache Writer

Responsable de persistir vistas compiladas.

---

## Objetivos

* evitar recompilación
* almacenar metadata
* administrar hashes
* invalidar cambios

---

## Ejemplo

```text id="u71zyw"
storage/framework/views/
```

---

# 4.9 Runtime Renderer

Responsable de ejecutar el template compilado.

---

## Funciones

* compartir variables
* renderizar layouts
* manejar secciones
* manejar includes
* output buffering

---

# 5. Arquitectura Interna

---

# 5.1 Pipeline Modular

Cada etapa debe ser independiente.

```text id="76kn1d"
Lexer
Parser
AST
Visitors
Compiler
Renderer
```

Esto permite:

* testing aislado
* reemplazo modular
* extensibilidad
* plugins futuros

---

# 5.2 Pipeline Stateless

El compiler debe ser stateless.

NO debe almacenar:

* contexto runtime
* estado global mutable
* referencias de request

---

# 5.3 Pipeline Immutable

Los nodos AST deben ser preferiblemente inmutables.

Beneficios:

* seguridad
* transforms predecibles
* paralelización futura

---

# 6. Flujo Completo Ejemplo

---

# Template

```volt id="apmz5x"
@if($user)
    Hello {{ $user->name }}
@endif
```

---

# Tokens

```text id="9p0khn"
T_IF
T_EXPRESSION($user)

T_TEXT("Hello ")

T_ECHO($user->name)

T_ENDIF
```

---

# AST

```text id="1j8jtv"
TemplateNode
 └── IfNode
      ├── TextNode
      └── EchoNode
```

---

# PHP Compilado

```php id="7p97ie"
<?php if($user): ?>
    Hello <?= e($user->name) ?>
<?php endif; ?>
```

---

# Render Final

```html id="ikbb7k"
Hello Francisco
```

---

# 7. Arquitectura de Performance

---

# 7.1 Compilación Incremental

Solo recompilar templates modificados.

---

# 7.2 Cache Persistente

Las vistas compiladas deben persistirse.

---

# 7.3 Lazy Compilation

Compilar únicamente vistas utilizadas.

---

# 7.4 Precompilación Futura

Permitir:

```bash id="r3g2bd"
php voltstack view:cache
```

---

# 8. Line Mapping

El compiler debe preservar referencias de líneas.

---

## Objetivo

Errores precisos:

```text id="89oknm"
Error in:
resources/views/home.volt

Line:
42
```

---

# 9. Error Pipeline

---

# 9.1 Lexer Errors

Ejemplo:

```text id="nwwmha"
Unexpected token.
```

---

# 9.2 Parser Errors

Ejemplo:

```text id="d1ol5i"
Unclosed @if directive.
```

---

# 9.3 Compiler Errors

Ejemplo:

```text id="x6y7ah"
Unable to compile directive.
```

---

# 10. Compatibilidad Futura

El pipeline debe prepararse para:

* Component AST
* Reactive AST
* Event Transforms
* Hydration Metadata
* SPA Runtime
* Islands Rendering
* Streaming SSR
* Async Rendering
* Concurrent Rendering

---

# 11. Estructura Recomendada

```text id="0h93vu"
src/
└── Quantum/
    └── View/
        ├── Compiler/
        │   ├── Loader/
        │   ├── Lexer/
        │   ├── Parser/
        │   ├── AST/
        │   ├── Visitors/
        │   ├── Compiler/
        │   ├── Generator/
        │   └── Pipeline/
        │
        ├── Runtime/
        │
        ├── Cache/
        │
        ├── Contracts/
        │
        └── Exceptions/
```

---

# 12. Objetivo Estratégico

El Compiler Pipeline representa la base tecnológica del runtime de VoltStack.

Toda futura funcionalidad del framework dependerá de esta arquitectura:

* componentes
* reactividad
* hidratación
* SPA
* runtime frontend
* SSR avanzado

Por ello, el pipeline debe ser:

* extensible
* desacoplado
* altamente optimizable
* enterprise-ready
* preparado para evolución progresiva
