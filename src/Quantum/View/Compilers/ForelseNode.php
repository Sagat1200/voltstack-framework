<?php

declare(strict_types=1);

namespace Quantum\View\Compilers;

final class ForelseNode extends TemplateNode
{
    /**
     * @param array<int, TemplateNode> $children
     * @param array<int, TemplateNode> $alternateChildren
     */
    public function __construct(?string $expression, array $children = [], array $alternateChildren = [], int $line = 1, int $column = 1)
    {
        parent::__construct(TemplateToken::BLOCK, '', 'forelse', $expression, $children, $alternateChildren, [], $line, $column);
    }
}
