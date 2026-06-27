# FRONTEND_ROUTE_MANIFEST.md

# VoltStack Frontend Route Manifest

**Versión:** 1.0
**Estado:** Draft

---

# 1. Introducción

El Frontend Route Manifest es el contrato oficial entre el sistema de Routing de VoltStack y cualquier Runtime de frontend.

Su función consiste en proporcionar una representación compilada, serializada y optimizada de las rutas públicas que pueden ser utilizadas por:

* Volt Runtime
* React Adapter
* Vue Adapter
* Svelte Adapter
* Solid Adapter
* NativePHP
* Aplicaciones móviles
* Herramientas CLI

El manifiesto nunca expone información privada del servidor.

---

# 2. Filosofía

El manifiesto sigue cinco principios.

## Generated

Siempre es generado por el Route Compiler.

---

## Immutable

Nunca cambia durante el Runtime.

---

## Public

Solo contiene información necesaria para el cliente.

---

## Framework Agnostic

No depende de ninguna librería JavaScript.

---

## Versioned

Cada manifiesto posee versión propia.

---

# 3. Objetivos

El sistema busca.

* evitar duplicación de rutas.
* sincronizar backend y frontend.
* facilitar navegación SPA.
* generar tipado.
* soportar Prefetch.
* soportar SSR.
* minimizar solicitudes al servidor.

---

# 4. Arquitectura

```text
Manifest/

Contracts/
Compiler/
Serializer/
Versioning/
Runtime/
Adapters/
TypeScript/
Validation/
Support/
Testing/
```

---

# 5. Flujo General

```text
Route Definitions
        │
        ▼
Route Compiler
        │
        ▼
Route Metadata
        │
        ▼
Frontend Manifest Generator
        │
        ▼
Route Manifest
        │
        ▼
Frontend Runtime
```

---

# 6. Estructura General

El manifiesto contiene únicamente información pública.

```text
Protocol

Routes

Layouts

Transitions

Assets

Navigation

Runtime

Version

Capabilities
```

---

# 7. Protocol

Describe el protocolo.

Ejemplo.

```json
{
    "protocol": {
        "name": "VoltStack Frontend Manifest",
        "version": "1.0"
    }
}
```

---

# 8. Routes

Cada ruta pública contiene.

* nombre
* path
* parámetros
* métodos
* metadata pública

Nunca middleware.

Nunca políticas.

Nunca bindings internos.

---

# 9. Route Entry

Ejemplo.

```json
{
    "name": "users.show",
    "path": "/users/{user}",
    "methods": ["GET"]
}
```

---

# 10. Parameters

Describe.

* nombre
* requerido
* tipo
* opcional

Ejemplo.

```json
{
    "parameters": [
        {
            "name": "user",
            "type": "number",
            "required": true
        }
    ]
}
```

---

# 11. Navigation

Información utilizada por el Runtime.

Ejemplo.

```json
{
    "transition": "fade",
    "prefetch": true,
    "keepAlive": false
}
```

---

# 12. Layout

Describe el Layout asociado.

```json
{
    "layout": "dashboard"
}
```

---

# 13. Runtime

El Runtime puede conocer.

* hydrate
* lazy
* partialReload
* streaming

Sin conocer implementación interna.

---

# 14. Assets

Cada ruta puede declarar.

* scripts
* styles
* preload
* prefetch

Todo generado automáticamente.

---

# 15. Components

Puede contener.

```json
{
    "component": "Dashboard"
}
```

No expone clases PHP.

Solo identificadores públicos.

---

# 16. Capabilities

Cada ruta declara capacidades.

Ejemplo.

```json
{
    "capabilities": [
        "hydrate",
        "prefetch",
        "stream"
    ]
}
```

No contiene configuración interna.

---

# 17. Versionado

Todo manifiesto posee.

* versión
* checksum
* timestamp
* compilador

---

# 18. Checksums

Permiten detectar.

