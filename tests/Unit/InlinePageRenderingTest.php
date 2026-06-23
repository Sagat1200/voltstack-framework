<?php

declare(strict_types=1);

namespace VoltStack\Test\Unit;

use PHPUnit\Framework\TestCase;
use VoltStack\Framework\Application;
use VoltStack\Test\Fixtures\InlineLayoutPage;
use VoltStack\Test\Fixtures\InlineTemplatePage;

final class InlinePageRenderingTest extends TestCase
{
    public function test_it_renders_an_inline_page_template_without_a_render_method(): void
    {
        new Application(sys_get_temp_dir());
        $page = new InlineTemplatePage();

        $html = $page->render();

        self::assertIsString($html);
        self::assertStringContainsString('<h1>Inline Title</h1>', $html);
        self::assertStringNotContainsString('data-volt-runtime="true"', $html);
    }

    public function test_it_renders_an_inline_page_template_with_a_layout(): void
    {
        $basePath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'volt-inline-layout-' . uniqid('', true);
        $layoutDirectory = $basePath . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'layouts';

        mkdir($layoutDirectory, 0777, true);
        file_put_contents(
            $layoutDirectory . DIRECTORY_SEPARATOR . 'app.volt.php',
            <<<'VOLT'
<!DOCTYPE html>
<html lang="en">
<body>
    <main>@yield('content')</main>
</body>
</html>
VOLT,
        );

        new Application($basePath);
        $page = new InlineLayoutPage();

        $html = $page->render();

        self::assertIsString($html);
        self::assertStringContainsString('<main>', $html);
        self::assertStringContainsString('<h1>Inline Layout Title</h1>', $html);
        self::assertStringNotContainsString("@extends('layouts.app')", $html);
    }
}
