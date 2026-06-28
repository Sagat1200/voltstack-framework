<?php

declare(strict_types=1);

namespace Quantum\Routing;

final class Route extends CompiledRoute
{
    private ?RouteCollection $collection = null;

    public function __construct(RouteDefinition $definition)
    {
        parent::__construct($definition);
    }

    public function attachCollection(RouteCollection $collection): void
    {
        $this->collection = $collection;
    }

    public function name(?string $name = null): string|static|null
    {
        if ($name === null) {
            return $this->routeName();
        }

        $previousName = $this->routeName();

        if ($this->collection !== null) {
            $this->collection->validateRouteName($this, $name, $previousName);
        }

        $this->replaceDefinition($this->definition()->withName($name));

        if ($this->collection !== null) {
            $this->collection->syncRouteName($this, $previousName);
        }

        return $this;
    }
}
