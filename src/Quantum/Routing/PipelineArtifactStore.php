<?php

declare(strict_types=1);

namespace Quantum\Routing;

use RuntimeException;
use VoltStack\Framework\Application;

final class PipelineArtifactStore
{
    private const ARTIFACT_VERSION = 1;
    private const RECOMMENDED_MAX_PIPELINE_LENGTH = 12;

    public function __construct(
        private readonly Application $app,
    ) {}

    public function path(): string
    {
        return $this->app->cachePath('routes/pipeline.php');
    }

    public function artifactVersion(): int
    {
        return self::ARTIFACT_VERSION;
    }

    public function compile(Router $router): PipelineArtifact
    {
        (new RouteCompilerValidator())->validateRoutes(
            $router->routes(),
            false,
            true,
            false,
        );

        $pipelines = [];

        foreach ($router->routes() as $route) {
            $id = $route->routePipeline()->id();

            if (isset($pipelines[$id])) {
                continue;
            }

            $pipelines[$id] = $this->serializeMiddlewares($route->routePipeline()->middlewares(), $route->uri());
        }

        return new PipelineArtifact(self::ARTIFACT_VERSION, $pipelines);
    }

    public function optimizationReport(Router $router): PipelineOptimizationReport
    {
        (new RouteCompilerValidator())->validateRoutes(
            $router->routes(),
            false,
            true,
            false,
        );

        $pipelineUsage = [];
        $longestRouteUri = null;
        $longestPipelineLength = 0;
        $analyzedRoutes = 0;

        foreach ($router->routes() as $route) {
            if ($this->isInternalRoute($route)) {
                continue;
            }

            $analyzedRoutes++;
            $pipeline = $route->routePipeline();
            $pipelineUsage[$pipeline->id()] = ($pipelineUsage[$pipeline->id()] ?? 0) + 1;

            $pipelineLength = count($pipeline->middlewares());

            if ($longestRouteUri !== null && $pipelineLength <= $longestPipelineLength) {
                continue;
            }

            $longestPipelineLength = $pipelineLength;
            $longestRouteUri = $route->uri();
        }

        $totalRoutes = $analyzedRoutes;
        $uniquePipelines = count($pipelineUsage);
        $warnings = [];

        if ($longestRouteUri !== null && $longestPipelineLength > self::RECOMMENDED_MAX_PIPELINE_LENGTH) {
            $warnings[] = sprintf(
                'El pipeline de la ruta %s tiene %d middleware; considere simplificarlo.',
                $longestRouteUri,
                $longestPipelineLength,
            );
        }

        return new PipelineOptimizationReport(
            $totalRoutes,
            $uniquePipelines,
            max(0, $totalRoutes - $uniquePipelines),
            $longestRouteUri,
            $longestPipelineLength,
            $warnings,
        );
    }

    public function write(PipelineArtifact $artifact): string
    {
        $path = $this->path();
        $directory = dirname($path);

        if (! is_dir($directory) && ! mkdir($directory, 0777, true) && ! is_dir($directory)) {
            throw new RuntimeException(sprintf('Unable to create pipeline artifact directory [%s].', $directory));
        }

        $contents = "<?php\n\nreturn " . var_export($artifact->toArray(), true) . ";\n";

        if (file_put_contents($path, $contents) === false) {
            throw new RuntimeException(sprintf('Unable to write pipeline artifact [%s].', $path));
        }

        return $path;
    }

    public function compileAndWrite(Router $router): string
    {
        return $this->write($this->compile($router));
    }

    public function load(): ?PipelineArtifact
    {
        $path = $this->path();

        if (! is_file($path)) {
            return null;
        }

        /** @var mixed $payload */
        $payload = require $path;

        if (! is_array($payload)) {
            throw new RuntimeException(sprintf('Pipeline artifact [%s] must return an array payload.', $path));
        }

        return PipelineArtifact::fromArray($payload);
    }

    /**
     * @param array<int, mixed> $middlewares
     * @return array<int, class-string>
     */
    private function serializeMiddlewares(array $middlewares, string $routeUri): array
    {
        $serialized = [];

        foreach ($middlewares as $middleware) {
            if (! is_string($middleware) || $middleware === '') {
                throw new RuntimeException(sprintf(
                    'Route [%s] contains non-serializable middleware in its compiled pipeline.',
                    $routeUri,
                ));
            }

            $serialized[] = $middleware;
        }

        return $serialized;
    }

    private function isInternalRoute(Route $route): bool
    {
        $transport = $route->routeMetadata()->get('transport');

        if (is_string($transport) && strtolower(trim($transport)) === 'internal') {
            return true;
        }

        return str_starts_with($route->uri(), '/_volt/');
    }
}
