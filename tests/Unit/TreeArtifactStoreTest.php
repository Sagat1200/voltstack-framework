<?php

declare(strict_types=1);

namespace VoltStack\Test\Unit;

use PHPUnit\Framework\TestCase;
use Quantum\Routing\Router;
use Quantum\Routing\TreeArtifactStore;
use RuntimeException;
use VoltStack\Framework\Application;

final class TreeArtifactStoreTest extends TestCase
{
    private string $basePath;
    private Application $app;

    protected function setUp(): void
    {
        parent::setUp();

        $this->basePath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'voltstack-tree-artifact-' . uniqid('', true);

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

    public function test_it_writes_and_loads_a_tree_artifact_with_static_and_dynamic_buckets(): void
    {
        $router = $this->app->make(Router::class);
        $router->get('/about', 'about.handler');
        $router->get('/users/{id}', 'users.show')->whereNumber('id');
        $router->get('/{tenant}/dashboard', 'tenant.dashboard')->whereAlphaNumeric('tenant');

        $store = $this->app->make(TreeArtifactStore::class);
        $path = $store->compileAndWrite($router);
        $artifact = $store->load();

        self::assertSame($this->app->cachePath('routes/tree.php'), $path);
        self::assertNotNull($artifact);
        self::assertSame(1, $artifact->version());
        self::assertSame(count($router->routes()), $artifact->routeCount());

        $tree = $artifact->compileTree();
        $routes = $router->routes();
        $aboutIndex = $this->routeIndexByUri($routes, '/about');
        $usersIndex = $this->routeIndexByUri($routes, '/users/{id}');
        $tenantDashboardIndex = $this->routeIndexByUri($routes, '/{tenant}/dashboard');

        self::assertSame([$aboutIndex], $tree->candidatesFor('/about'));
        self::assertSame([$usersIndex, $tenantDashboardIndex], $tree->candidatesFor('/users/42'));
        self::assertSame([$tenantDashboardIndex], $tree->candidatesFor('/acme/dashboard'));
        self::assertSame([], $tree->candidatesFor('/missing'));
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
