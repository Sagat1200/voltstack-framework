# VoltStack Version Guidelines

## Introducción

Este documento define qué debe desarrollarse en VoltStack para garantizar un funcionamiento correcto, estable, extensible y coherente con la visión arquitectónica del framework.

No describe únicamente versiones semánticas, sino las reglas técnicas que determinan qué piezas deben existir, qué nivel de madurez debe alcanzar cada subsistema y qué criterios debe cumplir cada fase antes de avanzar a la siguiente.

VoltStack no debe publicarse ni evolucionar únicamente por cantidad de código escrito, sino por la solidez real de sus capacidades base.

---

## Objetivo Principal

Establecer una guía oficial para:

- definir el alcance real de cada versión
- documentar los subsistemas obligatorios del framework
- evitar vacíos arquitectónicos entre módulos
- asegurar que cada release tenga utilidad práctica
- mantener consistencia entre visión, implementación y DX

---

## Filosofía de Versionado

### 1. Foundation First

Antes de agregar features avanzadas, VoltStack debe tener un núcleo estable.

---

### 2. Vertical Slices Over Incomplete Modules

Es preferible completar flujos funcionales de punta a punta antes que crear muchos módulos incompletos.

Ejemplo correcto:

```txt
request
↓
router
↓
controller o component
↓
render
↓
response
```

---

### 3. Runtime-Aware Evolution

Cada nueva versión debe considerar compatibilidad con:

- PHP-FPM
- FrankenPHP
- runtimes persistentes futuros

---

### 4. Security And Stability Before Convenience

La ergonomía es importante, pero nunca debe adelantarse a:

- seguridad
- predictibilidad
- aislamiento por request
- consistencia del runtime

---

### 5. Release By Capability

Una versión solo debe declararse completa cuando entrega capacidades reales utilizables por aplicaciones.

---

## Reglas Generales de Versionado

VoltStack adoptará una estrategia progresiva:

```txt
0.x = fase experimental y fundacional
1.0 = framework estable listo para producción general
2.x = expansión avanzada del runtime y del ecosistema
```

---

## Interpretación de Versiones

### 0.x

Versiones previas a producción estable.

Objetivo:

- construir el core
- validar arquitectura
- estabilizar APIs internas
- definir contratos base

Estas versiones pueden introducir cambios importantes en APIs internas.

---

### 1.x

Primera línea estable.

Objetivo:

- ofrecer framework utilizable en proyectos reales
- mantener compatibilidad razonable
- estabilizar contratos públicos
- garantizar experiencia coherente de desarrollo

---

### 2.x

Evolución mayor del runtime.

Objetivo:

- capacidades reactivas avanzadas
- nuevos drivers
- optimizaciones distribuidas
- runtime extendido

---

## Qué Debe Desarrollarse Para El Funcionamiento Correcto Del Framework

VoltStack necesita completar como mínimo las siguientes áreas.

---

## 1. Bootstrap System

El bootstrap es el punto de entrada real del framework.

Debe incluir:

- carga del autoload
- inicialización de `Application`
- registro de paths base
- carga de configuración
- carga de providers
- inicialización del container
- inicialización del kernel
- preparación del entorno

### Entregables mínimos

```txt
Application
Bootstrapper
ServiceProvider base
Environment loader
Path resolver
```

### Resultado esperado

La aplicación puede arrancar correctamente en cualquier entorno soportado.

---

## 2. Application Core

El `Application Core` coordina todo el framework.

Debe incluir:

- container principal
- registro de servicios
- gestión de aliases
- lifecycle de aplicación
- acceso a configuración
- resolución de contratos

### Clases mínimas

```txt
Application.php
Kernel.php
ServiceProvider.php
```

---

## 3. Container System

El container es obligatorio desde la primera fase.

Debe soportar:

- `bind`
- `singleton`
- `instance`
- `make`
- autowiring por reflection
- resolución de dependencias constructoras
- alias básicos

### Objetivo inicial

Resolver controllers, actions, services y dependencias del kernel.

### Objetivo posterior

Agregar:

- `scoped`
- contextual binding
- tags
- runtime-safe instances

