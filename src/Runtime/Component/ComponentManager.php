<?php

declare(strict_types=1);

namespace VoltStack\Runtime\Component;

use Quantum\Http\Request;
use Quantum\View\View;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionParameter;
use Throwable;
use RuntimeException;
use VoltStack\Framework\Application;
use VoltStack\Runtime\Component\Exceptions\ComponentMountException;
use VoltStack\Runtime\Component\Exceptions\ComponentRenderException;
use VoltStack\Runtime\Hydration\Dehydrator;
use VoltStack\Runtime\Hydration\Hydrator;
use VoltStack\Runtime\Hydration\Snapshot;

final class ComponentManager
{
    public function __construct(
        private readonly Application $app,
        private readonly Hydrator $hydrator,
        private readonly Dehydrator $dehydrator,
    ) {}

    public function make(string|Component $component): Component
    {
        if ($component instanceof Component) {
            return $component;
        }

        $component = $this->resolveComponentClass($component);

        /** @var Component $instance */
        $instance = $this->app->make($component);

        return $instance;
    }

    /**
     * @param array<string, mixed> $parameters
     */
    public function mount(string|Component $component, array $parameters = [], ?Request $request = null): Component
    {
        $component = $this->make($component);
        $component->setRequest($request);
        $component->setViewData($this->prepareViewData($parameters));

        if (! method_exists($component, 'mount')) {
            $this->applyUpdates($component, $parameters);

            return $component;
        }

        $method = new ReflectionMethod($component, 'mount');
        $arguments = [];
        $consumedParameters = [];

        foreach ($method->getParameters() as $parameter) {
            $consumedParameters[] = $parameter->getName();
            $arguments[] = $this->resolveParameter($request, $parameter, $parameters);
        }

        try {
            $method->invokeArgs($component, $arguments);
        } catch (Throwable $exception) {
            throw ComponentMountException::forComponent($component::class, $exception);
        }

        $this->applyUpdates($component, array_diff_key($parameters, array_flip($consumedParameters)));

        return $component;
    }

    /**
     * @param array<string, mixed>|Snapshot $snapshot
     */
    public function hydrate(string|Component $component, array|Snapshot $snapshot, ?Request $request = null): Component
    {
        return $this->hydrator->hydrate($this->make($component), $snapshot, $request);
    }

    public function dehydrate(Component $component, array $meta = []): Snapshot
    {
        return $this->dehydrator->dehydrate($component, $meta);
    }

    public function render(Component $component): string
    {
        try {
            $result = $component->render();

            if ($result instanceof View) {
                foreach ($component->viewData() as $key => $value) {
                    if (array_key_exists($key, $result->data())) {
                        continue;
                    }

                    $result = $result->with($key, $value);
                }

                foreach ($this->publicProperties($component) as $key => $value) {
                    if (array_key_exists($key, $result->data())) {
                        continue;
                    }

                    $result = $result->with($key, $value);
                }

                return $result->render();
            }

            if (is_string($result)) {
                return $result;
            }

            throw new RuntimeException('Components must render a string or a view instance.');
        } catch (Throwable $exception) {
            if ($exception instanceof ComponentRenderException) {
                throw $exception;
            }

            throw ComponentRenderException::forComponent($component::class, $exception);
        }
    }

