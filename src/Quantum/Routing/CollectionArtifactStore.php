<?php

declare(strict_types=1);

namespace Quantum\Routing;

use Closure;
use RuntimeException;
use VoltStack\Framework\Application;

final class CollectionArtifactStore
{
    private const ARTIFACT_VERSION = 1;

    public function __construct(
        private readonly Application $app,
    ) {}

    public function path(): string
    {
        return $this->app->cachePath('routes/collection.php');
    }

    public function artifactVersion(): int
    {
        return self::ARTIFACT_VERSION;
    }

    public function compile(Router $router): CollectionArtifact
    {
        (new RouteCompilerValidator())->validateRoutes(
            $router->routes(),
            true,
            true,
            true,
        );

        $routes = [];

        foreach ($router->routes() as $route) {
            $routes[] = [
                'methods' => $route->methods(),
                'uri' => $route->uri(),
                'domain' => $route->routeDomain(),
                'action' => $this->serializeAction($route->action(), $route->uri()),
                'name' => $route->routeName(),
                'constraints' => $this->serializeConstraints($route->definition()->constraints(), $route->uri()),
                'middlewares' => $this->serializeMiddlewares($route->routeMiddlewares(), $route->uri()),
                'metadata' => $this->serializeMetadataBag($route->definition()->metadata(), $route->uri()),
            ];
        }

        return new CollectionArtifact(self::ARTIFACT_VERSION, $routes);
    }

    public function write(CollectionArtifact $artifact): string
    {
        $path = $this->path();
        $directory = dirname($path);

        if (! is_dir($directory) && ! mkdir($directory, 0777, true) && ! is_dir($directory)) {
            throw new RuntimeException(sprintf('Unable to create collection artifact directory [%s].', $directory));
        }

        $contents = "<?php\n\nreturn " . var_export($artifact->toArray(), true) . ";\n";

        if (file_put_contents($path, $contents) === false) {
            throw new RuntimeException(sprintf('Unable to write collection artifact [%s].', $path));
        }

        return $path;
    }

    public function compileAndWrite(Router $router): string
    {
        return $this->write($this->compile($router));
    }

    public function load(): ?CollectionArtifact
    {
        $path = $this->path();

        if (! is_file($path)) {
            return null;
        }

        /** @var mixed $payload */
        $payload = require $path;

        if (! is_array($payload)) {
            throw new RuntimeException(sprintf('Collection artifact [%s] must return an array payload.', $path));
        }

        return CollectionArtifact::fromArray($payload);
    }

    /**
     * @return array{kind: string, value: string|array<int, string>}
     */
    private function serializeAction(mixed $action, string $routeUri): array
    {
        if (is_string($action) && $action !== '') {
            return [
                'kind' => 'string',
                'value' => $action,
            ];
        }

        if (is_array($action) && count($action) === 2 && is_string($action[0]) && $action[0] !== '' && is_string($action[1]) && $action[1] !== '') {
            return [
                'kind' => 'controller',
                'value' => [(string) $action[0], (string) $action[1]],
            ];
        }

        if ($action instanceof Closure) {
            throw new RuntimeException(sprintf(
                'Route [%s] contains a closure action that cannot be serialized into the collection artifact.',
                $routeUri,
            ));
        }

        throw new RuntimeException(sprintf(
            'Route [%s] contains a non-serializable action for the collection artifact.',
            $routeUri,
        ));
    }

    /**
     * @param array<string, string> $constraints
     * @return array<string, string>
     */
    private function serializeConstraints(array $constraints, string $routeUri): array
    {
        $serialized = [];

        foreach ($constraints as $parameter => $pattern) {
            if (! is_string($parameter) || trim($parameter) === '' || ! is_string($pattern) || trim($pattern) === '') {
                throw new RuntimeException(sprintf(
                    'Route [%s] contains a non-serializable constraint definition.',
                    $routeUri,
                ));
            }

            $serialized[$parameter] = $pattern;
        }

        return $serialized;
    }

    /**
     * @param array<int, mixed> $middlewares
     * @return array<int, string>
     */
    private function serializeMiddlewares(array $middlewares, string $routeUri): array
    {
        $serialized = [];

        foreach ($middlewares as $middleware) {
            if (! is_string($middleware) || $middleware === '') {
                throw new RuntimeException(sprintf(
                    'Route [%s] contains non-serializable middleware in the collection artifact payload.',
                    $routeUri,
                ));
            }

            $serialized[] = $middleware;
        }

        return $serialized;
    }

    /**
     * @param array<string, mixed> $metadata
     * @return array<string, mixed>
     */
    private function serializeMetadataBag(array $metadata, string $routeUri): array
    {
        $serialized = [];

        foreach ($metadata as $key => $value) {
            if (! is_string($key) || trim($key) === '') {
                throw new RuntimeException(sprintf(
                    'Route [%s] contains a non-serializable metadata key.',
                    $routeUri,
                ));
            }

            $serialized[$key] = $this->serializeMetadataValue($value, $routeUri, $key);
        }

        return $serialized;
    }

    private function serializeMetadataValue(mixed $value, string $routeUri, string $key): mixed
    {
        if (is_null($value) || is_string($value) || is_int($value) || is_float($value) || is_bool($value)) {
            return $value;
        }

        if (is_array($value)) {
            $serialized = [];

            foreach ($value as $nestedKey => $nestedValue) {
                if (! is_int($nestedKey) && ! is_string($nestedKey)) {
                    throw new RuntimeException(sprintf(
                        'Route [%s] contains non-serializable metadata at [%s].',
                        $routeUri,
                        $key,
                    ));
                }

                $serialized[$nestedKey] = $this->serializeMetadataValue($nestedValue, $routeUri, $key);
            }

            return $serialized;
        }

        throw new RuntimeException(sprintf(
            'Route [%s] contains non-serializable metadata at [%s].',
            $routeUri,
            $key,
        ));
    }
}