---

## 4. Configuration System

Sin configuración estable no existe framework consistente.

Debe incluir:

- archivos PHP de configuración
- lectura de variables de entorno
- repositorio de configuración
- valores por defecto
- acceso mediante helper y facade

### Entregables mínimos

```txt
config/app.php
config/runtime.php
config/view.php
ConfigRepository
env loader
```

---

## 5. HTTP Layer

VoltStack necesita una capa HTTP propia o claramente abstraída.

Debe incluir:

- `Request`
- `Response`
- `JsonResponse`
- `RedirectResponse`
- manejo de headers
- query params
- input data
- cookies
- files

### Resultado esperado

Controllers y componentes pueden trabajar sobre objetos HTTP consistentes.

---

## 6. HttpKernel

El `HttpKernel` debe actuar como orquestador del request lifecycle.

Debe incluir:

- recepción de request
- middleware pipeline
- dispatch al router
- manejo centralizado de excepciones
- terminación de request

### Flujo mínimo

```txt
Request
↓
Middleware Pipeline
↓
Router
↓
Controller o Component
↓
Response
```

---

## 7. Routing System

El router es parte esencial del framework.

Debe soportar:

- `GET`
- `POST`
- `PUT`
- `PATCH`
- `DELETE`
- nombres de ruta
- parámetros
- groups simples
- middlewares por ruta
- rutas hacia controllers
- rutas hacia páginas o componentes

### Entregables mínimos

```txt
Router
Route
RouteCollection
RouteDispatcher
```

---

## 8. Controller Layer

VoltStack debe soportar controllers tradicionales desde el inicio.

Casos de uso:

- páginas no reactivas
- endpoints de integración
- APIs
- respuestas JSON
- redirects

### Requerimientos

- controllers invocables
- métodos de acción
- resolución por container
- request injection

---

## 9. Actions Layer

Las `Actions` permiten desacoplar lógica de negocio de controllers y components.

Debe soportarse:

- clases reutilizables
- resolución por container
- ejecución explícita
- composición de lógica compartida

### Ejemplo conceptual

```php
CreateUserAction::run($data);
```

o:

```php
$action = app(CreateUserAction::class);
$action->handle($data);
```

---

## 10. View System

VoltStack necesita un sistema de vistas desde el MVP.

Debe incluir:

- renderizado PHP
- paso de datos
- layouts básicos
- fragments
- escape por defecto
- respuestas HTML consistentes

### Resultado esperado

Una ruta puede renderizar correctamente HTML inicial.

---

## 11. Component System

Los componentes son obligatorios para el modelo reactivo de VoltStack.

Cada componente debe poder tener:

- props o estado público
- hooks mínimos
- método `render()`
- acciones públicas
- snapshot serializable

### Hooks iniciales

```txt
mount
render
hydrate
dehydrate
```

---

## 12. Hydration System

La hydration es una de las piezas más críticas del framework.

Debe incluir:

- reconstrucción de estado
- serialización segura
- checksum
- restauración de metadata mínima
- validación de integridad

### Riesgo principal

Estado corrupto o manipulado desde el frontend.

---

## 13. Volt Protocol

El protocolo es el puente entre frontend y backend reactivo.

Debe transportar:

- nombre de componente
- acción solicitada
- estado
- snapshot
- checksum
- HTML actualizado
- efectos mínimos
- errores normalizados

### Primer payload recomendado

```json
{
  "component": "CounterPage",
  "action": "increment",
  "snapshot": {
    "state": {
      "count": 1
    },
    "checksum": "hash"
  }
}
```

---

## 14. Reactive Runtime

El runtime reactivo debe existir desde las primeras versiones experimentales.

Debe incluir:

- resolución de componentes
- hydrate
- execute action
- rerender
- dehydrate
- response protocol

### Flujo mínimo

```txt
request reactivo
↓
validate payload
↓
hydrate component
↓
execute action
↓
render
↓
dehydrate
↓
json response
```

---

## 15. Frontend Runtime Mínimo

El frontend runtime inicial debe ser pequeño.

Debe soportar:

