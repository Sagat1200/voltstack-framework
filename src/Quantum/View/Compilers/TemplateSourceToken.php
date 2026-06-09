<?php

declare(strict_types=1);

namespace Quantum\View\Compilers;

final class TemplateSourceToken
{
    public const INLINE_HTML = 'inline_html';
    public const PHP = 'php';

    private function __construct(
        private readonly string $type,
        private readonly string $value,
        private readonly int $line,
        private readonly int $column,
    ) {
    }

    public static function inlineHtml(string $value, int $line = 1, int $column = 1): self
    {
        return new self(self::INLINE_HTML, $value, $line, $column);
    }

    public static function php(string $value, int $line = 1, int $column = 1): self
    {
        return new self(self::PHP, $value, $line, $column);
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
