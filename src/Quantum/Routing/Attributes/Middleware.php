<?php

declare(strict_types=1);

namespace Quantum\Routing\Attributes;

use Attribute;
use InvalidArgumentException;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
final class Middleware
{
    /**
     * @param string|array<int, string> $value
     */
    public function __construct(private readonly string|array $value)
    {
        $values = is_array($value) ? array_values($value) : [$value];

        if ($values === []) {
            throw new InvalidArgumentException('Route attribute middleware cannot be empty.');
        }

        foreach ($values as $middleware) {
            if (! is_string($middleware) || trim($middleware) === '') {
                throw new InvalidArgumentException('Route attribute middleware entries must be non-empty strings.');
            }
        }
    }

    /**
     * @return array<int, string>
     */
    public function values(): array
    {
        return is_array($this->value) ? array_values($this->value) : [$this->value];
    }
}
