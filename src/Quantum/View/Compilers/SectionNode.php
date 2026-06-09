<?php

declare(strict_types=1);

namespace Quantum\View\Compilers;

final class SectionNode extends SimpleBlockNode
{
    /**
     * @param array<int, TemplateNode> $children
     */
    public function __construct(?string $expression, array $children = [], int $line = 1, int $column = 1)
    {
        parent::__construct('section', $expression, $children, $line, $column);
    }
}