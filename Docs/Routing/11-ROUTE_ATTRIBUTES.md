# ROUTE_ATTRIBUTES.md

# VoltStack Route Attributes

**Versión:** 1.0
**Estado:** Draft

---

# 1. Introducción

El sistema de Route Attributes permite definir rutas y comportamiento asociado directamente sobre clases y métodos utilizando atributos nativos de PHP.

Los atributos constituyen una de las tres formas oficiales de registrar rutas dentro de VoltStack.

* Fluent API
* Route Files
* Route Attributes

Todas ellas generan exactamente las mismas Route Definitions durante la compilación.

---

# 2. Filosofía

Los atributos siguen cuatro principios.

## Declarativos

No contienen lógica.

Solo describen comportamiento.

---

## Componibles

Cada atributo representa una única responsabilidad.

---

## Compilables

Nunca se procesan durante el Runtime.

---

## Extensibles

Los paquetes pueden registrar nuevos atributos.

---

# 3. Objetivos

El sistema busca.

* mejorar legibilidad.
* reducir configuración.
* favorecer modularidad.
* permitir descubrimiento automático.
* facilitar integración con IDE.

---

# 4. Arquitectura

```text
Attributes/

Contracts/
Core/
Routing/
Security/
Runtime/
Spa/
Api/
Cache/
Documentation/
Custom/
Compiler/
Registry/
Support/
Testing/
```

---

# 5. Flujo General

```text
PHP Attributes
        │
        ▼
Attribute Discovery
        │
        ▼
Attribute Registry
        │
        ▼
Attribute Compiler
        │
        ▼
Route Definition
        │
        ▼
Compiled Route
```

---

# 6. Descubrimiento

Durante la compilación.

El sistema analiza.

* Controllers
* Components
* Actions
* Resources

Nunca durante el Runtime.

---

# 7. Attribute Registry

Todos los atributos registrados implementan un contrato común.

Esto permite que cualquier paquete registre nuevos atributos.

---

# 8. Categorías

Los atributos se organizan por dominios.

## Routing

Define la ruta.

---

## Security

Controla autenticación y autorización.

---

## Runtime

Describe comportamiento SPA y SSR.

---

## Cache

Define políticas de cache.

---

## API

Describe endpoints REST.

---

## Documentación

Información para OpenAPI.

---

## Personalizados

Registrados por paquetes externos.

---

# 9. Routing Attributes

## HTTP Methods

```php
#[Get('/users')]

#[Post('/users')]

#[Put('/users/{user}')]

#[Patch(...)]

#[Delete(...)]

#[Options(...)]

#[Head(...)]

#[Any(...)]
```

---

## Name

```php
#[Name('users.show')]
```

---

## Prefix

```php
#[Prefix('admin')]
```

---

## Domain

```php
#[Domain('{tenant}.example.com')]
```

---

## Group

```php
#[Group('api')]
```

---

## Version

```php
#[Version('v1')]
```

---

# 10. Security Attributes

```php
#[Auth]

#[Guest]

#[Permission('users.view')]

#[Policy('view')]

#[Throttle('api')]

#[Signed]

#[Csrf]

#[Tenant]

#[Scope('admin')]
```

---

# 11. Runtime Attributes

```php
#[Spa]

#[SSR]

#[Hydrate]

#[Lazy]

#[KeepAlive]

#[PartialReload]

#[Transition('fade')]

#[Layout('dashboard')]

#[Prefetch]
```

---

# 12. Cache Attributes

```php
#[Cache(300)]

#[CacheTag('users')]

#[NoCache]
```

---

# 13. API Attributes

```php
#[Api]

#[Produces('application/json')]

#[Consumes('application/json')]

#[Deprecated]

#[OpenApi]
```

---

# 14. Documentation Attributes

```php
#[Summary(...)]

#[Description(...)]

#[Example(...)]

#[Tag(...)]
```

---

# 15. Custom Attributes

Los paquetes Quantum pueden registrar atributos propios.

Ejemplo.

```php
#[Workflow]

#[Audit]

#[Feature]

#[AiEndpoint]
```

