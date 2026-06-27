# URL_GENERATOR.md

# VoltStack URL Generator

**Versión:** 1.0
**Estado:** Draft

---

# 1. Introducción

El URL Generator es el subsistema responsable de construir cualquier dirección utilizada dentro del framework.

Su responsabilidad no se limita a generar URLs HTTP.

También proporciona infraestructura para:

* navegación SPA
* assets
* APIs
* enlaces firmados
* enlaces temporales
* componentes
* streaming
* recursos externos

Todo mediante una única interfaz unificada.

---

# 2. Filosofía

El sistema sigue cinco principios.

## Unified

Una única infraestructura genera todos los enlaces.

---

## Immutable

Las URLs generadas nunca modifican el estado del sistema.

---

## Context Aware

La URL depende del contexto de ejecución.

---

## Extensible

Nuevos tipos de URL pueden añadirse mediante Drivers.

---

## Compile Friendly

Toda la información posible proviene del Route Compiler.

---

# 3. Objetivos

El sistema busca.

* simplificar la generación de URLs.
* reducir configuraciones duplicadas.
* reutilizar metadata.
* soportar múltiples runtimes.
* facilitar integración con frontend.

---

# 4. Arquitectura

```text id="q6jjlwm"
Url/

Contracts/
Generators/
Builders/
Resolvers/
Signing/
Temporary/
Assets/
Spa/
Api/
Manifest/
Support/
Events/
Testing/
```

---

# 5. Flujo General

```text id="5jlwmq"
Route Name
      │
      ▼
Route Lookup
      │
      ▼
Metadata
      │
      ▼
Parameter Resolver
      │
      ▼
URL Builder
      │
      ▼
Transformers
      │
      ▼
Generated URL
```

---

# 6. URL Generator Interface

Todos los generadores implementan.

```php
interface UrlGeneratorInterface
{
    public function generate(
        string $name,
        array $parameters = []
    ): string;
}
```

---

# 7. Tipos de URL

VoltStack soporta.

* Route URL
* Signed URL
* Temporary URL
* SPA URL
* API URL
* Asset URL
* CDN URL
* Stream URL
* Component URL
* External URL

---

# 8. Route URL

Genera URLs tradicionales.

Ejemplo.

```php
url()->route('users.show', [
    'user' => 15
]);
```

Resultado.

```text
/users/15
```

---

# 9. Named Routes

El sistema trabaja principalmente mediante nombres.

Nunca mediante concatenación manual.

Ejemplo.

```php
url()->route('dashboard');
```

---

# 10. Parameter Resolver

Resuelve automáticamente.

* modelos
* enums
* UUID
* slug
* value objects

Utilizando los Binding ya compilados.

---

# 11. Signed URLs

Genera enlaces firmados.

Ejemplo.

```php
url()->signed('downloads.show');
```

Puede utilizar.

* HMAC
* expiración
* scopes
* tenant

---

# 12. Temporary URLs

Genera enlaces temporales.

Ejemplo.

```php
url()->temporary(
    'downloads.show',
    now()->addMinutes(10)
);
```

---

# 13. SPA URLs

Especializadas para el Runtime.

Ejemplo.

```php
url()->spa('users.show');
```

Puede añadir metadata de navegación.

---

# 14. Component URLs

Permite generar enlaces hacia componentes.

Ejemplo.

```php
url()->component('dashboard.statistics');
```

---

# 15. API URLs

Genera enlaces versionados.

Ejemplo.

```php
url()->api('users.index');
```

Puede resolver automáticamente.

* versión
* prefijo
* dominio

---

# 16. Asset URLs

Integración con Quantum Assets.

Ejemplo.

```php
url()->asset('logo.svg');
```

Puede resolver.

* CDN
* versión
* fingerprint
* manifest

---

# 17. CDN URLs

Puede delegar automáticamente.

Ejemplo.

```php
url()->cdn('images/logo.png');
```

---

# 18. Stream URLs

Especializadas para.

* SSE
* Downloads
* Media
* AI Streams

---

# 19. Absolute y Relative

Puede generar.

```php
absolute()

relative()
```

Según el contexto.

---

# 20. Domains

