<?php

declare(strict_types=1);

namespace Quantum\Routing;

final class Route extends CompiledRoute
{
    public function __construct(RouteDefinition $definition)
    {
        parent::__construct($definition);
    }
}
