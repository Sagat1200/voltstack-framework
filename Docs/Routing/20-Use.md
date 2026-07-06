# VoltStack Routing - Uso Actual

**Version:** 1.0  
**Estado:** Guia de uso operativo

---

## 1. Objetivo

Este documento describe la forma de uso del sistema de rutas que ya esta construido hoy en VoltStack.

El foco de esta guia es:

- registrar rutas reales en la app cliente
- declarar metadata publica de runtime
- probar navegacion HTML-first con soporte SPA
- inspeccionar el `Frontend Route Manifest`
- inspeccionar el header `X-Volt-Navigation`

No documenta ideas futuras. Solo cubre contratos y comportamientos que ya existen en el framework.

---

## 2. Alcance Actual

Hoy el sistema de rutas ya soporta:

- registro por `GET`, `POST`, `PUT`, `PATCH`, `DELETE`, `HEAD`, `OPTIONS`, `ANY`
- rutas con nombre
- rutas dinamicas con placeholders como `/users/{user}`
- constraints como `whereNumber()`, `whereSlug()`, `whereUuid()`
- grupos con `prefix`, `domain`, `middleware` y `metadata`
- metadata compilada por ruta
- artifacts AOT para runtime
- generacion de URLs por nombre con `route()`
- generacion de `signed URLs` con `signed_route()`
- generacion de `temporary signed URLs` con `temporary_signed_route()`
- `Frontend Route Manifest` publico en `/_volt/routes-manifest.json`
- header `X-Volt-Navigation` para requests de navegacion Volt
- bootstrap HTML-first con atributos `data-volt-*` inyectados desde metadata runtime

---

## 3. Donde Registrar Rutas

En la app cliente, las rutas HTTP convencionales se registran en:

```php
// routes/web.php
<?php

declare(strict_types=1);

use Quantum\Routing\Router;

return static function (Router $router): void {
    $router->get('/', fn() => 'home');
};
```

El bootstrap de la app ya carga este archivo desde `bootstrap/app.php`.

Tambien puedes registrar rutas usando la fachada:

```php
// routes/web.php
<?php

declare(strict_types=1);

use Quantum\Facades\Route;

return static function (): void {
    Route::get('/', fn() => 'home')->name('home');
};
```

Recomendacion practica:

- usa `use Quantum\Facades\Route;` dentro del route file
- usa el estilo con fachada cuando quieras un archivo de rutas mas declarativo
- usa el estilo con `Router $router` cuando necesites inyeccion explicita o prefieras dejar claro el dependency flow

---

## 4. API Basica

### 4.1 Ruta simple

```php
<?php

declare(strict_types=1);

use App\Controllers\HomeController;
use Quantum\Facades\Route;

return static function (): void {
    Route::get('/', HomeController::class)->name('home');
};
```

### 4.2 Ruta dinamica

```php
Route::get('/users/{user}', UserShowController::class)
    ->name('users.show');
```

### 4.3 Constraints

```php
Route::get('/users/{user}', UserShowController::class)
    ->name('users.show')
    ->whereNumber('user');

Route::get('/blog/{slug}', BlogShowController::class)
    ->name('blog.show')
    ->whereSlug('slug');
```

Helpers disponibles hoy:

- `where()`
- `whereNumber()`
- `whereAlpha()`
- `whereAlphaNumeric()`
- `whereUuid()`
- `whereSlug()`

### 4.4 Middleware por ruta

```php
Route::get('/dashboard', DashboardController::class)
    ->name('dashboard')
    ->middleware(AuthMiddleware::class);
```

Hoy tambien existe el alias `signed` para proteger enlaces firmados:

```php
$router->get('/downloads/{file}', DownloadController::class)
    ->name('downloads.secure')
    ->middleware('signed');
```

### 4.5 Grupos

```php
$router->group([
    'prefix' => '/admin',
    'middleware' => AuthMiddleware::class,
    'metadata' => [
        'runtime' => [
            'document' => 'reload',
        ],
    ],
], function (Router $router): void {
    $router->get('/users', AdminUsersController::class)->name('admin.users.index');
    $router->get('/users/{user}', AdminUserShowController::class)
        ->name('admin.users.show')
        ->whereNumber('user');
});
```

