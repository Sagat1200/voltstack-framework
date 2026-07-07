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
- `[x]` soportar path
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
- `[x]` reservar extension futura para `SPA` y `API` sin bloquear `V1`

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
- `[x]` implementar loader de artifacts para runtime
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

- `[x]` registrar y resolver ruta estatica `GET`
- `[x]` registrar y resolver ruta dinamica `GET /users/{id}`
- `[x]` registrar ruta con dominio especifico
- `[x]` confirmar que una ruta inexistente devuelve `404`
- `[x]` confirmar que ruta existente con verbo incorrecto devuelve `405`
- `[x]` confirmar que `405` incluye cabecera `Allow`
- `[x]` confirmar comportamiento de `HEAD`
- `[x]` confirmar comportamiento de `OPTIONS`
- `[x]` confirmar que el dispatcher ejecuta controller y closure
- `[x]` confirmar que el pipeline corre en el orden esperado
- `[x]` confirmar que `URL Generator` construye una ruta nombrada con parametros

### 4.2 Pruebas De Calidad Interna

- `[x]` no usar reflection en runtime de matching
- `[x]` no leer atributos en runtime
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
- `[x]` los artifacts pueden cargarse en runtime
- `[x]` el URL generator basico funciona

---

## 6. Checklist V1.1

Estas piezas pueden entrar justo despues de `V1`, pero no deben bloquearlo:

- `[x]` `Route Attributes` HTTP basicos
- `[x]` `Name`, `Domain` y `Middleware` por atributos
- `[x]` signed URLs si el modulo de seguridad ya esta listo
- `[x]` temporary URLs si el contrato de expiracion ya es estable
- `[x]` constraints mas ricos
- `[x]` mejores herramientas de compilacion e invalidacion

## 6.2 Checklist V1.2 Ergonomia De Recursos

Este bloque ya no toca infraestructura base del router. Se enfoca en mejorar la API publica de `Route::resource()` sobre la base que ya esta cerrada.

- `[x]` nested resources por notacion de recurso (`posts.comments`)
- `[x]` `shallow()` para desacoplar las rutas miembro del path padre
- `[x]` personalizacion de placeholders sobre recurso hijo y padres mediante `parameter(...)` y `parameters([...])`
- `[x]` personalizacion adicional de paths o verbos REST por accion
- `[x]` estrategia uniforme de `missing()` o resolucion declarativa cuando falte un recurso enlazado
- `[x]` documentacion publica actualizada en `Docs/Routing/20-Use.md`
- `[x]` pruebas de integracion y unidad para generacion de URLs y dispatch real

Criterio de cierre del corte actual:

- los recursos anidados ya pueden registrarse y generar nombres `posts.comments.*`
- `shallow()` ya mueve `show/edit/update/destroy` a rutas miembro cortas sin romper `index/create/store`
- el cambio de nombres de placeholders sigue siendo compatible con el binder de controladores
- existe una primera capa de route binding tipado mediante `RouteBindableInterface` y `missing()` ya puede reaccionar con status o redirect cuando el binder no encuentra el recurso
- `paths([...])` y `verbs([...])` ya permiten ajustar la URI publica y los metodos HTTP por accion sin re-registrar manualmente cada ruta del recurso
- el bloque `V1.2` queda funcionalmente cerrado; el siguiente avance ya puede salir de ergonomia de recursos y pasar al siguiente bloque del roadmap

---

## 7. Checklist V2 SPA

Abrir solo cuando `V1` este cerrado.

### 7.1 Metadata Y Manifest Publico

- `[x]` definir metadata SPA publica minima
- `[x]` generar `Frontend Route Manifest` minimo
- `[x]` exponer `routes`, `methods`, `path`, `public capabilities` y politica publica minima
- `[x]` evitar exponer middleware, metadata privada o policies internas no publicas

Propuesta minima recomendada para abrir este bloque:

