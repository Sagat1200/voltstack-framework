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

        mkdir($this->basePath . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'View' . DIRECTORY_SEPARATOR . 'Components', 0777, true);

        file_put_contents(
            $this->basePath . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'View' . DIRECTORY_SEPARATOR . 'Components' . DIRECTORY_SEPARATOR . 'Tarjeta.php',
            <<<'PHP'
<?php

declare(strict_types=1);

namespace App\View\Components;

use Quantum\View\View;
use VoltStack\Runtime\Component\Component;

final class Tarjeta extends Component
{
    public string $title = 'Tarjeta';

    public function render(): View
    {
        return view('tarjeta', [
            'title' => $this->title,
        ]);
    }
}
PHP
        );

        file_put_contents(
            $this->basePath . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'tarjeta.volt.php',
            <<<'PHP'
@props([
    'title' => 'Tarjeta',
    'variant' => 'default',
    'header' => '',
])
@attributes([
    'class' => 'card-base',
    'data-kind' => 'panel',
    'style' => 'padding: 1rem',
])
<article {!! $attributes !!}>
    <header>{!! $header !!}</header>
    <h2>{{ $title }}</h2>
    <p class="@class([
        'variant',
        'variant-primary' => $variant === 'primary',
        'variant-secondary' => $variant === 'secondary',
        'variant-default' => $variant === 'default',
    ])" style="@style([
        'color: #dc2626' => $variant === 'primary',
        'color: #2563eb' => $variant === 'secondary',
        'color: #374151' => $variant === 'default',
    ])">{{ $variant }}</p>
    <div class="slot">{!! $slot !!}</div>
