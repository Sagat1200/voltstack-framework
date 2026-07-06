<?php

declare(strict_types=1);

namespace VoltStack\Test\Unit;

use PHPUnit\Framework\TestCase;
use Quantum\Console\Commands\RouteCacheCommand;
use Quantum\Console\Commands\RouteClearCommand;
use Quantum\Console\Input;
use Quantum\Console\Output;
use Quantum\Routing\RouteArtifactManager;
use Quantum\Routing\Router;
use VoltStack\Framework\Application;

final class RouteArtifactCommandsTest extends TestCase
{
    private string $basePath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->basePath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'voltstack-route-artifacts-' . uniqid('', true);

        mkdir($this->basePath . DIRECTORY_SEPARATOR . 'bootstrap', 0777, true);
        mkdir($this->basePath . DIRECTORY_SEPARATOR . 'config', 0777, true);
        mkdir($this->basePath . DIRECTORY_SEPARATOR . 'routes', 0777, true);

        file_put_contents(
            $this->basePath . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'app.php',
            <<<'PHP'
<?php

declare(strict_types=1);

return [
    'env' => 'production',
    'providers' => [],
];
PHP
        );

        file_put_contents(
            $this->basePath . DIRECTORY_SEPARATOR . 'routes' . DIRECTORY_SEPARATOR . 'web.php',
            <<<'PHP'
<?php

declare(strict_types=1);

use Quantum\Routing\Router;
use VoltStack\Test\Unit\TestRouteArtifactCommandController;

return static function (Router $router): void {
    $router->get('/cached-route', TestRouteArtifactCommandController::class . '@show')
        ->name('cached.route');
};
PHP
        );

        $escapedBasePath = var_export($this->basePath, true);

        file_put_contents(
            $this->basePath . DIRECTORY_SEPARATOR . 'bootstrap' . DIRECTORY_SEPARATOR . 'app.php',
            <<<PHP
<?php

declare(strict_types=1);

use Quantum\Bootstrap\Bootstrapper;
use VoltStack\Framework\Application;

\$app = new Application({$escapedBasePath});
\$bootstrapper = new Bootstrapper(\$app);
\$bootstrapper->loadConfiguration();
\$app->boot();
\$bootstrapper->loadRoutes(__DIR__ . '/../routes/web.php');

return \$app;
PHP
        );
    }

    protected function tearDown(): void
    {
        $this->deleteDirectory($this->basePath);

        parent::tearDown();
    }

    public function test_it_can_compile_and_clear_route_artifacts(): void
    {
        $cacheCommand = new RouteCacheCommand($this->basePath);
        $cacheOutput = new Output();

        $cacheExitCode = $cacheCommand->handle(
            Input::fromArgv([
                'volt',
                'route:cache',
                '--verbose',
            ]),
            $cacheOutput,
        );

        $app = $this->bootstrappedApplication();
        $manager = $app->make(RouteArtifactManager::class);
        self::assertSame(0, $cacheExitCode);

        foreach ($manager->paths() as $name => $path) {
            self::assertFileExists($path, sprintf('Missing artifact [%s].', $name));
            self::assertStringContainsString($path, $cacheOutput->stdout());
        }

        self::assertStringContainsString('Artifacts escritos: 6', $cacheOutput->stdout());

        $manifest = file_get_contents($manager->paths()['frontend-manifest']);

        self::assertIsString($manifest);
        self::assertStringContainsString('"cached.route"', $manifest);

        $clearCommand = new RouteClearCommand($this->basePath);
        $clearOutput = new Output();

        $clearExitCode = $clearCommand->handle(
            Input::fromArgv([
                'volt',
                'route:clear',
                '--verbose',
            ]),
            $clearOutput,
        );

        self::assertSame(0, $clearExitCode);
        self::assertStringContainsString('Artifacts eliminados: 6', $clearOutput->stdout());

        foreach ($manager->paths() as $path) {
            self::assertFileDoesNotExist($path);
        }
    }

    private function bootstrappedApplication(): Application
    {
        /** @var Application $app */
        $app = require $this->basePath . DIRECTORY_SEPARATOR . 'bootstrap' . DIRECTORY_SEPARATOR . 'app.php';

        return $app;
    }

    private function deleteDirectory(string $path): void
    {
        if (! is_dir($path)) {
            return;
        }

        $items = scandir($path);

        if ($items === false) {
            return;
        }

        foreach ($items as $item) {
            if (in_array($item, ['.', '..'], true)) {
                continue;
            }

            $target = $path . DIRECTORY_SEPARATOR . $item;

            if (is_dir($target)) {
                $this->deleteDirectory($target);
                continue;
            }

            unlink($target);
        }

        rmdir($path);
    }
}

final class TestRouteArtifactCommandController
{
    public function show(): string
    {
        return 'cached';
    }
}
