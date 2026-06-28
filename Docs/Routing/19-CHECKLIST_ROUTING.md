# VoltStack Routing - Checklist Tecnico

**Version:** 1.0  
**Estado:** Working Checklist  
**Objetivo:** convertir `18-VERSION_DEVELOPMENT.md` en una lista operativa de implementacion para `Quantum Routing`, priorizando `V1 Core Routing` antes de seguir ampliando el runtime SPA reactivo.

---

## 1. Regla De Uso

Usar este archivo como checklist vivo.

Convencion:

- `[ ]` pendiente
- `[-]` en progreso
- `[x]` completado
- `[!]` bloqueado o con riesgo

Regla operativa:

- cerrar primero el bloque `V1 Core Routing`
- abrir `V2 SPA` solo cuando `V1` cumpla sus criterios de cierre
- no mezclar objetivos de `SPA`, `multitenancy`, `component endpoints` y `AI` dentro del mismo ciclo de implementacion

---

## 2. Meta General

Resultado esperado del primer ciclo:

- router compilable
- matcher HTTP multi-metodo
- dispatcher basico
- middleware pipeline compilado
- metadata minima
- artifacts de routing
- URL generator basico
- contrato formal `404` / `405` / `Allow`

---

## 3. Checklist V1 Core Routing

### 3.1 Definicion De Rutas

- `[x]` definir `RouteDefinition` como estructura inmutable y normalizada
- `[x]` definir `CompiledRoute` como estructura lista para runtime
- `[ ]` definir `CompiledRouteCollection` como coleccion principal del sistema
- `[x]` definir `RouteBuilder` o API fluida minima para registrar rutas
- `[x]` soportar al menos `GET`, `POST`, `PUT`, `PATCH`, `DELETE`, `HEAD`, `OPTIONS`, `ANY`
- `[x]` soportar nombre de ruta
- `[ ]` soportar path
- `[x]` soportar dominio opcional
- `[x]` soportar middleware declarativo por ruta
- `[ ]` soportar metadata minima asociada a la ruta

Criterio de cierre:

- cada ruta del sistema puede representarse primero como `RouteDefinition` y luego compilarse a `CompiledRoute` sin ambiguedad

### 3.2 Registro Y Coleccion

- `[x]` implementar `RouteCollection`
- `[x]` soportar registro de rutas individuales
- `[x]` soportar grupos con prefijo minimo
- `[x]` soportar middleware por grupo
- `[x]` soportar dominio por grupo
- `[x]` detectar rutas duplicadas por `metodo + dominio + path`
- `[x]` detectar nombres de ruta duplicados
- `[x]` definir orden deterministico de compilacion

Criterio de cierre:

- la coleccion valida duplicados y puede producir una salida compilable unica y estable

### 3.3 Compiler Minimo

- `[x]` implementar compilacion de route files o Fluent API a `RouteDefinition`
- `[ ]` implementar validacion de rutas antes de compilar
- `[x]` implementar normalizacion de paths
- `[ ]` implementar merge minimo de metadata
- `[ ]` compilar middleware resueltos por ruta
- `[ ]` compilar constraints basicos
- `[ ]` generar artefacto de coleccion
- `[ ]` generar artefacto de tree o indice de matching
- `[ ]` generar artefacto de metadata
- `[ ]` generar artefacto de pipeline

Criterio de cierre:

- el runtime puede cargar artefactos compilados sin volver a interpretar definiciones originales

### 3.4 Matcher HTTP

- `[x]` seleccionar candidatas por metodo HTTP
- `[x]` soportar rutas estaticas
- `[x]` soportar rutas dinamicas con parametros
- `[x]` soportar dominio opcional
- `[x]` extraer parametros simples
- `[x]` validar constraints basicos
- `[x]` retornar `RouteMatch` con ruta, parametros y referencias minimas
- `[x]` diferenciar `RouteNotFound` de `MethodNotAllowed`
- `[x]` construir lista `Allow` cuando exista mismatch por metodo
- `[x]` definir y probar estrategia `HEAD -> GET`
- `[x]` definir si `OPTIONS` es explicito o automatico

