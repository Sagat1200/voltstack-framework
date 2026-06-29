<?php

declare(strict_types=1);

namespace Quantum\Routing;

use RuntimeException;
use VoltStack\Framework\Application;

final class TreeArtifactStore
{
    private const ARTIFACT_VERSION = 1;

    public function __construct(
        private readonly Application $app,
    ) {}

    public function path(): string
    {
        return $this->app->cachePath('routes/tree.php');
    }

    public function artifactVersion(): int
    {
        return self::ARTIFACT_VERSION;
    }

    public function compile(Router $router): TreeArtifact
    {
        (new RouteCompilerValidator())->validateRoutes($router->routes());

        $staticRoutes = [];
        $dynamicRoutes = [];
        $routes = $router->routes();

        foreach ($routes as $index => $route) {
            $uri = $this->normalizePath($route->uri());

            if (! str_contains($uri, '{')) {
                $staticRoutes[$uri] ??= [];
                $staticRoutes[$uri][] = $index;

                continue;
            }

            $segments = $this->segments($uri);
            $segmentCount = count($segments);
            $firstSegment = $segments[0] ?? '';
            $bucket = $this->isParameterSegment($firstSegment) ? '*' : $firstSegment;

            $dynamicRoutes[$bucket] ??= [];
            $dynamicRoutes[$bucket][$segmentCount] ??= [];
            $dynamicRoutes[$bucket][$segmentCount][] = $index;
        }

        return new TreeArtifact(
            self::ARTIFACT_VERSION,
            count($routes),
            $staticRoutes,
            $dynamicRoutes,
        );
    }

    public function write(TreeArtifact $artifact): string
    {
        $path = $this->path();
        $directory = dirname($path);

        if (! is_dir($directory) && ! mkdir($directory, 0777, true) && ! is_dir($directory)) {
            throw new RuntimeException(sprintf('Unable to create tree artifact directory [%s].', $directory));
        }

        $contents = "<?php\n\nreturn " . var_export($artifact->toArray(), true) . ";\n";

        if (file_put_contents($path, $contents) === false) {
            throw new RuntimeException(sprintf('Unable to write tree artifact [%s].', $path));
        }

        return $path;
    }

    public function compileAndWrite(Router $router): string
    {
        return $this->write($this->compile($router));
    }

    public function load(): ?TreeArtifact
    {
        $path = $this->path();

        if (! is_file($path)) {
            return null;
        }

        /** @var mixed $payload */
        $payload = require $path;

        if (! is_array($payload)) {
            throw new RuntimeException(sprintf('Tree artifact [%s] must return an array payload.', $path));
        }

        return TreeArtifact::fromArray($payload);
    }

    private function normalizePath(string $path): string
    {
        $trimmed = trim($path, '/');

        return $trimmed === '' ? '/' : '/' . $trimmed;
    }

    /**
     * @return array<int, string>
     */
    private function segments(string $path): array
    {
        $trimmed = trim($path, '/');

        if ($trimmed === '') {
            return [];
        }

        return explode('/', $trimmed);
    }

    private function isParameterSegment(string $segment): bool
    {
        return $segment !== '' && preg_match('/^\{[^}]+\}$/', $segment) === 1;
    }
}
