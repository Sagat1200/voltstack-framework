# 05_AST_MODEL.md

# VoltStack AST Model

---

# 1. Introducción

El AST (Abstract Syntax Tree) representa la estructura semántica interna de los templates de VoltStack.

El AST es una de las piezas más importantes de toda la arquitectura del framework, ya que desacopla:

* parsing
* compilación
* rendering
* transformaciones
* optimizaciones futuras

En lugar de compilar templates directamente desde texto, VoltStack transforma los templates en un árbol estructurado de nodos.

---

# 2. Objetivos del AST

El sistema AST debe:

* representar templates estructuralmente
* desacoplar parser y compiler
* permitir transforms futuros
* soportar visitors
* permitir optimizaciones
* permitir análisis estático
* soportar reactividad futura
* soportar hydration metadata
* soportar SSR avanzado

---

# 3. Filosofía Arquitectónica

El AST debe representar intención semántica, NO solamente texto.

Ejemplo:

```volt id="mgk4s0"
@if($user)
    {{ $user->name }}
@endif
```

NO debe convertirse directamente en PHP.

Primero debe convertirse en:

```text id="mgk5x1"
TemplateNode
 └── IfNode
      └── EchoNode
```

---

# 4. Flujo Arquitectónico

```text id="mgk8v2"
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
Visitors
   ↓
Compiler
```

---

# 5. Arquitectura General del AST

---

# 5.1 Node Base

Todos los nodos deben extender un nodo base.

---

## Responsabilidades

* metadata
* line mapping
* hijos
* atributos
* visitors

---

## Ejemplo Conceptual

```text id="mgk6b4"
Node
 ├── TemplateNode
 ├── TextNode
 ├── EchoNode
 ├── DirectiveNode
 └── BlockNode
```

---

# 5.2 Node Metadata

Cada nodo debe contener:

| Propiedad  | Descripción        |
| ---------- | ------------------ |
| type       | Tipo de nodo       |
| line       | Línea original     |
| column     | Columna            |
| children   | Nodos hijos        |
| attributes | Metadata           |
| source     | Fragmento original |

---

# 6. Nodos Base

---

# 6.1 TemplateNode

Representa el template completo.

---

## Responsabilidades

* nodo raíz
* contener hijos principales

---

## Ejemplo

```text id="mgk9y0"
TemplateNode
 ├── TextNode
 └── IfNode
```

---

# 6.2 TextNode

Representa texto plano.

---

## Ejemplo

```volt id="mgk0r1"
Hello World
```

---

## AST

```text id="mgk2u4"
TextNode("Hello World")
```

---

# 6.3 EchoNode

Representa output escapado.

---

## Ejemplo

```volt id="mgk7m5"
{{ $name }}
```

---

## AST

```text id="mgk8p6"
EchoNode(
    expression: "$name"
)
```

---

# 6.4 RawEchoNode

Representa output raw.

---

## Ejemplo

```volt id="mgk3d7"
{!! $html !!}
```

---

## AST

```text id="mgk1q8"
RawEchoNode(
    expression: "$html"
)
```

---

# 7. Nodos Condicionales

---

# 7.1 IfNode

Representa estructuras @if.

---

## Ejemplo

```volt id="mgk9w0"
@if($user)
@endif
```

---

## AST

```text id="mgk4r2"
IfNode
 ├── condition: "$user"
 └── children
```

---

# 7.2 ElseIfNode

Representa @elseif.

---

# 7.3 ElseNode

Representa @else.

---

# 7.4 UnlessNode

Representa @unless.

---

# 8. Nodos de Loops

---

# 8.1 ForeachNode

---

## Ejemplo

```volt id="mgk5t4"
@foreach($users as $user)
@endforeach
```

---

## AST

```text id="mgk8g6"
ForeachNode
 ├── expression
 └── children
```

---

# 8.2 ForelseNode

Representa loops con fallback.

---

# 8.3 ForNode

Representa @for.

---

# 8.4 WhileNode

Representa @while.

---

# 9. Nodos Estructurales

---

# 9.1 IncludeNode

Representa @include.

---

## AST

```text id="mgk3v7"
IncludeNode(
    view: "partials.header"
)
```

---

# 9.2 ExtendsNode

Representa layouts padre.

---

# 9.3 SectionNode

Representa secciones.

---

# 9.4 YieldNode

Representa puntos de inserción.

---

# 10. Nodos PHP

---

# 10.1 PhpNode

Representa bloques @php.

---

## AST

```text id="mgk9b3"
PhpNode(
    code: "$name = 'Volt';"
)
```