Tambien puedes construir grupos de forma mas expresiva:

```php
use Quantum\Facades\Route;

Route::prefix('/admin')
    ->name('admin')
    ->domain('admin.example.com')
    ->group(function (): void {
        Route::get('/users', AdminUsersController::class)
            ->name('users.index');
    });
```

Notas del contrato actual:

- `prefix()` compone paths anidados
- `name()` compone prefijos de nombre de forma acumulativa
- `domain()` aplica el dominio al grupo completo
- el callback del grupo puede declarar `0` o `1` parametro

### 4.6 Dominio por ruta o grupo

```php
$router->group([
    'domain' => 'admin.example.com',
], function (Router $router): void {
    $router->get('/reports', AdminReportsController::class)
        ->name('admin.reports.index');
});
```

### 4.7 Resource Routes Minimas

La primera capa de `resource()` ya registra el set REST convencional sobre un controlador:

```php
Route::resource('posts', PostController::class);
```

Eso genera estas rutas:

- `GET /posts` -> `posts.index`
- `GET /posts/create` -> `posts.create`
- `POST /posts` -> `posts.store`
- `GET /posts/{post}` -> `posts.show`
- `GET /posts/{post}/edit` -> `posts.edit`
- `PUT|PATCH /posts/{post}` -> `posts.update`
- `DELETE /posts/{post}` -> `posts.destroy`

Tambien funciona dentro de grupos fluidos:

```php
Route::prefix('/admin')
    ->name('admin')
    ->domain('admin.example.com')
    ->group(function (Router $router): void {
        $router->resource('posts', AdminPostController::class);
    });
```

Alcance de esta primera capa:

- usa nombres convencionales `resource.action`
- deriva el parametro desde el ultimo segmento del recurso, con singularizacion basica
- permite filtrar acciones con `only()` y `except()`
- expone `apiResource()` como atajo para excluir `create` y `edit`
- permite personalizar nombres puntuales con `names([...])`
- permite renombrar el placeholder singular con `parameter(...)`
- permite renombrar el placeholder por clave de recurso con `parameters([...])`
- acepta recursos anidados por notacion `padre.hijo`
- expone `shallow()` para mover las rutas miembro a paths cortos
- permite binding tipado sobre parametros miembro si el tipo implementa `RouteBindableInterface`
- expone `missing(...)` para reaccionar cuando ese binder no encuentra el recurso enlazado

Ejemplos:

```php
Route::resource('posts', PostController::class)
    ->only(['index', 'show']);

Route::resource('posts', PostController::class)
    ->except(['destroy']);

Route::resource('posts', PostController::class)
    ->names([
        'index' => 'content.posts.list',
        'show' => 'content.posts.view',
    ]);

Route::resource('posts', PostController::class)
    ->parameter('entry');

Route::apiResource('posts', ApiPostController::class)
    ->parameters([
        'posts' => 'entry',
    ]);

Route::resource('posts.comments', CommentController::class);

Route::resource('posts.comments', CommentController::class)
    ->shallow();

Route::resource('posts.comments', CommentController::class)
    ->parameters([
        'posts' => 'entry',
        'comments' => 'note',
    ]);

Route::apiResource('posts', ApiPostController::class);
```

Notas:

- cuando una misma path sigue existiendo con otros metodos, el runtime devolvera `405 Method Not Allowed` en lugar de `404`
- `apiResource()` reserva `create` para que `GET /posts/create` no sea capturado por `show`
- `names([...])` reemplaza solo las acciones declaradas; las demas conservan `resource.action`
- `parameter(...)` y `parameters([...])` cambian el nombre publico del placeholder para generacion de URLs, pero el controlador puede seguir recibiendo el nombre original del argumento mientras el dispatcher resuelve el alias internamente
- `resource('posts.comments', ...)` genera nombres `posts.comments.*` y paths como `/posts/{post}/comments`
- `shallow()` mueve `show/edit/update/destroy` a `/comments/{comment}` y `/comments/{comment}/edit`, pero mantiene `index/create/store` anidados bajo `/posts/{post}/comments`
- el binding tipado solo se activa cuando el argumento del controller usa una clase que implementa `Quantum\Routing\Contracts\RouteBindableInterface`
- `missing(404)` o `missing(410)` devuelven ese status cuando el binder retorna `null`
- `missing('ruta.fallback')` hace redirect a una ruta nombrada usando los parametros actuales como input de generacion

