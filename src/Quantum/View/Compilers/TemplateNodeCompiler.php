<?php

declare(strict_types=1);

namespace Quantum\View\Compilers;

use Quantum\View\Directives\DirectiveRegistry;
use RuntimeException;

final class TemplateNodeCompiler
{
    private readonly TemplateDirectiveCompiler $directives;

    public function __construct(DirectiveRegistry $registry)
    {
        $this->directives = new TemplateDirectiveCompiler($registry);
    }

    public function reset(): void
    {
        $this->directives->reset();
    }

    public function assertBalanced(): void
    {
        $this->directives->assertBalanced();
    }

    public function compile(TemplateNode $node): string
    {
        return match ($node->type()) {
            TemplateToken::TEXT => $node->value(),
            TemplateToken::COMMENT => '',
            TemplateToken::ECHO => sprintf('<?= e(%s) ?>', $this->expression($node->expression())),
            TemplateToken::RAW_ECHO => sprintf('<?= %s ?>', $this->expression($node->expression())),
            TemplateToken::DIRECTIVE => $this->directives->compile($node),
            TemplateToken::BLOCK => $this->compileBlock($node),
            default => throw new RuntimeException(sprintf('Unknown template node type [%s].', $node->type())),
        };
    }

    private function compileBlock(TemplateNode $node): string
    {
        $name = $node->name();

        if (! is_string($name) || $name === '') {
            throw new RuntimeException('Template block nodes require a name.');
        }

        if ($name === 'forelse') {
            $compiled = $this->directives->compile(TemplateNode::directive('forelse', $node->expression()));

            foreach ($node->children() as $child) {
                $compiled .= $this->compile($child);
            }

            $compiled .= $this->directives->compile(TemplateNode::directive('empty', null));

            foreach ($node->alternateChildren() as $child) {
                $compiled .= $this->compile($child);
            }

            return $compiled . $this->directives->compile(TemplateNode::directive('endforelse', null));
        }

        if ($name === 'if') {
            $compiled = $this->directives->compile(TemplateNode::directive('if', $node->expression()));

            foreach ($node->children() as $child) {
                $compiled .= $this->compile($child);
            }

            foreach ($node->branches() as $branch) {
                $compiled .= $this->directives->compile(
                    TemplateNode::directive('elseif', $branch['expression']),
                );

                foreach ($branch['children'] as $child) {
                    $compiled .= $this->compile($child);
                }
            }

            if ($node->alternateChildren() !== []) {
                $compiled .= $this->directives->compile(TemplateNode::directive('else', null));

                foreach ($node->alternateChildren() as $child) {
                    $compiled .= $this->compile($child);
                }
            }

            return $compiled . $this->directives->compile(TemplateNode::directive('endif', null));
        }

        $compiled = $this->directives->compile(TemplateNode::directive($name, $node->expression()));

        foreach ($node->children() as $child) {
            $compiled .= $this->compile($child);
        }

        return $compiled . $this->directives->compile(
            TemplateNode::directive($this->closingDirectiveName($name), null),
        );
    }

    private function expression(?string $expression): string
    {
        $expression = trim((string) $expression);

        if ($expression === '') {
            throw new RuntimeException('The directive requires an expression.');
        }

        return $expression;
    }

    private function closingDirectiveName(string $name): string
    {
        return match ($name) {
            'unless' => 'endunless',
            'isset' => 'endisset',
            'empty' => 'endempty',
            'foreach' => 'endforeach',
            'for' => 'endfor',
            'while' => 'endwhile',
            'section' => 'endsection',
            default => throw new RuntimeException(sprintf('Unknown block directive [%s].', $name)),
        };
    }
}