---

# 11. Nodos de Comentarios

---

# 11.1 CommentNode

Representa comentarios internos.

No deben compilarse.

---

# 12. Arquitectura Jerárquica

---

# 12.1 Estructura Completa

```text id="mgk6n0"
Node
 ├── TemplateNode
 ├── TextNode
 ├── EchoNode
 ├── RawEchoNode
 │
 ├── ConditionalNode
 │    ├── IfNode
 │    ├── ElseIfNode
 │    ├── ElseNode
 │    └── UnlessNode
 │
 ├── LoopNode
 │    ├── ForeachNode
 │    ├── ForelseNode
 │    ├── ForNode
 │    └── WhileNode
 │
 ├── StructuralNode
 │    ├── IncludeNode
 │    ├── ExtendsNode
 │    ├── SectionNode
 │    └── YieldNode
 │
 ├── PhpNode
 │
 └── CommentNode
```

---

# 13. Node Relationships

Los nodos deben soportar:

* parent node
* child nodes
* sibling nodes
* traversal

---

# 14. AST Traversal

El AST debe soportar recorridos mediante visitors.

---

# Ejemplo

```text id="mgk7j9"
AST
 ↓
Visitor
 ↓
Transformation
```

---

# 15. Visitors

---

# 15.1 Objetivos

Los visitors permiten:

* compilación
* optimización
* transforms
* análisis

---

# 15.2 Ejemplo

```text id="mgk4e8"
IfNodeVisitor
EchoNodeVisitor
LoopNodeVisitor
```

---

# 16. AST Immutability

Los nodos deben ser preferiblemente inmutables.

---

# Beneficios

* predictibilidad
* seguridad
* transforms seguros
* compilación concurrente futura

---

# 17. AST Metadata

Cada nodo puede almacenar metadata adicional.

---

# Ejemplos

```text id="mgk1m7"
line
column
source
directive
expression
runtimeFlags
```

---

# 18. Line Mapping

El AST debe preservar referencias exactas.

---

# Objetivo

Errores precisos:

```text id="mgk6c5"
resources/views/home.volt
Line: 52
```

---

# 19. Compatibilidad Futura

La arquitectura AST debe prepararse para:

* ComponentNode
* SlotNode
* PropNode
* ReactiveNode
* EventNode
* HydrationNode
* SPA Node
* IslandNode
* AsyncNode

---

# 20. AST Transformations

La arquitectura debe permitir transforms posteriores.

---

# Ejemplo

```text id="mgk8f0"
AST
 ↓
Optimization Pass
 ↓
Hydration Pass
 ↓
SSR Pass
```

---

# 21. Performance Goals

---

# 21.1 Lightweight Nodes

Los nodos deben minimizar memoria.

---

# 21.2 Fast Traversal

Traversal eficiente.

---

# 21.3 Lazy Children

Opcionalmente soportar hijos lazy futuros.

---

# 22. Error Handling

---

# 22.1 Invalid Tree

Detectar nodos corruptos.

---

# 22.2 Missing Children

Validar estructuras requeridas.

---

# 22.3 Invalid Nesting

Ejemplo:

```text id="mgk5z1"
@endif without @if
```

---

# 23. Estructura Recomendada

```text id="mgk3k8"
src/
└── Quantum/
    └── View/
        ├── AST/
        │   ├── Node/
        │   ├── Conditional/
        │   ├── Loop/
        │   ├── Structural/
        │   ├── Echo/
        │   ├── PHP/
        │   ├── Comments/
        │   ├── Contracts/
        │   ├── Visitors/
        │   └── Support/
```

---

# 24. Ejemplo Completo

---

# Template

```volt id="mgk9l2"
@if($user)
    Hello {{ $user->name }}
@endif
```

---

# AST

```text id="mgk7y3"
TemplateNode
 └── IfNode
      ├── condition: "$user"
      ├── TextNode("Hello ")
      └── EchoNode("$user->name")
```

---

# PHP Compilado

```php id="mgk2d6"
<?php if($user): ?>
    Hello <?= e($user->name) ?>
<?php endif; ?>
```

---

# 25. Objetivo Estratégico

El AST representa el núcleo evolutivo del runtime de VoltStack.

Toda futura capacidad avanzada dependerá de este sistema:

* componentes
* reactividad
* SPA
* hidratación
* SSR
* streaming
* islands architecture
* concurrent rendering

Por ello, el AST debe diseñarse desde el inicio como una arquitectura:

* extensible
* desacoplada
* optimizable
* enterprise-ready
* preparada para evolución progresiva