Soporta.

* múltiples dominios
* subdominios
* dominios dinámicos
* dominios por tenant

---

# 21. Locale

Puede generar.

```text
/es/users

/en/users

/fr/users
```

Automáticamente.

---

# 22. Query Builder

Permite construir consultas.

Ejemplo.

```php
url()
    ->route('users.index')
    ->query([
        'page' => 2
    ]);
```

---

# 23. Fragmentos

Soporta.

```php
->fragment('comments')
```

---

# 24. URL Builder

Toda URL pasa por un Builder.

Responsable de.

* path
* dominio
* query
* fragment
* esquema

---

# 25. URL Transformers

Después del Builder.

Pueden ejecutarse transformadores.

Ejemplo.

* Signed
* Temporary
* CDN
* Locale
* Tenant

---

# 26. Integración con Metadata

Toda la información proviene del Route Metadata.

Ejemplo.

* dominio
* locale
* versión
* prefijos
* tenant

---

# 27. Integración con SPA Runtime

El Runtime puede solicitar.

```php
url()->spa()
```

Para generar enlaces compatibles con navegación reactiva.

---

# 28. Integración con TypeScript

El compilador puede generar.

```text
routes.ts

url.ts

navigation.ts
```

Manteniendo sincronía entre Backend y Frontend.

---

# 29. Integración con Quantum

Interactúa con.

* Quantum Routing
* Quantum Runtime
* Quantum Assets
* Quantum Security
* Quantum Tenant
* Quantum Storage
* Quantum SPA

---

# 30. Eventos

Se emiten.

```text
UrlGenerating

UrlGenerated

UrlSigning

UrlSigned

TemporaryUrlGenerated
```

---

# 31. Errores

Puede generar.

* RouteNotFound
* InvalidParameter
* MissingParameter
* InvalidSignature
* InvalidDomain

---

# 32. Compatibilidad

Funciona sobre.

* HTTP
* SPA
* API
* SSR
* CLI
* Streaming
* Queue
* Workers

---

# 33. Rendimiento

Toda la resolución utiliza.

* Route Metadata compilada.
* Route Collection compilada.
* Bindings compilados.
* Dominios compilados.

Nunca se analizan archivos de rutas.

---

# 34. Extensibilidad

Los paquetes pueden registrar.

* nuevos Builders.
* nuevos Transformers.
* nuevos Drivers.
* nuevos tipos de URL.
* nuevos resolvers.

Sin modificar el núcleo.

---

# 35. Testing

Cada generador valida.

* rutas.
* parámetros.
* dominios.
* locales.
* firmas.
* expiración.
* integración SPA.
* rendimiento.

---

# 36. Visión

El URL Generator de VoltStack constituye una plataforma unificada para la construcción de enlaces dentro del framework.

Al desacoplar la generación de URLs de los distintos módulos y reutilizar la información compilada por el sistema de Routing, proporciona una infraestructura consistente, extensible y preparada para aplicaciones modernas que combinan HTTP tradicional, SPA, SSR, APIs, multitenancy y múltiples entornos de ejecución.

Una propuesta que puede convertirlo en uno de los mejores sistemas del ecosistema PHP

Aquí incorporaría un concepto que no existe de forma integrada en Laravel ni Symfony: un URL Intent System.

En lugar de generar únicamente una cadena, el generador produciría primero un objeto inmutable UrlIntent.

Ejemplo conceptual:

url()
    ->route('users.show')
    ->intent('navigate')
    ->transition('fade')
    ->prefetch()
    ->locale('es')
    ->temporary(now()->addMinutes(5));

Internamente, el UrlIntent contendría toda la semántica del enlace (navegación, seguridad, tenant, idioma, estrategia SPA, etc.). Después, distintos Renderers lo convertirían en:

una URL HTTP,
un objeto para el Runtime SPA,
un enlace firmado,
un enlace para una aplicación móvil,
o incluso un comando de navegación para un adaptador React o Vue.

Con este enfoque, el sistema deja de ser un simple generador de cadenas y se convierte en un motor de construcción de enlaces declarativos, totalmente alineado con la filosofía compilada y desacoplada de VoltStack.
