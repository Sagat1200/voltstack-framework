<?php

declare(strict_types=1);

namespace Quantum\Routing\Dispatching;

use Quantum\Http\Request;
use Quantum\Routing\Dispatching\Contracts\DispatcherInterface;
use Quantum\Routing\RouteMatch;
use VoltStack\Runtime\Component\Component;
use VoltStack\Runtime\Component\ComponentManager;

final class ComponentDispatcher implements DispatcherInterface
{
    public function __construct(private readonly ComponentManager $components) {}

    public function dispatch(RouteMatch $match, Request $request): mixed
    {
        /** @var class-string $action */
        $action = $match->route()->action();

        $component = $this->components->mount($action, $match->parameters(), $request);
        $this->mergeComponentRuntimeMetadata($request, $component);

        return $component;
    }

    private function mergeComponentRuntimeMetadata(Request $request, Component $component): void
    {
        $componentRuntime = $component->runtimeMetadata();

        if (! is_array($componentRuntime) || $componentRuntime === []) {
            return;
        }

        $routeRuntime = $request->routeRuntimeMetadata();
        $merged = $this->mergeRuntimeMetadata($componentRuntime, $routeRuntime);
        $metadata = $request->routeMetadata();
        $metadata['runtime'] = $merged;
        $request->setRouteMetadata($metadata);
    }

    /**
     * @param array<string, mixed> $defaults
     * @param array<string, mixed> $overrides
     * @return array<string, mixed>
     */
    private function mergeRuntimeMetadata(array $defaults, array $overrides): array
    {
        $merged = $defaults;

        foreach ($overrides as $key => $value) {
            if (is_array($value) && is_array($merged[$key] ?? null)) {
                $merged[$key] = $this->mergeRuntimeMetadata($merged[$key], $value);
                continue;
            }

            $merged[$key] = $value;
        }

        return $merged;
    }
}