- el `Frontend Route Manifest` debe derivarse solo de artifacts compilados y metadata publica ya normalizada
- el manifiesto minimo no debe depender de adapters concretos ni de implementaciones internas del router
- el manifiesto minimo puede publicarse como `JSON` y versionarse por separado del artifact interno de routing

Contrato minimo sugerido:

```json
{
  "protocol": {
    "name": "VoltStack Frontend Manifest",
    "version": "1.0"
  },
  "version": {
    "manifest": 1,
    "checksum": "sha256..."
  },
  "routes": [
    {
      "name": "users.show",
      "path": "/users/{user}",
      "methods": ["GET"],
      "capabilities": ["navigate", "hydrate"],
      "policy": {
        "document": "reload",
        "navigation": "reload"
      },
      "runtime": {
        "layout": "dashboard",
        "transition": "fade",
        "hydrate": true
      }
    }
  ]
}
```

Campos publicos minimos por ruta:

- `name`: identificador publico y estable para navegacion y generacion de URLs en clientes
- `path`: patron publico navegable ya normalizado
- `methods`: verbos HTTP publicos soportados por la ruta; para SPA minima solo se debe consumir `GET`
- `capabilities`: lista declarativa de capacidades publicas como `navigate`, `hydrate`, `prefetch`
- `policy.document`: contrato publico minimo de documento como `spa` o `reload`
- `policy.navigation`: politica publica minima de navegacion como `auto`, `spa` o `reload`
- `runtime.layout`: layout publico consumible por el runtime sin exponer detalles internos
- `runtime.transition`: sugerencia visual publica y opcional
- `runtime.hydrate`: flag publico minimo para indicar si la pantalla requiere hidratacion SPA

Campos que no deben serializarse en esta fase:

- middleware
- policies internas no publicas
- aliases internos
- constraints internas no necesarias para el cliente
- metadata privada de seguridad
- class-strings PHP, closures o referencias del container

Criterio minimo para marcar `7.1`:

- el runtime puede decidir si una ruta publica es navegable por SPA usando solo `name`, `path`, `methods` y `capabilities`
- el manifest y el payload SPA comparten la misma politica publica minima para `document` y `navigation`
- el manifiesto no expone datos privados ni obliga al cliente a conocer `Route`, `CompiledRoute` o artifacts internos
- la informacion necesaria para `layout`, `transition` y `hydrate` ya sale de metadata compilada publica y estable
- el runtime puede usar el manifest como fuente previa para decidir `prefetch` y `reload` sin esperar a parsear el documento destino

Nota del corte actual:

- existe `FrontendRouteManifest` como contrato publico minimo serializable
- existe `FrontendRouteManifestStore` que compila el manifiesto desde `CompiledRouteCollection`
- el manifiesto se publica como `JSON` cacheable en `/_volt/routes-manifest.json`
- el manifiesto actual expone `name`, `path`, `methods`, `capabilities`, un bloque `policy` publico minimo y un bloque `runtime` publico reducido
- el bloque `policy` publico actual solo incluye `document` y `navigation`
- el bloque `runtime` publico actual solo incluye `layout`, `transition` y `hydrate`
- la serializacion excluye rutas internas como `/_volt/*`, rutas sin nombre, `middleware`, `auth`, policies internas adicionales y referencias internas del container
- hay pruebas focalizadas de store y de endpoint publico para validar serializacion minima y exclusion de metadata privada
- el runtime JS ya carga `/_volt/routes-manifest.json` bajo demanda y lo usa para deshabilitar `prefetch` no permitido y adelantar `reload` cuando la politica publica ya lo declara

### 7.2 SPA Routing Protocol Minimo

- `[x]` definir payload de navegacion minima
- `[x]` incluir `target`
- `[x]` incluir `layout`
- `[x]` incluir `transition`
- `[x]` incluir `hydrate`
- `[x]` incluir `redirect`
- `[x]` incluir `error`

Propuesta minima recomendada para este payload:

