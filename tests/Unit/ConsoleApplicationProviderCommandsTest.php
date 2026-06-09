<?php

declare(strict_types=1);

namespace VoltStack\Test\Unit;

use PHPUnit\Framework\TestCase;
use Quantum\Console\Command;
use Quantum\Console\ConsoleApplication;
use Quantum\Console\Input;
use Quantum\Console\Output;
use VoltStack\Framework\ServiceProvider;

final class ConsoleApplicationProviderCommandsTest extends TestCase
{
    private string $basePath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->basePath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'voltstack-console-provider-' . uniqid('', true);
        mkdir($this->basePath . DIRECTORY_SEPARATOR . 'config', 0777, true);

        file_put_contents(
            $this->basePath . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'app.php',
            <<<PHP
<?php

return [
    'providers' => [
        \VoltStack\Test\Unit\ConsoleCommandDiscoveryProvider::class,
    ],
];
PHP
        );
    }

    protected function tearDown(): void
    {
        $appConfig = $this->basePath . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'app.php';

        if (is_file($appConfig)) {
            unlink($appConfig);
        }

        $configDirectory = $this->basePath . DIRECTORY_SEPARATOR . 'config';

        if (is_dir($configDirectory)) {
            rmdir($configDirectory);
        }

        if (is_dir($this->basePath)) {
            rmdir($this->basePath);
        }

        parent::tearDown();
    }

    public function test_it_discovers_commands_exposed_by_registered_providers(): void
    {
        $output = new Output();
        $application = new ConsoleApplication($this->basePath, [], $output);

        $exitCode = $application->run([
            'volt',
        ]);

        self::assertSame(0, $exitCode);
        self::assertStringContainsString('frontend:install', $output->stdout());
    }
}

final class ConsoleCommandDiscoveryProvider extends ServiceProvider
{
    public function register(): void
    {
    }

    public function commands(): array
    {
        return [
            ConsoleCommandDiscoveryCommand::class,
        ];
    }
}

final class ConsoleCommandDiscoveryCommand extends Command
{
    public function name(): string
    {
        return 'frontend:install';
    }

    public function description(): string
    {
        return 'Discovered provider command for console integration tests.';
    }

    public function category(): string
    {
        return 'Frontend';
    }

    public function handle(Input $input, Output $output): int
    {
        $output->writeln('frontend:install');

        return 0;
    }
}
