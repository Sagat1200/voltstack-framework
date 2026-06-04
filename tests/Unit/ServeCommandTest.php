<?php

declare(strict_types=1);

namespace VoltStack\Test\Unit;

use PHPUnit\Framework\TestCase;
use Quantum\Console\Commands\ServeCommand;
use Quantum\Console\Input;
use Quantum\Console\Output;

final class ServeCommandTest extends TestCase
{
    private string $basePath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->basePath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'voltstack-console-' . uniqid('', true);
        mkdir($this->basePath . DIRECTORY_SEPARATOR . 'public', 0777, true);

        file_put_contents(
            $this->basePath . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'index.php',
            "<?php\n"
        );
    }

    protected function tearDown(): void
    {
        $indexFile = $this->basePath . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'index.php';

        if (is_file($indexFile)) {
            unlink($indexFile);
        }

        $publicDirectory = $this->basePath . DIRECTORY_SEPARATOR . 'public';

        if (is_dir($publicDirectory)) {
            rmdir($publicDirectory);
        }

        if (is_dir($this->basePath)) {
            rmdir($this->basePath);
        }

        parent::tearDown();
    }

    public function test_it_builds_the_php_built_in_server_command(): void
    {
        $capturedCommand = null;
        $command = new ServeCommand(
            $this->basePath,
            static function (string $serverCommand) use (&$capturedCommand): int {
                $capturedCommand = $serverCommand;

                return 0;
            },
        );

        $exitCode = $command->handle(
            Input::fromArgv([
                'volt',
                'serve',
                '--host=0.0.0.0',
                '--port=9001',
            ]),
            new Output(),
        );

        self::assertSame(0, $exitCode);
        self::assertIsString($capturedCommand);
        self::assertStringContainsString(' -S ', $capturedCommand);
        self::assertStringContainsString('0.0.0.0:9001', $capturedCommand);
        self::assertStringContainsString($this->basePath . DIRECTORY_SEPARATOR . 'public', $capturedCommand);
        self::assertStringContainsString('src' . DIRECTORY_SEPARATOR . 'Quantum' . DIRECTORY_SEPARATOR . 'Console' . DIRECTORY_SEPARATOR . 'Support' . DIRECTORY_SEPARATOR . 'server.php', $capturedCommand);
    }
}
