<?php

declare(strict_types=1);

namespace VoltStack\Test\Unit;

use PHPUnit\Framework\TestCase;
use Quantum\Console\Commands\MakeActionCommand;
use Quantum\Console\Input;
use Quantum\Console\Output;

final class MakeActionCommandTest extends TestCase
{
    private string $basePath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->basePath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'voltstack-make-action-' . uniqid('', true);
        mkdir($this->basePath . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'Actions', 0777, true);
    }

    protected function tearDown(): void
    {
        $this->deleteDirectory($this->basePath);

        parent::tearDown();
    }

    public function test_it_generates_an_action_from_the_framework_stub(): void
    {
        $command = new MakeActionCommand($this->basePath);

        $exitCode = $command->handle(
            Input::fromArgv([
                'volt',
                'make:action',
                'Admin/CreateUser',
            ]),
            new Output(),
        );

        $generated = $this->basePath . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'Actions' . DIRECTORY_SEPARATOR . 'Admin' . DIRECTORY_SEPARATOR . 'CreateUserAction.php';

        self::assertSame(0, $exitCode);
        self::assertFileExists($generated);

        $contents = file_get_contents($generated);

        self::assertIsString($contents);
        self::assertStringContainsString('namespace App\\Actions\\Admin;', $contents);
        self::assertStringContainsString('final class CreateUserAction extends Action', $contents);
        self::assertStringContainsString('public function handle(mixed ...$arguments): mixed', $contents);
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
