<?php

declare(strict_types=1);

namespace Quantum\Routing;

use JsonException;
use RuntimeException;
use VoltStack\Framework\Application;
use VoltStack\Runtime\Component\Component;

final class FrontendRouteManifestStore
{
    private const MANIFEST_VERSION = 1;
    private const PROTOCOL_VERSION = '1.0';

    public function __construct(
        private readonly Application $app,
    ) {}

    public function path(): string
    {
        return $this->app->cachePath('routes/frontend-manifest.json');
    }

    public function compile(Router $router): FrontendRouteManifest
    {
        return $this->compileCollection($router->compiledCollection());
    }

    public function write(FrontendRouteManifest $manifest): string
    {
        $path = $this->path();
        $directory = dirname($path);

        if (! is_dir($directory) && ! mkdir($directory, 0777, true) && ! is_dir($directory)) {
            throw new RuntimeException(sprintf('Unable to create frontend route manifest directory [%s].', $directory));
        }

        try {
            $contents = json_encode($manifest->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException('Unable to serialize the frontend route manifest.', 0, $exception);
        }

        if (file_put_contents($path, $contents . PHP_EOL) === false) {
            throw new RuntimeException(sprintf('Unable to write frontend route manifest [%s].', $path));
        }

        return $path;
    }

    public function compileAndWrite(Router $router): string
    {
        return $this->write($this->compile($router));
    }

    public function load(): ?FrontendRouteManifest
    {
        $path = $this->path();

        if (! is_file($path)) {
            return null;
        }

        $contents = file_get_contents($path);

        if ($contents === false) {
            throw new RuntimeException(sprintf('Unable to read frontend route manifest [%s].', $path));
        }

        try {
            $payload = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException(sprintf('Frontend route manifest [%s] contains invalid JSON.', $path), 0, $exception);
        }

        if (! is_array($payload)) {
            throw new RuntimeException(sprintf('Frontend route manifest [%s] must decode to an array payload.', $path));
        }

        return FrontendRouteManifest::fromArray($payload);
    }

    private function compileCollection(CompiledRouteCollection $collection): FrontendRouteManifest
    {
        $routes = [];

        foreach ($collection as $route) {
            $entry = $this->serializeRoute($route);

            if ($entry !== null) {
                $routes[] = $entry;
            }
        }

        return new FrontendRouteManifest(
            self::MANIFEST_VERSION,
            $this->checksum($routes),
            $routes,
            self::PROTOCOL_VERSION,
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    private function serializeRoute(CompiledRoute $route): ?array
    {
        $metadata = $route->routeMetadata();
        $name = trim((string) $route->routeName());

        if ($name === '' || $this->isInternalRoute($route, $metadata)) {
            return null;
        }

        $methods = $this->normalizeMethods($route->methods());
        $rawRuntime = $metadata->get('runtime');
        $runtime = $this->publicRuntimeMetadata($rawRuntime);
        $policy = $this->publicPolicyMetadata($rawRuntime);

        $route = [
            'name' => $name,
            'screen' => $this->publicScreenMetadata($route, $metadata),
            'path' => $route->uri(),
            'methods' => $methods,
            'capabilities' => $this->publicCapabilities($methods, $metadata, $runtime),
            'runtime' => $runtime,
        ];

        if ($policy !== []) {
            $route['policy'] = $policy;
        }

        return $route;
    }

    /**
     * @return array{kind: string, mode: string}
     */
    private function publicScreenMetadata(CompiledRoute $route, RouteMetadata $metadata): array
    {
        $screen = $metadata->get('screen');
        $kind = null;
        $mode = null;

        if (is_array($screen)) {
            $kind = $this->normalizeScreenKind($screen['kind'] ?? null);
            $mode = $this->normalizeScreenMode($screen['mode'] ?? null);
        }

        if ($kind === null) {
            $action = $route->action();

            if (is_string($action) && class_exists($action) && is_subclass_of($action, Component::class)) {
                $kind = 'component';
            }
        }

        return [
            'kind' => $kind ?? 'controller',
            'mode' => $mode ?? 'navigable',
        ];
    }

    private function isInternalRoute(CompiledRoute $route, RouteMetadata $metadata): bool
    {
        $transport = $metadata->get('transport');

        if (is_string($transport) && strtolower(trim($transport)) === 'internal') {
            return true;
        }

        return str_starts_with($route->uri(), '/_volt/');
    }

    /**
     * @param array<int, string> $methods
     * @return array<int, string>
     */
    private function normalizeMethods(array $methods): array
    {
        $normalized = [];

        foreach ($methods as $method) {
            if (! is_string($method) || trim($method) === '') {
                continue;
            }

            $normalized[] = strtoupper(trim($method));
        }

        return array_values(array_unique($normalized));
    }

    /**
     * @return array<string, mixed>
     */
    private function publicRuntimeMetadata(mixed $runtime): array
    {
        if (! is_array($runtime)) {
            return [];
        }

        $public = [];
        $layout = $runtime['layout'] ?? null;

        if (is_string($layout) && trim($layout) !== '') {
            $public['layout'] = trim($layout);
        }

        $transition = $runtime['transition'] ?? null;

        if (is_string($transition) && trim($transition) !== '') {
            $public['transition'] = trim($transition);
        } elseif (is_array($transition) && is_string($transition['name'] ?? null) && trim((string) $transition['name']) !== '') {
            $public['transition'] = trim((string) $transition['name']);
        }

        $hydrate = $runtime['hydrate'] ?? null;

        if (is_bool($hydrate)) {
            $public['hydrate'] = $hydrate;
        } elseif (is_array($hydrate) && is_bool($hydrate['enabled'] ?? null)) {
            $public['hydrate'] = (bool) $hydrate['enabled'];
        }

        return $public;
    }

    /**
     * @return array<string, string>
     */
    private function publicPolicyMetadata(mixed $runtime): array
    {
        if (! is_array($runtime)) {
            return [];
        }

        $public = [];
        $document = $runtime['document'] ?? ($runtime['contract'] ?? $runtime['mode'] ?? null);

        if (is_string($document) && trim($document) !== '') {
            $normalizedDocument = $this->normalizeDocumentContract($document);

            if ($normalizedDocument !== '') {
                $public['document'] = $normalizedDocument;
            }
        }

        $navigation = $runtime['navigation'] ?? ($runtime['navigationMode'] ?? null);

        if (is_string($navigation) && trim($navigation) !== '') {
            $public['navigation'] = strtolower(trim($navigation));
        }

        return $public;
    }

    /**
     * @param array<int, string> $methods
     * @param array<string, mixed> $runtime
     * @return array<int, string>
     */
    private function publicCapabilities(array $methods, RouteMetadata $metadata, array $runtime): array
    {
        $capabilities = [];
        $screen = $this->publicScreenMetadataFromMetadata($metadata);
        $mode = $screen['mode'];
        $isNavigable = $mode === 'navigable';

        if ($isNavigable && in_array('GET', $methods, true)) {
            $capabilities[] = 'navigate';
        } elseif (! $isNavigable && in_array('GET', $methods, true)) {
            $capabilities[] = 'embed';
        }

        if (($runtime['hydrate'] ?? null) === true) {
            $capabilities[] = 'hydrate';
        }

        $prefetch = $metadata->get('prefetch');
        $rawRuntime = $metadata->get('runtime');
        $runtimePrefetch = is_array($rawRuntime) ? ($rawRuntime['prefetch'] ?? null) : null;

        if ($isNavigable && $prefetch === true) {
            $capabilities[] = 'prefetch';
        } elseif ($isNavigable && $runtimePrefetch === true) {
            $capabilities[] = 'prefetch';
        }

        return array_values(array_unique($capabilities));
    }

    /**
     * @return array{kind: string, mode: string}
     */
    private function publicScreenMetadataFromMetadata(RouteMetadata $metadata): array
    {
        $screen = $metadata->get('screen');

        if (! is_array($screen)) {
            return [
                'kind' => 'controller',
                'mode' => 'navigable',
            ];
        }

        return [
            'kind' => $this->normalizeScreenKind($screen['kind'] ?? null) ?? 'controller',
            'mode' => $this->normalizeScreenMode($screen['mode'] ?? null) ?? 'navigable',
        ];
    }

    private function normalizeScreenKind(mixed $kind): ?string
    {
        if (! is_string($kind) || trim($kind) === '') {
            return null;
        }

        $normalized = strtolower(trim($kind));

        return in_array($normalized, ['component', 'controller'], true) ? $normalized : null;
    }

    private function normalizeScreenMode(mixed $mode): ?string
    {
        if (! is_string($mode) || trim($mode) === '') {
            return null;
        }

        $normalized = strtolower(trim($mode));

        return in_array($normalized, ['navigable', 'embeddable'], true) ? $normalized : null;
    }

    /**
     * @param array<int, array<string, mixed>> $routes
     */
    private function checksum(array $routes): string
    {
        try {
            $payload = json_encode($routes, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException('Unable to generate the frontend route manifest checksum.', 0, $exception);
        }

        return hash('sha256', $payload);
    }

    private function normalizeDocumentContract(string $document): string
    {
        return match (strtolower(trim($document))) {
            'reload-only', 'static', 'non-spa', 'document' => 'reload',
            'interactive', 'reactive' => 'spa',
            default => strtolower(trim($document)),
        };
    }
}
