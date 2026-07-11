<?php

declare(strict_types=1);

namespace VoltStack\Test\Unit;

use PHPUnit\Framework\TestCase;
use Quantum\Console\Commands\MakePageCommand;
use Quantum\Console\Input;
use Quantum\Console\Output;

final class MakePageCommandTest extends TestCase
{
    private string $basePath;
    private string $pagesPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->basePath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'voltstack-make-page-' . uniqid('', true);
        $this->pagesPath = $this->basePath . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'VoltPages';

        mkdir($this->pagesPath, 0777, true);
        mkdir($this->basePath . DIRECTORY_SEPARATOR . 'bootstrap', 0777, true);

        $escapedPagesPath = var_export($this->pagesPath, true);
        $escapedBasePath = var_export($this->basePath, true);

        file_put_contents(
            $this->basePath . DIRECTORY_SEPARATOR . 'bootstrap' . DIRECTORY_SEPARATOR . 'app.php',
            <<<PHP
<?php

declare(strict_types=1);

use Quantum\Config\ConfigRepository;
use VoltStack\Framework\Application;

\$app = new Application({$escapedBasePath});
\$app->make(ConfigRepository::class)->set('ui-reactive.single_page_components', [
    'App\\\\VoltPages' => {$escapedPagesPath},
    'VoltStack\\\\SPALab\\\\Pages' => {$escapedBasePath} . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'voltstack' . DIRECTORY_SEPARATOR . 'spa-lab' . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'Pages',
]);

return \$app;
PHP
        );
    }

    protected function tearDown(): void
    {
        $this->deleteDirectory($this->basePath);

        parent::tearDown();
    }

    public function test_it_generates_a_page_from_the_framework_stub(): void
    {
        $command = new MakePageCommand($this->basePath);

        $exitCode = $command->handle(
            Input::fromArgv([
                'volt',
                'make:page',
                'Admin/Dashboard',
            ]),
            new Output(),
        );

        $generated = $this->pagesPath . DIRECTORY_SEPARATOR . 'Admin' . DIRECTORY_SEPARATOR . 'DashboardPage.php';

        self::assertSame(0, $exitCode);
        self::assertFileExists($generated);

        $contents = file_get_contents($generated);

        self::assertIsString($contents);
        self::assertStringContainsString('namespace App\\VoltPages\\Admin;', $contents);
        self::assertStringContainsString('final class DashboardPage extends Component', $contents);
        self::assertStringContainsString("public string \$title = 'Admin Dashboard';", $contents);
        self::assertStringContainsString("?>\n<section", str_replace("\r\n", "\n", $contents));
        self::assertStringContainsString('php volt make:page', $contents);
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
