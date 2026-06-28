<?php

declare(strict_types=1);

namespace Quantum\Routing\Exceptions;

use RuntimeException;

final class DuplicateRouteException extends RuntimeException
{
    public function __construct(string $method, string $uri)
    {
        parent::__construct(sprintf(
            'A route is already registered for [%s] %s.',
            strtoupper($method),
            $uri,
        ));
    }
}
