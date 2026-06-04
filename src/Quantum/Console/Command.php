<?php

declare(strict_types=1);

namespace Quantum\Console;

abstract class Command
{
    public function __construct(protected readonly string $basePath)
    {
    }

    abstract public function name(): string;

    abstract public function description(): string;

    abstract public function handle(Input $input, Output $output): int;
}
