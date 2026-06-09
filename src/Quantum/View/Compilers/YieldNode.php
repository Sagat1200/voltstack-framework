<?php

declare(strict_types=1);

namespace Quantum\View\Compilers;

final class YieldNode extends TemplateNode
{
    public function __construct(?string $expression, int $line = 1, int $column = 1)
    {
        parent::__construct(TemplateToken::DIRECTIVE, '', 'yield', $expression, [], [], [], $line, $column);
    }
}