Ejemplo de recurso anidado sin `shallow()`:

```php
Route::resource('posts.comments', CommentController::class);
```

Eso genera:

- `GET /posts/{post}/comments` -> `posts.comments.index`
- `GET /posts/{post}/comments/create` -> `posts.comments.create`
- `POST /posts/{post}/comments` -> `posts.comments.store`
- `GET /posts/{post}/comments/{comment}` -> `posts.comments.show`
- `GET /posts/{post}/comments/{comment}/edit` -> `posts.comments.edit`
- `PUT|PATCH /posts/{post}/comments/{comment}` -> `posts.comments.update`
- `DELETE /posts/{post}/comments/{comment}` -> `posts.comments.destroy`

Ejemplo con `shallow()`:

```php
Route::resource('posts.comments', CommentController::class)
    ->shallow();
```

Con `shallow()` el resultado operativo queda asi:

- `GET /posts/{post}/comments` -> `posts.comments.index`
- `GET /posts/{post}/comments/create` -> `posts.comments.create`
- `POST /posts/{post}/comments` -> `posts.comments.store`
- `GET /comments/{comment}` -> `posts.comments.show`
- `GET /comments/{comment}/edit` -> `posts.comments.edit`
- `PUT|PATCH /comments/{comment}` -> `posts.comments.update`
- `DELETE /comments/{comment}` -> `posts.comments.destroy`

Ejemplo de binding tipado con fallback `missing()`:

```php
<?php

declare(strict_types=1);

use Quantum\Http\Request;
use Quantum\Routing\Contracts\RouteBindableInterface;
use Quantum\Facades\Route;

final class CommentResource implements RouteBindableInterface
{
    public function __construct(
        public readonly string $id,
    ) {}

    public static function resolveRouteBinding(string $value, string $parameter, Request $request): ?self
    {
        if ($parameter !== 'comment') {
            return null;
        }

        return in_array($value, ['10', '11'], true)
            ? new self($value)
            : null;
    }
}

final class CommentController
{
    public function show(string $post, CommentResource $comment): string
    {
        return 'comment:' . $post . ':' . $comment->id;
    }
}

Route::get('/missing-comments', fn() => 'missing')->name('comments.missing');

Route::resource('posts.comments', CommentController::class)
    ->only(['show'])
    ->missing('comments.missing');
```

Comportamiento actual:

- si `resolveRouteBinding(...)` devuelve una instancia, el controller recibe el recurso ya resuelto
- si devuelve `null`, la ruta miembro entra al contrato `missing(...)`
- `missing('comments.missing')` responde con redirect `302` por defecto
- tambien puedes usar `->missing(404)` o `->missing(410)` para responder solo con status

---

## 5. Metadata De Ruta

La metadata se declara con `->meta()` o mediante helpers como:

- `->auth()`
- `->guest()`
- `->csrf()`
- `->throttle()`
- `->runtime()`
- `->context()`
- `->http()`
- `->spa()`
- `->api()`

Ejemplo general:

```php
$router->get('/users/{user}', UserShowController::class)
    ->name('users.show')
    ->meta([
        'auth' => 'session',
        'prefetch' => true,
        'runtime' => [
            'layout' => 'dashboard',
            'document' => 'spa',
            'navigation' => 'auto',
            'transition' => [
                'name' => 'fade',
                'profile' => 'smooth',
                'duration' => 240,
                'mode' => 'in-out',
            ],
            'hydrate' => [
                'enabled' => true,
                'strategy' => 'partial',
                'dirtyState' => 'defer',
            ],
        ],
    ]);
```

Tambien puede escribirse asi:

```php
$router->get('/users/{user}', UserShowController::class)
    ->name('users.show')
    ->runtime([
        'layout' => 'dashboard',
        'document' => 'spa',
        'navigation' => 'auto',
        'transition' => 'fade',
        'hydrate' => true,
    ]);
```

