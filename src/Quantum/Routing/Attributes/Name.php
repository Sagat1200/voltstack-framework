<?php

declare(strict_types=1);

namespace Quantum\Routing\Attributes;

use Attribute;
use InvalidArgumentException;

#[Attribute(Attribute::TARGET_METHOD)]
final class Name
{
    public function __construct(private readonly string $value)
    {
        if (trim($value) === '') {
            throw new InvalidArgumentException('Route attribute name cannot be empty.');
        }
    }

    public function value(): string
    {
        return $this->value;
    }
}
