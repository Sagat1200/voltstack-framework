<?php

declare(strict_types=1);

namespace Quantum\Container;

final readonly class Binding
{
    public function __construct(
        public mixed $concrete,
        public bool $shared = false,
        public bool $scoped = false,
    ) {}
}