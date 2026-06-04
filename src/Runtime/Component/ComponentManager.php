<?php

declare(strict_types=1);

namespace VoltStack\Runtime\Component;

use Quantum\Http\Request;
use Quantum\View\View;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionParameter;
use RuntimeException;
use VoltStack\Framework\Application;
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

        if (! method_exists($component, 'mount')) {
            return $component;
        }

        $method = new ReflectionMethod($component, 'mount');
        $arguments = [];

        foreach ($method->getParameters() as $parameter) {
            $arguments[] = $this->resolveParameter($request, $parameter, $parameters);
        }

        $method->invokeArgs($component, $arguments);

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
        $result = $component->render();

        if ($result instanceof View) {
            return $result->render();
        }

        if (is_string($result)) {
            return $result;
        }

        throw new RuntimeException('Components must render a string or a view instance.');
    }

    public function renderRoot(Component $component, ?Snapshot $snapshot = null): string
    {
        $snapshot ??= $this->dehydrate($component);
        $encodedSnapshot = htmlspecialchars(
            json_encode($snapshot->toArray(), JSON_THROW_ON_ERROR),
            ENT_QUOTES | ENT_SUBSTITUTE,
            'UTF-8',
        );

        return sprintf(
            '<div data-volt-root="true" data-volt-component="%s" data-volt-endpoint="%s" data-volt-snapshot="%s">%s</div>',
            htmlspecialchars($component::class, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            htmlspecialchars((string) $this->app->config('runtime.endpoint', '/_volt/action'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            $encodedSnapshot,
            $this->render($component),
        );
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
}