# ROUTE_MATCHER.md

# VoltStack Route Matcher

**Versión:** 1.0
**Estado:** Draft

---

# 1. Introducción

El Route Matcher es el subsistema encargado de localizar la ruta compilada que corresponde a una solicitud entrante.

Su única responsabilidad consiste en realizar el proceso de búsqueda.

No ejecuta middleware.

No ejecuta controladores.

No realiza bindings.

No interpreta atributos.

No genera respuestas.

Simplemente encuentra la mejor coincidencia posible.

---

# 2. Objetivos

El Matcher debe cumplir los siguientes objetivos.

* Máxima velocidad.
* Mínimo consumo de memoria.
* Tiempo de búsqueda determinístico.
* Independencia del Runtime.
* Bajo acoplamiento.
* Fácil sustitución del algoritmo de búsqueda.

---

# 3. Filosofía

El Matcher trabaja exclusivamente sobre rutas compiladas.

Nunca analiza:

* archivos PHP
* atributos
* providers
* grupos
* metadata original

Toda esa información ya fue procesada por el Route Compiler.

---

# 4. Flujo General

```text
Incoming Request
        │
        ▼
Normalize Request
        │
        ▼
Select HTTP Tree
        │
        ▼
Domain Matching
        │
        ▼
Path Matching
        │
        ▼
Parameter Extraction
        │
        ▼
Constraint Validation
        │
        ▼
Compiled Route
```

---

# 5. Entrada

El Matcher recibe únicamente:

```text
CompiledRouteCollection

+

NormalizedRequest
```

Nunca recibe el Container.

Nunca recibe el Kernel.

Nunca recibe el Dispatcher.

---

# 6. Salida

El resultado será un objeto.

```text
RouteMatch
```

Contendrá:

* Route
* Parameters
* Metadata Reference
* Runtime Reference

Nada más.

---

# 7. Arquitectura

```text
RouteMatcher

│

├── MethodMatcher

├── DomainMatcher

├── PathMatcher

├── ConstraintMatcher

├── PriorityResolver

└── ResultBuilder
```

Cada componente posee una única responsabilidad.

---

# 8. Normalización

Antes del matching la Request será normalizada.

Ejemplos:

* eliminar doble "/"
* eliminar barras finales
* normalizar mayúsculas
* normalizar host
* normalizar puerto
* resolver basePath

Esto evita trabajo repetitivo.

---

# 9. Método HTTP

Primero se selecciona el árbol correspondiente.

```text
GET

POST

PUT

PATCH

DELETE

OPTIONS

HEAD

ANY
```

No se recorrerán rutas de otros métodos.

---

# 10. Domain Matching

Después del método.

Se valida el dominio.

Ejemplo:

```text
admin.example.com

tenant.example.com

{tenant}.example.com

api.example.com
```

Esto reduce enormemente el número de rutas candidatas.

---

# 11. Path Matching

Una vez seleccionado el dominio.

Comienza la búsqueda dentro del árbol.

Orden de prioridad.

1. Ruta estática
2. Ruta parametrizada
3. Wildcards
4. Catch All

Siempre gana la ruta más específica.

---

# 12. Parámetros

Durante la búsqueda se extraen.

Ejemplo:

```text
/users/15

↓

user = 15
```

Los parámetros aún no son convertidos.

Solo se almacenan.

---

# 13. Constraints

Después del matching.

Se validan restricciones.

Ejemplo:

```text
Number

UUID

Slug

Regex

Enum

Alpha

AlphaNumeric

Date

Custom
```

Las restricciones fueron compiladas previamente.

---

# 14. Prioridad

Cuando varias rutas coinciden.

El Matcher utiliza reglas determinísticas.

Prioridad:

* Static
* Dynamic
* Wildcard
* Catch All

Nunca depende del orden de registro.

---

# 15. Algoritmos

El sistema soportará distintos motores.

```text
Radix Tree

Trie

Static Map

Regex Map
```

Configurables mediante Drivers.

---

