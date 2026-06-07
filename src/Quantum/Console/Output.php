<?php

declare(strict_types=1);

namespace Quantum\Console;

final class Output
{
    private string $stdoutBuffer = '';
    private string $stderrBuffer = '';

    public function write(string $message): void
    {
        $this->stdoutBuffer .= $message;
        fwrite(STDOUT, $message);
    }

    public function writeln(string $message = ''): void
    {
        $this->write($message . PHP_EOL);
    }

    public function error(string $message): void
    {
        $this->stderrBuffer .= $message . PHP_EOL;
        fwrite(STDERR, $message . PHP_EOL);
    }

    public function stdout(): string
    {
        return $this->stdoutBuffer;
    }

    public function stderr(): string
    {
        return $this->stderrBuffer;
    }
}