- debe construirse a partir del `Frontend Route Manifest` publico y no desde estructuras internas del router
- debe describir navegacion, no modelar estado completo de la aplicacion
- debe reservar extensiones futuras sin introducir subsistemas vacios en `V2`

Contrato minimo sugerido:

```json
{
  "navigation": {
    "target": "/users/15",
    "method": "GET"
  },
  "screen": {
    "route": "users.show"
  },
  "runtime": {
    "layout": "dashboard",
    "transition": "fade",
    "hydrate": true
  },
  "redirect": null,
  "error": null
}
```

Campos minimos del payload:

- `navigation.target`: URL final que el runtime debe navegar
- `navigation.method`: verbo efectivo; en esta fase debe restringirse a `GET`
- `screen.route`: nombre publico de la ruta resuelta para trazabilidad y tooling
- `runtime.layout`: layout publico aplicable a la pantalla destino
- `runtime.transition`: transicion publica opcional
- `runtime.hydrate`: indica si la respuesta debe hidratarse en el runtime SPA
- `redirect`: instruccion de redireccion uniforme cuando aplica
- `error`: representacion uniforme de error navegable cuando la navegacion no puede completarse

Campos explicitamente fuera del payload minimo:

- payloads de acciones reactivas
- metadata privada del router
- middleware resuelto
- policies
- estado global completo
- snapshots de componentes salvo que una fase posterior formalice ese contrato

Dependencia formal recomendada:

- no marcar `7.2` como cerrado antes de cerrar `7.1`
- todo campo de `layout`, `transition` y `hydrate` debe poder justificarse previamente en el manifiesto publico
- si un dato no puede exponerse de forma publica y estable en `7.1`, no debe entrar todavia al payload minimo

Nota del corte actual:

- existe `SpaNavigationPayload` como contrato serializable minimo del protocolo SPA
- existe `SpaNavigationPayloadFactory` para construir el payload desde `Request` y `Response`
- el payload actual se expone en el header `X-Volt-Navigation` solo para requests con `X-Requested-With: VoltStack` y `X-Volt-Navigate: true`
- el bloque `navigation` actual expone `target` y `method`
- el bloque `screen` actual expone `route`
- el bloque `runtime` actual expone `layout`, `transition` y `hydrate`
- el bloque `redirect` actual se proyecta desde respuestas `3xx` con header `Location`
- el bloque `error` actual se proyecta uniformemente desde respuestas `4xx/5xx`
- hay pruebas focalizadas para exito, redirect y error del payload minimo

Estado de integracion:

- el runtime actual sigue navegando con HTML y parseo de documento como camino principal
- `7.2` ya queda definido y emitido como contrato publico minimo sin acoplar el runtime a internals del router
- el runtime ya consume `X-Volt-Navigation` como fuente complementaria para `target`, `route`, `redirect` y proyecciones runtime publicas
- los marcadores explicitos del documento siguen siendo la fuente primaria para `layout`, `transition` y `hydrate` cuando el HTML declara overrides
- la deteccion de cambio de layout en `visit()` ya puede apoyarse en el `layout` del contrato SPA cuando el documento destino no declara `data-volt-layout`
- `visit()` ya puede resolver `pageTransition` desde el contrato SPA cuando el documento no declara una transicion explicita
- la informacion de `hydrate` del contrato SPA ya se proyecta en hooks y telemetria de navegacion aunque el parcheo del documento siga siendo HTML-first
- `applyDocumentPayload()` ya materializa `hydrate` del contrato SPA como atributos `data-volt-hydrate*` sobre `body` cuando el HTML destino no declara hidratacion explicita
- el contrato SPA ya proyecta una politica publica minima con `document` y `navigation`
- `visit()` ya usa esa politica del contrato SPA como fallback para `document contract` y `navigation mode` cuando el HTML no declara esos marcadores
- el runtime ya puede apoyarse tambien en el `Frontend Route Manifest` para decidir `prefetch` y `reload` antes del fetch HTML cuando existe una coincidencia publica de ruta
- el siguiente paso natural de `V2` es profundizar el consumo del contrato SPA en el runtime para reducir aun mas la dependencia del parseo HTML cuando no existan overrides explicitos