</article>
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

    public function test_it_renders_a_class_view_component_from_a_template_directive(): void
    {
        file_put_contents(
            $this->basePath . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'component_host.volt.php',
            <<<'PHP'
@component('tarjeta')
<strong>Contenido desde slot</strong>
@endcomponent
PHP
        );

        $app = new Application($this->basePath);
        $app->make(ConfigRepository::class)->set(
            'cache.compiled.views',
            $this->basePath . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'framework' . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'compiled' . DIRECTORY_SEPARATOR . 'views',
        );

        $html = $app->make(ViewFactory::class)->render('component_host');

        self::assertStringContainsString('<h2>Tarjeta</h2>', $html);
        self::assertStringContainsString('<article class="card-base" data-kind="panel" style="padding: 1rem">', str_replace("\r\n", "\n", $html));
        self::assertStringContainsString('<p class="variant variant-default" style="color: #374151">default</p>', str_replace("\r\n", "\n", $html));
        self::assertStringContainsString('<div class="slot">', $html);
        self::assertStringContainsString('<strong>Contenido desde slot</strong>', $html);
    }

    public function test_it_passes_props_to_a_component_and_allows_defaults_inside_the_component_view(): void
    {
        file_put_contents(
            $this->basePath . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'component_host_with_props.volt.php',
            <<<'PHP'
@component('tarjeta', ['title' => 'Custom Card', 'variant' => 'primary'])
<em>Slot con props</em>
@endcomponent
PHP
        );

        $app = new Application($this->basePath);
        $app->make(ConfigRepository::class)->set(
            'cache.compiled.views',
            $this->basePath . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'framework' . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'compiled' . DIRECTORY_SEPARATOR . 'views',
        );

        $html = $app->make(ViewFactory::class)->render('component_host_with_props');

        self::assertStringContainsString('<h2>Custom Card</h2>', $html);
        self::assertStringContainsString('<p class="variant variant-primary" style="color: #dc2626">primary</p>', str_replace("\r\n", "\n", $html));
        self::assertStringContainsString('<em>Slot con props</em>', $html);
    }

    public function test_it_renders_named_slots_inside_a_component(): void
    {
        file_put_contents(
            $this->basePath . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'component_host_with_named_slots.volt.php',
            <<<'PHP'
@component('tarjeta', ['title' => 'Card With Header'])
@slot('header')
<strong>Cabecera del slot</strong>
@endslot
<em>Cuerpo principal</em>
@endcomponent
PHP
        );

        $app = new Application($this->basePath);
        $app->make(ConfigRepository::class)->set(
            'cache.compiled.views',
            $this->basePath . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'framework' . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'compiled' . DIRECTORY_SEPARATOR . 'views',
        );

        $html = $app->make(ViewFactory::class)->render('component_host_with_named_slots');

        self::assertStringContainsString('<header><strong>Cabecera del slot</strong>', $html);
        self::assertStringContainsString('<h2>Card With Header</h2>', $html);
        self::assertStringContainsString('<em>Cuerpo principal</em>', $html);
    }

    public function test_it_renders_a_dynamic_component_from_a_variable_name(): void
    {
        file_put_contents(
            $this->basePath . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'component_host_dynamic.volt.php',
            <<<'PHP'
@dynamic($componentName, ['title' => 'Dynamic Card', 'variant' => 'secondary', 'header' => '<span>Dynamic Header</span>'])
PHP
        );

        $app = new Application($this->basePath);
        $app->make(ConfigRepository::class)->set(
            'cache.compiled.views',
            $this->basePath . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'framework' . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'compiled' . DIRECTORY_SEPARATOR . 'views',
        );

        $html = $app->make(ViewFactory::class)->render('component_host_dynamic', [
            'componentName' => 'tarjeta',
        ]);

        self::assertStringContainsString('<header><span>Dynamic Header</span></header>', str_replace("\r\n", "\n", $html));
        self::assertStringContainsString('<h2>Dynamic Card</h2>', $html);
        self::assertStringContainsString('<p class="variant variant-secondary" style="color: #2563eb">secondary</p>', str_replace("\r\n", "\n", $html));
    }

    public function test_it_renders_self_closing_component_tags(): void
    {
        file_put_contents(
            $this->basePath . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'component_host_tag_short.volt.php',
            <<<'PHP'
<x-tarjeta title="Short Card" variant="primary" class="shadow-tag" />
PHP
        );

        $app = new Application($this->basePath);
        $app->make(ConfigRepository::class)->set(
            'cache.compiled.views',
            $this->basePath . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'framework' . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'compiled' . DIRECTORY_SEPARATOR . 'views',
        );

        $html = str_replace("\r\n", "\n", $app->make(ViewFactory::class)->render('component_host_tag_short'));

        self::assertStringContainsString('<h2>Short Card</h2>', $html);
        self::assertStringContainsString('<article class="card-base shadow-tag" data-kind="panel" style="padding: 1rem">', $html);
        self::assertStringContainsString('<p class="variant variant-primary" style="color: #dc2626">primary</p>', $html);
    }

    public function test_it_renders_wrapped_component_tags_with_slot_content(): void
    {
        file_put_contents(
            $this->basePath . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'component_host_tag_wrapped.volt.php',
            <<<'PHP'
@php $cardTitle = 'Tag Body Card'; @endphp
<x-tarjeta :title="$cardTitle">
    <strong>Slot corto</strong>
</x-tarjeta>
PHP
        );

        $app = new Application($this->basePath);
        $app->make(ConfigRepository::class)->set(
            'cache.compiled.views',
            $this->basePath . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'framework' . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'compiled' . DIRECTORY_SEPARATOR . 'views',
        );

        $html = str_replace("\r\n", "\n", $app->make(ViewFactory::class)->render('component_host_tag_wrapped'));

        self::assertStringContainsString('<h2>Tag Body Card</h2>', $html);
        self::assertStringContainsString('<strong>Slot corto</strong>', $html);
        self::assertStringContainsString('<div class="slot">', $html);
    }

    public function test_it_renders_named_slots_inside_component_tags(): void
    {
        file_put_contents(
            $this->basePath . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'component_host_tag_named_slots.volt.php',
            <<<'PHP'
<x-tarjeta title="Card With Header">
    <x-slot:header>
        <strong>Cabecera desde tag</strong>
    </x-slot:header>
    <em>Cuerpo del tag</em>
</x-tarjeta>
PHP
        );

        $app = new Application($this->basePath);
        $app->make(ConfigRepository::class)->set(
            'cache.compiled.views',
            $this->basePath . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'framework' . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'compiled' . DIRECTORY_SEPARATOR . 'views',
        );

        $html = str_replace("\r\n", "\n", $app->make(ViewFactory::class)->render('component_host_tag_named_slots'));

        self::assertStringContainsString('<header>', $html);
        self::assertStringContainsString('<strong>Cabecera desde tag</strong>', $html);
        self::assertStringContainsString('<h2>Card With Header</h2>', $html);
        self::assertStringContainsString('<em>Cuerpo del tag</em>', $html);
    }

    public function test_it_renders_namespaced_component_tags(): void
    {
        mkdir($this->basePath . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'View' . DIRECTORY_SEPARATOR . 'Components' . DIRECTORY_SEPARATOR . 'Ui', 0777, true);
        mkdir($this->basePath . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'ui', 0777, true);

        file_put_contents(
            $this->basePath . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'View' . DIRECTORY_SEPARATOR . 'Components' . DIRECTORY_SEPARATOR . 'Ui' . DIRECTORY_SEPARATOR . 'Button.php',
            <<<'PHP'
<?php

declare(strict_types=1);

namespace App\View\Components\Ui;

use Quantum\View\View;
use VoltStack\Runtime\Component\Component;

final class Button extends Component
{
    public string $label = 'Button';

    public function render(): View
    {
        return view('ui.button', [
            'label' => $this->label,
        ]);
    }
}
PHP
        );

        file_put_contents(
            $this->basePath . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'View' . DIRECTORY_SEPARATOR . 'Components' . DIRECTORY_SEPARATOR . 'Ui' . DIRECTORY_SEPARATOR . 'Panel.php',
            <<<'PHP'
<?php

declare(strict_types=1);

namespace App\View\Components\Ui;

use Quantum\View\View;
use VoltStack\Runtime\Component\Component;

final class Panel extends Component
{
    public string $title = 'Panel';

    public function render(): View
    {
        return view('ui.panel', [
            'title' => $this->title,
        ]);
    }
}
PHP
        );

        file_put_contents(
            $this->basePath . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'ui' . DIRECTORY_SEPARATOR . 'button.volt.php',
            <<<'PHP'
<button type="button">{{ $label }}</button>
PHP
        );

        file_put_contents(
            $this->basePath . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'ui' . DIRECTORY_SEPARATOR . 'panel.volt.php',
            <<<'PHP'
<section class="ui-panel">
    <h3>{{ $title }}</h3>
    <div class="ui-panel-slot">{!! $slot !!}</div>
</section>
PHP
        );

        file_put_contents(
            $this->basePath . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'component_host_tag_namespaced.volt.php',
            <<<'PHP'
<x-ui:button label="Namespaced Button" />
<x-ui:panel title="Namespaced Panel">
    <span>Contenido namespaced</span>
</x-ui:panel>
PHP
        );

        $app = new Application($this->basePath);
        $app->make(ConfigRepository::class)->set(
            'cache.compiled.views',
            $this->basePath . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'framework' . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'compiled' . DIRECTORY_SEPARATOR . 'views',
        );

        $html = str_replace("\r\n", "\n", $app->make(ViewFactory::class)->render('component_host_tag_namespaced'));

        self::assertStringContainsString('<button type="button">Namespaced Button</button>', $html);
        self::assertStringContainsString('<section class="ui-panel">', $html);
        self::assertStringContainsString('<h3>Namespaced Panel</h3>', $html);
        self::assertStringContainsString('<span>Contenido namespaced</span>', $html);
    }

    public function test_it_merges_component_attributes_with_defaults_defined_in_the_component_view(): void
    {
        file_put_contents(
            $this->basePath . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'component_host_with_attributes.volt.php',
            <<<'PHP'
@component('tarjeta', [
    'title' => 'Card With Attributes',
    'attributes' => [
        'class' => 'shadow-lg',
        'id' => 'main-card',
        'style' => 'border: 1px solid #111827',
    ],
])
<span>Contenido</span>
@endcomponent
PHP
        );

        $app = new Application($this->basePath);
        $app->make(ConfigRepository::class)->set(
            'cache.compiled.views',
            $this->basePath . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'framework' . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'compiled' . DIRECTORY_SEPARATOR . 'views',
        );

        $html = str_replace("\r\n", "\n", $app->make(ViewFactory::class)->render('component_host_with_attributes'));

        self::assertStringContainsString('<article class="card-base shadow-lg" data-kind="panel" style="padding: 1rem; border: 1px solid #111827" id="main-card">', $html);
        self::assertStringContainsString('<h2>Card With Attributes</h2>', $html);
    }

    public function test_it_isolates_variable_assignments_inside_scope_blocks(): void
    {
        file_put_contents(
            $this->basePath . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'scope_demo.volt.php',
            <<<'PHP'
@php $title = 'Exterior'; @endphp
@scope
    @php $title = 'Interior'; @endphp
    <span>{{ $title }}</span>
@endscope
<p>{{ $title }}</p>
PHP
        );

        $app = new Application($this->basePath);
        $app->make(ConfigRepository::class)->set(
            'cache.compiled.views',
            $this->basePath . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'framework' . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'compiled' . DIRECTORY_SEPARATOR . 'views',
        );

        $html = str_replace("\r\n", "\n", $app->make(ViewFactory::class)->render('scope_demo'));

        self::assertStringContainsString('<span>Interior</span>', $html);
        self::assertStringContainsString('<p>Exterior</p>', $html);
    }

    public function test_it_wraps_a_component_view_with_a_parent_component_using_extends_component(): void
    {
        file_put_contents(
            $this->basePath . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'View' . DIRECTORY_SEPARATOR . 'Components' . DIRECTORY_SEPARATOR . 'Marco.php',
            <<<'PHP'
<?php

declare(strict_types=1);

namespace App\View\Components;

use Quantum\View\View;
use VoltStack\Runtime\Component\Component;

final class Marco extends Component
{
    public string $title = 'Marco';

    public function render(): View
    {
        return view('marco', [
            'title' => $this->title,
        ]);
    }
}
PHP
        );

        file_put_contents(
            $this->basePath . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'View' . DIRECTORY_SEPARATOR . 'Components' . DIRECTORY_SEPARATOR . 'TarjetaHeredada.php',
            <<<'PHP'
<?php

declare(strict_types=1);

namespace App\View\Components;

use Quantum\View\View;
use VoltStack\Runtime\Component\Component;

final class TarjetaHeredada extends Component
{
    public string $title = 'Tarjeta Heredada';

    public function render(): View
    {
        return view('tarjeta-heredada', [
            'title' => $this->title,
        ]);
    }
}
PHP
        );

        file_put_contents(
            $this->basePath . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'marco.volt.php',
            <<<'PHP'
@props([
    'title' => 'Marco',
])
<section class="frame">
    <header>{{ $title }}</header>
    <div class="frame-slot">{!! $slot !!}</div>
</section>
PHP
        );

        file_put_contents(
            $this->basePath . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'tarjeta-heredada.volt.php',
            <<<'PHP'
@extendsComponent('marco', ['title' => $title])
<article class="child-card">
    <p>Contenido hijo</p>
</article>
PHP
        );

        file_put_contents(
            $this->basePath . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'component_host_with_inheritance.volt.php',
            <<<'PHP'
@dynamic('tarjeta-heredada', ['title' => 'Componente Base'])
PHP
        );

        $app = new Application($this->basePath);
        $app->make(ConfigRepository::class)->set(
            'cache.compiled.views',
            $this->basePath . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'framework' . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'compiled' . DIRECTORY_SEPARATOR . 'views',
        );

        $html = str_replace("\r\n", "\n", $app->make(ViewFactory::class)->render('component_host_with_inheritance'));

        self::assertStringContainsString('<section class="frame">', $html);
        self::assertStringContainsString('<header>Componente Base</header>', $html);
        self::assertStringContainsString('<div class="frame-slot"><article class="child-card">', $html);
        self::assertStringContainsString('<p>Contenido hijo</p>', $html);
    }

    public function test_it_renders_components_as_interactive_roots_when_render_mode_is_interactive(): void
    {
        file_put_contents(
            $this->basePath . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'component_host_interactive.volt.php',
            <<<'PHP'
@renderMode('interactive')
@dynamic('tarjeta', ['title' => 'Interactive Card'])
PHP
        );

        $app = new Application($this->basePath);
        $app->make(ConfigRepository::class)->set(
            'cache.compiled.views',
            $this->basePath . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'framework' . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'compiled' . DIRECTORY_SEPARATOR . 'views',
        );

        $html = str_replace("\r\n", "\n", $app->make(ViewFactory::class)->render('component_host_interactive'));

        self::assertStringContainsString('data-volt-root="true"', $html);
        self::assertStringContainsString('data-volt-render-mode="interactive"', $html);
        self::assertStringContainsString('<h2>Interactive Card</h2>', $html);
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
