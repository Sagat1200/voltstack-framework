# VoltStack Stable Release 1.0.0

## Introducción

Este documento define el alcance oficial de la primera release estable `1.0.0` de VoltStack.

Su objetivo es fijar una frontera clara entre:

- APIs publicas estables
- implementaciones internas
- capacidades experimentales
- funcionalidades que no deben bloquear la salida de `1.0.0`

La meta no es declarar estable todo el repositorio, sino estabilizar la superficie minima necesaria para construir y mantener aplicaciones reales sobre VoltStack.

---

## Objetivo De 1.0.0

`1.0.0` debe consolidar a VoltStack como un framework PHP utilizable en proyectos reales con una experiencia coherente de:

- bootstrap de aplicacion
- container y resolucion de dependencias
- flujo HTTP completo
- controllers, actions y vistas
- componentes reactivos base
- protocolo reactivo minimo
- seguridad minima operativa

---

## Estado Base Requerido

La release estable `1.0.0` parte del cierre tecnico ya alcanzado en `0.9.x`:

- framework ejecutable
- tests del core en verde
- integracion real con `app-skeleton`
- manejo centralizado de errores
- validacion, CSRF y auth base
- runtime reactivo minimo funcionando

---

## Superficie Publica Estable

Las siguientes piezas forman la superficie publica recomendada para `1.0.0`.

### 1. Bootstrap Y Application Core

- `VoltStack\Framework\Application`
- `VoltStack\Framework\ServiceProvider`
- `Quantum\Bootstrap\Bootstrapper`

### 2. Contratos Publicos

- `VoltStack\Framework\Contracts\Kernel`
- `VoltStack\Framework\Contracts\ExceptionHandler`
- `Quantum\Container\Contracts\ContainerInterface`
- `Quantum\HttpKernel\Contracts\MiddlewareInterface`

### 3. HTTP Layer

- `Quantum\Http\Request`
- `Quantum\Http\Response`
- `Quantum\Http\JsonResponse`
- `Quantum\Http\RedirectResponse`
- `Quantum\Http\ResponseFactory`
- `Quantum\HttpKernel\HttpKernel`

### 4. Routing Y Programacion De Aplicacion

- `Quantum\Routing\Router`
- `Quantum\Controllers\Controller`
- `Quantum\Actions\Action`
- `Quantum\View\View`
- `Quantum\View\ViewFactory`

### 5. Runtime Reactivo Base

- `VoltStack\Runtime\Component\Component`
- `VoltStack\Runtime\Component\ComponentManager`
- `VoltStack\Runtime\Hydration\Snapshot`
- endpoint reactivo `/_volt/action`
- runtime frontend minimo `frontend/runtime/volt.js`

### 6. Helpers Globales

- `app()`
- `config()`
- `view()`
- `response()`
- `e()`
- `volt_runtime_script()`
- `validator()`
- `csrf_token()`
- `csrf_field()`
- `auth()`

---

## APIs Estables Por Comportamiento

En `1.0.0` se consideran estables los siguientes comportamientos observables.

### Bootstrap

- una aplicacion puede construirse con `new Application($basePath)`
- el framework puede cargar configuracion y providers
- la aplicacion puede registrarse y bootearse una sola vez de forma segura

### Container

- soporte para `bind`, `singleton`, `instance`, `scoped`, `alias`, `make`
- autowiring por reflection para clases resolubles
- limpieza de scope por request

### HTTP

- captura o creacion manual de `Request`
- respuestas HTML y JSON consistentes
- resolucion de respuestas desde strings, arrays, `View` y `Component`

### Kernel

- pipeline de middlewares
- dispatch al router
- conversion a `Response`
- manejo centralizado de excepciones

### Routing

- definicion de rutas `GET` y `POST`
- dispatch de controllers invocables
- dispatch de paginas y componentes
- endpoint interno del protocolo reactivo

### Views