### 7.3 Integracion Con Runtime SPA Reactivo

- `[x]` mantener navegacion SPA por `GET`
- `[x]` mantener acciones internas del protocolo en `POST` hasta nueva decision formal
- `[x]` reutilizar metadata compilada desde el runtime
- `[x]` alinear errores de router, dispatcher y runtime
- `[x]` documentar claramente que verbos HTTP soporta el runtime y cuales solo soporta el framework

Criterio de cierre:

- el runtime puede navegar usando contratos publicos del router sin acoplarse a implementaciones internas

---

## 8. Siguiente Corte Recomendado

Con `V1`, `V1.1`, `V1.2` y `V2 SPA` ya cerrados, el siguiente avance recomendado no debe abrir de inmediato optimizaciones o multitenancy. El orden mas conveniente es:

1. consolidar el router actual sobre pruebas, skeleton y documentacion real
2. abrir `Route Component System` como siguiente bloque funcional del roadmap
3. dejar `adaptive matching`, optimizacion de pipeline y `Multi-Tenant Routing` para despues de ese cierre

### 8.1 Consolidacion Del Router Actual

Este bloque no busca agregar nuevas capacidades de routing. Busca cerrar la brecha entre framework, tests, skeleton consumidor y contratos documentados.

- `[x]` alinear la suite del framework con el comportamiento real del runtime y del bootstrap HTML actual
- `[x]` corregir tests o fixtures desfasados que aun asumen contratos viejos del documento o del runtime
- `[x]` verificar que el skeleton consumidor expone las rutas y pantallas que los tests de integracion esperan consumir
- `[x]` aislar mejor los tests que usan `sys_get_temp_dir()` para evitar contaminacion accidental por artifacts compartidos
- `[x]` revisar el uso por defecto de `app.env` y la activacion de artifacts para evitar falsos `404` o lectura de cache ajena en pruebas
- `[x]` alinear `Docs/Routing/20-Use.md`, el checklist y la suite con el estado real de la API publica
- `[x]` registrar en bitacora las decisiones tecnicas de consolidacion para que no se reabran regresiones ya cerradas

Criterio de cierre:

- la suite de routing/runtime vuelve a ser confiable como señal de regresion
- el skeleton usado por pruebas de integracion ya refleja el contrato publico actual
- la documentacion ya no describe contratos obsoletos ni escenarios que no existan en el repo actual

### 8.2 Checklist Route Component System

Abrir este bloque solo despues de cerrar `8.1`.

Este corte debe formalizar el uso de componentes o paginas como destinos de ruta de primer nivel, sin mezclarlo todavia con multitenancy, optimizacion avanzada o cambios de protocolo mayores.

- `[x]` definir el contrato publico de una ruta a componente o pagina navegable
- `[x]` separar formalmente componente navegable, componente embebible y endpoint interno del runtime
- `[x]` decidir que metadata publica del componente puede entrar al manifest y cual debe quedar interna
- `[x]` formalizar el path de render inicial para paginas basadas en componente
- `[x]` formalizar el dispatch de acciones sobre componentes resueltos desde ruta sin duplicar contratos con `/_volt/action`
- `[x]` definir el contrato de errores para paginas o componentes cuando fallen `mount`, `hydrate` o `render`
- `[x]` decidir como conviven layout, hydration y navigation policy entre metadata de ruta y metadata propia del componente
- `[x]` validar compatibilidad con `route()`, `signed_route()` y manifest publico cuando el destino sea un componente
- `[x]` cubrir con pruebas reales el flujo `match -> dispatch -> render -> bootstrap -> navigate`
- `[x]` documentar en `Docs/Routing/20-Use.md` y en documentos del runtime el uso oficial de rutas a componente o pagina