    public function renderRoot(Component $component, ?Snapshot $snapshot = null, string $renderMode = 'interactive'): string
    {
        $snapshot ??= $this->dehydrate($component, $this->routeScopeMeta($component));
        $encodedSnapshot = htmlspecialchars(
            json_encode($snapshot->toArray(), JSON_THROW_ON_ERROR),
            ENT_QUOTES | ENT_SUBSTITUTE,
            'UTF-8',
        );
        $csrf = htmlspecialchars($this->app->make(\Quantum\Security\CsrfTokenManager::class)->token(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        return sprintf(
            '<div data-volt-root="true" data-volt-render-mode="%s" data-volt-component="%s" data-volt-endpoint="%s" data-volt-csrf="%s" data-volt-snapshot="%s">%s</div>',
            htmlspecialchars($renderMode, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            htmlspecialchars($component::class, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            htmlspecialchars((string) $this->app->config('runtime.endpoint', '/_volt/action'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            $csrf,
            $encodedSnapshot,
            $this->render($component),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function routeScopeMeta(Component $component): array
    {
        $request = $component->request();

        if (! $request instanceof Request) {
            return [];
        }

        $route = [];
        $name = $request->routeMeta('name');

        if (is_string($name) && trim($name) !== '') {
            $route['name'] = trim($name);
        }

        $parameters = $request->routeParameters();

        if ($parameters !== []) {
            $route['params'] = $parameters;
        }

        $screen = $request->routeMeta('screen');

        if (is_array($screen) && $screen !== []) {
            $route['screen'] = $screen;
        }

        return $route === [] ? [] : ['route' => $route];
    }

    /**
     * @param array<string, mixed> $parameters
     */
    public function callAction(
        Component $component,
        string $action,
        array $parameters = [],
        ?Request $request = null,
    ): mixed {
        if ($request !== null) {
            $component->setRequest($request);
        }

        if ($action === '' || str_starts_with($action, '__')) {
            throw new RuntimeException('Invalid component action.');
        }

        if (! method_exists($component, $action)) {
            throw new RuntimeException(sprintf('Component action [%s] does not exist.', $action));
        }

        $method = new ReflectionMethod($component, $action);

        if (! $method->isPublic()) {
            throw new RuntimeException(sprintf('Component action [%s] is not public.', $action));
        }

        if (in_array($action, ['render', 'setRequest', 'request'], true)) {
            throw new RuntimeException(sprintf('Component action [%s] is reserved.', $action));
        }

        $arguments = [];

        foreach ($method->getParameters() as $parameter) {
            $arguments[] = $this->resolveParameter($request, $parameter, $parameters);
        }

        return $method->invokeArgs($component, $arguments);
    }

    /**
     * @param array<string, mixed> $updates
     */
    public function applyUpdates(Component $component, array $updates): void
    {
        foreach ($updates as $property => $value) {
            if (! property_exists($component, $property)) {
                continue;
            }

            $reflection = new \ReflectionProperty($component, $property);

            if (! $reflection->isPublic() || $reflection->isStatic()) {
                continue;
            }

            $reflection->setValue($component, $value);
        }
    }

    /**
     * @param array<string, mixed> $parameters
     */
    private function resolveParameter(?Request $request, ReflectionParameter $parameter, array $parameters): mixed
    {
        if (array_key_exists($parameter->getName(), $parameters)) {
            return $parameters[$parameter->getName()];
        }

        $type = $parameter->getType();

        if ($type instanceof ReflectionNamedType && ! $type->isBuiltin()) {
            $typeName = $type->getName();

            if ($typeName === Request::class) {
                return $request;
            }

            return $this->app->make($typeName);
        }

        if ($parameter->isDefaultValueAvailable()) {
            return $parameter->getDefaultValue();
        }

        throw new RuntimeException(sprintf(
            'Unable to resolve mount parameter [%s] for component [%s].',
            $parameter->getName(),
            $parameter->getDeclaringClass()?->getName() ?? Component::class,
        ));
    }

    /**
     * @return array<string, mixed>
     */
    private function publicProperties(Component $component): array
    {
        return get_object_vars($component);
    }

    private function resolveComponentClass(string $component): string
    {
        $component = trim($component);

        if ($component === '') {
            throw new RuntimeException('Component names cannot be empty.');
        }

        if (class_exists($component)) {
            return $component;
        }

        if (str_contains($component, '\\')) {
            $this->requireComponentClassFile($component);

            return $component;
        }

        $resolved = $this->componentNamespace() . '\\' . $this->normalizeComponentName($component);
        $this->requireComponentClassFile($resolved);

        return $resolved;
    }

    private function componentNamespace(): string
    {
        $configured = $this->app->config('ui-reactive.class_view_components', []);
        $directory = is_array($configured) && isset($configured[0]) && is_string($configured[0]) && trim($configured[0]) !== ''
            ? $this->normalizeDirectory($configured[0])
            : $this->app->basePath('app/View/Components');
        $baseAppPath = $this->normalizeDirectory($this->app->basePath('app'));

        if (str_starts_with($directory, $baseAppPath)) {
            $relative = trim(substr($directory, strlen($baseAppPath)), '\\/');
            $namespace = 'App';

            if ($relative !== '') {
                $namespace .= '\\' . str_replace(['/', '\\'], '\\', $relative);
            }

            return $namespace;
        }

        return 'App\\View\\Components';
    }

    private function normalizeDirectory(string $path): string
    {
        $normalized = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);

        if ($this->isAbsolutePath($normalized)) {
            return rtrim($normalized, '\\/');
        }

        return rtrim($this->app->basePath($normalized), '\\/');
    }

    private function isAbsolutePath(string $path): bool
    {
        return preg_match('/^[A-Za-z]:\\\\/', $path) === 1
            || str_starts_with($path, DIRECTORY_SEPARATOR);
    }

    private function normalizeComponentName(string $component): string
    {
        $component = str_replace(['/', '.'], '\\', $component);
        $segments = array_values(array_filter(explode('\\', $component), static fn(string $segment): bool => trim($segment) !== ''));
        $segments = array_map(
            static function (string $segment): string {
                $words = preg_split('/[-_]/', $segment) ?: [$segment];

                return implode('', array_map(static fn(string $word): string => ucfirst(strtolower($word)), $words));
            },
            $segments,
        );

        return implode('\\', $segments);
    }

    private function requireComponentClassFile(string $class): void
    {
        if (class_exists($class, false) || interface_exists($class, false) || trait_exists($class, false)) {
            return;
        }

        $path = $this->componentClassFile($class);

        if ($path === null || ! is_file($path)) {
            return;
        }

        require_once $path;
    }

    private function componentClassFile(string $class): ?string
    {
        $namespace = $this->componentNamespace();

        if (! str_starts_with($class, $namespace . '\\') && $class !== $namespace) {
            return null;
        }

        $configured = $this->app->config('ui-reactive.class_view_components', []);
        $directory = is_array($configured) && isset($configured[0]) && is_string($configured[0]) && trim($configured[0]) !== ''
            ? $this->normalizeDirectory($configured[0])
            : $this->app->basePath('app/View/Components');
        $relative = $class === $namespace
            ? ''
            : substr($class, strlen($namespace) + 1);
        $path = $directory;

        if (is_string($relative) && $relative !== '') {
            $path .= DIRECTORY_SEPARATOR . str_replace('\\', DIRECTORY_SEPARATOR, $relative);
        }

        return $path . '.php';
    }

    /**
     * @param array<string, mixed> $parameters
     * @return array<string, mixed>
     */
    private function prepareViewData(array $parameters): array
    {
        $viewData = $parameters;
        $attributes = $viewData['attributes'] ?? [];

        if ($attributes instanceof ComponentAttributeBag) {
            $viewData['attributes'] = $attributes;

            return $viewData;
        }

        $viewData['attributes'] = is_array($attributes)
            ? new ComponentAttributeBag($attributes)
            : new ComponentAttributeBag();

        return $viewData;
    }
}