Criterio de cierre:

- el matcher resuelve correctamente `metodo + dominio + path`, y genera `404` o `405` segun corresponda

### 3.5 Dispatcher Basico

- `[x]` implementar `DispatcherResolver`
- `[x]` implementar `ControllerDispatcher`
- `[x]` implementar `ClosureDispatcher`
- `[x]` implementar `ActionDispatcher` si ya existe contrato de acciones en el framework
- `[x]` inyectar parametros ya resueltos desde binding o matcher
- `[x]` delegar la construccion de respuesta a un normalizador
- `[x]` implementar `ResponseNormalizer` minimo para `string`, `array`, `Response`, `JsonResponse` y `View` si aplica
- `[x]` propagar errores del endpoint al handler del kernel

Criterio de cierre:

- una ruta resuelta puede ejecutar un endpoint HTTP basico y devolver una respuesta uniforme

### 3.6 Middleware Pipeline

- `[x]` implementar pipeline global
- `[x]` implementar pipeline por grupo
- `[x]` implementar pipeline por ruta
- `[x]` soportar aliases minimos de middleware
- `[x]` ordenar middleware de forma deterministica
- `[ ]` eliminar duplicados simples durante compilacion
- `[x]` soportar contexto `HTTP`
- `[ ]` reservar extension futura para `SPA` y `API` sin bloquear `V1`

Criterio de cierre:

- la ejecucion del pipeline no requiere resolver orden ni aliases de forma dinamica en cada request

### 3.7 Metadata Minima

- `[ ]` definir `RouteMetadata` minimo
- `[ ]` incluir `name`
- `[ ]` incluir `methods`
- `[ ]` incluir `domain`
- `[ ]` incluir `middleware`
- `[ ]` incluir `auth` si aplica
- `[ ]` incluir `guest` si aplica
- `[ ]` incluir `csrf` si aplica
- `[ ]` incluir `throttle` si aplica
- `[ ]` incluir `runtime` basico solo como metadata, no como logica
- `[ ]` documentar que metadata queda publica y cual permanece interna

Criterio de cierre:

- seguridad, pipeline y runtime pueden consumir la metadata minima sin depender de atributos o archivos originales

### 3.8 Semantica HTTP Base

- `[x]` definir politica oficial para `404 Not Found`
- `[x]` definir politica oficial para `405 Method Not Allowed`
- `[x]` emitir cabecera `Allow` cuando exista `405`
- `[x]` definir comportamiento oficial de `HEAD`
- `[x]` definir comportamiento oficial de `OPTIONS`
- `[x]` decidir si existira `method override`
- `[ ]` definir cuando aplica CSRF segun verbo o contexto
- `[ ]` separar claramente endpoints HTTP convencionales de acciones internas del protocolo reactivo

Criterio de cierre:

- el contrato HTTP del router queda estable y puede ser consumido por el resto del framework sin supuestos ambiguos

### 3.9 Artifacts Y Cache

- `[ ]` definir directorio de artefactos compilados
- `[ ]` guardar `collection`
- `[ ]` guardar `tree`
- `[ ]` guardar `metadata`
- `[ ]` guardar `pipeline`
- `[ ]` guardar `version` o checksum minimo
- `[ ]` implementar loader de artifacts para runtime
- `[ ]` implementar invalidacion basica en desarrollo
- `[ ]` evitar recompilacion durante request en produccion

Criterio de cierre:

- el runtime solo carga artefactos compilados y no recompila rutas en tiempo de peticion

### 3.10 URL Generator Basico

- `[ ]` generar URL por nombre de ruta
- `[ ]` resolver parametros basicos
- `[ ]` soportar query string
- `[ ]` soportar fragment
- `[ ]` soportar absoluto y relativo
- `[ ]` soportar dominio si la ruta lo define
- `[ ]` fallar de forma clara cuando falten parametros

Criterio de cierre:

- el framework puede generar URLs estables sin concatenacion manual

---

## 4. Validacion Tecnica De V1

### 4.1 Pruebas Funcionales Minimas

