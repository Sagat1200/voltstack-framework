# VoltStack

## Introducción

VoltStack es un framework PHP fullstack de nueva generación diseñado para construir aplicaciones SPA reactivas utilizando PHP como lenguaje principal de desarrollo.

El framework está inspirado filosóficamente en la productividad y experiencia de desarrollo de Laravel, combinando una arquitectura reactiva tipo Livewire con un runtime SPA moderno y persistente optimizado para FrankenPHP.

VoltStack busca eliminar la complejidad tradicional del ecosistema frontend moderno, permitiendo que los desarrolladores PHP construyan aplicaciones altamente reactivas, fluidas y modernas sin depender directamente de frameworks JavaScript complejos como React o Vue.

El objetivo principal del framework es ofrecer:

- Experiencia de desarrollo elegante.
- Arquitectura reactiva integrada desde el núcleo.
- Runtime SPA nativo.
- Estado reactivo sincronizado.
- Componentes server-driven.
- Navegación SPA sin recarga.
- Compatibilidad con runtimes persistentes.
- Optimización avanzada para FrankenPHP.
- Ecosistema modular basado en micro-paquetes internos.

---

## Filosofía

VoltStack adopta una filosofía basada en los siguientes principios:

### 1. PHP First

El desarrollador debe poder construir interfaces modernas utilizando principalmente PHP.

JavaScript existe internamente dentro del runtime del framework, pero no debe convertirse en una barrera obligatoria para construir aplicaciones modernas.

---

### 2. Reactive Native

La reactividad no es un complemento.

VoltStack nace como un framework reactivo desde su arquitectura principal.

El sistema de componentes, navegación, estado y rendering están diseñados alrededor de un runtime reactivo persistente.

---

### 3. SPA by Default

Todas las aplicaciones VoltStack son SPA reactivas desde el inicio.

No es necesario instalar herramientas adicionales para obtener:

- navegación SPA
- hydration
- rendering reactivo
- actualizaciones parciales
- preserve state
- transitions
- navegación fluida

---

### 4. Runtime Persistente

VoltStack está diseñado para aprovechar runtimes persistentes modernos como FrankenPHP.

El framework minimiza el costo de bootstrap tradicional de PHP mediante:

- containers persistentes
- registries en memoria
- metadata cache
- reflection cache
- hydration optimizada
- render pipelines persistentes

---

### 5. Developer Experience First

VoltStack prioriza:

- sintaxis elegante
- baja complejidad
- productividad
- convenciones claras
- estructura empresarial
- modularidad
- extensibilidad

---

## Objetivos del Framework

### Objetivos principales

- Crear un framework SPA reactivo moderno impulsado por PHP.
- Reducir la dependencia directa de frameworks frontend complejos.
- Mantener una experiencia similar a Laravel + Livewire.
- Ofrecer mejor rendimiento mediante runtimes persistentes.
- Permitir rendering reactivo sin configuración adicional.
- Facilitar desarrollo empresarial escalable.
- Proporcionar arquitectura modular desacoplada.
- Crear un ecosistema de micro-paquetes internos reutilizables.

---

## Arquitectura General

VoltStack se divide en múltiples capas principales:

```txt
VoltStack
├── Platform
├── Quantum
├── Support
├── Facades
├── Helpers
├── Testing
└── Runtime
```

---

## Estructura Principal del Framework

### src/Platform

Contiene la infraestructura central del framework.

Aquí viven las clases principales responsables del runtime general, bootstrap del framework y coordinación del ecosistema.

Ejemplos:

- Application
- RuntimeManager
- ReactiveKernel
- ComponentRegistry
- RuntimeDriverManager
- EnvironmentManager
- ModuleManager

---

### src/Facades

Contiene las fachadas estáticas del framework inspiradas en la experiencia de Laravel.

Las fachadas permiten acceso elegante y expresivo a servicios internos del container.

Ejemplos:

- Route
- View
- Runtime
- State
- Event
- Cache
- Config

---

### src/Helpers

Contiene funciones helper globales reutilizables del framework.

Ejemplos:

- app()
- config()
- runtime()
- state()
- env()
- public_path()
- base_path()

---

### src/Support

Contiene clases utilitarias y estructuras de soporte reutilizables.

