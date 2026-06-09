<?php

declare(strict_types=1);

namespace VoltStack\Test\Feature;

use PHPUnit\Framework\TestCase;
use Quantum\Config\ConfigRepository;
use Quantum\View\Cache\CompiledViewStore;
use Quantum\View\ViewFactory;
use Quantum\View\Exceptions\DirectiveBalanceException;
use Quantum\View\Exceptions\ViewRenderException;
use VoltStack\Framework\Application;

final class CompiledViewRenderingTest extends TestCase
{
    private string $basePath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->basePath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'voltstack-compiled-view-' . uniqid('', true);
        mkdir($this->basePath . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'partials', 0777, true);
        mkdir($this->basePath . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'layouts', 0777, true);
        mkdir($this->basePath . DIRECTORY_SEPARATOR . 'storage', 0777, true);

        file_put_contents(
            $this->basePath . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'home.volt.php',
            <<<'PHP'
<h1>{{ $title }}</h1>
<div>{!! $html !!}</div>
{{-- hidden --}}
@if($showDetails)
<p>{{ $message }}</p>
@else
<p>Oculto</p>
@endif
@include('partials.note')
PHP
        );

        file_put_contents(
            $this->basePath . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'partials' . DIRECTORY_SEPARATOR . 'note.volt.php',
            <<<'PHP'
<small>{{ $note }}</small>
PHP
        );

        file_put_contents(
            $this->basePath . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'layouts' . DIRECTORY_SEPARATOR . 'app.volt.php',
            <<<'PHP'
<html>
<body>
<header>{{ $title }}</header>
<main>@yield('content')</main>
</body>
</html>
PHP
        );

        file_put_contents(
            $this->basePath . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'dashboard.volt.php',
            <<<'PHP'
@extends('layouts.app')

@section('content')
@php
    $suffix = '!';
    $index = 0;
@endphp
<ul>
@foreach($items as $item)
    <li>{{ $item }}{{ $suffix }}</li>
@endforeach
</ul>
<ol>
@for($i = 0; $i < $count; $i++)
    <li>{{ $i }}</li>
@endfor
</ol>
<div>
@while($index < $count)
    <span>{{ $index }}</span>
    @php $index++; @endphp
@endwhile
</div>
@endsection
PHP
        );