Tambien puedes reservar un contexto de ejecucion explicito para middleware y futuros consumers:

```php
Route::get('/dashboard', DashboardController::class)
    ->name('dashboard')
    ->http();

Route::get('/spa/profile', ProfileController::class)
    ->name('spa.profile')
    ->spa();

Route::get('/api/users', ApiUsersController::class)
    ->name('api.users.index')
    ->api();
```

Contrato actual:

- `http` es el contexto por defecto para rutas convencionales
- `spa` y `api` quedan disponibles como metadata explicita de la ruta
- este contexto no cambia todavia el dispatcher ni crea un pipeline paralelo por si mismo
- sirve para que middleware y consumers futuros no dependan de inferencias ad hoc

---

## 6. Metadata Publica Consumible Hoy

No toda la metadata compilada es publica. Para la app cliente, hoy importa especialmente lo siguiente.

### 6.1 `runtime.layout`

Define el layout publico de la pantalla.

```php
'runtime' => [
    'layout' => 'dashboard',
]
```

Se proyecta a:

- `data-volt-layout` en el documento HTML cuando el HTML no lo declara
- `runtime.layout` en `X-Volt-Navigation`
- `runtime.layout` en `/_volt/routes-manifest.json`

### 6.2 `runtime.transition`

Acepta string o array.

```php
'runtime' => [
    'transition' => 'fade',
]
```

o

```php
'runtime' => [
    'transition' => [
        'name' => 'fade',
        'profile' => 'smooth',
        'duration' => 240,
        'mode' => 'in-out',
    ],
]
```

Comportamiento actual:

- el HTML puede recibir atributos `data-volt-page-transition*`
- el payload `X-Volt-Navigation` solo publica el nombre de la transicion
- el manifest tambien publica solo el nombre de la transicion

### 6.3 `runtime.hydrate`

Acepta bool o array.

```php
'runtime' => [
    'hydrate' => true,
]
```

o

```php
'runtime' => [
    'hydrate' => [
        'enabled' => true,
        'strategy' => 'partial',
        'dirtyState' => 'defer',
    ],
]
```

Comportamiento actual:

- el HTML puede recibir `data-volt-hydrate`, `data-volt-hydrate-strategy` y `data-volt-hydrate-dirty-state`
- el payload `X-Volt-Navigation` publica solo `true` o `false`
- el manifest publica solo `true` o `false`

### 6.4 `runtime.document`

Controla el contrato publico de documento.

```php
'runtime' => [
    'document' => 'spa',
]
```

Valores utiles hoy:

- `spa`
- `reload`
- `reload-only`
- `static`
- `non-spa`
- `document`
- `interactive`
- `reactive`

Normalizacion actual:

- `reload-only`, `static`, `non-spa` y `document` terminan proyectando `reload`
- `interactive` y `reactive` terminan proyectando `spa`

### 6.5 `runtime.navigation`

Controla la politica publica de navegacion.

```php
'runtime' => [
    'navigation' => 'auto',
]
```

Valores utiles hoy:

- `auto`
- `spa`
- `reload`

### 6.6 `prefetch`

La capacidad publica de `prefetch` se puede habilitar con:

```php
'prefetch' => true
```

o

```php
'runtime' => [
    'prefetch' => true,
]
```

Si esta habilitado y la ruta publica lo permite, el manifest agregara la capacidad `prefetch`.

---

## 7. Que Sale En El Manifest Publico

El endpoint publico actual es:

```text
/_volt/routes-manifest.json
```

Solo incluye:

- rutas con nombre
- rutas no internas
- informacion publica minima

No incluye:

- `middleware`
- `auth`
- metadata privada
- rutas `/_volt/*`
- rutas sin nombre

