<?php

declare(strict_types=1);

namespace Quantum\Routing;

use RuntimeException;
use VoltStack\Framework\Application;

final class PipelineArtifactStore
{
    private const ARTIFACT_VERSION = 1;
    private const RECOMMENDED_MAX_PIPELINE_LENGTH = 12;
    private const MIN_ROUTES_FOR_FRAGMENTATION_WARNING = 10;
    private const FRAGMENTATION_SINGLETON_RATIO_WARNING = 0.8;
    private const TOP_REUSED_PIPELINES_LIMIT = 5;
    private const SINGLETON_ROUTE_EXAMPLES_LIMIT = 5;

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
        $pipelineSamples = [];
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
            $pipelineSamples[$pipeline->id()] ??= $route->uri();

            $pipelineLength = count($pipeline->middlewares());

            if ($longestRouteUri !== null && $pipelineLength <= $longestPipelineLength) {
                continue;
            }

            $longestPipelineLength = $pipelineLength;
            $longestRouteUri = $route->uri();
        }

        $totalRoutes = $analyzedRoutes;
        $uniquePipelines = count($pipelineUsage);
        $singletonPipelines = 0;
        $maxPipelineReuse = 0;
        $warnings = [];

        foreach ($pipelineUsage as $count) {
            $maxPipelineReuse = max($maxPipelineReuse, (int) $count);

            if ($count === 1) {
                $singletonPipelines++;
            }
        }

        $topReusedPipelines = $this->topReusedPipelines($pipelineUsage, $pipelineSamples);
        $singletonRouteExamples = $this->singletonRouteExamples($pipelineUsage, $pipelineSamples);

        if ($longestRouteUri !== null && $longestPipelineLength > self::RECOMMENDED_MAX_PIPELINE_LENGTH) {
            $warnings[] = sprintf(
                'El pipeline de la ruta %s tiene %d middleware; considere simplificarlo.',
                $longestRouteUri,
                $longestPipelineLength,
            );
        }

        if ($totalRoutes >= self::MIN_ROUTES_FOR_FRAGMENTATION_WARNING && $uniquePipelines > 0) {
            if ($uniquePipelines === $totalRoutes) {
                $warnings[] = sprintf(
                    'No hay reutilizacion de pipelines: %d rutas, %d pipelines unicos.',
                    $totalRoutes,
                    $uniquePipelines,
                );
            }

            $singletonRatio = $singletonPipelines / $uniquePipelines;

            if ($singletonRatio >= self::FRAGMENTATION_SINGLETON_RATIO_WARNING) {
                $warnings[] = sprintf(
                    'Fragmentacion alta de pipelines: %d de %d pipelines se usan una sola vez.',
                    $singletonPipelines,
                    $uniquePipelines,
                );
            }
        }

        return new PipelineOptimizationReport(
            $totalRoutes,
            $uniquePipelines,
            max(0, $totalRoutes - $uniquePipelines),
            $singletonPipelines,
            $maxPipelineReuse,
            $topReusedPipelines,
            $singletonRouteExamples,
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

    private function topReusedPipelines(array $usage, array $samples): array
    {
        $candidates = [];

        foreach ($usage as $id => $count) {
            if ($count <= 1) {
                continue;
            }

            $candidates[] = [
                'id' => (string) $id,
                'routes' => (int) $count,
                'example' => (string) ($samples[$id] ?? ''),
            ];
        }

        usort($candidates, static function (array $left, array $right): int {
            $compare = ($right['routes'] <=> $left['routes']);

            if ($compare !== 0) {
                return $compare;
            }

            return strcmp((string) $left['id'], (string) $right['id']);
        });

        return array_slice($candidates, 0, self::TOP_REUSED_PIPELINES_LIMIT);
    }

    private function singletonRouteExamples(array $usage, array $samples): array
    {
        $routes = [];

        foreach ($usage as $id => $count) {
            if ($count !== 1) {
                continue;
            }

            $routes[] = (string) ($samples[$id] ?? '');
        }

        sort($routes);

        return array_slice($routes, 0, self::SINGLETON_ROUTE_EXAMPLES_LIMIT);
    }
}
