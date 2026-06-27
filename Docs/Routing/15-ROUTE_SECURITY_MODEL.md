# ROUTE_SECURITY_MODEL.md

# VoltStack Route Security Model

**Versión:** 1.0
**Estado:** Draft

---

# 1. Introducción

El Route Security Model define la forma en que el sistema de Routing interactúa con el módulo de seguridad del framework.

Su propósito es describir los requisitos de seguridad asociados a cada endpoint sin incorporar lógica de autenticación o autorización dentro del Router.

Toda la información de seguridad se expresa mediante metadata compilada y es interpretada por Quantum Security durante el Pipeline de ejecución.

---

# 2. Filosofía

El modelo sigue cinco principios.

## Security by Metadata

Las rutas describen requisitos.

No ejecutan lógica de seguridad.

---

## Compile First

Las reglas son compiladas.

No se interpretan durante el Runtime.

---

## Context Aware

Las decisiones dependen del contexto de ejecución.

---

## Capability Driven

Las rutas declaran capacidades de seguridad.

No implementaciones.

---

## Extensible

Nuevos mecanismos pueden añadirse mediante paquetes.

---

# 3. Objetivos

El sistema busca.

* desacoplar Routing y Security.
* centralizar metadata.
* facilitar múltiples mecanismos de autenticación.
* soportar aplicaciones empresariales.
* mantener compatibilidad con SPA y SSR.
* eliminar duplicación de configuración.

---

# 4. Arquitectura

```text id="6kpsrx"
RouteSecurity/

Contracts/
Metadata/
Capabilities/
Policies/
Permissions/
Guards/
Scopes/
Validators/
Runtime/
Support/
Testing/
```

---

# 5. Flujo General

```text id="i7kn0w"
Incoming Request
        │
        ▼
Route Matcher
        │
        ▼
Route Metadata
        │
        ▼
Security Pipeline
        │
        ▼
Quantum Security
        │
        ▼
Authorized Endpoint
```

El Router nunca valida credenciales.

---

# 6. Security Metadata

Cada ruta puede declarar.

* auth
* guest
* guard
* permissions
* policies
* scopes
* tenant
* csrf
* signed
* throttle
* rateLimit
* mfa
* capabilities

---

# 7. Security Capabilities

Cada endpoint puede requerir capacidades.

Ejemplo.

```text id="bj5gdn"
Authentication

Authorization

TenantIsolation

SignedUrl

CsrfProtection

RateLimit

MFA

ApiKey

OAuth

Jwt
```

Quantum Security determina cómo satisfacerlas.

---

# 8. Authentication

Las rutas pueden indicar.

```php id="3lbh5k"
#[Auth]
```

o

```php id="h7xvru"
Route::middleware('auth');
```

Ambas generan exactamente la misma metadata.

---

# 9. Guest

Permite restringir acceso a usuarios autenticados.

```php id="wm4e3p"
#[Guest]
```

---

# 10. Guards

Puede especificarse.

```php id="r0v6r2"
#[Guard('admin')]
```

o múltiples Guards.

```php id="ddwvk0"
#[Guard(['web','api'])]
```

---

# 11. Permissions

Ejemplo.

```php id="ic4wql"
#[Permission('users.view')]
```

---

# 12. Policies

Ejemplo.

```php id="4dzf0u"
#[Policy('view')]
```

La evaluación corresponde a Quantum Security.

---

# 13. Scopes

Especialmente útil para APIs.

```php id="6m8vlq"
#[Scope('users.read')]
```

---

# 14. Tenant Isolation

La ruta puede requerir.

```php id="rnt1zq"
#[Tenant]
```

El Router únicamente transporta esta información.

---

# 15. Signed URLs

Ejemplo.

```php id="6mq5zl"
#[Signed]
```

La verificación corresponde al módulo de seguridad.

---

# 16. Temporary URLs

Puede requerirse.

```php id="g5s2ch"
#[Temporary]
```

---

# 17. CSRF

Ejemplo.

```php id="jlwm83"
#[Csrf]
```

El Router no conoce la implementación.

---

# 18. Rate Limiting

Ejemplo.