# 16. Radix Tree

Será el algoritmo recomendado.

Ventajas.

* Muy rápido.
* Bajo consumo.
* Excelente para miles de rutas.
* Ideal para servidores persistentes.

---

# 17. Trie

Alternativa para escenarios específicos.

Especialmente útil cuando existan millones de prefijos compartidos.

---

# 18. Static Routes

Las rutas completamente estáticas se almacenarán en una tabla hash.

Ejemplo.

```text
/

/login

/logout

/dashboard

/about
```

Su resolución será prácticamente inmediata.

---

# 19. Dynamic Routes

Las rutas parametrizadas utilizarán el árbol compilado.

Ejemplo.

```text
/users/{id}

/posts/{slug}

/products/{uuid}
```

---

# 20. Catch All

Las rutas del tipo:

```text
/{path*}
```

Siempre serán la última opción.

Nunca competirán con rutas específicas.

---

# 21. Wildcards

Los comodines tendrán prioridad inferior a parámetros normales.

Ejemplo.

```text
/files/*
```

---

# 22. RouteMatch

El objeto generado contendrá.

```text
Route

Parameters

Metadata

Bindings Reference

Pipeline Reference

Runtime Reference
```

No contendrá lógica.

---

# 23. Cache

El Matcher trabaja únicamente con:

```text
CompiledRouteCollection
```

No genera cache.

No modifica cache.

---

# 24. Errores

Puede producir únicamente.

```text
RouteNotFound

MethodNotAllowed

DomainNotAllowed

ConstraintViolation
```

La generación de respuestas corresponde al Dispatcher o al Kernel.

---

# 25. Extensibilidad

Se podrán registrar nuevos algoritmos.

Ejemplo.

```text
TreeMatcher

RegexMatcher

GraphMatcher

CustomMatcher
```

Todos implementarán:

```text
RouteMatcherInterface
```

---

# 26. Integración con Quantum

El Matcher interactúa únicamente con:

* Quantum HTTP
* Route Compiler
* Route Collection
* Metadata System

No conoce el resto del framework.

---

# 27. Rendimiento

El Matcher está diseñado para que cada petición ejecute únicamente:

* Normalización.
* Selección del árbol.
* Matching.
* Extracción de parámetros.
* Validación de restricciones.

No realiza ninguna otra operación.

---

# 28. Testing

Cada algoritmo de búsqueda deberá superar pruebas de:

* rutas estáticas
* rutas dinámicas
* dominios
* prioridades
* parámetros
* constraints
* rendimiento
* concurrencia

---

# 29. Visión

El Route Matcher constituye el núcleo del proceso de búsqueda de VoltStack.

Su diseño desacoplado, determinístico y orientado a estructuras compiladas permite resolver rutas con un coste mínimo, independientemente del tamaño de la aplicación, manteniendo una arquitectura preparada para servidores persistentes, aplicaciones SPA, SSR y futuras extensiones del framework.


Mejora propuesta para superar a Laravel y Symfony

Aquí añadiría una característica que no existe de forma integrada en los routers PHP actuales: un Adaptive Matching Engine.

En lugar de usar un único algoritmo para todas las rutas, el compilador clasificaría automáticamente las rutas y generaría varios índices especializados:

Static Map para rutas completamente estáticas (/login, /about).
Radix Tree para rutas dinámicas (/users/{id}).
Domain Index para aplicaciones multi-dominio y multitenant.
Regex Index solo para las pocas rutas que realmente necesiten expresiones regulares complejas.
Fallback Index para comodines y catch-all.

El RouteMatcher únicamente consultaría el índice adecuado según el tipo de petición. Así evitas recorrer estructuras innecesarias y reduces el trabajo del algoritmo principal. En aplicaciones grandes (miles o decenas de miles de rutas), esta estrategia puede ofrecer una mejora notable tanto en latencia como en consumo de memoria, especialmente en entornos como FrankenPHP, RoadRunner o Swoole, que son un objetivo central para VoltStack.