Criterio de cierre:

- una ruta a componente ya tiene contrato publico claro y no depende de supuestos internos del runtime
- el router, el runtime y el manifest distinguen correctamente entre navegacion de pagina y accion reactiva
- el consumo desde app cliente queda documentado y cubierto por pruebas de integracion

---

## 9. Checklist Postergado

No iniciar estos bloques antes de cerrar `V1` y `V2`:

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

## 10. Riesgos A Vigilar

- `[ ]` sobredisenar el core antes de tener matcher y dispatcher estables
- `[ ]` mezclar necesidades del runtime SPA con el contrato HTTP base del framework
- `[ ]` introducir metadata excesiva antes de definir consumers reales
- `[ ]` construir demasiados subsistemas vacios por simetria documental
- `[ ]` abrir component endpoints o multitenancy antes de cerrar `404/405/Allow`

---

## 11. Orden Recomendado De Ejecucion

Seguir este orden de trabajo:

1. `[x]` `RouteDefinition + RouteCollection + Fluent API`
2. `[x]` `Compiler` minimo
3. `[x]` `Matcher` multi-metodo
4. `[x]` `404 / 405 / Allow / HEAD / OPTIONS`
5. `[x]` `Dispatcher` basico
6. `[x]` `Middleware Pipeline`
7. `[x]` `Metadata` minima
8. `[x]` `Artifacts`
9. `[x]` `URL Generator`
10. `[x]` `Route Attributes`
11. `[x]` `Frontend Manifest` minimo
12. `[x]` `SPA Routing Protocol` minimo
13. `[x]` consolidacion del router actual sobre framework, tests, skeleton y documentacion
14. `[x]` `Route Component System`
15. `[ ]` `pipeline optimizer`
16. `[ ]` `Multi-Tenant Routing`

Estado real del corte actual:

- `V1 Core Routing`, `V1.1` y el bloque SPA minimo (`7.1`, `7.2`, `7.3`) ya quedaron cerrados
- la fase corta de consolidacion del router actual ya quedo cerrada sobre framework, tests, skeleton y documentacion
- el siguiente bloque funcional recomendado pasa a ser `Route Component System`

---

## 12. Nota Sobre El Runtime SPA Reactivo

Mientras este checklist no cierre `V1`, la recomendacion tecnica es:

- no seguir ampliando el runtime sobre supuestos HTTP incompletos
- mantener `GET` para navegacion
- mantener `POST` para acciones reactivas internas
- dejar que el framework complete primero su soporte multi-metodo real

Cuando `V1` este cerrado, ya se puede decidir si el runtime:

- sigue centralizado en `POST` para acciones internas
- o empieza a consumir `PUT`, `PATCH`, `DELETE` de forma semantica

---

## 13. Bitacora De Avance

Usar esta seccion para registrar hitos reales conforme se vayan cerrando bloques.

