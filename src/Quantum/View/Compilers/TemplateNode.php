<?php

declare(strict_types=1);

namespace Quantum\View\Compilers;

class TemplateNode
{
    /**
     * @param array<int, self> $children
     * @param array<int, self> $alternateChildren
     * @param array<int, array{expression: ?string, children: array<int, self>}> $branches
     */
    protected function __construct(
        private readonly string $type,
        private readonly string $value = '',
        private readonly ?string $name = null,
        private readonly ?string $expression = null,
        private readonly array $children = [],
        private readonly array $alternateChildren = [],
        private readonly array $branches = [],
        private readonly int $line = 1,
        private readonly int $column = 1,
    ) {
    }

    public static function text(string $value, int $line = 1, int $column = 1): self
    {
        return new self(TemplateToken::TEXT, $value, null, null, [], [], [], $line, $column);
    }

    public static function comment(string $value, int $line = 1, int $column = 1): self
    {
        return new self(TemplateToken::COMMENT, $value, null, null, [], [], [], $line, $column);
    }

    public static function echo(string $expression, int $line = 1, int $column = 1): self
    {
        return new self(TemplateToken::ECHO, '', null, $expression, [], [], [], $line, $column);
    }

    public static function rawEcho(string $expression, int $line = 1, int $column = 1): self
    {
        return new self(TemplateToken::RAW_ECHO, '', null, $expression, [], [], [], $line, $column);
    }

    public static function directive(string $name, ?string $expression, int $line = 1, int $column = 1): self
    {
        return match ($name) {
            'include' => new IncludeNode($expression, $line, $column),
            'extends' => new ExtendsNode($expression, $line, $column),
            'yield' => new YieldNode($expression, $line, $column),
            default => new self(TemplateToken::DIRECTIVE, '', $name, $expression, [], [], [], $line, $column),
        };
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
        int $line = 1,
        int $column = 1,
    ): self
    {
        return match ($name) {
            'if' => new IfNode($expression, $children, $alternateChildren, $branches, $line, $column),
            'forelse' => new ForelseNode($expression, $children, $alternateChildren, $line, $column),
            'section' => new SectionNode($expression, $children, $line, $column),
            default => new SimpleBlockNode($name, $expression, $children, $line, $column),
        };
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

    public function line(): int
    {
        return $this->line;
    }

    public function column(): int
    {
        return $this->column;
    }
}
