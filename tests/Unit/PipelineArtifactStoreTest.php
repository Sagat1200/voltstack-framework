<?php

declare(strict_types=1);

namespace VoltStack\Test\Unit;

use PHPUnit\Framework\TestCase;
use Quantum\Config\ConfigRepository;
use Quantum\Http\Request;
use Quantum\HttpKernel\Contracts\MiddlewareInterface;
use Quantum\Routing\PipelineArtifactStore;
use Quantum\Routing\Router;
use RuntimeException;
use VoltStack\Framework\Application;

final class PipelineArtifactStoreTest extends TestCase
{
    private string $basePath;
    private Application $app;

    protected function setUp(): void
    {
        parent::setUp();

        $this->basePath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'voltstack-pipeline-artifact-' . uniqid('', true);

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

    public function test_it_writes_and_loads_a_pipeline_artifact_with_unique_compiled_pipelines(): void
    {
        $this->app->make(ConfigRepository::class)->set('app.env', 'production');

        $router = $this->app->make(Router::class);
        $first = $router->get('/first', fn() => 'first')->middleware(TestArtifactMiddleware::class);
        $second = $router->get('/second', fn() => 'second')->middleware(TestArtifactMiddleware::class);

        $store = $this->app->make(PipelineArtifactStore::class);
        $path = $store->compileAndWrite($router);
        $artifact = $store->load();

        self::assertSame($first->routePipeline(), $second->routePipeline());
        self::assertSame($this->app->cachePath('routes/pipeline.php'), $path);
        self::assertNotNull($artifact);
        self::assertSame(1, $artifact->version());
        self::assertCount(2, $artifact->pipelines());
        self::assertTrue($artifact->has($first->routePipeline()->id()));
        self::assertSame([TestArtifactMiddleware::class], $artifact->middlewares($first->routePipeline()->id()));

        $compiled = $artifact->compilePipelines();

        self::assertArrayHasKey($first->routePipeline()->id(), $compiled);
        self::assertSame($first->routePipeline()->id(), $compiled[$first->routePipeline()->id()]->id());

        $router->reloadPipelineArtifacts();
        $resolvedPipeline = $router->resolvedRoutePipeline($first);

        self::assertSame($first->routePipeline(), $resolvedPipeline);
        self::assertSame($first->routePipeline()->id(), $resolvedPipeline->id());
    }

    public function test_it_rejects_non_serializable_route_middlewares_when_generating_the_artifact(): void
    {
        $router = $this->app->make(Router::class);
        $router->get('/non-serializable', fn() => 'ok')->middleware(
            static fn(Request $request, \Closure $next): mixed => $next($request)
        );

        $store = $this->app->make(PipelineArtifactStore::class);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Route [/non-serializable] contains non-serializable middleware in its compiled pipeline.');

        $store->compile($router);
    }

    public function test_it_generates_an_optimization_report_with_budget_warnings_for_long_pipelines(): void
    {
        $router = $this->app->make(Router::class);
        $router->get('/budget-warning', fn() => 'ok')->middleware([
            TestArtifactMiddleware01::class,
            TestArtifactMiddleware02::class,
            TestArtifactMiddleware03::class,
            TestArtifactMiddleware04::class,
            TestArtifactMiddleware05::class,
            TestArtifactMiddleware06::class,
            TestArtifactMiddleware07::class,
            TestArtifactMiddleware08::class,
            TestArtifactMiddleware09::class,
            TestArtifactMiddleware10::class,
            TestArtifactMiddleware11::class,
            TestArtifactMiddleware12::class,
            TestArtifactMiddleware13::class,
        ]);

        $report = $this->app->make(PipelineArtifactStore::class)->optimizationReport($router);

        self::assertSame(1, $report->totalRoutes());
        self::assertSame(1, $report->uniquePipelines());
        self::assertSame(0, $report->sharedRouteCount());
        self::assertSame(1, $report->singletonPipelines());
        self::assertSame(1, $report->maxPipelineReuse());
        self::assertSame([], $report->topReusedPipelines());
        self::assertSame(['/budget-warning'], $report->singletonRouteExamples());
        self::assertSame('/budget-warning', $report->longestRouteUri());
        self::assertSame(13, $report->longestPipelineLength());
        self::assertTrue($report->hasWarnings());
        self::assertSame([
            'El pipeline de la ruta /budget-warning tiene 13 middleware; considere simplificarlo.',
        ], $report->warnings());
    }

    public function test_it_emits_fragmentation_warnings_when_there_is_no_pipeline_reuse_across_many_routes(): void
    {
        $router = $this->app->make(Router::class);

        $middlewares = [
            TestArtifactMiddleware01::class,
            TestArtifactMiddleware02::class,
            TestArtifactMiddleware03::class,
            TestArtifactMiddleware04::class,
            TestArtifactMiddleware05::class,
            TestArtifactMiddleware06::class,
            TestArtifactMiddleware07::class,
            TestArtifactMiddleware08::class,
            TestArtifactMiddleware09::class,
            TestArtifactMiddleware10::class,
        ];

        foreach ($middlewares as $index => $middleware) {
            $router->get('/fragmentation-' . ($index + 1), TestArtifactPipelineController::class . '@show')
                ->name('fragmentation.' . ($index + 1))
                ->middleware([$middleware]);
        }

        $report = $this->app->make(PipelineArtifactStore::class)->optimizationReport($router);

        self::assertSame(10, $report->totalRoutes());
        self::assertSame(10, $report->uniquePipelines());
        self::assertSame(0, $report->sharedRouteCount());
        self::assertSame(10, $report->singletonPipelines());
        self::assertSame(1, $report->maxPipelineReuse());
        self::assertSame([], $report->topReusedPipelines());
        self::assertSame([
            '/fragmentation-1',
            '/fragmentation-10',
            '/fragmentation-2',
            '/fragmentation-3',
            '/fragmentation-4',
        ], $report->singletonRouteExamples());
        self::assertTrue($report->hasWarnings());
        self::assertSame([
            'No hay reutilizacion de pipelines: 10 rutas, 10 pipelines unicos.',
            'Fragmentacion alta de pipelines: 10 de 10 pipelines se usan una sola vez.',
        ], $report->warnings());
    }

    public function test_it_ranks_top_reused_pipelines(): void
    {
        $router = $this->app->make(Router::class);

        foreach (range(1, 10) as $index) {
            $router->get('/reused-a-' . $index, TestArtifactPipelineController::class . '@show')
                ->name('reused.a.' . $index)
                ->middleware([TestArtifactMiddleware01::class]);
        }

        foreach (range(1, 10) as $index) {
            $router->get('/reused-b-' . $index, TestArtifactPipelineController::class . '@show')
                ->name('reused.b.' . $index)
                ->middleware([TestArtifactMiddleware02::class]);
        }

        $report = $this->app->make(PipelineArtifactStore::class)->optimizationReport($router);

        self::assertSame(20, $report->totalRoutes());
        self::assertSame(2, $report->uniquePipelines());
        self::assertSame(18, $report->sharedRouteCount());
        self::assertSame(0, $report->singletonPipelines());
        self::assertSame(10, $report->maxPipelineReuse());
        self::assertCount(2, $report->topReusedPipelines());
        self::assertSame('/reused-a-1', $report->topReusedPipelines()[0]['example']);
        self::assertSame(10, $report->topReusedPipelines()[0]['routes']);
        self::assertSame('/reused-b-1', $report->topReusedPipelines()[1]['example']);
        self::assertSame(10, $report->topReusedPipelines()[1]['routes']);
        self::assertSame([], $report->singletonRouteExamples());
        self::assertFalse($report->hasWarnings());
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

class TestArtifactMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, \Closure $next): mixed
    {
        return $next($request);
    }
}

final class TestArtifactMiddleware01 extends TestArtifactMiddleware {}
final class TestArtifactMiddleware02 extends TestArtifactMiddleware {}
final class TestArtifactMiddleware03 extends TestArtifactMiddleware {}
final class TestArtifactMiddleware04 extends TestArtifactMiddleware {}
final class TestArtifactMiddleware05 extends TestArtifactMiddleware {}
final class TestArtifactMiddleware06 extends TestArtifactMiddleware {}
final class TestArtifactMiddleware07 extends TestArtifactMiddleware {}
final class TestArtifactMiddleware08 extends TestArtifactMiddleware {}
final class TestArtifactMiddleware09 extends TestArtifactMiddleware {}
final class TestArtifactMiddleware10 extends TestArtifactMiddleware {}
final class TestArtifactMiddleware11 extends TestArtifactMiddleware {}
final class TestArtifactMiddleware12 extends TestArtifactMiddleware {}
final class TestArtifactMiddleware13 extends TestArtifactMiddleware {}

final class TestArtifactPipelineController
{
    public function show(): string
    {
        return 'ok';
    }
}
