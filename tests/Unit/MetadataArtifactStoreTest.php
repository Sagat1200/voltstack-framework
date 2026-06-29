<?php

declare(strict_types=1);

namespace VoltStack\Test\Unit;

use PHPUnit\Framework\TestCase;
use Quantum\Routing\MetadataArtifactStore;
use Quantum\Routing\RouteMetadata;
use Quantum\Routing\Router;
use RuntimeException;
use VoltStack\Framework\Application;

final class MetadataArtifactStoreTest extends TestCase
{
    private string $basePath;
    private Application $app;

    protected function setUp(): void
    {
        parent::setUp();

        $this->basePath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'voltstack-metadata-artifact-' . uniqid('', true);

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

    public function test_it_writes_and_loads_a_metadata_artifact_that_can_restore_route_metadata(): void
    {
        $router = $this->app->make(Router::class);
        $router->get('/users/{id}', TestSerializedMetadataController::class . '@show')
            ->name('users.show')
            ->domain('{tenant}.example.com')
            ->whereAlphaNumeric('tenant')
            ->whereNumber('id')
            ->middleware(TestMetadataArtifactMiddleware::class)
            ->meta([
                'auth' => 'session',
                'runtime' => ['mode' => 'spa'],
            ])
            ->guest();

        $store = $this->app->make(MetadataArtifactStore::class);
        $path = $store->compileAndWrite($router);
        $artifact = $store->load();

        self::assertSame($this->app->cachePath('routes/metadata.php'), $path);
        self::assertNotNull($artifact);
        self::assertSame(1, $artifact->version());
        self::assertSame(count($router->routes()), $artifact->routeCount());

        $routes = $router->compiledCollection();
        $targetIndex = $this->routeIndexByUri($router->routes(), '/users/{id}');
        $compiledRoute = $routes->at($targetIndex);

        self::assertNotNull($compiledRoute);

        $compiledRoute->replaceRouteMetadata(new RouteMetadata([
            'name' => 'users.show',
            'methods' => ['GET'],
            'domain' => '{tenant}.example.com',
        ]));

        $artifact->applyTo($routes);

        self::assertSame('session', $compiledRoute->routeMetadata()->get('auth'));
        self::assertSame(['mode' => 'spa'], $compiledRoute->routeMetadata()->get('runtime'));
        self::assertTrue($compiledRoute->routeMetadata()->get('guest'));
        self::assertSame([TestMetadataArtifactMiddleware::class], $compiledRoute->routeMetadata()->get('middleware'));
    }

    /**
     * @param array<int, \Quantum\Routing\Route> $routes
     */
    private function routeIndexByUri(array $routes, string $uri): int
    {
        foreach ($routes as $index => $route) {
            if ($route->uri() === $uri) {
                return $index;
            }
        }

        self::fail(sprintf('Route [%s] was not found in the registered routes.', $uri));
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

final class TestSerializedMetadataController
{
    public function show(): string
    {
        return 'serialized-metadata-controller';
    }
}

final class TestMetadataArtifactMiddleware
{
    public function handle(mixed $request, \Closure $next): mixed
    {
        return $next($request);
    }
}