- `[ ]` registrar y resolver ruta estatica `GET`
- `[ ]` registrar y resolver ruta dinamica `GET /users/{id}`
- `[ ]` registrar ruta con dominio especifico
- `[x]` confirmar que una ruta inexistente devuelve `404`
- `[x]` confirmar que ruta existente con verbo incorrecto devuelve `405`
- `[x]` confirmar que `405` incluye cabecera `Allow`
- `[x]` confirmar comportamiento de `HEAD`
- `[x]` confirmar comportamiento de `OPTIONS`
- `[x]` confirmar que el dispatcher ejecuta controller y closure
- `[x]` confirmar que el pipeline corre en el orden esperado
- `[ ]` confirmar que `URL Generator` construye una ruta nombrada con parametros

### 4.2 Pruebas De Calidad Interna

- `[ ]` no usar reflection en runtime de matching
- `[ ]` no leer atributos en runtime
- `[ ]` no interpretar route files en runtime
- `[ ]` no hacer merge dinamico de metadata en request
- `[ ]` no construir el pipeline en cada request
- `[ ]` no recompilar artefactos en produccion

### 4.3 Pruebas De Errores

- `[x]` ruta duplicada detectada en compilacion
- `[x]` nombre duplicado detectado en compilacion
- `[ ]` constraint invalido detectado correctamente
- `[x]` middleware inexistente detectado antes de runtime
- `[x]` dispatcher invalido produce error controlado
- `[ ]` parametro requerido faltante en `URL Generator` produce error claro

---

## 5. Criterio De Cierre De V1

Marcar `V1 Core Routing` como cerrado solo si todas estas condiciones se cumplen:

- `[x]` existe `RouteDefinition`
- `[x]` existe `CompiledRoute`
- `[ ]` existe `CompiledRouteCollection`
- `[x]` el matcher soporta multi-metodo
- `[x]` el sistema diferencia formalmente `404` y `405`
- `[x]` el sistema emite `Allow` cuando aplica
- `[x]` el dispatcher ejecuta endpoints basicos
- `[x]` el pipeline funciona con orden estable
- `[ ]` la metadata minima ya puede ser consumida por seguridad y runtime
- `[ ]` los artifacts pueden cargarse en runtime
- `[ ]` el URL generator basico funciona

---

## 6. Checklist V1.1

Estas piezas pueden entrar justo despues de `V1`, pero no deben bloquearlo:

- `[ ]` `Route Attributes` HTTP basicos
- `[ ]` `Name`, `Domain` y `Middleware` por atributos
- `[ ]` signed URLs si el modulo de seguridad ya esta listo
- `[ ]` temporary URLs si el contrato de expiracion ya es estable
- `[ ]` constraints mas ricos
- `[ ]` mejores herramientas de compilacion e invalidacion

---

## 7. Checklist V2 SPA

Abrir solo cuando `V1` este cerrado.

### 7.1 Metadata Y Manifest Publico

- `[ ]` definir metadata SPA publica minima
- `[ ]` generar `Frontend Route Manifest` minimo
- `[ ]` exponer `routes`, `methods`, `path` y `public capabilities`
- `[ ]` evitar exponer middleware, policies o metadata privada

### 7.2 SPA Routing Protocol Minimo

- `[ ]` definir payload de navegacion minima
- `[ ]` incluir `target`
- `[ ]` incluir `layout`
- `[ ]` incluir `transition`
- `[ ]` incluir `hydrate`
- `[ ]` incluir `redirect`
- `[ ]` incluir `error`

### 7.3 Integracion Con Runtime SPA Reactivo

- `[ ]` mantener navegacion SPA por `GET`
- `[ ]` mantener acciones internas del protocolo en `POST` hasta nueva decision formal
- `[ ]` reutilizar metadata compilada desde el runtime
- `[ ]` alinear errores de router, dispatcher y runtime
- `[ ]` documentar claramente que verbos HTTP soporta el runtime y cuales solo soporta el framework

Criterio de cierre:

- el runtime puede navegar usando contratos publicos del router sin acoplarse a implementaciones internas

