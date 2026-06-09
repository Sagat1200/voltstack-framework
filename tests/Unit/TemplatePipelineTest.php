<?php

declare(strict_types=1);

namespace VoltStack\Test\Unit;

use PHPUnit\Framework\TestCase;
use Quantum\View\Compilers\ExtendsNode;
use Quantum\View\Compilers\ForelseNode;
use Quantum\View\Compilers\IncludeNode;
use Quantum\View\Compilers\IfNode;
use Quantum\View\Compilers\SectionNode;
use Quantum\View\Compilers\SimpleBlockNode;
use Quantum\View\Compilers\TemplateBlockParser;
use Quantum\View\Compilers\TemplateDirectiveCompiler;
use Quantum\View\Compilers\TemplateNodeCompiler;
use Quantum\View\Compilers\TemplateParser;
use Quantum\View\Compilers\TemplateNode;
use Quantum\View\Compilers\TemplateSourceToken;
use Quantum\View\Compilers\TemplateSourceTokenizer;
use Quantum\View\Compilers\TemplateToken;
use Quantum\View\Compilers\TemplateTokenizer;
use Quantum\View\Compilers\YieldNode;
use Quantum\View\Directives\DirectiveRegistry;
use Quantum\View\Exceptions\DirectiveBalanceException;
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
        self::assertSame(1, $tokens[0]->line());
        self::assertSame(1, $tokens[0]->column());
        self::assertSame(1, $tokens[1]->line());
        self::assertSame(6, $tokens[1]->column());
        self::assertSame(1, $tokens[3]->line());
        self::assertSame(33, $tokens[3]->column());
        self::assertSame(2, $tokens[4]->line());
        self::assertSame(1, $tokens[4]->column());

        self::assertSame('Hola ', $nodes[0]->value());
        self::assertSame('$name', $nodes[1]->expression());
        self::assertSame('tailwind-vite', $nodes[3]->name());
        self::assertNull($nodes[3]->expression());
        self::assertSame('$html', $nodes[4]->expression());
        self::assertSame(1, $nodes[3]->line());
        self::assertSame(33, $nodes[3]->column());
        self::assertSame(2, $nodes[4]->line());
        self::assertSame(1, $nodes[4]->column());
    }

    public function test_it_parses_structural_directives_into_specialized_nodes(): void
    {
        $tokenizer = new TemplateTokenizer();
        $parser = new TemplateParser();

        $nodes = $parser->parse($tokenizer->tokenize(
            "@extends('layouts.app')@include('partials.card')@yield('content')"
        ));

        self::assertInstanceOf(ExtendsNode::class, $nodes[0]);
        self::assertInstanceOf(IncludeNode::class, $nodes[1]);
        self::assertInstanceOf(YieldNode::class, $nodes[2]);
        self::assertSame("'layouts.app'", $nodes[0]->expression());
        self::assertSame("'partials.card'", $nodes[1]->expression());
        self::assertSame("'content'", $nodes[2]->expression());
        self::assertSame(1, $nodes[0]->line());
        self::assertSame(1, $nodes[0]->column());
        self::assertSame(1, $nodes[1]->line());
        self::assertSame(24, $nodes[1]->column());
        self::assertSame(1, $nodes[2]->line());
        self::assertSame(49, $nodes[2]->column());
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
        self::assertSame(1, $tokens[0]->line());
        self::assertSame(1, $tokens[0]->column());
        self::assertSame(2, $tokens[1]->line());
        self::assertSame(1, $tokens[1]->column());
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
        $compiler->compile(TemplateNode::directive('if', '$user', 3, 7));

        $this->expectException(DirectiveBalanceException::class);
        $this->expectExceptionMessage('Unclosed @if directive at line 3, column 7.');

        $compiler->assertBalanced();
    }

    public function test_it_reports_block_parser_errors_with_opening_location(): void
    {
        $parser = new TemplateBlockParser();

        $this->expectException(DirectiveBalanceException::class);
        $this->expectExceptionMessage('Unclosed @if directive at line 4, column 9.');

        $parser->parse([
            TemplateNode::directive('if', '$user', 4, 9),
            TemplateNode::text('Hola', 4, 19),
        ]);
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
        self::assertInstanceOf(SimpleBlockNode::class, $nodes[0]);
        self::assertSame('foreach', $nodes[0]->name());
        self::assertSame('$items as $item', $nodes[0]->expression());
        self::assertCount(3, $nodes[0]->children());
        self::assertSame(1, $nodes[0]->line());
        self::assertSame(1, $nodes[0]->column());
    }

    public function test_it_builds_a_hierarchical_component_block(): void
    {
        $parser = new TemplateBlockParser();

        $nodes = $parser->parse([
            TemplateNode::directive('component', "'tarjeta'"),
            TemplateNode::text('Hola componente'),
            TemplateNode::directive('endcomponent', null),
        ]);

        self::assertCount(1, $nodes);
        self::assertInstanceOf(SimpleBlockNode::class, $nodes[0]);
        self::assertSame('component', $nodes[0]->name());
        self::assertSame("'tarjeta'", $nodes[0]->expression());
        self::assertCount(1, $nodes[0]->children());
    }

    public function test_it_builds_a_hierarchical_slot_block(): void
    {
        $parser = new TemplateBlockParser();

        $nodes = $parser->parse([
            TemplateNode::directive('slot', "'header'"),
            TemplateNode::text('Cabecera'),
            TemplateNode::directive('endslot', null),
        ]);

        self::assertCount(1, $nodes);
        self::assertInstanceOf(SimpleBlockNode::class, $nodes[0]);
        self::assertSame('slot', $nodes[0]->name());
        self::assertSame("'header'", $nodes[0]->expression());
        self::assertCount(1, $nodes[0]->children());
    }

    public function test_it_builds_a_specialized_section_block(): void
    {
        $parser = new TemplateBlockParser();

        $nodes = $parser->parse([
            TemplateNode::directive('section', "'content'"),
            TemplateNode::text('<p>Hola</p>'),
            TemplateNode::directive('endsection', null),
        ]);

        self::assertCount(1, $nodes);
        self::assertInstanceOf(SectionNode::class, $nodes[0]);
        self::assertSame('section', $nodes[0]->name());
        self::assertSame("'content'", $nodes[0]->expression());
        self::assertCount(1, $nodes[0]->children());
        self::assertSame(1, $nodes[0]->line());
        self::assertSame(1, $nodes[0]->column());
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
        self::assertInstanceOf(ForelseNode::class, $nodes[0]);
        self::assertSame('forelse', $nodes[0]->name());
        self::assertCount(1, $nodes[0]->children());
        self::assertCount(1, $nodes[0]->alternateChildren());
        self::assertSame('Nada', $nodes[0]->alternateChildren()[0]->value());
        self::assertSame(1, $nodes[0]->line());
        self::assertSame(1, $nodes[0]->column());
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
        self::assertInstanceOf(IfNode::class, $nodes[0]);
        self::assertSame('if', $nodes[0]->name());
        self::assertSame('$user', $nodes[0]->expression());
        self::assertCount(1, $nodes[0]->children());
        self::assertCount(1, $nodes[0]->branches());
        self::assertSame('$admin', $nodes[0]->branches()[0]['expression']);
        self::assertCount(1, $nodes[0]->branches()[0]['children']);
        self::assertCount(1, $nodes[0]->alternateChildren());
        self::assertSame('C', $nodes[0]->alternateChildren()[0]->value());
        self::assertSame(1, $nodes[0]->line());
        self::assertSame(1, $nodes[0]->column());
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

        $compiled = $compiler->compile(new IfNode(
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

    public function test_it_compiles_specialized_view_structure_nodes(): void
    {
        $compiler = new TemplateNodeCompiler(new DirectiveRegistry());
        $compiler->reset();

        $compiled = $compiler->compile(new ExtendsNode("'layouts.app'"))
            . $compiler->compile(new IncludeNode("'partials.card'"))
            . $compiler->compile(new YieldNode("'content'"))
            . $compiler->compile(new SectionNode("'sidebar'", [TemplateNode::text('Links')]));

        self::assertSame(
            "<?php \$__volt->extend('layouts.app'); ?><?php echo \$__volt->render('partials.card'); ?><?php echo \$__volt->yieldContent('content'); ?><?php \$__volt->startSection('sidebar'); ?>Links<?php \$__volt->endSection(); ?>",
            $compiled,
        );
        $compiler->assertBalanced();
    }

    public function test_it_compiles_component_blocks_through_the_node_compiler(): void
    {
        $compiler = new TemplateNodeCompiler(new DirectiveRegistry());
        $compiler->reset();

        $compiled = $compiler->compile(new SimpleBlockNode('component', "'tarjeta'", [
            TemplateNode::text('Hola'),
        ]));

        self::assertSame("<?php \$__volt->startComponent('tarjeta'); ?>Hola<?php echo \$__volt->endComponent(); ?>", $compiled);
        $compiler->assertBalanced();
    }

    public function test_it_compiles_props_directive(): void
    {
        $compiler = new TemplateNodeCompiler(new DirectiveRegistry());
        $compiler->reset();

        $compiled = $compiler->compile(TemplateNode::directive('props', "['title' => 'Hola', 'size' => 'md']"));

        self::assertSame(
            "<?php extract(\$__volt->normalizeProps(['title' => 'Hola', 'size' => 'md']) + get_defined_vars(), EXTR_SKIP); ?>",
            $compiled
        );
    }

    public function test_it_compiles_dynamic_directive(): void
    {
        $compiler = new TemplateNodeCompiler(new DirectiveRegistry());
        $compiler->reset();

        $compiled = $compiler->compile(TemplateNode::directive('dynamic', '$component'));

        self::assertSame("<?php echo \$__volt->renderDynamicComponent(\$component); ?>", $compiled);
    }

    public function test_it_compiles_attributes_directive(): void
    {
        $compiler = new TemplateNodeCompiler(new DirectiveRegistry());
        $compiler->reset();

        $compiled = $compiler->compile(TemplateNode::directive('attributes', "['class' => 'card']"));

        self::assertSame(
            "<?php \$attributes = ((\$attributes ?? new \\VoltStack\\Runtime\\Component\\ComponentAttributeBag())->merge(['class' => 'card'])); ?>",
            $compiled
        );
    }

    public function test_it_compiles_class_directive(): void
    {
        $compiler = new TemplateNodeCompiler(new DirectiveRegistry());
        $compiler->reset();

        $compiled = $compiler->compile(TemplateNode::directive('class', "['btn', 'btn-primary' => \$primary]"));

        self::assertSame("<?php echo e(\$__volt->classList(['btn', 'btn-primary' => \$primary])); ?>", $compiled);
    }

    public function test_it_compiles_slot_blocks_through_the_node_compiler(): void
    {
        $compiler = new TemplateNodeCompiler(new DirectiveRegistry());
        $compiler->reset();

        $compiled = $compiler->compile(new SimpleBlockNode('slot', "'header'", [
            TemplateNode::text('Cabecera'),
        ]));

        self::assertSame("<?php \$__volt->startSlot('header'); ?>Cabecera<?php \$__volt->endSlot(); ?>", $compiled);
        $compiler->assertBalanced();
    }
}
