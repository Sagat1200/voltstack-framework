<?php

declare(strict_types=1);

namespace VoltStack\Test\Unit;

use PHPUnit\Framework\TestCase;
use Quantum\Bootstrap\Bootstrapper;
use Quantum\Routing\CollectionArtifactStore;
use Quantum\Routing\Router;
use VoltStack\Framework\Application;

final class BootstrapperTest extends TestCase
{
    private string $basePath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->basePath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'voltstack-bootstrapper-' . uniqid('', true);

        if (! mkdir($concurrentDirectory = $this->basePath, 0777, true) && ! is_dir($concurrentDirectory)) {
            throw new \RuntimeException(sprintf('Unable to create test directory [%s].', $this->basePath));
        }
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->basePath);

        parent::tearDown();
    }

    public function test_it_loads_route_files_when_live_route_registration_is_required(): void
    {
        $app = new Application($this->basePath);
        $bootstrapper = new Bootstrapper($app);

        $bootstrapper->loadRoutes($this->writeRouteFile('route.file', '/from-route-file'));

        $route = $app->make(Router::class)->collection()->named('route.file');

        self::assertNotNull($route);
        self::assertSame('/from-route-file', $route->uri());
    }

    public function test_it_skips_route_file_interpretation_when_compiled_route_artifacts_are_available(): void
    {
        $builderApp = new Application($this->basePath);
        $builderRouter = $builderApp->make(Router::class);
        $builderRouter->get('/from-artifact', TestBootstrapController::class . '@show')->name('artifact.route');
        $builderApp->make(CollectionArtifactStore::class)->compileAndWrite($builderRouter);

        $runtimeApp = new Application($this->basePath);
        $bootstrapper = new Bootstrapper($runtimeApp);
        $bootstrapper->loadRoutes($this->writeRouteFile('route.file', '/from-route-file'));

        $runtimeRouter = $runtimeApp->make(Router::class);

        self::assertNull($runtimeRouter->collection()->named('route.file'));
        self::assertNull($runtimeRouter->compiledCollection()->named('route.file'));
        self::assertSame('/from-artifact', $runtimeRouter->compiledCollection()->named('artifact.route')?->uri());
    }

    private function writeRouteFile(string $name, string $uri): string
    {
        $path = $this->basePath . DIRECTORY_SEPARATOR . 'routes.php';
        $action = var_export(TestBootstrapController::class . '@show', true);
        $routeName = var_export($name, true);
        $routeUri = var_export($uri, true);
        $contents = <<<PHP
<?php

declare(strict_types=1);

use Quantum\Routing\Router;

return static function (Router \$router): void {
    \$router->get({$routeUri}, {$action})->name({$routeName});
};
PHP;

        file_put_contents($path, $contents);

        return $path;
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

final class TestBootstrapController
{
    public function show(): string
    {
        return 'ok';
    }
}
