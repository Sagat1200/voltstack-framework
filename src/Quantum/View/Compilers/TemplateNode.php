<?php

declare(strict_types=1);

namespace Quantum\View\Compilers;

final class TemplateNode
{
    /**
     * @param array<int, self> $children
     * @param array<int, self> $alternateChildren
     * @param array<int, array{expression: ?string, children: array<int, self>}> $branches
     */
    private function __construct(
        private readonly string $type,
        private readonly string $value = '',
        private readonly ?string $name = null,
        private readonly ?string $expression = null,
        private readonly array $children = [],
        private readonly array $alternateChildren = [],
        private readonly array $branches = [],
    ) {
    }

    public static function text(string $value): self
    {
        return new self(TemplateToken::TEXT, $value);
    }

    public static function comment(string $value): self
    {
        return new self(TemplateToken::COMMENT, $value);
    }

    public static function echo(string $expression): self
    {
        return new self(TemplateToken::ECHO, '', null, $expression);
    }

    public static function rawEcho(string $expression): self
    {
        return new self(TemplateToken::RAW_ECHO, '', null, $expression);
    }

    public static function directive(string $name, ?string $expression): self
    {
        return new self(TemplateToken::DIRECTIVE, '', $name, $expression);
    }

    /**
     * @param array<int, self> $children
     * @param array<int, self> $alternateChildren
     * @param array<int, array{expression: ?string, children: array<int, self>}> $branches
     */
    public static function block(
        string $name,
        ?string $expression,
        array $children = [],
        array $alternateChildren = [],
        array $branches = [],
    ): self
    {
        return new self(TemplateToken::BLOCK, '', $name, $expression, $children, $alternateChildren, $branches);
    }

    public function type(): string
    {
        return $this->type;
    }

    public function value(): string
    {
        return $this->value;
    }

    public function name(): ?string
    {
        return $this->name;
    }

    public function expression(): ?string
    {
        return $this->expression;
    }

    /**
     * @return array<int, self>
     */
    public function children(): array
    {
        return $this->children;
    }

    /**
     * @return array<int, self>
     */
    public function alternateChildren(): array
    {
        return $this->alternateChildren;
    }

    /**
     * @return array<int, array{expression: ?string, children: array<int, self>}>
     */
    public function branches(): array
    {
        return $this->branches;
    }
}
