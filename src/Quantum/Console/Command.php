<?php

declare(strict_types=1);

namespace Quantum\Console;

use RuntimeException;
use VoltStack\Framework\Application;

abstract class Command
{
    public function __construct(protected readonly string $basePath) {}

    abstract public function name(): string;

    abstract public function description(): string;

    abstract public function handle(Input $input, Output $output): int;

    protected function bootstrapApplication(): Application
    {
        $bootstrapPath = $this->basePath . DIRECTORY_SEPARATOR . 'bootstrap' . DIRECTORY_SEPARATOR . 'app.php';

        if (! is_file($bootstrapPath)) {
            throw new RuntimeException(sprintf('The application bootstrap file could not be found at [%s].', $bootstrapPath));
        }

        $app = require $bootstrapPath;

        if (! $app instanceof Application) {
            throw new RuntimeException('The application bootstrap file must return a VoltStack application instance.');
        }

        return $app;
    }
}