- captura de eventos simples
- envío de requests reactivas
- recepción de payload
- reemplazo de fragmento HTML
- sincronización mínima con el backend

### No debe incluir aún

- virtual DOM complejo
- diffing pesado en cliente
- sistema avanzado de plugins

---

## 16. SPA Navigation Base

No es necesario que la primera implementación incluya navegación SPA completa, pero sí debe existir la base para:

- interceptar navegación
- cargar páginas parciales
- reemplazar contenido principal
- preservar estado donde aplique

### MVP aceptable

Una navegación parcial funcional entre páginas VoltStack sin recarga completa.

---

## 17. Error Handling

VoltStack debe tener manejo unificado de errores.

Debe soportar:

- excepción HTTP tradicional
- error de componente reactivo
- error de protocolo
- validación fallida
- respuesta segura en producción

---

## 18. Security Layer

Sin esta capa el framework no es confiable.

Debe incluir:

- CSRF
- checksum de snapshots
- validación de payload
- validación de acciones
- protección de propiedades sensibles
- escape por defecto en render
- aislamiento por request

### Reglas mínimas

- nunca confiar en estado enviado por el cliente
- nunca serializar secretos
- nunca ejecutar métodos arbitrarios

---

## 19. Runtime Persistence Safety

Aunque la optimización persistente llegue más tarde, el diseño debe ser seguro desde el inicio.

Debe prepararse para:

- scoped services
- reset de request scope
- limpieza de auth
- limpieza de sesión
- limpieza de estado temporal

---

## 20. Testing Infrastructure

VoltStack necesita pruebas desde las primeras iteraciones.

Debe cubrir:

- container
- router
- kernel
- responses
- views
- components
- hydration
- protocol

### Suites mínimas

```txt
tests/Unit
tests/Feature
tests/Runtime
```

---

## 21. Developer Experience

La DX debe existir desde temprano, aunque sea mínima.

Debe incluir:

- estructura de proyecto clara
- helpers básicos
- facades mínimas
- errores legibles
- documentación de uso

### CLI puede esperar

El CLI es importante, pero no es más prioritario que tener el flujo HTTP y reactivo funcionando.

---

## 22. Documentation Requirements

Cada versión importante debe documentar:

- qué módulos existen
- qué módulos son estables
- qué APIs son experimentales
- qué limitaciones existen
- qué runtimes están soportados

---

## Criterios de Salida por Versión

Una versión no debe considerarse lista si:

- el flujo base request/response no funciona
- no existen tests mínimos del core
- el sistema de componentes no puede hidratarse correctamente
- la validación del protocolo es insuficiente
- la documentación no refleja el estado real del código

---

## Criterios de Aprobación del MVP

VoltStack MVP debe poder:

- arrancar una aplicación
- resolver dependencias
- manejar requests HTTP
- despachar rutas
- ejecutar controllers
- renderizar vistas
- montar componentes
- ejecutar una acción reactiva
- devolver respuesta Volt Protocol mínima

---

## Qué No Debe Bloquear La Primera Versión

Estas capacidades pueden esperar:

- ORM completo
- queue system
- auth empresarial
- realtime nativo
- streaming UI
- runtime distribuido
- drivers múltiples completos
- concurrent rendering

---

## Regla Estratégica Principal

El orden correcto de construcción debe ser:

```txt
bootstrap
↓
container
↓
http
↓
http kernel
↓
routing
↓
controllers y actions
↓
views
↓
components
↓
hydration
↓
protocol
↓
frontend runtime
```

---

## Resultado Esperado

Si esta guía se sigue correctamente, VoltStack podrá evolucionar desde una base simple hacia un framework reactivo completo sin perder:

- coherencia arquitectónica
- seguridad
- estabilidad
- extensibilidad
- DX para PHP

---

## Conclusión

El funcionamiento correcto de VoltStack depende de construir primero un núcleo real y verificable, no una colección de conceptos aislados.

Las versiones deben representar capacidades concretas y operativas, asegurando que cada fase deje una base estable sobre la cual pueda crecer el runtime reactivo, la experiencia SPA y la optimización para runtimes persistentes.
