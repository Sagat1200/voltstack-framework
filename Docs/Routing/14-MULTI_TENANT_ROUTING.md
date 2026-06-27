# MULTI_TENANT_ROUTING.md

# VoltStack Multi-Tenant Routing

**Versión:** 1.0
**Estado:** Draft

---

# 1. Introducción

El sistema de Multi-Tenant Routing proporciona la infraestructura necesaria para que el Router pueda operar en aplicaciones multi-tenant sin asumir una estrategia específica de identificación del tenant.

VoltStack desacopla completamente el Routing del sistema de multitenancy.

La resolución del Tenant corresponde al módulo Quantum Tenant (NeuronTenant).

El Router únicamente consume un Tenant Context ya resuelto.

---

# 2. Filosofía

El sistema sigue cinco principios.

## Tenant Aware

El Router conoce el Tenant.

Nunca lo resuelve.

---

## Strategy Independent

No existe una única estrategia de identificación.

---

## Compile First

Las reglas son compiladas.

---

## Context Driven

Toda la información viaja mediante Tenant Context.

---

## Extensible

Nuevas estrategias pueden añadirse sin modificar el Router.

---

# 3. Objetivos

El sistema busca.

* soportar múltiples estrategias.
* desacoplar Routing y Tenant.
* facilitar dominios personalizados.
* soportar aplicaciones SaaS.
* minimizar impacto en rendimiento.
* mantener compatibilidad con SPA y SSR.

---

# 4. Arquitectura

```text
TenantRouting/

Contracts/
Strategies/
Context/
Metadata/
Resolvers/
Domains/
Compiler/
Runtime/
Support/
Testing/
```

---

# 5. Flujo General

```text
Incoming Request
        │
        ▼
Tenant Resolver
        │
        ▼
Tenant Context
        │
        ▼
Route Matcher
        │
        ▼
Route Dispatcher
```

El Router nunca descubre el Tenant.

Solo consume el contexto recibido.

---

# 6. Tenant Context

Todo el sistema utiliza un único objeto.

```text
TenantContext
```

Puede contener.

* id
* uuid
* slug
* domain
* locale
* timezone
* configuration
* capabilities

---

# 7. Estrategias

VoltStack soporta múltiples estrategias.

## Subdomain

```text
tenant.app.com
```

---

## Domain

```text
empresa.com
```

---

## Path

```text
/app/tenant
```

---

## Header

```text
X-Tenant
```

---

## JWT

Extraído del token.

---

## API Gateway

Información recibida desde infraestructura externa.

---

## CLI

Tenant indicado mediante opciones de consola.

---

# 8. Tenant Resolver

El Resolver pertenece a Quantum Tenant.

El Router únicamente recibe.

```text
TenantContext
```

Nunca ejecuta consultas.

Nunca accede a la base de datos.

---

# 9. Tenant Metadata

Cada ruta puede declarar.

* tenant aware
* tenant required
* tenant optional
* tenant scope

Toda esta información forma parte del Route Metadata.

---

# 10. Route Groups

Los grupos pueden declarar.

```php
Route::tenant()
```

o

```php
Route::group()
    ->tenant()
```

Aplicando automáticamente la metadata correspondiente.

---

# 11. Domains

Las rutas pueden utilizar.

```text
{tenant}.example.com
```

o

```text
customer.example.com
```

o

```text
empresa.com
```

Todos son tratados mediante la misma infraestructura.

---

# 12. Custom Domains

Cada Tenant puede utilizar dominios personalizados.

Ejemplo.

```text
empresa.com

midominio.mx

app.miempresa.net
```

Sin modificar las rutas.

---

# 13. Dynamic Domains

Los dominios dinámicos son compilados como patrones.

No requieren redefinir rutas.

---

# 14. Tenant Constraints

Las rutas pueden restringir.

* tipo de tenant
* plan
* estado
* región

Todo mediante metadata.

---

# 15. Tenant Pipeline

El Middleware correspondiente pertenece a Quantum Tenant.

El Router únicamente incorpora la referencia al Pipeline.

---

# 16. Tenant Bindings

Los bindings pueden depender del Tenant.

Ejemplo.

```text
/user/15
```

Puede resolver distintos recursos dependiendo del Tenant Context.