        file_put_contents(
            $this->basePath . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'listing.volt.php',
            <<<'PHP'
<section>
@forelse($items as $item)
    <article>{{ $item }}</article>
@empty
    <p>Sin resultados</p>
@endforelse
</section>
PHP
        );
    }

    protected function tearDown(): void
    {
        $this->deleteDirectory($this->basePath);

        parent::tearDown();
    }

    public function test_it_compiles_and_renders_templates_with_core_directives(): void
    {
        $app = new Application($this->basePath);
        $app->make(ConfigRepository::class)->set(
            'cache.compiled.views',
            $this->basePath . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'framework' . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'compiled' . DIRECTORY_SEPARATOR . 'views',
        );

        $html = $app->make(ViewFactory::class)->render('home', [
            'title' => '<VoltStack>',
            'html' => '<strong>raw</strong>',
            'message' => 'Compilado',
            'showDetails' => true,
            'note' => 'Incluida',
        ]);

        self::assertStringContainsString('<h1>&lt;VoltStack&gt;</h1>', $html);
        self::assertStringContainsString('<div><strong>raw</strong></div>', $html);
        self::assertStringContainsString('<p>Compilado</p>', $html);
        self::assertStringContainsString('<small>Incluida</small>', $html);
        self::assertStringNotContainsString('hidden', $html);
        self::assertStringNotContainsString('Oculto', $html);
    }

    public function test_it_writes_a_compiled_view_file_to_cache(): void
    {
        $app = new Application($this->basePath);
        $compiledDirectory = $this->basePath . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'framework' . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'compiled' . DIRECTORY_SEPARATOR . 'views';
        $app->make(ConfigRepository::class)->set('cache.compiled.views', $compiledDirectory);

        $sourcePath = $app->make(ViewFactory::class)->find('home');
        $store = $app->make(CompiledViewStore::class);
        $compiledPath = $store->ensureCompiled($sourcePath);

        self::assertFileExists($compiledPath);
        self::assertStringStartsWith($compiledDirectory, $compiledPath);
    }

    public function test_it_renders_layouts_loops_and_php_blocks(): void
    {
        $app = new Application($this->basePath);
        $app->make(ConfigRepository::class)->set(
            'cache.compiled.views',
            $this->basePath . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'framework' . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'compiled' . DIRECTORY_SEPARATOR . 'views',
        );

        $html = $app->make(ViewFactory::class)->render('dashboard', [
            'title' => 'Panel',
            'items' => ['Uno', 'Dos'],
            'count' => 3,
        ]);

        self::assertStringContainsString('<header>Panel</header>', $html);
        self::assertStringContainsString('<li>Uno!</li>', $html);
        self::assertStringContainsString('<li>Dos!</li>', $html);
        self::assertStringContainsString('<li>0</li>', $html);
        self::assertStringContainsString('<li>1</li>', $html);
        self::assertStringContainsString('<li>2</li>', $html);
        self::assertStringContainsString('<span>0</span>', $html);
        self::assertStringContainsString('<span>1</span>', $html);
        self::assertStringContainsString('<span>2</span>', $html);
    }

    public function test_it_renders_forelse_with_and_without_results(): void
    {
        $app = new Application($this->basePath);
        $app->make(ConfigRepository::class)->set(
            'cache.compiled.views',
            $this->basePath . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'framework' . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'compiled' . DIRECTORY_SEPARATOR . 'views',
        );

        $filled = $app->make(ViewFactory::class)->render('listing', [
            'items' => ['Alpha', 'Beta'],
        ]);

        $empty = $app->make(ViewFactory::class)->render('listing', [
            'items' => [],
        ]);

        self::assertStringContainsString('<article>Alpha</article>', $filled);
        self::assertStringContainsString('<article>Beta</article>', $filled);
        self::assertStringNotContainsString('Sin resultados', $filled);
        self::assertStringContainsString('<p>Sin resultados</p>', $empty);
    }

    public function test_it_preserves_compiler_exceptions_thrown_while_rendering_nested_views(): void
    {
        file_put_contents(
            $this->basePath . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'partials' . DIRECTORY_SEPARATOR . 'note.volt.php',
            <<<'PHP'
@if($note)
<small>{{ $note }}</small>
PHP
        );

        $app = new Application($this->basePath);
        $app->make(ConfigRepository::class)->set(
            'cache.compiled.views',
            $this->basePath . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'framework' . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'compiled' . DIRECTORY_SEPARATOR . 'views',
        );

        try {
            $app->make(ViewFactory::class)->render('home', [
                'title' => 'VoltStack',
                'html' => '<strong>raw</strong>',
                'message' => 'Compilado',
                'showDetails' => true,
                'note' => 'Incluida',
            ]);

            self::fail('Expected compiler exception was not thrown.');
        } catch (DirectiveBalanceException $exception) {
            self::assertStringEndsWith('partials' . DIRECTORY_SEPARATOR . 'note.volt.php', (string) $exception->sourcePath());
            self::assertSame(1, $exception->sourceLine());
            self::assertSame(1, $exception->sourceColumn());
            self::assertStringContainsString('Unclosed @if directive', $exception->getMessage());
        }
    }

    public function test_it_wraps_non_compiler_runtime_errors_in_a_view_render_exception(): void
    {
        file_put_contents(
            $this->basePath . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'partials' . DIRECTORY_SEPARATOR . 'note.volt.php',
            <<<'PHP'
<?php throw new RuntimeException('Boom from partial'); ?>
PHP
        );

        $app = new Application($this->basePath);
        $app->make(ConfigRepository::class)->set(
            'cache.compiled.views',
            $this->basePath . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'framework' . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'compiled' . DIRECTORY_SEPARATOR . 'views',
        );

        try {
            $app->make(ViewFactory::class)->render('home', [
                'title' => 'VoltStack',
                'html' => '<strong>raw</strong>',
                'message' => 'Compilado',
                'showDetails' => true,
                'note' => 'Incluida',
            ]);

            self::fail('Expected render exception was not thrown.');
        } catch (ViewRenderException $exception) {
            self::assertStringEndsWith('partials' . DIRECTORY_SEPARATOR . 'note.volt.php', $exception->viewPath());
            self::assertInstanceOf(\RuntimeException::class, $exception->getPrevious());
            self::assertSame('Boom from partial', $exception->getPrevious()?->getMessage());
            self::assertStringContainsString('Unable to render view [', $exception->getMessage());
        }
    }

    private function deleteDirectory(string $path): void
    {
        if (! is_dir($path)) {
            return;
        }

        $items = scandir($path);

        if ($items === false) {
            return;
        }

        foreach ($items as $item) {
            if (in_array($item, ['.', '..'], true)) {
                continue;
            }

            $target = $path . DIRECTORY_SEPARATOR . $item;

            if (is_dir($target)) {
                $this->deleteDirectory($target);
                continue;
            }

            unlink($target);
        }

        rmdir($path);
    }
}
