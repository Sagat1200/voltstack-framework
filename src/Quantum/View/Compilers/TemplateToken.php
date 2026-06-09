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
    ) {}

    public static function text(string $value): self
    {
        return new self(self::TEXT, $value);
    }

    public static function comment(string $value): self
    {
        return new self(self::COMMENT, $value);
    }

    public static function echo(string $value): self
    {
        return new self(self::ECHO, $value);
    }

    public static function rawEcho(string $value): self
    {
        return new self(self::RAW_ECHO, $value);
    }

    public static function directive(string $value): self
    {
        return new self(self::DIRECTIVE, $value);
    }

    public function type(): string
    {
        return $this->type;
    }

    public function value(): string
    {
        return $this->value;
    }
}
