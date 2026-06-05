<?php

declare(strict_types=1);

namespace VoltStack\Test\Unit;

use PHPUnit\Framework\TestCase;
use Quantum\Console\Commands\MakeControllerCommand;
use Quantum\Console\Input;
use Quantum\Console\Output;

final class MakeControllerCommandTest extends TestCase
{
    private string $basePath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->basePath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'voltstack-make-controller-' . uniqid('', true);
        mkdir($this->basePath . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'Controllers', 0777, true);
    }

    protected function tearDown(): void
    {
        $generated = $this->basePath . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'Controllers' . DIRECTORY_SEPARATOR . 'Admin' . DIRECTORY_SEPARATOR . 'UserController.php';
        $generatedDirectory = dirname($generated);
        $controllersDirectory = $this->basePath . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'Controllers';
        $appDirectory = $this->basePath . DIRECTORY_SEPARATOR . 'app';

        if (is_file($generated)) {
            unlink($generated);
        }

        if (is_dir($generatedDirectory)) {
            rmdir($generatedDirectory);
        }

        if (is_dir($controllersDirectory)) {
            rmdir($controllersDirectory);
        }

        if (is_dir($appDirectory)) {
            rmdir($appDirectory);
        }

        if (is_dir($this->basePath)) {
            rmdir($this->basePath);
        }

        parent::tearDown();
    }

    public function test_it_generates_a_controller_from_the_framework_stub(): void
    {
        $command = new MakeControllerCommand($this->basePath);

        $exitCode = $command->handle(
            Input::fromArgv([
                'volt',
                'make:controller',
                'Admin/User',
            ]),
            new Output(),
        );

        $generated = $this->basePath . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'Controllers' . DIRECTORY_SEPARATOR . 'Admin' . DIRECTORY_SEPARATOR . 'UserController.php';

        self::assertSame(0, $exitCode);
        self::assertFileExists($generated);

        $contents = file_get_contents($generated);

        self::assertIsString($contents);
        self::assertStringContainsString('namespace App\\Controllers\\Admin;', $contents);
        self::assertStringContainsString('final class UserController extends Controller', $contents);
        self::assertStringContainsString("return \$this->view('admin.user');", $contents);
    }
}