* cambios
* incompatibilidades
* actualizaciones

---

# 19. Manifest Serializer

El Serializer transforma las estructuras internas en formatos públicos.

Puede generar.

* JSON
* TypeScript
* Binary (futuro)

---

# 20. Integración con TypeScript

El compilador genera automáticamente.

```text
routes.ts

route-types.ts

navigation.ts

manifest.ts
```

El frontend obtiene tipado completo.

---

# 21. Integración con Volt Runtime

El Runtime utiliza el manifiesto para.

* navegación
* preload
* transición
* hydrate
* layouts

Sin realizar solicitudes adicionales.

---

# 22. Integración con React

El adaptador React utiliza.

* rutas
* parámetros
* metadata

No necesita conocer el Router PHP.

---

# 23. Integración con Vue

El adaptador Vue consume exactamente el mismo manifiesto.

---

# 24. Integración con Svelte

No existen diferencias.

Todos consumen el mismo contrato.

---

# 25. Integración con NativePHP

El manifiesto puede reutilizarse para navegación Desktop.

---

# 26. Integración con Quantum

Participan.

* Quantum Routing
* Quantum Runtime
* Quantum Components
* Quantum Assets
* Quantum Hydration
* Quantum SPA
* Quantum TypeScript

---

# 27. Seguridad

Nunca se serializan.

* Middleware
* Policies
* Container
* Bindings
* Metadata privada
* Configuración interna

Solo información pública.

---

# 28. Eventos

Durante la compilación.

```text
ManifestGenerating

ManifestGenerated

ManifestSerialized

ManifestValidated

ManifestPublished
```

---

# 29. Extensibilidad

Los paquetes pueden registrar.

* nuevos serializers.
* nuevos adapters.
* nuevas categorías.
* nuevas capacidades.
* nuevas versiones.

Sin modificar el núcleo.

---

# 30. Compatibilidad

Puede consumirse desde.

* Volt Runtime
* React
* Vue
* Svelte
* Solid
* NativePHP
* Mobile
* Electron
* Tauri

---

# 31. Rendimiento

El manifiesto está optimizado para.

* tamaño reducido.
* lectura secuencial.
* carga rápida.
* caché de navegador.
* compresión.

---

# 32. Testing

Debe validar.

* serialización.
* compatibilidad.
* versionado.
* checksum.
* adapters.
* TypeScript.

---

# 33. Visión

El Frontend Route Manifest constituye la representación pública y compilada del sistema de Routing de VoltStack.

Gracias a este contrato unificado, cualquier Runtime de frontend puede navegar, resolver rutas, generar enlaces y aprovechar capacidades avanzadas como hidratación, transiciones, layouts y prefetch sin depender de implementaciones específicas del backend, garantizando sincronización, seguridad y un alto nivel de rendimiento.

Propuesta para hacer a VoltStack realmente diferente

Hay una característica que considero que podría convertirse en una de las señas de identidad del framework: un Manifest Capability Negotiation System.

En lugar de asumir que todos los clientes soportan las mismas capacidades, cada Runtime declararía qué entiende:

{
  "runtime": "react",
  "supports": [
    "hydrate",
    "stream",
    "prefetch",
    "partialReload",
    "keepAlive"
  ]
}

Durante la compilación, VoltStack podría generar distintos manifiestos optimizados para cada adaptador (voltstack/runtime, voltstack/react, voltstack/vue, etc.) o incluso negociar capacidades en tiempo de conexión.

Con este enfoque, el Router y el compilador dejan de generar un manifiesto genérico y pasan a producir contratos específicos para cada Runtime, reduciendo el tamaño de los datos enviados, eliminando información innecesaria y permitiendo que cada adaptador evolucione de forma independiente sin romper la compatibilidad del ecosistema. Esto encaja perfectamente con la arquitectura modular basada en Quantum y con tu visión de un Runtime SPA propio acompañado de puentes hacia otros ecosistemas.
