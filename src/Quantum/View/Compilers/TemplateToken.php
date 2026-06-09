<?php

declare(strict_types=1);

namespace Quantum\View\Compilers;

final class TemplateToken
{
    public const TEXT = 'text';
    public const COMMENT = 'comment';
    public const ECHO = 'echo';
    public const RAW_ECHO = 'raw_echo';
    public const DIRECTIVE = 'directive';
    public const BLOCK = 'block';

    private function __construct(
        private readonly string $type,
        private readonly string $value,
        private readonly int $line,
        private readonly int $column,
    ) {}

    public static function text(string $value, int $line = 1, int $column = 1): self
    {
        return new self(self::TEXT, $value, $line, $column);
    }

    public static function comment(string $value, int $line = 1, int $column = 1): self
    {
        return new self(self::COMMENT, $value, $line, $column);
    }

    public static function echo(string $value, int $line = 1, int $column = 1): self
    {
        return new self(self::ECHO, $value, $line, $column);
    }

    public static function rawEcho(string $value, int $line = 1, int $column = 1): self
    {
        return new self(self::RAW_ECHO, $value, $line, $column);
    }

    public static function directive(string $value, int $line = 1, int $column = 1): self
    {
        return new self(self::DIRECTIVE, $value, $line, $column);
    }

    public function type(): string
    {
        return $this->type;
    }

    public function value(): string
    {
        return $this->value;
    }

    public function line(): int
    {
        return $this->line;
    }

    public function column(): int
    {
        return $this->column;
    }
}