- renderizado PHP
- paso de datos a vista
- HTML server-side como respuesta inicial

### Runtime Reactivo

- mount de componentes
- hydration y dehydration
- snapshots con checksum
- ejecucion de acciones publicas
- soporte base para `volt-click`, `volt-model` y `volt-submit`

### Seguridad Minima

- validacion backend-first
- verificacion CSRF para requests mutantes
- checksum de snapshot
- respuestas de error HTML o JSON segun el request

---

## APIs Internas O No Congeladas

Las siguientes areas no deben tratarse como contratos estables de `1.0.0`, aunque ya existan en el repositorio:

- estructura interna exacta de `Application::registerBaseBindings()`
- detalles internos del `ComponentManager`
- formato interno completo del payload reactivo mas alla de los campos minimos actuales
- implementacion interna del `ExceptionHandler`
- clases auxiliares de hydration y protocol usadas solo como infraestructura interna
- organizacion interna futura de drivers de runtime persistente

Estas piezas pueden seguir evolucionando en `1.0.x` si no rompen la experiencia publica documentada.

---

## Campos Minimos Del Protocolo Reactivo

Para `1.0.0` se congela solo el contrato minimo observable del flujo reactivo:

### Request reactivo

```json
{
  "component": "ComponentClass",
  "action": "methodName",
  "snapshot": {
    "state": {},
    "checksum": "signature"
  }
}
```

Campos opcionales actualmente soportados:

- `params`
- `updates`
- `_token`

### Response reactiva

La respuesta JSON debe incluir como minimo:

- `component`
- `snapshot`
- `html`
- `meta`

No se congelan aun efectos avanzados ni navegacion SPA.

---

## Integracion Oficial De Aplicacion

La forma oficial de integrar VoltStack en `1.0.0` queda representada por el flujo validado en `app-skeleton`:

1. crear `Application`
2. cargar configuracion
3. registrar providers
4. bootear la aplicacion
5. registrar rutas
6. resolver `VoltStack\Framework\Contracts\Kernel`
7. manejar el `Request`
8. enviar la `Response`

---

## Lo Que 1.0.0 Garantiza

`1.0.0` debe garantizar:

- que una app puede arrancar y responder requests reales
- que controllers y views funcionan como flujo server-side estable
- que un componente reactivo base puede renderizarse y rerenderizarse
- que el framework puede responder errores de forma consistente
- que la integracion minima con `app-skeleton` sigue operativa

---

## Lo Que 1.0.0 No Garantiza Aun

`1.0.0` no debe prometer todavia:

- navegacion SPA avanzada
- `volt:navigate`
- `volt:show`
- sistema de efectos avanzado
- nested components complejos
- ORM
- queue system
- cache publica estable
- auth empresarial
- CLI completo
- runtime distribuido
- optimizaciones profundas para FrankenPHP

Estas areas pueden evolucionar despues de `1.0.0` o declararse experimentales en releases posteriores.

---

## Criterios De Publicacion De 1.0.0

La release estable puede publicarse cuando se mantenga el siguiente piso minimo:

- tests del framework en verde
- smoke checks del `app-skeleton` en verde
- documentacion alineada al comportamiento real
- errores HTTP y de validacion consistentes
- protocolo reactivo minimo estable
- superficie publica estable claramente identificada

---

## Regla De Mantenimiento Para 1.0.x

En la linea `1.0.x`:

- se pueden corregir bugs internos
- se pueden mejorar implementaciones internas
- se pueden reforzar validaciones y seguridad
- no deben romperse los contratos y comportamientos declarados estables en este documento

---

## Conclusión

La primera release estable de VoltStack no debe medirse por la cantidad total de modulos soñados, sino por la solidez de su nucleo operativo.

`1.0.0` debe consolidar un framework PHP con bootstrap real, HTTP real, renderizado server-side y reactividad base segura, dejando las capacidades mas avanzadas para iteraciones posteriores sin comprometer la estabilidad del core.
