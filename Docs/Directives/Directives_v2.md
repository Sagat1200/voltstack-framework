Estado actual del primer slice implementado

Hoy ya existe una primera base funcional para V2, pero todavía NO está implementada la lista completa de este documento.

Implementado actualmente:

* `@component('nombre') ... @endcomponent`
* `@component('nombre', ['prop' => valor])`
* `@props([...])` dentro de la vista del componente
* `@slot('nombre') ... @endslot` dentro de `@component`
* `@dynamic($component)` y `@dynamic($component, ['prop' => valor])`
* `@attributes([...])` dentro de la vista del componente
* `@class([...])` para listas condicionales de clases
* `@style([...])` para listas condicionales de estilos inline
* `@scope ... @endscope` para aislamiento local de variables
* `@extendsComponent('nombre')` y `@extendsComponent('nombre', ['prop' => valor])` para envolver la salida del componente hijo en un componente padre
* `@renderMode('server')` y `@renderMode('interactive')` para controlar cómo se renderizan los componentes desde una vista
* tags cortos básicos tipo `<x-button />` y `<x-card>...</x-card>` transformados al pipeline actual de directivas
* named slots en component tags con sintaxis `<x-slot:nombre>...</x-slot:nombre>`
* namespaces avanzados en component tags tipo `<x-ui:button />` y `<x-ui:panel>...</x-ui:panel>`
* render de componentes clase+vista usando `VoltStack\Runtime\Component\ComponentManager`
* resolución de componentes por nombre corto hacia el namespace configurado en `ui-reactive.class_view_components`
* soporte del slot principal mediante la propiedad pública `$slot`
* soporte de slots nombrados como variables simples y también en `$slots`
* defaults simples y props opcionales dentro de la vista del componente
* `ComponentAttributeBag` con merge básico de defaults y concatenación de clases
* normalización compartida de clases entre `@class` y `ComponentAttributeBag`
* normalización compartida de estilos entre `@style` y `ComponentAttributeBag`
* aislamiento de asignaciones dentro de `@scope` sin contaminar el contexto exterior
* composición padre/hijo en vistas de componentes mediante `@extendsComponent`, usando el output del hijo como `slot` del padre
* `@renderMode('interactive')` hace que `@component` y `@dynamic` rendericen roots reactivos con `data-volt-root` y snapshot
* los component tags cortos hoy soportan props simples, atributos HTML básicos, slot principal, named slots con `x-slot:nombre` y namespaces tipo `ui:button`
* compilación estructural del bloque `@component` dentro del pipeline actual de vistas

Pendiente todavía:

* sintaxis HTML más rica para atributos/slots complejos y namespaces externos configurables

Referencia real del primer slice:

* `src/Quantum/View/Runtime/ViewRuntime.php`
* `src/Runtime/Component/ComponentManager.php`
* `src/Quantum/View/Compilers/TemplateBlockParser.php`
* `src/Quantum/View/Compilers/TemplateDirectiveCompiler.php`
* `src/Quantum/View/Directives/DirectiveRegistry.php`

---

Para la V2 de VoltStack, el enfoque correcto es introducir el Component System, pero todavía sin entrar en reactividad compleja (eso queda para V3).

La V2 debe enfocarse en:

composición UI
encapsulación
slots
props
layouts reutilizables
renderización desacoplada
integración futura con SSR/hydration
Objetivo de la V2

Construir un sistema de componentes moderno inspirado en:

Blade
Vue.js
React
Svelte
Astro

pero diseñado para PHP-first SSR.

V2 — Lista Oficial de Directivas de Componentes

1. Directivas de Componentes Base
@component

Renderiza un componente manualmente.

@component('button')
@endcomponent
@endcomponent

Cierre de componente.

@endcomponent
Sintaxis corta tipo JSX/Blade
<x-button />
2. Directivas de Props
@props

Define propiedades del componente.

@props([
    'title',
    'size' => 'md'
])
Objetivo
props tipadas futuras
defaults
validación
hydration futura
3. Slots
@slot

Define slots nombrados.

@slot('header')
@endslot
Uso
<x-card>
    @slot('header')
        Title
    @endslot
</x-card>
@endslot

Cierre del slot.

@endslot
4. Slot Principal
$slot

Contenido principal del componente.

<div>
    {{ $slot }}
</div>
5. Dynamic Components
@dynamic

Renderiza componentes dinámicos.

@dynamic($component)
Ejemplo
@dynamic('button')
6. Component Attributes
@attributes

Merge de atributos HTML.

<div {{ $attributes }}>
Objetivo
class merge
attribute forwarding
runtime extensible
7. Attribute Merge
@class

Merge inteligente de clases.

@class([
    'btn',
    'btn-primary' => $primary
])
Compilación conceptual
class="btn btn-primary"
8. Style Merge
@style

Merge dinámico de estilos.

@style([
    'color: red' => $danger
])
9. Component Namespaces

Preparación para librerías UI.

Sintaxis futura
<x-ui:button />
<x-form:input />
10. Inline Components
@inline

Componentes inline renderizados directamente.

@inline('badge')
11. Anonymous Components
@anonymous

Definición de componentes sin clase.

@anonymous('alert')
12. Component Includes
@render

Render explícito.

@render('components.button')
13. Conditional Components
@show

Render condicional simplificado.

@show($visible)
14. Fragment Components

Preparación SSR futuro.

@fragment

Define fragmentos reutilizables.

@fragment('header')
15. Component Metadata
@meta

Metadata futura del componente.

@meta([
    'hydrate' => true
])
16. Component Config
@config

Configuración local del componente.

@config([
    'theme' => 'dark'
])
17. Scoped Variables
@scope

Aislamiento de variables.

@scope
@endscope
18. Component Lifecycle (Preparación)

Aunque V3 manejará runtime reactivo, puedes preparar:

@mount
@mount
@unmount
@unmount
19. Component Inheritance
@extendsComponent

Herencia de componentes.

@extendsComponent('card')
20. Render Modes

Preparación SSR futura.

@renderMode
@renderMode('server')
Clasificación Recomendada
Categoría Directivas
Core Components @component @endcomponent
Props @props
Slots @slot @endslot
Dynamic @dynamic
Attributes @attributes @class @style
Fragments @fragment
Config @meta @config
Scope @scope
Runtime Prep @mount @unmount
Mi recomendación REAL para V2

No implementes todo desde el inicio.

V2 Inicial Recomendada
PRIORIDAD ALTA
@component
@endcomponent
@props
@slot
@endslot
@dynamic
@attributes
@class
V2.1
@style
@fragment
@config
V2.2
@scope
@extendsComponent
@renderMode
Lo más importante arquitectónicamente

La V2 ya necesita nuevos nodos AST:

ComponentNode
SlotNode
PropNode
AttributeNode
DynamicComponentNode

Y también nuevos visitors:

ComponentVisitor
SlotVisitor
AttributeVisitor
