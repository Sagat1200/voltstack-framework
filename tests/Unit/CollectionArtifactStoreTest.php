<?php

declare(strict_types=1);

namespace VoltStack\Test\Unit;

use PHPUnit\Framework\TestCase;
use Quantum\Routing\CollectionArtifactStore;
use Quantum\Routing\Exceptions\RouteCompilationException;
use Quantum\Routing\Router;
use RuntimeException;
use VoltStack\Framework\Application;

final class CollectionArtifactStoreTest extends TestCase
{
    private string $basePath;
    private Application $app;

    protected function setUp(): void
    {
        parent::setUp();

        $this->basePath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'voltstack-collection-artifact-' . uniqid('', true);

        if (! mkdir($concurrentDirectory = $this->basePath, 0777, true) && ! is_dir($concurrentDirectory)) {
            throw new RuntimeException(sprintf('Unable to create test directory [%s].', $this->basePath));
        }

        $this->app = new Application($this->basePath);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->basePath);

        parent::tearDown();
    }

    public function test_it_writes_and_loads_a_collection_artifact_that_rebuilds_compiled_routes(): void
    {
        $router = $this->app->make(Router::class);
        $route = $router->get('/users/{id}', TestSerializedCollectionController::class . '@show')
            ->name('users.show')
            ->domain('{tenant}.example.com')
            ->whereAlphaNumeric('tenant')
            ->whereNumber('id')
            ->middleware(TestCollectionArtifactMiddleware::class)
            ->meta([
                'auth' => 'session',
                'runtime' => ['mode' => 'spa'],
            ]);

        $store = $this->app->make(CollectionArtifactStore::class);
        $path = $store->compileAndWrite($router);
        $artifact = $store->load();

        self::assertSame($this->app->cachePath('routes/collection.php'), $path);
        self::assertNotNull($artifact);
        self::assertSame(1, $artifact->version());
        self::assertCount(count($router->routes()), $artifact->routes());
        self::assertSame([
            'tenant' => '[A-Za-z0-9]+',
            'id' => '[0-9]+',
        ], $this->artifactRouteByName($artifact->routes(), 'users.show')['constraints']);

        $compiled = $artifact->compileCollection();
        $compiledRoute = $compiled->named('users.show');

        self::assertNotNull($compiledRoute);
        self::assertNotSame($route, $compiledRoute);
        self::assertSame(['GET'], $compiledRoute->methods());
        self::assertSame('/users/{id}', $compiledRoute->uri());
        self::assertSame('{tenant}.example.com', $compiledRoute->routeDomain());
        self::assertSame(TestSerializedCollectionController::class . '@show', $compiledRoute->action());
        self::assertSame(['tenant' => 'acme', 'id' => '42'], $compiledRoute->matchTarget('acme.example.com', '/users/42'));
        self::assertSame([TestCollectionArtifactMiddleware::class], $compiledRoute->routePipeline()->middlewares());
        self::assertSame('session', $compiledRoute->routeMetadata()->get('auth'));
        self::assertSame(['mode' => 'spa'], $compiledRoute->routeMetadata()->get('runtime'));
    }

    public function test_it_writes_compiled_constraint_fragments_into_the_collection_artifact(): void
    {
        $router = $this->app->make(Router::class);
        $router->get('/posts/{slug}/{id}', TestSerializedCollectionController::class . '@show')
            ->name('posts.show')
            ->where('slug', '(foo|bar)')
            ->whereNumber('id');

        $store = $this->app->make(CollectionArtifactStore::class);
        $artifact = $store->compile($router);

        self::assertSame([
            'slug' => '(?:foo|bar)',
            'id' => '[0-9]+',
        ], $this->artifactRouteByName($artifact->routes(), 'posts.show')['constraints']);

        $compiledRoute = $artifact->compileCollection()->named('posts.show');

        self::assertNotNull($compiledRoute);
        self::assertSame([
            'slug' => 'foo',
            'id' => '42',
        ], $compiledRoute->matchPath('/posts/foo/42'));
    }

    public function test_it_rejects_closure_actions_when_generating_the_collection_artifact(): void
    {
        $router = $this->app->make(Router::class);
        $router->get('/closures', fn() => 'ok');

        $store = $this->app->make(CollectionArtifactStore::class);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Route [/closures] contains a closure action that cannot be serialized into the collection artifact.');

        $store->compile($router);
    }

    public function test_it_rejects_malformed_route_placeholders_before_generating_the_collection_artifact(): void
    {
        $router = $this->app->make(Router::class);
        $router->get('/users/{id', TestSerializedCollectionController::class . '@show');

        $store = $this->app->make(CollectionArtifactStore::class);

        $this->expectException(RouteCompilationException::class);
        $this->expectExceptionMessage('Route [/users/{id] contains malformed uri placeholders.');

        $store->compile($router);
    }

    public function test_it_rejects_invalid_constraint_patterns_before_the_collection_artifact_can_be_generated(): void
    {
        $router = $this->app->make(Router::class);

        $this->expectException(RouteCompilationException::class);
        $this->expectExceptionMessage('Route [/users/{id}] contains an invalid constraint pattern for [id].');

        $router->get('/users/{id}', TestSerializedCollectionController::class . '@show')
            ->where('id', '[0-9+');
    }

    /**
     * @param array<int, array<string, mixed>> $routes
     * @return array<string, mixed>
     */
    private function artifactRouteByName(array $routes, string $name): array
    {
        foreach ($routes as $route) {
            if (($route['name'] ?? null) === $name) {
                return $route;
            }
        }

        self::fail(sprintf('Route [%s] was not found in the collection artifact payload.', $name));
    }

    private function removeDirectory(string $directory): void
    {
        if (! is_dir($directory)) {
            return;
        }

        $items = scandir($directory);

        if ($items === false) {
            return;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = $directory . DIRECTORY_SEPARATOR . $item;

            if (is_dir($path)) {
                $this->removeDirectory($path);
                continue;
            }

            @unlink($path);
        }

        @rmdir($directory);
    }
}

final class TestSerializedCollectionController
{
    public function show(): string
    {
        return 'serialized-controller';
    }
}

final class TestCollectionArtifactMiddleware
{
    public function handle(mixed $request, \Closure $next): mixed
    {
        return $next($request);
    }
}