- `[2026-07-05]` cierre de `8.1 Consolidacion Del Router Actual`: se corrigio el fallback de entorno para que una `Application` sin configuracion cargada arranque en `local`, se aislaron pruebas afectadas por artifacts compartidos y se hicieron explicitas las pruebas que realmente requieren `production`
- `[2026-07-05]` la suite del framework volvio a verde con `347 tests` y `1466 assertions`, usando el skeleton real actual como base para las pruebas de integracion SPA
- `[2026-07-05]` `Docs/Routing/20-Use.md` quedo alineado con el estado real del core: `/_volt/runtime.js`, `/_volt/action`, `/_volt/routes-manifest.json`, `Routing Lab`, contratos de `X-Volt-Navigation` y normalizacion de `runtime.document`
- `[2026-07-05]` primer corte de `8.2 Route Component System`: `Route::componentPage()` publica `screen.kind=component` y `screen.mode=navigable`, `Route::embeddableComponent()` publica `screen.mode=embeddable`, el manifest distingue `navigate` vs `embed`, y `HtmlDocumentBootstrapper` deja de tratar componentes embebibles como documentos navegables
- `[2026-07-05]` cobertura del nuevo contrato de componentes validada con suite verde: `348 tests` y `1489 assertions`
- `[2026-07-05]` segundo corte de `8.2 Route Component System`: las acciones reactivas originadas desde una ruta preservan `snapshot.meta.route` y `meta.route` en `/_volt/action`, reutilizando el mismo endpoint interno sin abrir contratos paralelos por path
- `[2026-07-05]` tercer corte de `8.2 Route Component System`: `mount` y `render` de componentes ahora tienen codigos semanticos estables (`runtime.component_mount_failed`, `runtime.component_render_failed`), `hydrate` mantiene `runtime.invalid_snapshot`, y la navegacion Volt proyecta ese contrato como `error.reason` en `X-Volt-Navigation`
- `[2026-07-05]` cuarto corte de `8.2 Route Component System`: `Component::runtimeMetadata()` permite defaults de `runtime.*` por componente y la ruta conserva precedencia; `X-Volt-Navigation` refleja la proyeccion efectiva en runtime
- `[2026-07-06]` quinto corte de `8.2 Route Component System`: rutas a componente ya son compatibles con `route()` y `signed_route()`, incluyendo middleware `signed` y navegacion Volt con `error.reason=security.invalid_signature` cuando la firma es invalida; suite en verde con `357 tests` y `1542 assertions`

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
- `[x]` el runtime HTTP ya puede despachar controladores con atributos PHP declarados tanto en rutas vivas como desde `collection.php` sin instanciarlos ni depender de ellos durante la peticion
- `[x]` el matcher ya puede resolver rutas compiladas y candidatas desde `RouteMatchTree` apuntando a controladores con atributos PHP declarados sin reflejarlos ni instanciarlos durante la busqueda
- `[x]` una instancia nueva del runtime ya puede arrancar en `production` usando `collection.php`, `metadata.php`, `pipeline.php` y `version.php` sin `reload*Artifacts()` manual ni registro vivo de rutas; el bootstrap salta `routes/web.php` y la request se atiende desde artifacts
- `[x]` `HtmlDocumentBootstrapper` ya puede reutilizar metadata compilada restaurada desde `metadata.php` para completar `data-volt-document`, `data-volt-navigation-mode` y `data-volt-page-transition` cuando el HTML no declara esos markers explicitamente
- `[x]` el `ExceptionHandler` ya entrega para `/_volt/action` un contrato de error alineado con router, dispatcher y runtime mediante `error.kind=protocol-error`, `error.code`, `error.status` y detalles como `allow`/`allowHeader`, cubriendo `404`, `405`, `419`, `422` y `500`
- `[x]` dispatcher basico operativo con `DispatcherResolver`, `ClosureDispatcher`, `ControllerDispatcher`, `ActionDispatcher`, `ComponentDispatcher` y `ResponseNormalizer`; la separacion posterior por tipos avanzados queda fuera del cierre minimo de `V1`
- `[x]` pipeline HTTP minimo operativo con middleware global, middleware por grupo, middleware declarativo por ruta, aliases minimos resueltos en registro, deduplicacion estable, compilacion formal en memoria, artefacto persistido y loader automatico en runtime para produccion
- `[x]` metadata minima consumible
- `[x]` artifacts de routing operativos con `collection.php`, `tree.php`, `metadata.php`, `pipeline.php` y `version.php`; ya existe invalidacion automatica base en desarrollo y politica minima de no recompilacion durante request en produccion
- `[x]` URL generator basico operativo mediante `Router::route(...)` y helper global `route()`, con soporte para parametros, query string residual o explicita (`_query`), fragment (`_fragment`), dominio y generacion absoluta/relativa
- `[x]` la integracion con runtime SPA reactivo ya queda cerrada en su contrato actual: `visit()` se mantiene sobre `GET`, `dispatchAction()` se mantiene sobre `POST`, las rutas internas compiladas exponen esos verbos y el codigo fuente del runtime queda cubierto por tests para evitar regresiones accidentales
- `[x]` `layout` ya forma parte del contrato minimo reutilizable desde metadata compilada: `HtmlDocumentBootstrapper` proyecta `runtime.layout` a `data-volt-layout` cuando el HTML no lo declara, `frontend/runtime/volt.js` lo expone como `payload.layout` en navegacion y el HTML explicito mantiene prioridad sobre la metadata inyectada
- `[x]` `target` ya forma parte del contrato minimo de navegacion: `frontend/runtime/volt.js` expone `payload.target` como la URL objetivo solicitada, la conserva en cache y la refleja en los hooks de navegacion aunque `finalUrl` o `redirect` terminen resolviendo otro destino efectivo
- `[x]` se corrigio un bug de alcance en `visit()` del runtime SPA donde `navigationTarget` se declaraba dentro del `try` y luego se reutilizaba en hooks de cierre y telemetria; ahora queda inicializado fuera del bloque para evitar `ReferenceError` durante `volt:request-finish`
- `[x]` se corrigio un segundo bug de alcance en `visit()` del runtime SPA donde `payloadHydrate` se declaraba dentro del `try` y luego se reutilizaba en hooks y telemetria de cierre; ahora queda inicializado fuera del bloque para evitar `ReferenceError` al finalizar la navegacion
- `[x]` `transition` ya forma parte del contrato minimo reutilizable desde metadata compilada: `HtmlDocumentBootstrapper` acepta `runtime.transition` como string u objeto enriquecido (`name`, `profile`, `duration`, `mode`), lo proyecta al documento y el runtime lo rehidrata sin depender de HTML manual
- `[x]` `hydrate` ya forma parte del contrato minimo reutilizable desde metadata compilada: `HtmlDocumentBootstrapper` proyecta `runtime.hydrate` (`enabled`, `strategy`, `dirtyState`) al documento, `frontend/runtime/volt.js` lo expone como `payload.hydrate` en navegacion, y el HTML explicito mantiene prioridad sobre metadata inyectada
- `[x]` `redirect` ya forma parte del contrato minimo de navegacion: `frontend/runtime/volt.js` expone `payload.redirect` cuando una navegacion `GET` termina en una URL distinta por redirect HTTP, mantiene `finalUrl` como fuente principal y usa `redirect` como fallback explicito del protocolo
- `[x]` `error` ya forma parte del contrato minimo de navegacion: una respuesta HTTP fallida en navegacion `GET` produce `payload.error = { code, message }`, no entra al cache SPA y `visit()` reutiliza ese contrato uniforme antes de aplicar fallback o rethrow
- `[x]` `signed URLs` minimas ya operan con `Router::signedRoute(...)`, `Router::hasValidSignature(...)` y helper `signed_route(...)`, usando firma `HMAC-SHA256` sobre URL canonica, exclusiones de `fragment`/`signature` en la recomputacion y pruebas focalizadas para generacion, validacion positiva y deteccion de tampering
- `[x]` `temporary signed URLs` ya operan con `Router::temporarySignedRoute(...)` y helper `temporary_signed_route(...)`, aceptando expiracion como `DateInterval`, `DateTimeInterface` o `int` TTL, proyectando `expires` en la URL firmada y reutilizando `hasValidSignature(...)` para rechazar expiraciones vencidas o mal formadas
- `[x]` la guia operativa `Docs/Routing/20-Use.md` ya documenta el uso practico de `signed URLs` y `temporary signed URLs`, incluyendo generacion, validacion en runtime y escenarios manuales para pruebas en la app cliente
- `[x]` existe un consumer HTTP reutilizable para firmas mediante el middleware alias `signed`, con `403 Forbidden` uniforme para firmas invalidas o expiradas y cobertura de integracion sobre rutas protegidas
- `[x]` el bootstrap de rutas ya soporta loaders con fachada `Quantum\Facades\Route`, permitiendo registrar rutas como `Route::get(...)` en `routes/web.php` sin depender del parametro `$router`
- `[x]` `RouteDefinition`, `CompiledRoute` y `collection.php` ya exponen `path` como contrato explicito del sistema, manteniendo `uri()` como alias retrocompatible para no romper matcher, generator, artifacts ni consumers ya existentes
- `[x]` el loader de artifacts para runtime ya queda cerrado: `Bootstrapper::loadRoutes()` omite route files cuando existen artifacts validos en produccion, arranca una instancia nueva sin `reload*Artifacts()` manual y cae correctamente al registro vivo cuando `version.php` invalida `collection/tree/metadata`
- `[x]` el pipeline HTTP ya expone un contrato minimo de `context` para reserva futura (`http`, `spa`, `api`): `Route::context()/http()/spa()/api()` lo proyectan a metadata compilada, `Request::routeContext()` lo resuelve con default `http` y los endpoints internos Volt quedan marcados como `spa`, permitiendo a middleware y futuros consumers diferenciar contexto sin abrir todavia pipelines o dispatchers paralelos
- `[x]` la API de grupos ya ofrece una capa fluida para `prefix()/name()/domain()->group(...)`, incluyendo composicion de prefijos de nombre, callback con `0` o `1` parametro y soporte directo desde la fachada `Quantum\Facades\Route`
- `[x]` existe una primera capa de `Route::resource()` para el set REST convencional (`index/create/store/show/edit/update/destroy`), con nombres `resource.action`, herencia de `prefix/name/domain` de grupo y guia practica actualizada en `Docs/Routing/20-Use.md`
- `[x]` `Route::resource()` ya soporta filtrado minimo con `only()` y `except()`, mientras `Route::apiResource()` excluye `create/edit` sin romper el matcher de `show` para literales reservados como `create`; la guia publica ya documenta estos matices y las pruebas cubren tanto `404/405` como el registro real
- `[x]` la app skeleton ya adopta la API fluida en `routes/web.php` para el laboratorio `routing-lab`, agrupando `prefix('/routing-lab')->name('routing.lab')->group(...)` sin cambiar paths ni nombres publicos; esto valida el uso real de la fachada `Route` fuera de las pruebas del framework
- `[x]` `Route::resource()` ya soporta una capa minima de personalizacion con `names([...])`, `parameter(...)` y `parameters([...])`; el placeholder publico puede renombrarse sin romper el despacho de controladores existentes porque el binder conserva aliases internos del parametro original durante la resolucion de argumentos
- `[x]` `Route::resource()` ya soporta recursos anidados mediante notacion `posts.comments`, construyendo paths como `/posts/{post}/comments/{comment}` y nombres `posts.comments.show` sin requerir un builder separado
- `[x]` `Route::resource()->shallow()` ya permite mover `show/edit/update/destroy` a rutas miembro cortas como `/comments/{comment}` mientras mantiene `index/create/store` anidados; la cobertura valida generacion de URLs, dispatch real y compatibilidad con renombre de placeholders padre e hijo
- `[x]` existe una primera capa de binding tipado para rutas de recurso mediante `Quantum\Routing\Contracts\RouteBindableInterface`; cuando el binder devuelve `null`, `Route::resource()->missing(...)` ya puede responder con `status` custom o `redirect` declarativo sin introducir todavia un subsistema completo de model binding global
- `[x]` `Route::resource()` ya permite personalizar paths y verbos HTTP por accion con `paths([...])` y `verbs([...])`; el cambio reindexa correctamente las firmas en `RouteCollection`, mantiene la generacion de URLs por nombre y evita tener que desarmar manualmente el recurso convencional para ajustes puntuales
