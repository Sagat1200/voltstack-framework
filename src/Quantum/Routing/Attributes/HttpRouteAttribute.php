<?php

declare(strict_types=1);

namespace Quantum\Routing\Attributes;

use Attribute;
use InvalidArgumentException;

#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
abstract class HttpRouteAttribute
{
    public function __construct(private readonly string $uri)
    {
        if (trim($uri) === '') {
            throw new InvalidArgumentException('Route attribute URI cannot be empty.');
        }
    }

    public function uri(): string
    {
        return $this->uri;
    }

    /**
     * @return array<int, string>
     */
    abstract public function methods(): array;
}
