<?php

declare(strict_types=1);

namespace VoltStack\Test\Unit;

use PHPUnit\Framework\TestCase;
use Quantum\Routing\FrontendRouteManifestStore;
use Quantum\Routing\Router;
use RuntimeException;
use VoltStack\Framework\Application;

final class FrontendRouteManifestStoreTest extends TestCase
{
    private string $basePath;
    private Application $app;

    protected function setUp(): void
    {
        parent::setUp();

        $this->basePath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'voltstack-frontend-route-manifest-' . uniqid('', true);

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

    public function test_it_writes_and_loads_a_minimal_public_frontend_route_manifest(): void
    {
        $router = $this->app->make(Router::class);
        $router->get('/users/{user}', TestFrontendManifestController::class . '@show')
            ->name('users.show')
            ->middleware(TestFrontendManifestMiddleware::class)
            ->meta([
                'auth' => 'session',
                'prefetch' => true,
                'runtime' => [
                    'layout' => 'dashboard',
                    'document' => 'reload',
                    'transition' => [
                        'name' => 'fade',
                        'profile' => 'smooth',
                    ],
                    'hydrate' => [
                        'enabled' => true,
                        'strategy' => 'partial',
                    ],
                ],
            ]);
        $router->post('/users', TestFrontendManifestController::class . '@store')
            ->name('users.store')
            ->meta([
                'runtime' => [
                    'hydrate' => false,
                    'transition' => 'slide',
                ],
            ]);
        $router->get('/internal-preview', TestFrontendManifestController::class . '@show')
            ->name('internal.preview')
            ->meta([
                'transport' => 'internal',
            ]);
        $router->get('/unnamed-public-route', TestFrontendManifestController::class . '@show');

        $store = $this->app->make(FrontendRouteManifestStore::class);
        $path = $store->compileAndWrite($router);
        $manifest = $store->load();

        self::assertSame($this->app->cachePath('routes/frontend-manifest.json'), $path);
        self::assertNotNull($manifest);
        self::assertSame('VoltStack Frontend Manifest', $manifest->protocolName());
        self::assertSame('1.0', $manifest->protocolVersion());
        self::assertSame(1, $manifest->manifestVersion());
        self::assertSame(hash('sha256', json_encode($manifest->routes(), JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)), $manifest->checksum());

        $routes = $manifest->routes();

        self::assertCount(2, $routes);
        self::assertSame([
            'name' => 'users.show',
            'path' => '/users/{user}',
            'methods' => ['GET'],
            'capabilities' => ['navigate', 'hydrate', 'prefetch'],
            'runtime' => [
                'layout' => 'dashboard',
                'transition' => 'fade',
                'hydrate' => true,
            ],
        ], $routes[0]);
        self::assertSame([
            'name' => 'users.store',
            'path' => '/users',
            'methods' => ['POST'],
            'capabilities' => [],
            'runtime' => [
                'transition' => 'slide',
                'hydrate' => false,
            ],
        ], $routes[1]);
        self::assertArrayNotHasKey('middleware', $routes[0]);
        self::assertArrayNotHasKey('auth', $routes[0]);
        self::assertArrayNotHasKey('document', $routes[0]['runtime']);
        self::assertSame(['users.show', 'users.store'], array_column($routes, 'name'));
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

final class TestFrontendManifestController
{
    public function show(): string
    {
        return 'frontend-manifest-show';
    }

    public function store(): string
    {
        return 'frontend-manifest-store';
    }
}

final class TestFrontendManifestMiddleware
{
    public function handle(mixed $request, \Closure $next): mixed
    {
        return $next($request);
    }
}