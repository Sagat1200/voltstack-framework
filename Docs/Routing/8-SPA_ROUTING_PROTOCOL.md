# SPA_ROUTING_PROTOCOL.md

# VoltStack SPA Routing Protocol

**Versión:** 1.0
**Estado:** Draft

---

# 1. Introducción

El SPA Routing Protocol define el contrato de comunicación entre el sistema de Routing de VoltStack y cualquier Runtime SPA compatible.

Su objetivo consiste en desacoplar completamente el Router del framework de interfaz utilizado por el cliente.

El Router nunca conoce React, Vue, Svelte o el Runtime nativo.

Únicamente genera un protocolo declarativo que describe la navegación.

---

# 2. Objetivos

El protocolo busca:

* Unificar la navegación SPA.
* Reducir el acoplamiento.
* Facilitar múltiples runtimes.
* Permitir SSR.
* Permitir Hydration.
* Permitir navegación parcial.
* Permitir Streaming.
* Facilitar Prefetch.
* Mantener compatibilidad futura.

---

# 3. Filosofía

El Router describe.

El Runtime interpreta.

El protocolo nunca contiene lógica.

Únicamente información declarativa.

---

# 4. Flujo General

```text
Browser

↓

SPA Runtime

↓

Navigate()

↓

HTTP Request

↓

VoltStack Router

↓

Route Dispatcher

↓

Volt Protocol

↓

SPA Routing Protocol

↓

Frontend Runtime

↓

Render
```

---

# 5. Componentes

El protocolo está dividido en varias secciones.

```text
Navigation

Screen

Runtime

Hydration

Transition

Layout

Components

Assets

State

Metadata

Events
```

Cada sección es independiente.

---

# 6. Navigation

Describe la navegación.

Ejemplo.

```json
{
    "mode": "navigate",
    "target": "/users/15",
    "replace": false,
    "preserveScroll": true,
    "preserveState": false
}
```

---

# 7. Screen

Describe la pantalla.

Ejemplo.

```json
{
    "id": "users.show",
    "title": "User Profile",
    "layout": "dashboard",
    "locale": "es"
}
```

---

# 8. Runtime

Describe cómo debe actuar el Runtime.

Ejemplo.

```json
{
    "spa": true,
    "hydrate": true,
    "lazy": false,
    "keepAlive": true
}
```

---

# 9. Hydration

Define la estrategia de hidratación.

```json
{
    "enabled": true,
    "strategy": "partial",
    "checksum": "...",
    "snapshot": "..."
}
```

---

# 10. Layout

Información del Layout.

```json
{
    "name": "dashboard",
    "replace": false
}
```

---

# 11. Transition

Define la transición visual.

```json
{
    "name": "fade",
    "duration": 250
}
```

---

# 12. Components

Lista de componentes que forman la pantalla.

```json
[
    {
        "id": "header",
        "type": "navigation"
    },
    {
        "id": "content",
        "type": "page"
    }
]
```

---

# 13. Assets

Describe los recursos requeridos.

```json
{
    "scripts": [],
    "styles": [],
    "preload": [],
    "prefetch": []
}
```

---

# 14. State

Permite transferir estado inicial.

```json
{
    "state": {
        "user": {},
        "notifications": []
    }
}
```

---

# 15. Metadata

Información adicional.

```json
{
    "route": "users.show",
    "version": "1.0",
    "timestamp": 123456789
}
```

---

# 16. Events

Eventos que el Runtime deberá disparar.

```json
[
    "mounted",
    "hydrated",
    "loaded"
]
```

---

# 17. Prefetch

El Router puede indicar recursos para precargar.

```json
{
    "prefetch": [
        "/users",
        "/notifications"
    ]
}
```

---

# 18. Partial Reload

El protocolo soporta recargas parciales.

Ejemplo.

```json
{
    "reload": [
        "notifications",
        "sidebar"
    ]
}
```

---

# 19. Lazy Components

Componentes diferidos.

```json
{
    "lazy": [
        "statistics",
        "activity"
    ]
}
```

---

# 20. Streaming

