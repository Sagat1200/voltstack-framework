# 01_DIRECTIVES_ARCHITECTURE.md

# VoltStack Directive System Architecture

---

# Estado Actual Implementado

Antes de leer este documento como contrato técnico, conviene distinguir entre:

* arquitectura objetivo
* arquitectura actualmente implementada

Hoy el framework YA implementa una arquitectura funcional y modular, pero todavía no la arquitectura completa descrita más abajo.

Estado real actual:

* existe compilación previa a PHP, no interpretación runtime
* existe separación entre compiler y runtime
* existe `DirectiveRegistry` para directivas core y custom
* existe soporte para directivas con guiones, por ejemplo `@tailwind-vite`
* existe un pipeline mínimo con segmentación de fuente, tokenización inline, parseo, parser de bloques y compilación de nodos
* existe un AST mínimo con nodos especializados como `IfNode`, `ForelseNode`, `SectionNode`, `IncludeNode`, `ExtendsNode` y `YieldNode`
* existe cache de vistas compiladas y runtime para layouts, sections e includes
* existe metadata `line/column` en tokens y nodos
* existen excepciones especializadas del subsistema de vistas

Limitaciones actuales:

* no existe un lexer independiente de propósito general como capa formal separada
* no existe todavía un AST completo con visitors dedicados por nodo
* no existe `DirectiveResolver` separado del registry
* no existe line mapping completo hacia PHP compilado, solo metadata de origen en el pipeline
* varias secciones de este documento describen la dirección objetivo, no el estado V1 real

Referencia real del código actual:

