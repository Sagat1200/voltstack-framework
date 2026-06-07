<?php

declare(strict_types=1);

namespace VoltStack\Test\Unit;

use PHPUnit\Framework\TestCase;
use Quantum\Console\Commands\MakeLayoutCommand;
use Quantum\Console\Input;
use Quantum\Console\Output;

final class MakeLayoutCommandTest extends TestCase
{
    private string $basePath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->basePath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'voltstack-make-layout-' . uniqid('', true);
        mkdir($this->basePath . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'views', 0777, true);
    }

    protected function tearDown(): void
    {
        $this->deleteDirectory($this->basePath);

        parent::tearDown();
    }

    public function test_it_generates_a_layout_inside_the_layouts_directory(): void
    {
        $command = new MakeLayoutCommand($this->basePath);

        $exitCode = $command->handle(
            Input::fromArgv([
                'volt',
                'make:layout',
                'app',
            ]),
            new Output(),
        );

        $generatedLayout = $this->basePath . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'layouts' . DIRECTORY_SEPARATOR . 'app.php';

        self::assertSame(0, $exitCode);
        self::assertFileExists($generatedLayout);

        $contents = file_get_contents($generatedLayout);

        self::assertIsString($contents);
        self::assertStringContainsString("<?= e(\$title ?? 'App') ?>", $contents);
        self::assertStringContainsString("<?= \$slot ?? (\$content ?? '') ?>", $contents);
        self::assertStringContainsString('php volt make:layout', $contents);
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