---

# 17. Integración con SPA

El SPA Runtime recibe.

* tenant id público
* dominio
* locale
* branding

Nunca información privada.

---

# 18. Integración con SSR

El SSR Renderer utiliza el mismo Tenant Context.

No existe lógica duplicada.

---

# 19. Integración con URL Generator

El URL Generator utiliza el Tenant Context para generar.

* dominios
* subdominios
* rutas
* enlaces firmados

---

# 20. Integración con Metadata

El Metadata puede declarar.

```text
Tenant Required

Tenant Optional

Tenant Scope

Tenant Visibility

Tenant Branding
```

---

# 21. Integración con Quantum

Participan.

* Quantum Tenant
* Quantum Routing
* Quantum Runtime
* Quantum Security
* Quantum Cache
* Quantum Components
* Quantum Storage

---

# 22. Tenant Capabilities

Cada Tenant puede declarar capacidades.

Ejemplo.

```text
SPA

SSR

API

Streaming

Billing

Storage

AI
```

El Router únicamente transporta esta información.

---

# 23. Eventos

Durante la ejecución.

```text
TenantResolved

TenantAttached

TenantValidated

TenantRoutingStarted

TenantRoutingCompleted
```

---

# 24. Errores

Puede producir.

* TenantNotFound
* InvalidTenant
* TenantSuspended
* TenantUnavailable
* InvalidTenantDomain

La gestión corresponde a Quantum Tenant.

---

# 25. Compatibilidad

El sistema soporta.

* SaaS
* White Label
* Marketplace
* Multi Región
* Multi Dominio
* Multi Organización

---

# 26. Rendimiento

Toda la información.

* dominios
* patrones
* metadata

Se encuentra compilada.

El Runtime nunca reconstruye estructuras.

---

# 27. Extensibilidad

Los paquetes pueden registrar.

* nuevas estrategias.
* nuevos resolvers.
* nuevos contextos.
* nuevos validadores.
* nuevos metadata providers.

Sin modificar el núcleo.

---

# 28. Testing

Cada estrategia debe validar.

* resolución.
* dominios.
* subdominios.
* branding.
* bindings.
* SSR.
* SPA.
* rendimiento.

---

# 29. Seguridad

El Router nunca confía únicamente en la URL.

La validación del Tenant corresponde a Quantum Tenant.

El Tenant Context debe estar autenticado y validado antes de ser consumido por el sistema de Routing.

---

# 30. Visión

El sistema de Multi-Tenant Routing convierte al Router de VoltStack en una infraestructura completamente consciente del contexto del Tenant sin asumir responsabilidades que pertenecen al dominio de multitenancy.

Gracias a esta separación de responsabilidades, VoltStack puede soportar múltiples estrategias de identificación, dominios personalizados, aplicaciones SaaS, white-label y futuros escenarios distribuidos manteniendo un Router desacoplado, compilable y preparado para servidores persistentes.

Una propuesta arquitectónica que creo que sería una de las mayores fortalezas de VoltStack

Aquí añadiría un concepto que no he visto implementado de forma completa en otros frameworks: un Tenant Route Overlay System.

En lugar de que todos los tenants compartan exactamente la misma colección de rutas, el compilador podría construir una colección base y aplicar overlays específicos por tenant o por plan.

Conceptualmente:

Compiled Route Collection
        │
        ├── Base Routes
        │
        ├── Enterprise Overlay
        │
        ├── Premium Overlay
        │
        ├── White Label Overlay
        │
        └── Custom Package Overlay

El TenantContext indicaría qué overlays activar y el RouteMatcher trabajaría con una vista lógica ya resuelta.

Esto permitiría escenarios muy potentes:

funcionalidades exclusivas por plan;
módulos habilitados o deshabilitados por tenant;
rutas añadidas por paquetes instalados solo para determinados clientes;
personalización white-label sin duplicar archivos de rutas;
despliegues progresivos (feature flags) controlados por compilación.

Todo ello manteniendo una única base compilada, minimizando el consumo de memoria y respetando la filosofía AOT que estás construyendo para VoltStack. En mi opinión, esta capacidad puede convertirse en una ventaja competitiva muy importante para aplicaciones SaaS empresariales.