* [ViewCompiler.php](file:///c:/W4/Packages/VoltStack/voltstack-framework/src/Quantum/View/Compilers/ViewCompiler.php)
* [TemplateSourceTokenizer.php](file:///c:/W4/Packages/VoltStack/voltstack-framework/src/Quantum/View/Compilers/TemplateSourceTokenizer.php)
* [TemplateTokenizer.php](file:///c:/W4/Packages/VoltStack/voltstack-framework/src/Quantum/View/Compilers/TemplateTokenizer.php)
* [TemplateParser.php](file:///c:/W4/Packages/VoltStack/voltstack-framework/src/Quantum/View/Compilers/TemplateParser.php)
* [TemplateBlockParser.php](file:///c:/W4/Packages/VoltStack/voltstack-framework/src/Quantum/View/Compilers/TemplateBlockParser.php)
* [TemplateNodeCompiler.php](file:///c:/W4/Packages/VoltStack/voltstack-framework/src/Quantum/View/Compilers/TemplateNodeCompiler.php)
* [DirectiveRegistry.php](file:///c:/W4/Packages/VoltStack/voltstack-framework/src/Quantum/View/Directives/DirectiveRegistry.php)
* [PhpViewEngine.php](file:///c:/W4/Packages/VoltStack/voltstack-framework/src/Quantum/View/PhpViewEngine.php)

---

# 1. Introducción

El sistema de directivas de VoltStack representa el núcleo arquitectónico del motor de vistas del framework.

Su responsabilidad principal es transformar templates declarativos escritos mediante directivas en código PHP optimizado y ejecutable.

La arquitectura ha sido diseñada para:

* alto rendimiento
* extensibilidad
* compilación incremental
* soporte SSR
* evolución futura hacia reactividad
* hidratación progresiva
* arquitectura SPA híbrida
* componentes desacoplados

---

# 2. Objetivos Arquitectónicos

El sistema debe:

* separar parsing y rendering
* evitar lógica runtime innecesaria
* compilar templates a PHP puro optimizado
* permitir extensibilidad mediante directivas personalizadas
* soportar futuras transformaciones AST
* permitir compilación parcial
* soportar cache inteligente
* minimizar expresiones regex complejas
* mantener compatibilidad empresarial

---

# 3. Filosofía del Sistema

VoltStack NO interpreta templates en tiempo real.

VoltStack compila templates.

Esto significa:

```text
Template Volt
    ↓
Compiler
    ↓
PHP Compilado
    ↓
Runtime Renderer
```

La compilación previa permite:

* mayor rendimiento
* menor consumo runtime
* mejor cache
* optimización estática
* análisis estructural
* debugging avanzado

---

# 4. Arquitectura General

```text
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
```

---

# 5. Capas del Sistema

---

# 5.1 Template Layer

Responsable de:

* cargar archivos .volt
* resolver layouts
* resolver includes
* administrar namespaces de vistas

Ejemplo:

```text
resources/views/home.volt
```

---

# 5.2 Lexer Layer

Responsable de tokenizar el template.

Entrada:

```volt
@if($user)
```

Salida:

```text
T_DIRECTIVE_IF
T_EXPRESSION
```

El lexer NO interpreta lógica.

Únicamente identifica tokens.

---

# 5.3 Parser Layer

Responsable de transformar tokens en nodos AST.

Ejemplo:

```text
IfNode
 └── EchoNode
```

El parser valida:

* bloques abiertos/cerrados
* estructura sintáctica
* nesting
* expresiones inválidas

---

# 5.4 AST Layer

Representa el árbol estructural del template.

Ejemplo:

```text
TemplateNode
 ├── IfNode
 │    └── EchoNode
 ├── ForeachNode
 └── IncludeNode
```

El AST será la base futura para:

* reactividad
* hydration metadata
* compilación parcial
* optimizaciones
* islands architecture
* transformaciones SSR

---

# 5.5 Compiler Layer

Convierte el AST en PHP optimizado.

Ejemplo:

```volt
@if($user)
```

↓

```php
<?php if($user): ?>
```

El compiler debe:

* minimizar overhead
* generar código limpio
* mantener line mapping
* soportar debugging

---

# 5.6 Runtime Renderer

Responsable de:

* renderizar vistas compiladas
* inyectar variables
* administrar contexto
* manejar secciones/layouts
* renderizar includes
* ejecutar templates

El runtime NO debe parsear templates.

Solo ejecuta código compilado.

---

# 5.7 Cache Layer

Responsable de:

* almacenar vistas compiladas
* invalidar templates modificados
* manejar hashes/checksums
* evitar recompilaciones innecesarias

---

# 6. Arquitectura de Directivas

---

# 6.1 Registry Centralizado

Todas las directivas serán registradas mediante un registry.

Ejemplo:

```php
Directive::register(
    'if',
    IfDirective::class
);
```

---

# 6.2 Resolución de Directivas

El parser resolverá directivas mediante:

```text
DirectiveRegistry
    ↓
DirectiveResolver
    ↓
DirectiveCompiler
```

---

# 6.3 Tipos de Directivas

La arquitectura debe soportar:

| Tipo                 | Ejemplo           |
| -------------------- | ----------------- |
| Block Directive      | @if               |
| Inline Directive     | @include          |
| Echo Directive       | {{ }}             |
| Raw Directive        | {!! !!}           |
| Structural Directive | @extends          |
| Runtime Directive    | futuras versiones |

---

# 7. Arquitectura de Tokens

---

# 7.1 Tokens Base

```text
T_TEXT
T_DIRECTIVE
T_ECHO
T_RAW_ECHO
T_OPEN_BLOCK
T_CLOSE_BLOCK
T_EXPRESSION
```

---

# 7.2 Tokens Especializados

```text
T_IF
T_ELSE
T_ELSEIF
T_ENDIF
T_FOREACH
T_ENDFOREACH
T_INCLUDE
```

---

# 8. Arquitectura AST

---

# 8.1 Nodos Base

```text
Node
 ├── TemplateNode
 ├── TextNode
 ├── EchoNode
 ├── RawEchoNode
 ├── DirectiveNode
```

---

# 8.2 Nodos Estructurales

```text
ConditionalNode
LoopNode
IncludeNode
LayoutNode
SectionNode
YieldNode
```

---

# 9. Sistema de Compiler

---

# 9.1 Compiler Pipeline

```text
AST
 ↓
Node Visitors
 ↓
Directive Transformers
 ↓
PHP Generator
 ↓
Compiled Output
```

---

# 9.2 Node Visitors

Cada nodo será compilado mediante visitors especializados.

Ejemplo:

```text
IfNodeVisitor
ForeachNodeVisitor
EchoNodeVisitor
```

---

# 10. Arquitectura del Runtime

---

# 10.1 Responsabilidades

El runtime debe:

* ejecutar templates compilados
* compartir variables
* manejar stacks de layouts
* manejar secciones
* manejar includes
* manejar output buffering

---

# 10.2 Output Buffering

VoltStack utilizará buffering controlado:

```php
ob_start();
```

para:

* layouts
* sections
* nested rendering
* streaming futuro

---

# 11. Sistema de Cache

---

# 11.1 Estrategia

Cada vista compilada tendrá:

* hash del template
* checksum
* timestamp
* metadata

---

# 11.2 Flujo

```text
Template Changed?
    ↓
YES → Recompile
NO  → Use Cache
```

---

# 12. Manejo de Errores

El sistema debe detectar:

* bloques no cerrados
* directivas inválidas
* includes inexistentes
* layouts inválidos
* expresiones corruptas

Ejemplo:

```text
Unclosed @foreach directive found.

File:
resources/views/users/index.volt

Line:
52
```

---

# 13. Extensibilidad

La arquitectura debe permitir:

* directivas custom
* plugins
* macros
* visitors personalizados
* transforms AST
* compiladores adicionales

---

# 14. Compatibilidad Futura

La arquitectura V1 debe prepararse para:

* V2 Component System
* V3 Reactivity
* V4 Event System
* V5 SPA Engine
* V6 Hydration
* SSR Streaming
* Islands Architecture
* Async Rendering
* Partial Compilation

---

# 15. Estructura Recomendada

```text
src/
└── Quantum/
    └── View/
        ├── Compiler/
        │   ├── Lexer/
        │   ├── Parser/
        │   ├── AST/
        │   ├── Compiler/
        │   ├── Visitors/
        │   └── Tokens/
        │
        ├── Directives/
        │   └── Core/
        │
        ├── Runtime/
        │
        ├── Cache/
        │
        ├── Contracts/
        │
        ├── Exceptions/
        │
        └── Support/
```

---

# 16. Objetivo Estratégico

La arquitectura del sistema de directivas representa el inicio del runtime fullstack de VoltStack.

La meta final es construir:

* un motor SSR moderno
* una arquitectura híbrida SPA/MPA
* un runtime reactivo PHP-first
* un sistema de componentes desacoplado
* una plataforma enterprise-ready

El sistema V1 será la base sobre la cual evolucionará todo el ecosistema del framework.