Ejemplo esperado:

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
      "capabilities": ["navigate", "hydrate", "prefetch"],
      "runtime": {
        "layout": "dashboard",
        "transition": "fade",
        "hydrate": true
      },
      "policy": {
        "document": "spa",
        "navigation": "auto"
      }
    }
  ]
}
```

Reglas practicas:

- si la ruta no tiene `name()`, no esperes verla en el manifest
- si la ruta no soporta `GET`, no esperes la capacidad `navigate`
- `policy.document` y `policy.navigation` salen del bloque `runtime`
- `transition` e `hydrate` salen reducidos a su proyeccion publica minima

---

## 8. Que Sale En `X-Volt-Navigation`

El header `X-Volt-Navigation` solo se emite cuando la request indica navegacion Volt.

Headers requeridos:

```text
X-Requested-With: VoltStack
X-Volt-Navigate: true
```

Payload actual:

```json
{
  "protocol": {
    "name": "VoltStack SPA Routing",
    "version": "1.0"
  },
  "navigation": {
    "target": "/users/15",
    "method": "GET"
  },
  "screen": {
    "route": "users.show"
  },
  "policy": {
    "document": "spa",
    "navigation": "auto"
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

Comportamiento actual:

- `navigation.target` usa la URL final o el `Location` de un redirect
- `screen.route` depende del nombre de ruta resuelto
- `policy.document` y `policy.navigation` salen del runtime publico
- `runtime.transition` y `runtime.hydrate` salen reducidos
- `redirect` aparece en respuestas `3xx`
- `error` aparece en respuestas `4xx/5xx`

---

## 9. HTML De Prueba En La App Cliente

Para pruebas de navegacion SPA, lo mas simple hoy es mantener paginas HTML completas y dejar que Volt las mejore.

Ejemplo de layout:

```php
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Cliente VoltStack</title>
</head>
<body data-volt-document="spa" data-volt-navigation-mode="auto" data-volt-layout="app">
    <main>
        @yield('content')
    </main>
</body>
</html>
```

Ejemplo de enlace navegable:

```html
<a href="/users/15" volt:navigate volt:prefetch="none">Ver usuario</a>
```

Notas practicas:

- `volt:navigate` activa el flujo de navegacion del runtime
- si quieres evitar prefetch en una prueba concreta, usa `volt:prefetch="none"`
- el sistema sigue siendo HTML-first: el documento destino sigue siendo importante

---

## 10. Ejemplo Completo Para Probar En Cliente

Este ejemplo cubre:

- ruta publica con nombre
- ruta dinamica
- metadata publica
- manifest visible
- payload SPA visible

```php
<?php

declare(strict_types=1);

use App\Controllers\HomeController;
use App\Controllers\UserShowController;
use Quantum\Routing\Router;

return static function (Router $router): void {
    $router->get('/', HomeController::class)
        ->name('home')
        ->runtime([
            'layout' => 'public',
            'document' => 'spa',
            'navigation' => 'auto',
        ]);

    $router->get('/users/{user}', UserShowController::class)
        ->name('users.show')
        ->whereNumber('user')
        ->meta([
            'prefetch' => true,
            'runtime' => [
                'layout' => 'dashboard',
                'document' => 'spa',
                'navigation' => 'auto',
                'transition' => [
                    'name' => 'fade',
                    'profile' => 'smooth',
                    'duration' => 240,
                    'mode' => 'in-out',
                ],
                'hydrate' => [
                    'enabled' => true,
                    'strategy' => 'partial',
                    'dirtyState' => 'defer',
                ],
            ],
        ]);

    $router->get('/reports/export', fn() => '<!DOCTYPE html><html><body><main>Export</main></body></html>')
        ->name('reports.export')
        ->runtime([
            'document' => 'reload',
            'navigation' => 'reload',
        ]);
};
```

Con esto deberias poder probar:

- una ruta SPA normal: `users.show`
- una ruta que obliga recarga: `reports.export`
- visualizacion del manifest publico
- visualizacion del payload SPA por header

---

## 11. Escenarios De Prueba Recomendados

### 11.1 Verificar que la ruta aparece en el manifest

Condiciones:

- la ruta tiene nombre
- no es interna

Prueba manual:

```powershell
Invoke-WebRequest http://127.0.0.1:8000/_volt/routes-manifest.json | Select-Object -ExpandProperty Content
```

Esperado:

- aparece `users.show`
- aparece `path`
- aparece `methods`
- aparecen `runtime` y `policy` si fueron declarados

### 11.2 Verificar que una ruta sin nombre no aparece

Registrar:

```php
$router->get('/debug/unnamed', fn() => 'debug');
```

Esperado:

- responde por HTTP normal
- no aparece en `/_volt/routes-manifest.json`

### 11.3 Verificar proyeccion HTML de metadata runtime

Registrar una ruta HTML completa con metadata:

```php
$router->get('/document-runtime', fn() => '<!DOCTYPE html><html><body><main>Doc</main></body></html>')
    ->name('document.runtime')
    ->runtime([
        'layout' => 'app-shell',
        'document' => 'reload',
        'navigation' => 'reload',
        'transition' => [
            'name' => 'fade',
            'profile' => 'smooth',
            'duration' => 240,
            'mode' => 'in-out',
        ],
        'hydrate' => [
            'enabled' => true,
            'strategy' => 'partial',
            'dirtyState' => 'defer',
        ],
    ]);
```

Esperado en el HTML bootstrapado:

- `data-volt-document="reload"`
- `data-volt-navigation-mode="reload"`
- `data-volt-layout="app-shell"`
- `data-volt-page-transition="fade"`
- `data-volt-hydrate="true"`

### 11.4 Verificar `X-Volt-Navigation`

Prueba manual:

```powershell
$response = Invoke-WebRequest http://127.0.0.1:8000/users/15 -Headers @{
    "X-Requested-With" = "VoltStack"
    "X-Volt-Navigate" = "true"
}

$response.Headers["X-Volt-Navigation"]
```

Esperado:

- header presente
- `protocol.name = VoltStack SPA Routing`
- `screen.route = users.show`
- `runtime.layout = dashboard`
- `runtime.transition = fade`
- `runtime.hydrate = true`

### 11.5 Verificar ruta con politica `reload`

Prueba manual:

```powershell
$response = Invoke-WebRequest http://127.0.0.1:8000/reports/export -Headers @{
    "X-Requested-With" = "VoltStack"
    "X-Volt-Navigate" = "true"
}

$response.Headers["X-Volt-Navigation"]
```

Esperado:

- `policy.document = reload`
- `policy.navigation = reload`

Y en el manifest:

- la misma ruta debe publicar `policy.document = reload`
- la misma ruta debe publicar `policy.navigation = reload`

### 11.6 Verificar redirect SPA

Registrar:

```php
$router->get('/private', fn() => redirect('/login'))
    ->name('private');
```

Prueba manual:

```powershell
$response = Invoke-WebRequest http://127.0.0.1:8000/private -MaximumRedirection 0 -ErrorAction SilentlyContinue -Headers @{
    "X-Requested-With" = "VoltStack"
    "X-Volt-Navigate" = "true"
}

$response.Headers["X-Volt-Navigation"]
```

Esperado:

- status `3xx`
- `redirect.location = /login`
- `navigation.target = /login`

### 11.7 Verificar error SPA

Registrar una ruta que lance error o devuelva `404/500`.

Esperado en `X-Volt-Navigation`:

- `error.code`
- `error.message`

---

## 12. Generacion De URLs

Si la ruta esta nombrada, puede generarse por nombre:

```php
route('users.show', ['user' => 15]);
```

Con query string y fragment:

```php
route('users.show', [
    'user' => 15,
    '_query' => ['tab' => 'profile'],
    '_fragment' => 'summary',
]);
```

Resultado esperado:

```text
/users/15?tab=profile#summary
```

Recomendacion:

- nombra las rutas que quieras usar desde tooling, manifest o pruebas cliente

### 12.1 Signed URLs

Si necesitas emitir un enlace firmado sin expiracion, usa:

```php
signed_route('users.show', [
    'user' => 15,
    'via' => 'mail',
]);
```

Tambien puedes incluir fragment:

```php
signed_route('users.show', [
    'user' => 15,
    'via' => 'mail',
    '_fragment' => 'summary',
]);
```

Comportamiento actual:

- `signed_route()` genera URL absoluta por defecto
- agrega `signature` como query param firmado con `HMAC-SHA256`
- el `_fragment` no participa en la firma
- durante la validacion, `signature` se excluye de la recomputacion

Ejemplo esperado:

```text
https://example.test/users/15?via=mail&signature=...
```

### 12.2 Temporary Signed URLs

Si necesitas un enlace firmado con expiracion, usa:

```php
temporary_signed_route('downloads.secure', 3600, [
    'file' => 'report.pdf',
]);
```

Tambien acepta `DateInterval` o `DateTimeInterface`:

```php
temporary_signed_route('downloads.secure', new DateInterval('PT1H'), [
    'file' => 'report.pdf',
]);

temporary_signed_route('downloads.secure', new DateTimeImmutable('2030-01-01T00:00:00+00:00'), [
    'file' => 'report.pdf',
]);
```

Comportamiento actual:

- el query param `expires` queda reservado para el timestamp UNIX de expiracion
- `temporary_signed_route()` reutiliza la misma firma canonica de `signed_route()`
- `hasValidSignature()` rechaza enlaces vencidos
- `hasValidSignature()` tambien rechaza `expires` mal formado

Ejemplo esperado:

```text
https://example.test/downloads/report.pdf?expires=1893456000&signature=...
```

### 12.3 Validacion En Runtime

Para validar una request firmada dentro de un controller o middleware:

```php
<?php

declare(strict_types=1);

use Quantum\Http\Request;
use Quantum\Http\Response;
use Quantum\Routing\Router;

final class DownloadController
{
    public function __construct(
        private readonly Router $router,
    ) {}

    public function show(Request $request): Response
    {
        if (! $this->router->hasValidSignature($request)) {
            return new Response('Invalid signature.', 403);
        }

        return new Response('ok');
    }
}
```

Recomendaciones practicas:

- usa `signed_route()` para flujos sin vencimiento inmediato, como enlaces de desuscripcion
- usa `temporary_signed_route()` para descargas, invitaciones o magic links
- si quieres enforcement automatico por ruta, usa `->middleware('signed')`
- no sobrescribas manualmente `signature` ni `expires` en `_query`
- si cambias cualquier query firmada despues de generar la URL, la validacion fallara

### 12.4 Middleware `signed`

Si prefieres no llamar manualmente a `hasValidSignature()` en el controller, puedes proteger la ruta con el alias `signed`:

```php
$router->get('/downloads/{file}', DownloadController::class)
    ->name('downloads.secure')
    ->middleware('signed');
```

Y generar el enlace asi:

```php
temporary_signed_route('downloads.secure', 3600, [
    'file' => 'report.pdf',
]);
```

Comportamiento actual:

- si la firma es valida, la request continua normalmente
- si la firma fue alterada, la ruta devuelve `403 Forbidden`
- si la URL temporal ya expiro, la ruta devuelve `403 Forbidden`
- el alias `signed` reutiliza internamente `Router::hasValidSignature(...)`

---

## 13. Recomendaciones Para Armar Pruebas Cliente

- usa siempre rutas con `name()` si quieres validarlas desde manifest o `route()`
- usa respuestas HTML completas para las pruebas de navegacion, no JSON
- prueba primero con `GET` y enlaces `volt:navigate`
- declara `runtime.document` y `runtime.navigation` de forma explicita cuando quieras probar fallback a `reload`
- declara `runtime.layout`, `runtime.transition` y `runtime.hydrate` cuando quieras validar el contrato publico
- usa `signed_route()` y `temporary_signed_route()` para probar enlaces protegidos sin depender de concatenacion manual
- usa `prefetch` solo en rutas `GET` que realmente quieras publicar como navegables
- no bases pruebas cliente en metadata privada como `middleware` o `auth`, porque no forman parte del contrato publico

---

## 14. Resumen Operativo

Para crear pruebas en la app cliente hoy, la receta minima es:

1. registrar rutas nombradas en `routes/web.php`
2. declarar metadata `runtime` publica en las rutas que quieras observar
3. renderizar HTML completo con enlaces `volt:navigate`
4. inspeccionar `/_volt/routes-manifest.json`
5. inspeccionar el header `X-Volt-Navigation` enviando `X-Requested-With: VoltStack` y `X-Volt-Navigate: true`

Con eso ya puedes validar el contrato actual de routing sin depender de APIs futuras ni de internals del router.
