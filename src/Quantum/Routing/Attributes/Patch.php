<?php

declare(strict_types=1);

namespace Quantum\Routing\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
final class Patch extends HttpRouteAttribute
{
    public function methods(): array
    {
        return ['PATCH'];
    }
}
