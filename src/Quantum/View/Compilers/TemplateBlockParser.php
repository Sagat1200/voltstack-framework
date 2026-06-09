<?php

declare(strict_types=1);

namespace Quantum\View\Compilers;

use RuntimeException;

final class TemplateBlockParser
{
    /**
     * @var array<string, string>
     */
    private const SIMPLE_BLOCKS = [
        'unless' => 'endunless',
        'isset' => 'endisset',
        'empty' => 'endempty',
        'foreach' => 'endforeach',
        'for' => 'endfor',
        'while' => 'endwhile',
        'section' => 'endsection',
    ];

    /**
     * @param array<int, TemplateNode> $nodes
     * @return array<int, TemplateNode>
     */
    public function parse(array $nodes): array
    {
        $index = 0;

        return $this->parseSequence($nodes, $index);
    }

    /**
     * @param array<int, TemplateNode> $nodes
     * @param array<int, string> $terminators
     * @return array<int, TemplateNode>
     */
    private function parseSequence(array $nodes, int &$index, array $terminators = []): array
    {
        $parsed = [];
        $total = count($nodes);

        while ($index < $total) {
            $node = $nodes[$index];

            if ($node->type() === TemplateToken::DIRECTIVE) {
                $name = $node->name();

                if (is_string($name) && in_array($name, $terminators, true)) {
                    break;
                }

                if ($name === 'if') {
                    $parsed[] = $this->parseIfBlock($nodes, $index);
                    continue;
                }

                if (is_string($name) && array_key_exists($name, self::SIMPLE_BLOCKS) && ! $this->isFlatConditionalBlock($name)) {
                    $parsed[] = $this->parseSimpleBlock($nodes, $index, $name);
                    continue;
                }

                if ($name === 'forelse') {
                    $parsed[] = $this->parseForelseBlock($nodes, $index);
                    continue;
                }
            }

            $parsed[] = $node;
            $index++;
        }

        return $parsed;
    }

    /**
     * @param array<int, TemplateNode> $nodes
     */
    private function parseSimpleBlock(array $nodes, int &$index, string $opening): TemplateNode
    {
        $current = $nodes[$index];
        $index++;
        $children = $this->parseSequence($nodes, $index, [self::SIMPLE_BLOCKS[$opening]]);

        if (! isset($nodes[$index]) || $nodes[$index]->name() !== self::SIMPLE_BLOCKS[$opening]) {
            throw new RuntimeException(sprintf('Unclosed @%s directive.', $opening));
        }

        $index++;

        return TemplateNode::block($opening, $current->expression(), $children);
    }

    /**
     * @param array<int, TemplateNode> $nodes
     */
    private function parseForelseBlock(array $nodes, int &$index): TemplateNode
    {
        $current = $nodes[$index];
        $index++;
        $children = $this->parseSequence($nodes, $index, ['empty', 'endforelse']);
        $alternateChildren = [];

        if (! isset($nodes[$index])) {
            throw new RuntimeException('Unclosed @forelse directive.');
        }

        if ($nodes[$index]->name() === 'empty' && $nodes[$index]->expression() === null) {
            $index++;
            $alternateChildren = $this->parseSequence($nodes, $index, ['endforelse']);
        }

        if (! isset($nodes[$index]) || $nodes[$index]->name() !== 'endforelse') {
            throw new RuntimeException('Unclosed @forelse directive.');
        }

        $index++;

        if ($alternateChildren === []) {
            throw new RuntimeException('The @forelse directive requires an @empty block.');
        }

        return TemplateNode::block('forelse', $current->expression(), $children, $alternateChildren);
    }

    /**
     * @param array<int, TemplateNode> $nodes
     */
    private function parseIfBlock(array $nodes, int &$index): TemplateNode
    {
        $current = $nodes[$index];
        $index++;
        $children = $this->parseSequence($nodes, $index, ['elseif', 'else', 'endif']);
        $branches = [];
        $alternateChildren = [];

        while (isset($nodes[$index]) && $nodes[$index]->name() === 'elseif') {
            $elseif = $nodes[$index];
            $index++;

            $branches[] = [
                'expression' => $elseif->expression(),
                'children' => $this->parseSequence($nodes, $index, ['elseif', 'else', 'endif']),
            ];
        }

        if (isset($nodes[$index]) && $nodes[$index]->name() === 'else') {
            $index++;
            $alternateChildren = $this->parseSequence($nodes, $index, ['endif']);
        }

        if (! isset($nodes[$index]) || $nodes[$index]->name() !== 'endif') {
            throw new RuntimeException('Unclosed @if directive.');
        }

        $index++;

        return TemplateNode::block('if', $current->expression(), $children, $alternateChildren, $branches);
    }

    private function isFlatConditionalBlock(string $name): bool
    {
        return false;
    }
}
