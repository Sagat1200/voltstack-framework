<?php

declare(strict_types=1);

namespace VoltStack\Test\Unit;

use PHPUnit\Framework\TestCase;
use Quantum\Bootstrap\Bootstrapper;
use Quantum\Config\ConfigRepository;
use VoltStack\Framework\Application;
use VoltStack\Framework\ServiceProvider;

final class ApplicationBootstrapTest extends TestCase
{
    private string $basePath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->basePath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'voltstack-framework-' . uniqid('', true);
        mkdir($this->basePath . DIRECTORY_SEPARATOR . 'config', 0777, true);

        file_put_contents(
            $this->basePath . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'app.php',
            <<<PHP
<?php

return [
    'name' => 'VoltStack',
    'env' => 'testing',
];
PHP
        );
    }

    protected function tearDown(): void
    {
        $configFile = $this->basePath . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'app.php';

        if (is_file($configFile)) {
            unlink($configFile);
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

    public function test_bootstrapper_loads_configuration_and_boots_providers(): void
    {
        $app = new Application($this->basePath);
        $bootstrapper = new Bootstrapper($app);

        $bootstrapper->bootstrap([
            TestServiceProvider::class,
        ]);

        self::assertTrue($app->isBooted());
        self::assertSame('VoltStack', $app->config('app.name'));
        self::assertSame('booted', $app->make('test.provider.state'));
    }

    public function test_global_helpers_resolve_the_current_application_and_config(): void
    {
        $app = new Application($this->basePath);
        $config = $app->make(ConfigRepository::class);
        $config->set('app.name', 'VoltStack');

        self::assertSame($app, app());
        self::assertSame('VoltStack', config('app.name'));
    }
}

final class TestServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->instance('test.provider.state', 'registered');
    }

    public function boot(): void
    {
        $this->app->instance('test.provider.state', 'booted');
    }
}
