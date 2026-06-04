<?php

declare(strict_types=1);

namespace Quantum\Console;

final class Input
{
    /**
     * @param array<int, string> $arguments
     * @param array<string, string|bool> $options
     * @param array<int, string> $rawTokens
     */
    private function __construct(
        private readonly string $script,
        private readonly ?string $command,
        private readonly array $arguments,
        private readonly array $options,
        private readonly array $rawTokens,
    ) {
    }

    /**
     * @param array<int, string> $argv
     */
    public static function fromArgv(array $argv): self
    {
        $script = $argv[0] ?? 'volt';
        $tokens = array_values(array_slice($argv, 1));
        $command = null;
        $commandTokens = [];

        foreach ($tokens as $index => $token) {
            if (! str_starts_with($token, '--')) {
                $command = $token;
                $commandTokens = array_values(array_slice($tokens, $index + 1));
                break;
            }
        }

        if ($command === null) {
            $commandTokens = $tokens;
        }

        [$arguments, $options] = self::parseTokens($commandTokens);

        return new self($script, $command, $arguments, $options, $tokens);
    }

    public function script(): string
    {
        return $this->script;
    }

    public function command(): ?string
    {
        return $this->command;
    }

    /**
     * @return array<int, string>
     */
    public function arguments(): array
    {
        return $this->arguments;
    }

    /**
     * @return array<int, string>
     */
    public function rawTokens(): array
    {
        return $this->rawTokens;
    }

    public function option(string $name, string|bool|null $default = null): string|bool|null
    {
        return $this->options[$name] ?? $default;
    }

    public function hasOption(string $name): bool
    {
        return array_key_exists($name, $this->options);
    }

    /**
     * @return array{0: array<int, string>, 1: array<string, string|bool>}
     */
    private static function parseTokens(array $tokens): array
    {
        $arguments = [];
        $options = [];

        foreach ($tokens as $token) {
            if (! str_starts_with($token, '--')) {
                $arguments[] = $token;
                continue;
            }

            $option = substr($token, 2);

            if ($option === '') {
                continue;
            }

            if (str_contains($option, '=')) {
                [$name, $value] = explode('=', $option, 2);
                $options[$name] = $value;
                continue;
            }

            $options[$option] = true;
        }

        return [$arguments, $options];
    }
}
