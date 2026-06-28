<?php

declare(strict_types=1);

namespace Quantum\Routing\Exceptions;

use RuntimeException;

final class DuplicateRouteNameException extends RuntimeException
{
    public function __construct(string $name)
    {
        parent::__construct(sprintf(
            'A route is already registered with the name [%s].',
            $name,
        ));
    }
}
