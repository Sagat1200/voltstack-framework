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
        self::assertStringContainsString('Pipeline optimizer:', $cacheOutput->stdout());
        self::assertStringContainsString('Rutas analizadas: 1', $cacheOutput->stdout());
        self::assertStringContainsString('Pipelines unicos: 1', $cacheOutput->stdout());
        self::assertStringContainsString('Rutas reutilizando pipeline: 0', $cacheOutput->stdout());
        self::assertStringContainsString('Pipelines singleton: 1', $cacheOutput->stdout());
        self::assertStringContainsString('Max reutilizacion: 1 rutas por pipeline', $cacheOutput->stdout());
        self::assertStringContainsString('Pipeline mas largo: /cached-route (0 middleware)', $cacheOutput->stdout());
        self::assertStringContainsString('Top pipelines reutilizados: 0', $cacheOutput->stdout());
        self::assertStringContainsString('Ejemplos singleton: 1', $cacheOutput->stdout());
        self::assertStringContainsString('  - /cached-route', $cacheOutput->stdout());

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

    public function test_route_cache_emits_pipeline_optimizer_warnings_when_a_route_exceeds_the_budget(): void
    {
        $aliases = [
            'VoltStack\\Test\\Unit\\TestRouteArtifactCommandBudgetMiddleware01',
            'VoltStack\\Test\\Unit\\TestRouteArtifactCommandBudgetMiddleware02',
            'VoltStack\\Test\\Unit\\TestRouteArtifactCommandBudgetMiddleware03',
            'VoltStack\\Test\\Unit\\TestRouteArtifactCommandBudgetMiddleware04',
            'VoltStack\\Test\\Unit\\TestRouteArtifactCommandBudgetMiddleware05',
            'VoltStack\\Test\\Unit\\TestRouteArtifactCommandBudgetMiddleware06',
            'VoltStack\\Test\\Unit\\TestRouteArtifactCommandBudgetMiddleware07',
            'VoltStack\\Test\\Unit\\TestRouteArtifactCommandBudgetMiddleware08',
            'VoltStack\\Test\\Unit\\TestRouteArtifactCommandBudgetMiddleware09',
            'VoltStack\\Test\\Unit\\TestRouteArtifactCommandBudgetMiddleware10',
            'VoltStack\\Test\\Unit\\TestRouteArtifactCommandBudgetMiddleware11',
            'VoltStack\\Test\\Unit\\TestRouteArtifactCommandBudgetMiddleware12',
            'VoltStack\\Test\\Unit\\TestRouteArtifactCommandBudgetMiddleware13',
        ];

        foreach ($aliases as $alias) {
            if (! class_exists($alias, false)) {
                class_alias(TestRouteArtifactCommandPassThroughMiddleware::class, $alias);
            }
        }

        file_put_contents(
            $this->basePath . DIRECTORY_SEPARATOR . 'routes' . DIRECTORY_SEPARATOR . 'web.php',
            <<<'PHP'
<?php

declare(strict_types=1);

use Quantum\Routing\Router;
use VoltStack\Test\Unit\TestRouteArtifactCommandController;

return static function (Router $router): void {
    $router->get('/heavy-pipeline', TestRouteArtifactCommandController::class . '@show')
        ->name('heavy.pipeline')
        ->middleware([
            \VoltStack\Test\Unit\TestRouteArtifactCommandBudgetMiddleware01::class,
            \VoltStack\Test\Unit\TestRouteArtifactCommandBudgetMiddleware02::class,
            \VoltStack\Test\Unit\TestRouteArtifactCommandBudgetMiddleware03::class,
            \VoltStack\Test\Unit\TestRouteArtifactCommandBudgetMiddleware04::class,
            \VoltStack\Test\Unit\TestRouteArtifactCommandBudgetMiddleware05::class,
            \VoltStack\Test\Unit\TestRouteArtifactCommandBudgetMiddleware06::class,
            \VoltStack\Test\Unit\TestRouteArtifactCommandBudgetMiddleware07::class,
            \VoltStack\Test\Unit\TestRouteArtifactCommandBudgetMiddleware08::class,
            \VoltStack\Test\Unit\TestRouteArtifactCommandBudgetMiddleware09::class,
            \VoltStack\Test\Unit\TestRouteArtifactCommandBudgetMiddleware10::class,
            \VoltStack\Test\Unit\TestRouteArtifactCommandBudgetMiddleware11::class,
            \VoltStack\Test\Unit\TestRouteArtifactCommandBudgetMiddleware12::class,
            \VoltStack\Test\Unit\TestRouteArtifactCommandBudgetMiddleware13::class,
        ]);
};
PHP
        );

        $cacheCommand = new RouteCacheCommand($this->basePath);
        $cacheOutput = new Output();

        $exitCode = $cacheCommand->handle(
            Input::fromArgv([
                'volt',
                'route:cache',
            ]),
            $cacheOutput,
        );

        self::assertSame(0, $exitCode);
        self::assertStringContainsString('Advertencias del pipeline optimizer:', $cacheOutput->stdout());
        self::assertStringContainsString('El pipeline de la ruta /heavy-pipeline tiene 13 middleware; considere simplificarlo.', $cacheOutput->stdout());
    }

    public function test_route_cache_optimizer_only_emits_the_optimizer_report_without_writing_artifacts(): void
    {
        $cacheCommand = new RouteCacheCommand($this->basePath);
        $cacheOutput = new Output();

        $exitCode = $cacheCommand->handle(
            Input::fromArgv([
                'volt',
                'route:cache',
                '--optimizer-only',
            ]),
            $cacheOutput,
        );

        self::assertSame(0, $exitCode);
        self::assertStringContainsString('Pipeline optimizer:', $cacheOutput->stdout());
        self::assertStringContainsString('Rutas analizadas: 1', $cacheOutput->stdout());
        self::assertStringNotContainsString('Artifacts de rutas compilados correctamente.', $cacheOutput->stdout());
        self::assertStringNotContainsString('[collection]', $cacheOutput->stdout());

        $app = $this->bootstrappedApplication();
        $manager = $app->make(RouteArtifactManager::class);

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

final class TestRouteArtifactCommandPassThroughMiddleware implements \Quantum\HttpKernel\Contracts\MiddlewareInterface
{
    public function handle(\Quantum\Http\Request $request, \Closure $next): mixed
    {
        return $next($request);
    }
}
