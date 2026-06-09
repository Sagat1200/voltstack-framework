# 05_AST_MODEL.md

# VoltStack AST Model

---

# Estado Actual Implementado

Este documento describía una arquitectura AST mucho más amplia que la implementación real actual. Hoy VoltStack ya no compila todo directamente desde texto plano, pero tampoco tiene todavía un subsistema AST completo con visitors, contracts separados, nodos por cada construcción y múltiples passes de transformación.

Estado real actual:

* existe un AST mínimo y especializado, suficiente para desacoplar tokenización, parseo estructural y compilación
* el nodo base real es `TemplateNode`
* el parser genera primero una secuencia plana de `TemplateNode`
* `TemplateBlockParser` transforma esa secuencia plana en una jerarquía mínima para bloques y estructuras
* `TemplateNodeCompiler` compila los nodos y delega la semántica final de directivas en `TemplateDirectiveCompiler`
* el sistema ya preserva metadata `line` y `column` en tokens y nodos
* existen nodos especializados solo donde ya aportan valor real hoy

Lo que NO existe todavía:

* una jerarquía completa con `TextNode`, `EchoNode`, `RawEchoNode`, `ElseNode`, `ElseIfNode`, `ForeachNode`, `ForNode` y `WhileNode` como clases dedicadas
* un subsistema de visitors
* transforms de AST
* metadata arbitraria por nodo
* relaciones `parent`, `siblings` o traversal genérico
* un namespace `AST/` separado del compilador actual

Referencia real del código actual:

