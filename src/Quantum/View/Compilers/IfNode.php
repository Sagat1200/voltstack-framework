<?php

declare(strict_types=1);

namespace Quantum\View\Compilers;

final class IfNode extends TemplateNode
{
    /**
     * @param array<int, TemplateNode> $children
     * @param array<int, TemplateNode> $alternateChildren
     * @param array<int, array{expression: ?string, children: array<int, TemplateNode>}> $branches
     */
    public function __construct(
        ?string $expression,
        array $children = [],
        array $alternateChildren = [],
        array $branches = [],
        int $line = 1,
        int $column = 1,
    ) {
        parent::__construct(TemplateToken::BLOCK, '', 'if', $expression, $children, $alternateChildren, $branches, $line, $column);
    }
}