Ejemplos:

- Collections
- String helpers
- Array helpers
- Metadata objects
- Attribute bags
- Runtime utilities
- Protocol utilities
- Reflection utilities

---

### src/Testing

Contiene herramientas internas de testing y utilidades para pruebas automatizadas.

Ejemplos:

- TestCase base
- Runtime testing tools
- Component testing utilities
- HTTP testing layer
- SPA navigation testing
- Reactive assertion tools

---

## src/Quantum

Quantum es el núcleo modular del framework.

Aquí viven todos los micro-paquetes internos desacoplados que conforman VoltStack.

Cada módulo Quantum puede evolucionar independientemente manteniendo cohesión interna.

---

## Filosofía de Quantum

Quantum busca dividir el framework en pequeñas unidades altamente mantenibles y desacopladas.

Cada paquete Quantum debe:

- tener responsabilidad única
- ser extensible
- ser reemplazable
- ser desacoplado
- ser compatible con runtime persistente

---

## Estructura Inicial de Quantum

```txt
Quantum
├── Actions
├── Bootstrap
├── Cache
├── Config
├── Concurrency
├── Container
├── Controllers
├── Events
├── Exceptions
├── Filesystem
├── Http
├── HttpKernel
├── Logging
├── Middlewares
├── Pipeline
├── Protocol
├── Queue
├── Reactive
├── Routing
├── Runtime
├── Session
├── Signals
├── State
├── Support
├── Validation
└── View
```

---

## Runtime Reactivo

VoltStack implementa un runtime reactivo integrado.

El runtime será responsable de:

- sincronización de estado
- hydration
- dehydrate
- SPA navigation
- rendering parcial
- event bridge
- effects system
- protocol transport
- diff engine
- DOM patching

---

## Runtime Frontend

VoltStack incluye un runtime frontend ligero que será responsable de:

- manejar navegación SPA
- sincronizar componentes
- aplicar efectos
- actualizar DOM parcialmente
- preservar estado
- manejar transitions
- escuchar eventos reactivos

El objetivo es minimizar la necesidad de escribir JavaScript manualmente.

---

## Integración con FrankenPHP

VoltStack está optimizado desde su arquitectura para funcionar con FrankenPHP.

Características planeadas:

- workers persistentes
- preload de componentes
- cache runtime
- metadata persistente
- reflection persistente
- component registry en memoria
- route cache persistente
- hydration optimizada
- runtime acceleration

---

## Compatibilidad de Runtime

VoltStack será compatible con múltiples drivers de ejecución:

```txt
Runtime Drivers
├── FrankenPHP
├── PHP-FPM
├── RoadRunner
└── Swoole
```

FrankenPHP será el runtime recomendado oficialmente.

---

## Objetivo Técnico Principal

El objetivo técnico de VoltStack es combinar:

- la productividad de Laravel
- la experiencia reactiva de Livewire
- la fluidez SPA de Next.js
- el runtime persistente de FrankenPHP

dentro de un ecosistema PHP moderno y cohesionado.

---

## Público Objetivo

VoltStack está diseñado para:

- desarrolladores PHP
- empresas SaaS
- aplicaciones empresariales
- plataformas administrativas
- dashboards
- sistemas multitenant
- aplicaciones reactivas
- plataformas cloud
- aplicaciones SPA modernas

---

## Visión a Largo Plazo

VoltStack busca convertirse en un ecosistema completo de desarrollo reactivo moderno para PHP.

El framework pretende evolucionar hacia:

- runtime distribuido
- rendering híbrido
- streaming UI
- realtime native
- adapters multiplataforma
- rendering móvil
- rendering desktop
- microfrontends reactivos
- cloud-native runtime
- AI-assisted runtime systems

---

## Estado Inicial del Proyecto

Fase actual:

```txt
Foundation Architecture Phase
```

Objetivos inmediatos:

- definir arquitectura core
- construir container
- construir runtime reactivo
- definir protocolo SPA
- construir router reactivo
- definir component system
- implementar runtime frontend
- integrar FrankenPHP
- construir sistema de hydration
- definir lifecycle de componentes

---

## Nombre Oficial

```txt
VoltStack
```

---

## Slogan Provisional

```txt
Reactive PHP Runtime Framework
```
