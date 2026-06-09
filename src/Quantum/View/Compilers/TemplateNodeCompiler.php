<?php

declare(strict_types=1);

namespace Quantum\View\Compilers;

use Quantum\View\Directives\DirectiveRegistry;
use Quantum\View\Exceptions\TemplateParseException;

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
        if ($node instanceof IncludeNode || $node instanceof ExtendsNode || $node instanceof YieldNode) {
            return $this->compileSpecialDirective($node);
        }

        return match ($node->type()) {
            TemplateToken::TEXT => $node->value(),
            TemplateToken::COMMENT => '',
            TemplateToken::ECHO => sprintf('<?= e(%s) ?>', $this->expression($node->expression())),
            TemplateToken::RAW_ECHO => sprintf('<?= %s ?>', $this->expression($node->expression())),
            TemplateToken::DIRECTIVE => $this->directives->compile($node),
            TemplateToken::BLOCK => $this->compileBlock($node),
            default => throw new TemplateParseException(
                sprintf('Unknown template node type [%s]', $node->type()),
                $node->line(),
                $node->column(),
            ),
        };
    }

    private function compileBlock(TemplateNode $node): string
    {
        if ($node instanceof ForelseNode) {
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

        if ($node instanceof IfNode) {
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

        if (! $node instanceof SimpleBlockNode) {
            throw new TemplateParseException(
                sprintf('Unsupported specialized block node [%s]', $node::class),
                $node->line(),
                $node->column(),
            );
        }

        $compiled = $this->directives->compile(TemplateNode::directive((string) $node->name(), $node->expression()));

        foreach ($node->children() as $child) {
            $compiled .= $this->compile($child);
        }

        return $compiled . $this->directives->compile(
            TemplateNode::directive($node->closingDirectiveName(), null),
        );
    }

    private function compileSpecialDirective(TemplateNode $node): string
    {
        $name = $node->name();

        if (! is_string($name) || $name === '') {
            throw new TemplateParseException('Specialized directive nodes require a name', $node->line(), $node->column());
        }

        return $this->directives->compile(TemplateNode::directive($name, $node->expression()));
    }

    private function expression(?string $expression): string
    {
        $expression = trim((string) $expression);

        if ($expression === '') {
            throw new TemplateParseException('The directive requires an expression');
        }

        return $expression;
    }
}
