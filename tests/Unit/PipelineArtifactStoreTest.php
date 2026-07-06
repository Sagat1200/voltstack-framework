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
        $router->get('/second', fn() => 'second')->middleware(TestArtifactMiddleware::class);

        $store = $this->app->make(PipelineArtifactStore::class);
        $path = $store->compileAndWrite($router);
        $artifact = $store->load();

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

        self::assertNotSame($first->routePipeline(), $resolvedPipeline);
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

final class TestArtifactMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, \Closure $next): mixed
    {
        return $next($request);
    }
}
