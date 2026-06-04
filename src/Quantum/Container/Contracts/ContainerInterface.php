<?php

declare(strict_types=1);

namespace Quantum\Container\Contracts;

interface ContainerInterface
{
    public function bind(string $abstract, mixed $concrete = null, bool $shared = false): void;

    public function singleton(string $abstract, mixed $concrete = null): void;

    public function instance(string $abstract, mixed $instance): void;

    public function alias(string $abstract, string $alias): void;

    public function has(string $abstract): bool;

    public function make(string $abstract, array $parameters = []): mixed;
}