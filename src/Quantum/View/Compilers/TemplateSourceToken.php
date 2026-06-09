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
    ) {
    }

    public static function inlineHtml(string $value): self
    {
        return new self(self::INLINE_HTML, $value);
    }

    public static function php(string $value): self
    {
        return new self(self::PHP, $value);
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
