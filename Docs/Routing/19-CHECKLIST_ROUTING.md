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
- `[x]` definir `CompiledRouteCollection` como coleccion principal del sistema
- `[x]` definir `RouteBuilder` o API fluida minima para registrar rutas
- `[x]` soportar al menos `GET`, `POST`, `PUT`, `PATCH`, `DELETE`, `HEAD`, `OPTIONS`, `ANY`
- `[x]` soportar nombre de ruta
- `[ ]` soportar path
- `[x]` soportar dominio opcional
- `[x]` soportar middleware declarativo por ruta
- `[x]` soportar metadata minima asociada a la ruta

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
- `[x]` implementar validacion de rutas antes de compilar
- `[x]` implementar normalizacion de paths
- `[x]` implementar merge minimo de metadata
- `[x]` compilar middleware resueltos por ruta
- `[x]` compilar constraints basicos
- `[x]` generar artefacto de coleccion
- `[x]` generar artefacto de tree o indice de matching
- `[x]` generar artefacto de metadata
- `[x]` generar artefacto de pipeline

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
- `[x]` eliminar duplicados simples durante compilacion
- `[x]` soportar contexto `HTTP`
- `[ ]` reservar extension futura para `SPA` y `API` sin bloquear `V1`

Criterio de cierre:

- la ejecucion del pipeline no requiere resolver orden ni aliases de forma dinamica en cada request

Nota del corte actual:

- el kernel y cada `CompiledRoute` mantienen un `CompiledMiddlewarePipeline` inmutable en memoria
- el runtime ya no reconstruye la cadena del pipeline en cada request y ya puede resolver `pipeline.php` cuando el artifact existe

### 3.7 Metadata Minima

- `[x]` definir `RouteMetadata` minimo
- `[x]` incluir `name`
- `[x]` incluir `methods`
- `[x]` incluir `domain`
- `[x]` incluir `middleware`
- `[x]` incluir `auth` si aplica
- `[x]` incluir `guest` si aplica
- `[x]` incluir `csrf` si aplica
- `[x]` incluir `throttle` si aplica
- `[x]` incluir `runtime` basico solo como metadata, no como logica
- `[x]` documentar que metadata queda publica y cual permanece interna

Nota del corte actual:

- la metadata consumible en runtime incluye `name`, `methods`, `domain`, `runtime`, `auth`, `guest`, `csrf` y `throttle`
- `middleware` forma parte de la metadata compilada para consumo interno de pipeline y no debe considerarse todavia metadata publica de manifiesto

Criterio de cierre:

- seguridad, pipeline y runtime pueden consumir la metadata minima sin depender de atributos o archivos originales

### 3.8 Semantica HTTP Base

- `[x]` definir politica oficial para `404 Not Found`
- `[x]` definir politica oficial para `405 Method Not Allowed`
- `[x]` emitir cabecera `Allow` cuando exista `405`
- `[x]` definir comportamiento oficial de `HEAD`
- `[x]` definir comportamiento oficial de `OPTIONS`
- `[x]` decidir si existira `method override`
- `[x]` definir cuando aplica CSRF segun verbo o contexto
- `[x]` separar claramente endpoints HTTP convencionales de acciones internas del protocolo reactivo

Criterio de cierre:

- el contrato HTTP del router queda estable y puede ser consumido por el resto del framework sin supuestos ambiguos

### 3.9 Artifacts Y Cache

- `[x]` definir directorio de artefactos compilados
- `[x]` guardar `collection`
- `[x]` guardar `tree`
- `[x]` guardar `metadata`
- `[x]` guardar `pipeline`
- `[x]` guardar `version` o checksum minimo
- `[-]` implementar loader de artifacts para runtime
- `[x]` implementar invalidacion basica en desarrollo
- `[x]` evitar recompilacion durante request en produccion

Nota del corte actual:

- el artefacto de pipeline ya se guarda en `storage/framework/cache/routes/pipeline.php`
- el artefacto de coleccion ya se guarda en `storage/framework/cache/routes/collection.php`
- el artefacto de tree ya se guarda en `storage/framework/cache/routes/tree.php`
- el artefacto de metadata ya se guarda en `storage/framework/cache/routes/metadata.php`
- el artefacto de version ya se guarda en `storage/framework/cache/routes/version.php`
- `version.php` mantiene version minima y checksum `sha256` por artifact para `collection`, `tree`, `metadata` y `pipeline`
- `Router` ya puede cargar `pipeline.php` de forma perezosa y usar `collection.php` junto con `metadata.php` y `tree.php` de forma explicita mediante `reloadCollectionArtifacts()`, validando antes `version.php` si existe
- en `production`, el router activa automaticamente artifacts validos sin requerir `reload*Artifacts()` manual y reutiliza la coleccion compilada en memoria cuando debe caer al fallback vivo
- en `app.env = local|development|dev`, el router invalida automaticamente `collection.php`, `tree.php`, `metadata.php`, `pipeline.php` y `version.php` cuando se intenta reutilizarlos, salvo que `routing.artifacts.invalidate_in_development` se configure en `false`
- `tree.php` indexa candidatas por path estatico y buckets dinamicos basados en `first segment + segment count`, sin duplicar la semantica final de dominio, `HEAD`, `OPTIONS` ni `405`
- `metadata.php` mantiene snapshots completos de `RouteMetadata` por indice de ruta y puede restaurarlos sobre la coleccion compilada cuando `collection.php` no los trae o queda desactualizado
- la serializacion de `collection.php` aun excluye closures y otras acciones no serializables

