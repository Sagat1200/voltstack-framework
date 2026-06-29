<?php

declare(strict_types=1);

namespace Quantum\Routing;

use Quantum\HttpKernel\CompiledMiddlewarePipeline;
use Quantum\HttpKernel\MiddlewareAliasRegistry;
use Quantum\HttpKernel\MiddlewareStack;
use Quantum\Http\Response;
use Quantum\Http\Request;
use Quantum\Routing\Dispatching\DispatcherResolver;
use Quantum\Routing\Exceptions\MethodNotAllowedException;
use VoltStack\Framework\Application;

final class Router
{
    private RouteCollection $routes;
    private RouteMatcher $matcher;
    private ?CompiledRouteCollection $artifactCollection = null;
    private ?MetadataArtifact $artifactMetadata = null;
    private ?RouteMatchTree $artifactTree = null;
    private ?VersionArtifact $artifactVersion = null;
    private bool $artifactCollectionLoaded = false;
    private bool $artifactMetadataLoaded = false;
    private bool $artifactTreeLoaded = false;
    private bool $artifactVersionLoaded = false;
    private bool $developmentArtifactsInvalidated = false;
    private bool $preferArtifactCollection = false;
    /**
     * @var array<string, CompiledMiddlewarePipeline>
     */
    private array $artifactPipelines = [];
    private bool $artifactPipelinesLoaded = false;
    /**
     * @var array<int, array{prefix: string, domain: ?string, middleware: array<int, mixed>, metadata: array<string, mixed>}>
     */
    private array $groupStack = [];

    public function __construct(private readonly Application $app)
    {
        $this->routes = new RouteCollection();
        $this->matcher = new RouteMatcher();
    }

    public function get(string $uri, mixed $action): Route
    {
        return $this->addRoute(['GET'], $uri, $action);
    }

    public function post(string $uri, mixed $action): Route
    {
        return $this->addRoute(['POST'], $uri, $action);
    }

    public function put(string $uri, mixed $action): Route
    {
        return $this->addRoute(['PUT'], $uri, $action);
    }

    public function patch(string $uri, mixed $action): Route
    {
        return $this->addRoute(['PATCH'], $uri, $action);
    }

    public function delete(string $uri, mixed $action): Route
    {
        return $this->addRoute(['DELETE'], $uri, $action);
    }

    public function head(string $uri, mixed $action): Route
    {
        return $this->addRoute(['HEAD'], $uri, $action);
    }

    public function options(string $uri, mixed $action): Route
    {
        return $this->addRoute(['OPTIONS'], $uri, $action);
    }

    /**
     * @param array<int, string> $methods
     */
    public function match(array $methods, string $uri, mixed $action): Route
    {
        return $this->addRoute($methods, $uri, $action);
    }