---

# 16. Attribute Composition

Un endpoint puede utilizar múltiples atributos.

```php
#[Get('/users/{user}')]

#[Name('users.show')]

#[Auth]

#[Hydrate]

#[Layout('dashboard')]

#[Transition('fade')]
```

Cada atributo aporta únicamente su propia metadata.

---

# 17. Attribute Compiler

El compilador es responsable de.

* descubrir.
* validar.
* fusionar.
* normalizar.
* optimizar.

Nunca existe reflexión durante el Runtime.

---

# 18. Attribute Validation

Cada atributo valida.

* tipos.
* parámetros.
* compatibilidad.
* dependencias.

---

# 19. Metadata Generation

Cada atributo produce Metadata.

Ejemplo.

```text
LayoutMetadata

CacheMetadata

SecurityMetadata

RuntimeMetadata

ApiMetadata
```

La ruta nunca almacena directamente el atributo.

Solo la metadata compilada.

---

# 20. Integración con Route Compiler

El Route Compiler consume los atributos y genera una Route Definition unificada.

No existen diferencias entre rutas creadas mediante:

* Attributes
* Fluent API
* Route Files

---

# 21. Integración con Quantum

Los atributos pueden ser consumidos por.

* Quantum Routing
* Quantum Security
* Quantum Runtime
* Quantum SPA
* Quantum Hydration
* Quantum Cache
* Quantum Components
* Quantum OpenAPI

---

# 22. Eventos

Durante la compilación.

```text
AttributesDiscovered

AttributesValidated

AttributesCompiled

MetadataGenerated
```

---

# 23. Errores

Puede producir.

* UnknownAttribute
* InvalidAttribute
* InvalidCombination
* MissingRequiredAttribute
* DuplicateAttribute

---

# 24. Compatibilidad

El sistema funciona sobre.

* Controllers
* Actions
* Components
* Resources
* API Endpoints

---

# 25. Rendimiento

Durante el Runtime.

No existe.

* Reflection
* Lectura de atributos
* Validación
* Descubrimiento
* Compilación

Toda la información ya está serializada.

---

# 26. Extensibilidad

Los paquetes pueden registrar.

* nuevos atributos.
* nuevos compiladores.
* nuevos validadores.
* nuevos resolvers.
* nuevas categorías.

Sin modificar el núcleo.

---

# 27. Testing

Cada atributo deberá validar.

* compilación.
* serialización.
* metadata.
* compatibilidad.
* integración.
* rendimiento.

---

# 28. Visión

El sistema de Route Attributes de VoltStack proporciona una forma declarativa, modular y completamente compilable de describir rutas.

Gracias a su arquitectura componible, cada atributo representa una única responsabilidad y genera metadata reutilizable por todo el framework, permitiendo que Routing, Seguridad, SPA Runtime, Hydration, Cache, OpenAPI y futuros paquetes Quantum compartan una infraestructura común sin aumentar el acoplamiento del sistema.

Una propuesta que considero una de las mayores ventajas para VoltStack

Aquí introduciría el concepto de Attribute Macros.

Imagina que una empresa siempre necesita el mismo conjunto de atributos para los endpoints administrativos. En lugar de escribir:

# [Auth]
# [Permission('admin')]
# [Layout('dashboard')]
# [Hydrate]
# [Transition('fade')]
# [Prefetch]

podría definir:

# [AdminPage]

Y registrar ese macro:

AttributeMacro::define(
    AdminPage::class,
    [
        new Auth(),
        new Permission('admin'),
        new Layout('dashboard'),
        new Hydrate(),
        new Transition('fade'),
        new Prefetch(),
    ]
);

Durante la compilación, el macro se expandiría como si todos los atributos hubieran sido escritos explícitamente.

Esto simplifica enormemente el código, mantiene la filosofía declarativa y permite que empresas y paquetes Quantum creen su propio lenguaje de atributos reutilizable sin tocar el núcleo del framework. Creo que sería una característica muy diferenciadora dentro del ecosistema PHP.
