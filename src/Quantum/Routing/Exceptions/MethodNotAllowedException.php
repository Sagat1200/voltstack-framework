<?php

declare(strict_types=1);

namespace Quantum\Routing\Exceptions;

use RuntimeException;

final class MethodNotAllowedException extends RuntimeException
{
    /**
     * @param array<int, string> $allowedMethods
     */
    public function __construct(
        private readonly string $method,
        private readonly array $allowedMethods,
        string $path,
    ) {
        parent::__construct(sprintf(
            'The [%s] method is not allowed for route [%s]. Allowed methods: %s.',
            $method,
            $path,
            implode(', ', $allowedMethods),
        ));
    }

    public function method(): string
    {
        return $this->method;
    }

    /**
     * @return array<int, string>
     */
    public function allowedMethods(): array
    {
        return $this->allowedMethods;
    }

    public function allowHeader(): string
    {
        return implode(', ', $this->allowedMethods);
    }
}