Criterio de cierre:

- el runtime solo carga artefactos compilados y no recompila rutas en tiempo de peticion

### 3.10 URL Generator Basico

- `[x]` generar URL por nombre de ruta
- `[x]` resolver parametros basicos
- `[x]` soportar query string
- `[x]` soportar fragment
- `[x]` soportar absoluto y relativo
- `[x]` soportar dominio si la ruta lo define
- `[x]` fallar de forma clara cuando falten parametros

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
- `[x]` confirmar que `URL Generator` construye una ruta nombrada con parametros

### 4.2 Pruebas De Calidad Interna

- `[ ]` no usar reflection en runtime de matching
- `[ ]` no leer atributos en runtime
- `[x]` no interpretar route files en runtime
- `[x]` no hacer merge dinamico de metadata en request
- `[x]` no construir el pipeline en cada request
- `[x]` no recompilar `CompiledRouteCollection` cuando se usa `collection.php`
- `[x]` no recompilar artefactos en produccion

### 4.3 Pruebas De Errores

- `[x]` ruta duplicada detectada en compilacion
- `[x]` nombre duplicado detectado en compilacion
- `[x]` constraint invalido detectado correctamente
- `[x]` middleware inexistente detectado antes de runtime
- `[x]` dispatcher invalido produce error controlado
- `[x]` parametro requerido faltante en `URL Generator` produce error claro

---

## 5. Criterio De Cierre De V1

Marcar `V1 Core Routing` como cerrado solo si todas estas condiciones se cumplen:

- `[x]` existe `RouteDefinition`
- `[x]` existe `CompiledRoute`
- `[x]` existe `CompiledRouteCollection`
- `[x]` el matcher soporta multi-metodo
- `[x]` el sistema diferencia formalmente `404` y `405`
- `[x]` el sistema emite `Allow` cuando aplica
- `[x]` el dispatcher ejecuta endpoints basicos
- `[x]` el pipeline funciona con orden estable
- `[x]` la metadata minima ya puede ser consumida por seguridad y runtime
- `[ ]` los artifacts pueden cargarse en runtime
- `[x]` el URL generator basico funciona

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
- `[x]` `RouteDefinition`, `CompiledRoute`, `CompiledRouteCollection` y `RouteCollection` minimos integrados al router actual; `collection.php` ya recompone rutas compiladas serializables, `tree.php` ya indexa candidatas de matching, `metadata.php` ya restaura snapshots de `RouteMetadata` y el router ya puede activar automaticamente estos artifacts en produccion como fuente principal valida
- `[x]` validacion estructural previa a compilacion activa para `compiled()`, `tree.php`, `metadata.php`, `pipeline.php` y `collection.php`; detecta placeholders mal formados, nombres de parametro invalidos y parametros duplicados antes de compilar
- `[x]` los constraints invalidos ya se detectan durante la validacion previa a compilacion mediante verificacion real de regex, antes de llegar al matcher o a la generacion de artifacts
- `[x]` los constraints basicos ya se compilan a fragments regex normalizados para runtime y `collection.php`, incluyendo normalizacion de capturas simples internas a grupos no capturantes
- `[x]` politica base de CSRF definida: en HTTP convencional aplica automaticamente solo a verbos mutantes (`POST`, `PUT`, `PATCH`, `DELETE` y overrides), permite opt-out declarativo por metadata de ruta cuando el middleware corre a nivel de ruta, y excluye el endpoint interno `/_volt/action` porque ese protocolo valida CSRF dentro de su propio controller
- `[x]` los endpoints internos del runtime (`/_volt/runtime.js`, `/_volt/action`) ya se registran con metadata explicita (`transport=internal`, `endpoint`, `protocol=volt`) y `Request` ya puede distinguirlos de requests HTTP convencionales sin depender solo de strings dispersos
- `[x]` el bootstrap ya puede omitir la interpretacion de route files en produccion cuando existe un `collection.php` utilizable; el runtime arranca sobre artifacts validos sin reevaluar `routes/web.php`
- `[x]` dispatcher basico operativo con `DispatcherResolver`, `ClosureDispatcher`, `ControllerDispatcher`, `ActionDispatcher`, `ComponentDispatcher` y `ResponseNormalizer`; la separacion posterior por tipos avanzados queda fuera del cierre minimo de `V1`
- `[x]` pipeline HTTP minimo operativo con middleware global, middleware por grupo, middleware declarativo por ruta, aliases minimos resueltos en registro, deduplicacion estable, compilacion formal en memoria, artefacto persistido y loader automatico en runtime para produccion
- `[x]` metadata minima consumible
- `[x]` artifacts de routing operativos con `collection.php`, `tree.php`, `metadata.php`, `pipeline.php` y `version.php`; ya existe invalidacion automatica base en desarrollo y politica minima de no recompilacion durante request en produccion
- `[x]` URL generator basico operativo mediante `Router::route(...)` y helper global `route()`, con soporte para parametros, query string residual o explicita (`_query`), fragment (`_fragment`), dominio y generacion absoluta/relativa
