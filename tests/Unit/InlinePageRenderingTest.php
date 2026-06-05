<?php

declare(strict_types=1);

namespace VoltStack\Test\Unit;

use PHPUnit\Framework\TestCase;
use VoltStack\Framework\Application;
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
        self::assertStringContainsString('<script>', $html);
    }
}