    public function any(string $uri, mixed $action): Route
    {
        return $this->addRoute(['GET', 'HEAD', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'], $uri, $action);
    }

    public function aliasMiddleware(string $alias, mixed $middleware): void
    {
        $this->middlewareAliases()->alias($alias, $middleware);
    }

    public function group(array|callable $attributes, ?callable $callback = null): void
    {
        if (is_callable($attributes) && $callback === null) {
            $callback = $attributes;
            $attributes = [];
        }

        if (! is_array($attributes) || ! is_callable($callback)) {
            throw new \InvalidArgumentException('Router::group expects attributes and a callback.');
        }

        $this->groupStack[] = $this->mergeGroupAttributes($this->currentGroupAttributes(), $attributes);

        try {
            $callback($this);
        } finally {
            array_pop($this->groupStack);
        }
    }

    /**
     * @param array<int, string> $methods
     */
    public function addRoute(array $methods, string $uri, mixed $action): Route
    {
        $groupAttributes = $this->currentGroupAttributes();
        $route = new Route(RouteDefinition::make(
            $methods,
            $this->mergeGroupPrefix($groupAttributes['prefix'], $uri),
            $action,
        ));
        $route->attachMiddlewareResolver(fn(mixed $middleware): mixed => $this->middlewareAliases()->resolve($middleware));

        if ($groupAttributes['domain'] !== null) {
            $route->domain($groupAttributes['domain']);
        }

        if ($groupAttributes['middleware'] !== []) {
            $route->middleware($groupAttributes['middleware']);
        }

        if ($groupAttributes['metadata'] !== []) {
            $route->meta($groupAttributes['metadata']);
        }

        $registered = $this->routes->add($route);
        $this->artifactCollectionLoaded = false;
        $this->artifactCollection = null;
        $this->artifactMetadataLoaded = false;
        $this->artifactMetadata = null;
        $this->artifactTreeLoaded = false;
        $this->artifactTree = null;
        $this->artifactVersionLoaded = false;
        $this->artifactVersion = null;
        $this->developmentArtifactsInvalidated = false;
        $this->preferArtifactCollection = false;

        return $registered;
    }

    /**
     * @return array<int, Route>
     */
    public function routes(): array
    {
        return $this->routes->all();
    }

    public function collection(): RouteCollection
    {
        return $this->routes;
    }

    public function compiledCollection(): CompiledRouteCollection
    {
        if (! $this->preferArtifactCollection) {
            return $this->routes->compiled();
        }

        $this->loadCollectionArtifacts();

        return $this->artifactCollection ?? $this->routes->compiled();
    }

    public function reloadCollectionArtifacts(): void
    {
        $this->preferArtifactCollection = true;
        $this->artifactCollectionLoaded = false;
        $this->artifactCollection = null;
        $this->artifactMetadataLoaded = false;
        $this->artifactMetadata = null;
        $this->artifactTreeLoaded = false;
        $this->artifactTree = null;
        $this->artifactVersionLoaded = false;
        $this->artifactVersion = null;
        $this->developmentArtifactsInvalidated = false;
        $this->loadCollectionArtifacts();
        $this->loadMetadataArtifacts();
        $this->loadTreeArtifacts();
    }

    public function reloadPipelineArtifacts(): void
    {
        $this->artifactPipelinesLoaded = false;
        $this->artifactPipelines = [];
        $this->developmentArtifactsInvalidated = false;
        $this->loadPipelineArtifacts();
    }

    public function resolvedRoutePipeline(CompiledRoute $route): CompiledMiddlewarePipeline
    {
        $this->loadPipelineArtifacts();

        return $this->artifactPipelines[$route->routePipeline()->id()] ?? $route->routePipeline();
    }

    public function dispatch(Request $request): mixed
    {
        $collection = $this->compiledCollection();

        try {
            $match = $this->matcher->match($request, $collection, $this->resolvedMatchTree($collection));
        } catch (MethodNotAllowedException $exception) {
            if ($request->method() === 'OPTIONS') {
                return new Response('', 204, [
                    'Allow' => $exception->allowHeader(),
                ]);
            }

            throw $exception;
        }

        $request->setRouteParameters($match->parameters());
        $request->setRouteMetadata($match->metadata()->all());

        return $this->resolvedRoutePipeline($match->route())->handle(
            $this->app,
            $request,
            fn(Request $request): mixed => $this->app->make(DispatcherResolver::class)->dispatch($match, $request),
        );
    }

    /**
     * @return array{prefix: string, domain: ?string, middleware: array<int, mixed>, metadata: array<string, mixed>}
     */
    private function currentGroupAttributes(): array
    {
        return $this->groupStack === []
            ? ['prefix' => '', 'domain' => null, 'middleware' => [], 'metadata' => []]
            : $this->groupStack[array_key_last($this->groupStack)];
    }

    /**
     * @param array{prefix: string, domain: ?string, middleware: array<int, mixed>, metadata: array<string, mixed>} $parent
     * @param array<string, mixed> $attributes
     * @return array{prefix: string, domain: ?string, middleware: array<int, mixed>, metadata: array<string, mixed>}
     */
    private function mergeGroupAttributes(array $parent, array $attributes): array
    {
        $prefix = $parent['prefix'];

        if (isset($attributes['prefix']) && is_string($attributes['prefix'])) {
            $prefix = $this->mergeGroupPrefix($prefix, $attributes['prefix']);
        }

        $domain = $parent['domain'];

        if (array_key_exists('domain', $attributes)) {
            $domain = is_string($attributes['domain']) && $attributes['domain'] !== ''
                ? $attributes['domain']
                : null;
        }

        $middlewares = $parent['middleware'];

        if (array_key_exists('middleware', $attributes)) {
            $resolvedMiddlewares = $this->middlewareAliases()->resolveMany(
                is_array($attributes['middleware']) ? array_values($attributes['middleware']) : [$attributes['middleware']]
            );

            $middlewares = MiddlewareStack::deduplicate([
                ...$middlewares,
                ...$resolvedMiddlewares,
            ]);
        }

        $metadata = $parent['metadata'];

        if (array_key_exists('metadata', $attributes) && is_array($attributes['metadata'])) {
            $metadata = array_replace($metadata, $attributes['metadata']);
        }

        return [
            'prefix' => $prefix,
            'domain' => $domain,
            'middleware' => $middlewares,
            'metadata' => $metadata,
        ];
    }

    private function mergeGroupPrefix(string $prefix, string $uri): string
    {
        $normalizedPrefix = trim($prefix, '/');
        $normalizedUri = trim($uri, '/');

        if ($normalizedPrefix === '') {
            return $normalizedUri === '' ? '/' : $normalizedUri;
        }

        if ($normalizedUri === '') {
            return $normalizedPrefix;
        }

        return $normalizedPrefix . '/' . $normalizedUri;
    }

    private function middlewareAliases(): MiddlewareAliasRegistry
    {
        return $this->app->make(MiddlewareAliasRegistry::class);
    }

    private function loadCollectionArtifacts(): void
    {
        if ($this->artifactCollectionLoaded) {
            return;
        }

        if (! $this->artifactsEnabledForRuntime() || ! $this->artifactsManifestAllows(['collection', 'metadata', 'tree'])) {
            $this->artifactCollectionLoaded = true;

            return;
        }

        $artifact = $this->app->make(CollectionArtifactStore::class)->load();
        $this->artifactCollection = $artifact?->compileCollection();
        $this->applyLoadedMetadataArtifacts();
        $this->artifactCollectionLoaded = true;
    }

    private function loadMetadataArtifacts(): void
    {
        if ($this->artifactMetadataLoaded) {
            return;
        }

        if (! $this->artifactsEnabledForRuntime() || ! $this->artifactsManifestAllows(['metadata'])) {
            $this->artifactMetadataLoaded = true;

            return;
        }

        $this->artifactMetadata = $this->app->make(MetadataArtifactStore::class)->load();
        $this->artifactMetadataLoaded = true;
        $this->applyLoadedMetadataArtifacts();
    }

    private function loadTreeArtifacts(): void
    {
        if ($this->artifactTreeLoaded) {
            return;
        }

        if (! $this->artifactsEnabledForRuntime() || ! $this->artifactsManifestAllows(['tree'])) {
            $this->artifactTreeLoaded = true;

            return;
        }

        $artifact = $this->app->make(TreeArtifactStore::class)->load();
        $this->artifactTree = $artifact?->compileTree();
        $this->artifactTreeLoaded = true;
    }

    private function loadPipelineArtifacts(): void
    {
        if ($this->artifactPipelinesLoaded) {
            return;
        }

        if (! $this->artifactsEnabledForRuntime() || ! $this->artifactsManifestAllows(['pipeline'])) {
            $this->artifactPipelinesLoaded = true;

            return;
        }

        $artifact = $this->app->make(PipelineArtifactStore::class)->load();
        $this->artifactPipelines = $artifact?->compilePipelines() ?? [];
        $this->artifactPipelinesLoaded = true;
    }

    private function resolvedMatchTree(CompiledRouteCollection $collection): ?RouteMatchTree
    {
        if (! $this->preferArtifactCollection) {
            return null;
        }

        $this->loadTreeArtifacts();

        if ($this->artifactTree === null || $this->artifactTree->routeCount() !== $collection->count()) {
            return null;
        }

        return $this->artifactTree;
    }

    private function applyLoadedMetadataArtifacts(): void
    {
        if (! $this->preferArtifactCollection || $this->artifactCollection === null) {
            return;
        }

        if (! $this->artifactMetadataLoaded) {
            $this->loadMetadataArtifacts();
        }

        if ($this->artifactMetadata === null || $this->artifactMetadata->routeCount() !== $this->artifactCollection->count()) {
            return;
        }

        $this->artifactMetadata->applyTo($this->artifactCollection);
    }

    /**
     * @param array<int, string> $artifactNames
     */
    private function artifactsManifestAllows(array $artifactNames): bool
    {
        $manifest = $this->loadVersionArtifact();

        if ($manifest === null) {
            return true;
        }

        foreach ($artifactNames as $artifactName) {
            if (! $this->manifestEntryIsValid($manifest, $artifactName)) {
                return false;
            }
        }

        return true;
    }

    private function loadVersionArtifact(): ?VersionArtifact
    {
        if ($this->artifactVersionLoaded) {
            return $this->artifactVersion;
        }

        $this->artifactVersion = $this->app->make(VersionArtifactStore::class)->load();
        $this->artifactVersionLoaded = true;

        return $this->artifactVersion;
    }

    private function manifestEntryIsValid(VersionArtifact $manifest, string $artifactName): bool
    {
        return match ($artifactName) {
            'collection' => $manifest->validates(
                'collection',
                $this->app->make(CollectionArtifactStore::class)->path(),
                $this->app->make(CollectionArtifactStore::class)->artifactVersion(),
            ),
            'metadata' => $manifest->validates(
                'metadata',
                $this->app->make(MetadataArtifactStore::class)->path(),
                $this->app->make(MetadataArtifactStore::class)->artifactVersion(),
            ),
            'tree' => $manifest->validates(
                'tree',
                $this->app->make(TreeArtifactStore::class)->path(),
                $this->app->make(TreeArtifactStore::class)->artifactVersion(),
            ),
            'pipeline' => $manifest->validates(
                'pipeline',
                $this->app->make(PipelineArtifactStore::class)->path(),
                $this->app->make(PipelineArtifactStore::class)->artifactVersion(),
            ),
            default => false,
        };
    }

    private function artifactsEnabledForRuntime(): bool
    {
        if ((bool) $this->app->config('routing.artifacts.enabled', true) === false) {
            return false;
        }

        if (! $this->shouldInvalidateArtifactsInDevelopment()) {
            return true;
        }

        $this->invalidateDevelopmentArtifacts();

        return false;
    }

    private function shouldInvalidateArtifactsInDevelopment(): bool
    {
        if (! $this->app->isDevelopment()) {
            return false;
        }

        return (bool) $this->app->config('routing.artifacts.invalidate_in_development', true);
    }

    private function invalidateDevelopmentArtifacts(): void
    {
        if ($this->developmentArtifactsInvalidated) {
            return;
        }

        foreach ($this->routeArtifactPaths() as $path) {
            if (is_file($path)) {
                @unlink($path);
            }
        }

        $this->artifactCollection = null;
        $this->artifactMetadata = null;
        $this->artifactTree = null;
        $this->artifactVersion = null;
        $this->artifactPipelines = [];
        $this->developmentArtifactsInvalidated = true;
    }

    /**
     * @return array<int, string>
     */
    private function routeArtifactPaths(): array
    {
        return [
            $this->app->make(CollectionArtifactStore::class)->path(),
            $this->app->make(TreeArtifactStore::class)->path(),
            $this->app->make(MetadataArtifactStore::class)->path(),
            $this->app->make(PipelineArtifactStore::class)->path(),
            $this->app->make(VersionArtifactStore::class)->path(),
        ];
    }
}