---

## 8. Checklist Postergado

No iniciar estos bloques antes de cerrar `V1` y `V2`:

- `[ ]` `Route Component System` completo
- `[ ]` `Multi-Tenant Routing` completo
- `[ ]` adaptive matching engine
- `[ ]` compiler plugins completos
- `[ ]` pipeline optimizer
- `[ ]` performance budget system
- `[ ]` manifests negociados por runtime adapter
- `[ ]` overlays por tenant
- `[ ]` security graph o capability graph
- `[ ]` AI / Edge / Distributed routing

---

## 9. Riesgos A Vigilar

- `[ ]` sobredisenar el core antes de tener matcher y dispatcher estables
- `[ ]` mezclar necesidades del runtime SPA con el contrato HTTP base del framework
- `[ ]` introducir metadata excesiva antes de definir consumers reales
- `[ ]` construir demasiados subsistemas vacios por simetria documental
- `[ ]` abrir component endpoints o multitenancy antes de cerrar `404/405/Allow`

---

## 10. Orden Recomendado De Ejecucion

Seguir este orden de trabajo:

1. `[-]` `RouteDefinition + RouteCollection + Fluent API`
2. `[ ]` `Compiler` minimo
3. `[ ]` `Matcher` multi-metodo
4. `[ ]` `404 / 405 / Allow / HEAD / OPTIONS`
5. `[ ]` `Dispatcher` basico
6. `[ ]` `Middleware Pipeline`
7. `[ ]` `Metadata` minima
8. `[ ]` `Artifacts`
9. `[ ]` `URL Generator`
10. `[ ]` `Route Attributes`
11. `[ ]` `Frontend Manifest` minimo
12. `[ ]` `SPA Routing Protocol` minimo

---

## 11. Nota Sobre El Runtime SPA Reactivo

Mientras este checklist no cierre `V1`, la recomendacion tecnica es:

- no seguir ampliando el runtime sobre supuestos HTTP incompletos
- mantener `GET` para navegacion
- mantener `POST` para acciones reactivas internas
- dejar que el framework complete primero su soporte multi-metodo real

Cuando `V1` este cerrado, ya se puede decidir si el runtime:

- sigue centralizado en `POST` para acciones internas
- o empieza a consumir `PUT`, `PATCH`, `DELETE` de forma semantica

---

## 12. Bitacora De Avance

Usar esta seccion para registrar hitos reales conforme se vayan cerrando bloques.

### 2026-06

- `[ ]` checklist inicial de `Quantum Routing` definido
- `[-]` inicio de implementacion de `V1 Core Routing`
- `[-]` matcher multi-metodo operativo con `RouteMatch` minimo, dominio opcional, constraints basicos, `method override` controlado y semantica HTTP base (`GET`, `POST`, `PUT`, `PATCH`, `DELETE`, `HEAD`, `OPTIONS`, `ANY`); falta consolidar prioridad/artefactos compilados
- `[x]` contrato `404/405/Allow` implementado y cubierto por pruebas focalizadas, incluyendo `method override` limitado a `POST -> PUT/PATCH/DELETE`
- `[-]` `RouteDefinition`, `CompiledRoute` y `RouteCollection` minimos integrados al router actual; ya hay nombres de ruta, grupos minimos (`prefix`, `domain`, `middleware`) y deteccion de duplicados, pero falta `CompiledRouteCollection` y compilacion formal de colecciones
- `[x]` dispatcher basico operativo con `DispatcherResolver`, `ClosureDispatcher`, `ControllerDispatcher`, `ActionDispatcher`, `ComponentDispatcher` y `ResponseNormalizer`; la separacion posterior por tipos avanzados queda fuera del cierre minimo de `V1`
- `[-]` pipeline HTTP minimo operativo con middleware global, middleware por grupo, middleware declarativo por ruta y aliases minimos resueltos en registro; faltan deduplicacion y compilacion formal del pipeline
- `[ ]` metadata minima consumible
- `[ ]` artifacts de routing operativos
- `[ ]` URL generator basico operativo