```php id="wjlwm4"
#[Throttle('api')]
```

---

# 19. Multi-Factor Authentication

Puede declararse.

```php id="jlwm5z"
#[Mfa]
```

o

```php id="jlwm5x"
#[Mfa('required')]
```

---

# 20. OAuth

Las rutas pueden requerir.

* OAuth
* OpenID Connect
* JWT
* API Keys

Todo mediante metadata.

---

# 21. Integración con Metadata

Toda la información de seguridad forma parte del Route Metadata.

Nunca se mantiene duplicada.

---

# 22. Integración con Middleware

El Middleware únicamente consulta la metadata.

Nunca analiza atributos.

Nunca analiza archivos.

---

# 23. Integración con Quantum Security

Quantum Security consume.

* Route Metadata
* Tenant Context
* User Context
* Runtime Context

Y produce una decisión.

---

# 24. Security Decision

El Router recibe únicamente.

```text id="jlwm6c"
Authorized

Denied

Challenge

Redirect

Deferred
```

Nunca participa en la decisión.

---

# 25. Integración con SPA

El Runtime puede conocer únicamente información pública.

Ejemplo.

* loginRequired
* guestOnly
* permissionsHint

Nunca tokens.

Nunca políticas internas.

---

# 26. Integración con SSR

El mismo modelo aplica para renderizado del servidor.

---

# 27. Integración con Multi-Tenant

Las capacidades pueden depender del Tenant Context.

No del Router.

---

# 28. Eventos

Durante la ejecución.

```text id="jlwm6d"
SecurityMetadataLoaded

AuthorizationStarted

AuthorizationCompleted

AuthorizationDenied

AuthorizationGranted
```

---

# 29. Errores

Puede producir.

* Unauthorized
* Forbidden
* InvalidSignature
* InvalidCsrfToken
* InvalidScope
* InvalidPermission

---

# 30. Compatibilidad

El sistema soporta.

* HTTP
* SPA
* SSR
* API
* Streaming
* CLI
* WebSocket

---

# 31. Rendimiento

Toda la metadata se encuentra compilada.

Durante el Runtime.

No existe.

* reflexión.
* lectura de atributos.
* merges.
* descubrimiento.

---

# 32. Extensibilidad

Los paquetes pueden registrar.

* nuevas capacidades.
* nuevos Guards.
* nuevos validadores.
* nuevos Providers.
* nuevos mecanismos de autenticación.

Sin modificar el núcleo.

---

# 33. Testing

Cada capacidad debe validar.

* autenticación.
* autorización.
* scopes.
* tenant.
* MFA.
* rendimiento.
* integración.

---

# 34. Visión

El Route Security Model convierte la seguridad en una responsabilidad declarativa y completamente desacoplada del sistema de Routing.

Las rutas expresan únicamente sus requisitos mediante metadata compilada, mientras que Quantum Security interpreta dichas capacidades y toma las decisiones correspondientes. Esta arquitectura permite incorporar nuevos mecanismos de autenticación y autorización sin modificar el Router, manteniendo una infraestructura flexible, escalable y preparada para aplicaciones empresariales.

Una propuesta que considero especialmente potente para VoltStack

Aquí introduciría un Security Policy Graph, inspirado en los grafos de dependencias que ya estás utilizando en otros documentos.

En lugar de evaluar cada requisito de forma aislada, el compilador construiría un grafo de políticas por endpoint.

Ejemplo conceptual:

Route
   │
   ├── Authentication
   │
   ├── Tenant Isolation
   │
   ├── Permission(users.view)
   │
   ├── MFA
   │
   └── Signed URL

Durante la compilación se podrían detectar:

dependencias redundantes;
políticas incompatibles;
reglas imposibles de satisfacer;
middleware innecesarios;
optimizaciones del pipeline de seguridad.

Además, el runtime podría evaluar únicamente el subgrafo relevante según el contexto (HTTP, SPA, API, CLI), reduciendo el trabajo por petición. Esta aproximación está muy alineada con la filosofía AOT y modular de VoltStack y ofrece una base sólida para evolucionar el sistema de seguridad sin incrementar el acoplamiento entre Routing y Quantum Security.
