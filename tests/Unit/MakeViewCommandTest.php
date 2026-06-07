<?php

declare(strict_types=1);

namespace VoltStack\Test\Unit;

use PHPUnit\Framework\TestCase;
use Quantum\Console\Commands\MakeViewCommand;
use Quantum\Console\Input;
use Quantum\Console\Output;

final class MakeViewCommandTest extends TestCase
{
    private string $basePath;
    private string $viewPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->basePath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'voltstack-make-view-' . uniqid('', true);
        $this->viewPath = $this->basePath . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'custom-views';

        mkdir($this->viewPath, 0777, true);
        mkdir($this->basePath . DIRECTORY_SEPARATOR . 'bootstrap', 0777, true);

        $escapedBasePath = var_export($this->basePath, true);
        $escapedViewPath = var_export($this->viewPath, true);

        file_put_contents(
            $this->basePath . DIRECTORY_SEPARATOR . 'bootstrap' . DIRECTORY_SEPARATOR . 'app.php',
            <<<PHP
<?php

declare(strict_types=1);

use Quantum\Config\ConfigRepository;
use VoltStack\Framework\Application;

\$app = new Application({$escapedBasePath});
\$app->make(ConfigRepository::class)->set('ui-reactive.class_view_components', ['ignored-class-path', {$escapedViewPath}]);

return \$app;
PHP
        );
    }

    protected function tearDown(): void
    {
        $this->deleteDirectory($this->basePath);

        parent::tearDown();
    }

    public function test_it_generates_a_view_using_the_configured_view_directory(): void
    {
        $command = new MakeViewCommand($this->basePath);

        $exitCode = $command->handle(
            Input::fromArgv([
                'volt',
                'make:view',
                'admin/profile_card',
            ]),
            new Output(),
        );

        $generatedView = $this->viewPath . DIRECTORY_SEPARATOR . 'admin' . DIRECTORY_SEPARATOR . 'profile_card.volt.php';

        self::assertSame(0, $exitCode);
        self::assertFileExists($generatedView);

        $contents = file_get_contents($generatedView);

        self::assertIsString($contents);
        self::assertStringContainsString("<?= e(\$title ?? 'Profile Card') ?>", $contents);
        self::assertStringContainsString('php volt make:view', $contents);
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
