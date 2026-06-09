<?php

declare(strict_types=1);

namespace VoltStack\Test\Unit;

use PHPUnit\Framework\TestCase;
use Quantum\View\Compilers\TemplateBlockParser;
use Quantum\View\Compilers\TemplateDirectiveCompiler;
use Quantum\View\Compilers\TemplateNodeCompiler;
use Quantum\View\Compilers\TemplateParser;
use Quantum\View\Compilers\TemplateNode;
use Quantum\View\Compilers\TemplateSourceToken;
use Quantum\View\Compilers\TemplateSourceTokenizer;
use Quantum\View\Compilers\TemplateToken;
use Quantum\View\Compilers\TemplateTokenizer;
use Quantum\View\Directives\DirectiveRegistry;
use RuntimeException;

final class TemplatePipelineTest extends TestCase
{
    public function test_it_tokenizes_and_parses_inline_template_fragments(): void
    {
        $tokenizer = new TemplateTokenizer();
        $parser = new TemplateParser();

        $tokens = $tokenizer->tokenize("Hola {{ \$name }}{{-- hidden --}}@tailwind-vite\n{!! \$html !!}");
        $nodes = $parser->parse($tokens);

        self::assertCount(5, $tokens);
        self::assertSame(TemplateToken::TEXT, $tokens[0]->type());
        self::assertSame(TemplateToken::ECHO, $tokens[1]->type());
        self::assertSame(TemplateToken::COMMENT, $tokens[2]->type());
        self::assertSame(TemplateToken::DIRECTIVE, $tokens[3]->type());
        self::assertSame(TemplateToken::RAW_ECHO, $tokens[4]->type());

        self::assertSame('Hola ', $nodes[0]->value());
        self::assertSame('$name', $nodes[1]->expression());
        self::assertSame('tailwind-vite', $nodes[3]->name());
        self::assertNull($nodes[3]->expression());
        self::assertSame('$html', $nodes[4]->expression());
    }

    public function test_it_splits_php_and_inline_html_into_source_segments(): void
    {
        $tokenizer = new TemplateSourceTokenizer();

        $tokens = $tokenizer->tokenize("<?php \$title = 'Volt'; ?>\n<h1>{{ \$title }}</h1>");

        self::assertCount(2, $tokens);
        self::assertSame(TemplateSourceToken::PHP, $tokens[0]->type());
        self::assertStringContainsString("\$title = 'Volt';", $tokens[0]->value());
        self::assertSame(TemplateSourceToken::INLINE_HTML, $tokens[1]->type());
        self::assertSame('<h1>{{ $title }}</h1>', $tokens[1]->value());
    }

    public function test_it_compiles_and_validates_block_directives_in_a_dedicated_stage(): void
    {
        $compiler = new TemplateDirectiveCompiler(new DirectiveRegistry());
        $compiler->reset();

        $opening = $compiler->compile(TemplateNode::directive('forelse', '$items as $item'));
        $empty = $compiler->compile(TemplateNode::directive('empty', null));
        $closing = $compiler->compile(TemplateNode::directive('endforelse', null));

        self::assertStringContainsString('$__empty_1 = true; foreach($items as $item): $__empty_1 = false;', $opening);
        self::assertSame('<?php endforeach; if($__empty_1): ?>', $empty);
        self::assertSame('<?php endif; ?>', $closing);
        $compiler->assertBalanced();
    }

    public function test_it_reports_unbalanced_directives_in_the_dedicated_stage(): void
    {
        $compiler = new TemplateDirectiveCompiler(new DirectiveRegistry());
        $compiler->reset();
        $compiler->compile(TemplateNode::directive('if', '$user'));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unclosed @if directive.');

        $compiler->assertBalanced();
    }

    public function test_it_builds_a_hierarchical_block_for_simple_balanced_directives(): void
    {
        $parser = new TemplateBlockParser();

        $nodes = $parser->parse([
            TemplateNode::directive('foreach', '$items as $item'),
            TemplateNode::text('<li>'),
            TemplateNode::echo('$item'),
            TemplateNode::text('</li>'),
            TemplateNode::directive('endforeach', null),
        ]);

        self::assertCount(1, $nodes);
        self::assertSame(TemplateToken::BLOCK, $nodes[0]->type());
        self::assertSame('foreach', $nodes[0]->name());
        self::assertSame('$items as $item', $nodes[0]->expression());
        self::assertCount(3, $nodes[0]->children());
    }

    public function test_it_builds_a_hierarchical_block_for_forelse_with_fallback_children(): void
    {
        $parser = new TemplateBlockParser();

        $nodes = $parser->parse([
            TemplateNode::directive('forelse', '$items as $item'),
            TemplateNode::echo('$item'),
            TemplateNode::directive('empty', null),
            TemplateNode::text('Nada'),
            TemplateNode::directive('endforelse', null),
        ]);

        self::assertCount(1, $nodes);
        self::assertSame(TemplateToken::BLOCK, $nodes[0]->type());
        self::assertSame('forelse', $nodes[0]->name());
        self::assertCount(1, $nodes[0]->children());
        self::assertCount(1, $nodes[0]->alternateChildren());
        self::assertSame('Nada', $nodes[0]->alternateChildren()[0]->value());
    }

    public function test_it_builds_a_hierarchical_if_block_with_elseif_and_else_branches(): void
    {
        $parser = new TemplateBlockParser();

        $nodes = $parser->parse([
            TemplateNode::directive('if', '$user'),
            TemplateNode::text('A'),
            TemplateNode::directive('elseif', '$admin'),
            TemplateNode::text('B'),
            TemplateNode::directive('else', null),
            TemplateNode::text('C'),
            TemplateNode::directive('endif', null),
        ]);

        self::assertCount(1, $nodes);
        self::assertSame(TemplateToken::BLOCK, $nodes[0]->type());
        self::assertSame('if', $nodes[0]->name());
        self::assertSame('$user', $nodes[0]->expression());
        self::assertCount(1, $nodes[0]->children());
        self::assertCount(1, $nodes[0]->branches());
        self::assertSame('$admin', $nodes[0]->branches()[0]['expression']);
        self::assertCount(1, $nodes[0]->branches()[0]['children']);
        self::assertCount(1, $nodes[0]->alternateChildren());
        self::assertSame('C', $nodes[0]->alternateChildren()[0]->value());
    }

    public function test_it_compiles_a_hierarchical_if_block_through_the_main_compiler(): void
    {
        $compiler = new \Quantum\View\Compilers\ViewCompiler(new DirectiveRegistry());

        $compiled = $compiler->compileString('@if($user)A@elseif($admin)B@else C@endif');

        self::assertSame('<?php if($user): ?>A<?php elseif($admin): ?>B<?php else: ?>C<?php endif; ?>', $compiled);
    }

    public function test_it_compiles_hierarchical_nodes_in_a_dedicated_node_compiler(): void
    {
        $compiler = new TemplateNodeCompiler(new DirectiveRegistry());
        $compiler->reset();

        $compiled = $compiler->compile(TemplateNode::block(
            'if',
            '$user',
            [TemplateNode::text('A')],
            [TemplateNode::text('C')],
            [
                [
                    'expression' => '$admin',
                    'children' => [TemplateNode::text('B')],
                ],
            ],
        ));

        self::assertSame('<?php if($user): ?>A<?php elseif($admin): ?>B<?php else: ?>C<?php endif; ?>', $compiled);
        $compiler->assertBalanced();
    }
}