* [TemplateNode.php](file:///c:/W4/Packages/VoltStack/voltstack-framework/src/Quantum/View/Compilers/TemplateNode.php)
* [TemplateParser.php](file:///c:/W4/Packages/VoltStack/voltstack-framework/src/Quantum/View/Compilers/TemplateParser.php)
* [TemplateBlockParser.php](file:///c:/W4/Packages/VoltStack/voltstack-framework/src/Quantum/View/Compilers/TemplateBlockParser.php)
* [TemplateNodeCompiler.php](file:///c:/W4/Packages/VoltStack/voltstack-framework/src/Quantum/View/Compilers/TemplateNodeCompiler.php)
* [IfNode.php](file:///c:/W4/Packages/VoltStack/voltstack-framework/src/Quantum/View/Compilers/IfNode.php)
* [ForelseNode.php](file:///c:/W4/Packages/VoltStack/voltstack-framework/src/Quantum/View/Compilers/ForelseNode.php)
* [SimpleBlockNode.php](file:///c:/W4/Packages/VoltStack/voltstack-framework/src/Quantum/View/Compilers/SimpleBlockNode.php)
* [SectionNode.php](file:///c:/W4/Packages/VoltStack/voltstack-framework/src/Quantum/View/Compilers/SectionNode.php)
* [IncludeNode.php](file:///c:/W4/Packages/VoltStack/voltstack-framework/src/Quantum/View/Compilers/IncludeNode.php)
* [ExtendsNode.php](file:///c:/W4/Packages/VoltStack/voltstack-framework/src/Quantum/View/Compilers/ExtendsNode.php)
* [YieldNode.php](file:///c:/W4/Packages/VoltStack/voltstack-framework/src/Quantum/View/Compilers/YieldNode.php)

---

# 1. Introducción

El AST actual de VoltStack representa una capa intermedia mínima entre los tokens del template y el PHP compilado.

Su objetivo hoy es:

* separar el parseo básico de la compilación
* construir jerarquías mínimas para bloques reales como `@if`, `@forelse` y `@section`
* preservar ubicación de origen para mejores errores
* permitir evolución incremental del compilador sin volver al enfoque monolítico anterior

No es todavía un AST "completo" en sentido académico o de framework maduro. Es un AST pragmático, enfocado en las necesidades actuales del motor de vistas.

---

# 2. Flujo Actual

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
TemplateNode[] plano
    ↓
TemplateBlockParser
    ↓
AST minimo jerarquico
    ↓
TemplateNodeCompiler
    ↓
Compiled PHP
```

Punto importante:

* `TemplateParser` no construye por sí solo toda la jerarquía final
* la estructura de bloques se arma en una segunda etapa: `TemplateBlockParser`

---

# 3. Nodo Base Real

El nodo base real es `TemplateNode`. No existe hoy una clase abstracta `Node` separada ni un árbol completo de subtipos por cada construcción del lenguaje.

Propiedades reales actuales de `TemplateNode`:

| Propiedad | Descripción |
| --------- | ----------- |
| `type` | Tipo de token o nodo (`text`, `comment`, `echo`, `raw_echo`, `directive`, `block`) |
| `value` | Valor crudo del nodo cuando aplica |
| `name` | Nombre de la directiva o bloque |
| `expression` | Expresión asociada a la directiva |
| `children` | Hijos principales del bloque |
| `alternateChildren` | Hijos alternos, usados por ejemplo en `else` o `empty` |
| `branches` | Ramas adicionales, hoy usadas para `elseif` |
| `line` | Línea original |
| `column` | Columna original |

Lo que `TemplateNode` no modela hoy:

* `parent`
* `attributes`
* `source` completo del fragmento
* visitors
* contracts de traversal

---

# 4. Tipos de Nodo Reales

El modelo actual combina dos ideas:

* nodos genéricos basados en `TemplateNode`
* nodos especializados solo en los casos donde la estructura o compilación ya lo justifican

---

# 4.1 Nodos Simples

Se crean usando factories estáticas sobre `TemplateNode`:

* `TemplateNode::text(...)`
* `TemplateNode::comment(...)`
* `TemplateNode::echo(...)`
* `TemplateNode::rawEcho(...)`
* `TemplateNode::directive(...)`
* `TemplateNode::block(...)`

Ejemplos conceptuales:

```text
TemplateNode(type: text, value: "Hello")
TemplateNode(type: echo, expression: "$name")
TemplateNode(type: directive, name: "php", expression: "$x = 1;")
```

Importante:

* texto, comentarios, echos y raw echos no tienen hoy clases dedicadas como `TextNode` o `EchoNode`
* siguen representándose mediante `TemplateNode`

---

# 4.2 Nodos Especializados de Directiva

Hoy existen clases concretas para directivas estructurales no-bloque:

* `IncludeNode`
* `ExtendsNode`
* `YieldNode`

Estas clases siguen heredando de `TemplateNode`, pero fijan `type`, `name` y la forma esperada del nodo.

Ejemplo conceptual:

```text
IncludeNode(expression: "'partials.header'")
ExtendsNode(expression: "'layouts.app'")
YieldNode(expression: "'content'")
```

---

# 4.3 Nodos Especializados de Bloque

Hoy existen estas clases concretas para bloques:

* `IfNode`
* `ForelseNode`
* `SimpleBlockNode`
* `SectionNode`

Responsabilidades actuales:

* `IfNode`: modela `@if`, sus `children`, sus ramas `elseif` en `branches` y su `else` en `alternateChildren`
* `ForelseNode`: modela `@forelse` y su rama `@empty`
* `SimpleBlockNode`: cubre bloques simples como `@foreach`, `@for`, `@while`, `@unless`, `@isset`, `@empty(...)`
* `SectionNode`: especializa `@section` sobre `SimpleBlockNode`

Ejemplo conceptual para `@if`:

```text
IfNode
 ├── expression: "$user"
 ├── children
 ├── branches
 │    └── { expression: "$admin", children: [...] }
 └── alternateChildren
```

Ejemplo conceptual para `@forelse`:

```text
ForelseNode
 ├── expression: "$users as $user"
 ├── children
 └── alternateChildren
```

---

# 5. Construcción Jerárquica Real

La jerarquía del AST actual no sale completa desde el tokenizer ni desde `TemplateParser`.

El flujo real es:

1. `TemplateTokenizer` produce `TemplateToken`
2. `TemplateParser` convierte cada token en un `TemplateNode` plano
3. `TemplateBlockParser` detecta aperturas/cierres y construye bloques jerárquicos

`TemplateBlockParser` soporta hoy:

* `if / elseif / else / endif`
* `forelse / empty / endforelse`
* `unless / endunless`
* `isset / endisset`
* `empty(expr) / endempty`
* `foreach / endforeach`
* `for / endfor`
* `while / endwhile`
* `section / endsection`

Esto significa que hoy no existe una clase dedicada para cada apertura/cierre intermedio. Por ejemplo:

* `@elseif` no se convierte en un `ElseIfNode`
* `@else` no se convierte en un `ElseNode`
* ambas estructuras quedan absorbidas dentro de `IfNode`

---

# 6. Ejemplo Real del AST Actual

Template:

```volt
@if($user)
    Hello {{ $user->name }}
@elseif($admin)
    Admin
@else
    Guest
@endif
```

AST conceptual actual:

```text
IfNode
 ├── expression: "$user"
 ├── children
 │    ├── TemplateNode(type: text, value: "    Hello ")
 │    └── TemplateNode(type: echo, expression: "$user->name")
 ├── branches
 │    └── { expression: "$admin", children: [TemplateNode(type: text, value: "    Admin")] }
 └── alternateChildren
      └── TemplateNode(type: text, value: "    Guest")
```

Template:

```volt
@section('content')
    @include('partials.header')
@endsection
```

AST conceptual actual:

```text
SectionNode
 ├── expression: "'content'"
 └── children
      └── IncludeNode(expression: "'partials.header'")
```

---

# 7. Compilación del AST

La compilación del AST actual ocurre en `TemplateNodeCompiler`.

Responsabilidades reales:

* compilar nodos simples como texto, comentarios y echos
* compilar nodos especializados como `IncludeNode`, `ExtendsNode` y `YieldNode`
* compilar bloques jerárquicos como `IfNode`, `ForelseNode` y `SimpleBlockNode`
* delegar la semántica de directivas en `TemplateDirectiveCompiler`

Esto significa que el AST actual ya desacopla estructura y compilación, pero todavía no introduce una fase separada de visitors o transforms.

Ejemplo conceptual:

```text
IfNode
   ↓
TemplateNodeCompiler
   ↓
@if + children + @elseif + @else + @endif
   ↓
TemplateDirectiveCompiler
   ↓
Compiled PHP
```

---

# 8. Metadata y Errores

Una de las mejoras reales del AST/pipeline actual es la preservación de ubicación de origen.

Cada `TemplateNode` conserva:

* `line`
* `column`

Esto permite que errores estructurales y de compilación apunten al lugar correcto del template.

Ejemplos de uso real:

* `TemplateParseException`
* `DirectiveBalanceException`

Casos típicos:

* `Unclosed @if directive at line X, column Y.`
* `The @forelse directive requires an @empty block at line X, column Y.`

---

# 9. Limitaciones Actuales

La implementación actual todavía no ofrece:

* nodos concretos para cada tipo de texto, echo, comentario o loop
* un `TemplateNode` raíz que envuelva siempre todo el template como objeto independiente
* transforms del AST
* visitors
* optimizaciones por passes
* metadata enriquecida más allá de `line` y `column`
* un modelo genérico de traversal

Esto es intencional en el estado actual: primero se consolidó un AST mínimo útil antes de ampliar la arquitectura.

---

# 10. Dirección Evolutiva

La arquitectura actual ya deja una base razonable para crecer de forma incremental hacia:

* más nodos especializados cuando aporten claridad real
* separación mayor entre parseo estructural y compilación
* futuras transformaciones de AST
* mejor análisis estático
* componentes, slots o features reactivas más adelante

Pero hoy el contrato correcto de este documento es describir el AST mínimo que realmente existe, no el roadmap completo como si ya estuviera implementado.
