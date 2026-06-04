<?php

declare(strict_types=1);

namespace Quantum\Console;

final class Output
{
    public function write(string $message): void
    {
        fwrite(STDOUT, $message);
    }

    public function writeln(string $message = ''): void
    {
        $this->write($message . PHP_EOL);
    }

    public function error(string $message): void
    {
        fwrite(STDERR, $message . PHP_EOL);
    }
}