El protocolo soporta render incremental.

```json
{
    "stream": true
}
```

---

# 21. Redirect

El servidor puede indicar navegación.

```json
{
    "redirect": "/login"
}
```

---

# 22. Error

Representación uniforme.

```json
{
    "error": {
        "code": 404,
        "message": "Not Found"
    }
}
```

---

# 23. Runtime Adapter

Cada Runtime implementa un adaptador.

Ejemplo.

```text
Volt Runtime Adapter

React Adapter

Vue Adapter

Svelte Adapter

Solid Adapter
```

Todos consumen exactamente el mismo protocolo.

---

# 24. Integración con Routing

El Router únicamente añade metadata.

Nunca conoce React.

Nunca conoce Vue.

Nunca conoce componentes del frontend.

---

# 25. Integración con Volt Protocol

El SPA Routing Protocol constituye una extensión del Volt Protocol.

El Volt Protocol representa el estado completo de la aplicación.

El SPA Routing Protocol representa únicamente la navegación.

Ambos protocolos son complementarios.

---

# 26. Integración con Metadata

Toda la información proviene del Route Metadata System.

Ejemplos.

* layout
* hydrate
* transition
* prefetch
* keepAlive

El protocolo únicamente la serializa.

---

# 27. Integración con Hydration

El Runtime utiliza.

* checksum
* snapshot
* strategy
* dirtyState

Para reconstruir el árbol de componentes.

---

# 28. Integración con Quantum

Participan.

* Quantum Routing
* Quantum Runtime
* Quantum Components
* Quantum Hydration
* Quantum Events
* Quantum Assets
* Quantum Cache
* Quantum Security

---

# 29. Versionado

Todo protocolo contiene versión.

```json
{
    "protocol": {
        "name": "VoltStack SPA Routing",
        "version": "1.0"
    }
}
```

Esto garantiza compatibilidad futura.

---

# 30. Compatibilidad

El protocolo es independiente del cliente.

Puede utilizarse desde.

* React
* Vue
* Svelte
* Solid
* Runtime nativo
* Aplicaciones móviles
* Desktop
* WebView

---

# 31. Seguridad

El protocolo nunca expone.

* Middleware internos.
* Información del servidor.
* Credenciales.
* Objetos del Container.
* Metadata privada.

Solo transmite la información necesaria para el Runtime.

---

# 32. Rendimiento

El protocolo está diseñado para:

* minimizar tamaño.
* reutilizar referencias.
* permitir compresión.
* soportar streaming.
* soportar navegación incremental.

---

# 33. Testing

Se validará.

* serialización.
* compatibilidad.
* versionado.
* adapters.
* hidratación.
* SSR.
* SPA.
* Streaming.

---

# 34. Visión

El SPA Routing Protocol constituye el contrato universal de navegación de VoltStack.

Gracias a este protocolo, el sistema de Routing permanece completamente desacoplado de cualquier tecnología de frontend, permitiendo que múltiples runtimes interpreten una misma representación declarativa de la navegación, manteniendo consistencia, rendimiento y extensibilidad en todo el ecosistema del framework.

Una mejora que considero fundamental

Aquí incorporaría una idea que, hasta donde conozco, no implementa ningún framework PHP de forma nativa: un Navigation Intent System.

En lugar de que el protocolo únicamente diga "navega a /users/15", cada navegación llevaría su intención:

{
  "intent": "view",
  "resource": "users",
  "target": "/users/15",
  "reason": "user-click",
  "priority": "normal",
  "transition": "fade"
}

Otros ejemplos serían:

create
edit
delete
modal
background-refresh
prefetch
optimistic-update
restore-state

Esto permitiría que el Runtime tome decisiones inteligentes sin que el desarrollador tenga que escribir código adicional. Por ejemplo, una navegación con intención modal podría reutilizar el layout actual; una background-refresh podría actualizar únicamente determinados componentes; una optimistic-update podría preservar el estado hasta recibir la confirmación del servidor. Es una capa semántica sobre la navegación que encaja muy bien con la visión reactiva y compilada de VoltStack.
