<?php

declare(strict_types=1);

namespace Quantum\View\Compilers;

use Quantum\View\Exceptions\TemplateParseException;

class SimpleBlockNode extends TemplateNode
{
    /**
     * @param array<int, TemplateNode> $children
     */
    public function __construct(string $name, ?string $expression, array $children = [], int $line = 1, int $column = 1)
    {
        parent::__construct(TemplateToken::BLOCK, '', $name, $expression, $children, [], [], $line, $column);
    }

    public function closingDirectiveName(): string
    {
        return match ($this->name()) {
            'component' => 'endcomponent',
            'slot' => 'endslot',
            'scope' => 'endscope',
            'unless' => 'endunless',
            'isset' => 'endisset',
            'empty' => 'endempty',
            'foreach' => 'endforeach',
            'for' => 'endfor',
            'while' => 'endwhile',
            'section' => 'endsection',
            default => throw new TemplateParseException(
                sprintf('Unknown block directive [%s]', (string) $this->name()),
                $this->line(),
                $this->column(),
            ),
        };
    }
}
