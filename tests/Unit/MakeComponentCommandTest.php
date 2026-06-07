<?php

declare(strict_types=1);

namespace VoltStack\Test\Unit;

use PHPUnit\Framework\TestCase;
use Quantum\Console\Commands\MakeComponentCommand;
use Quantum\Console\Input;
use Quantum\Console\Output;

final class MakeComponentCommandTest extends TestCase
{
    private string $basePath;
    private string $classPath;
    private string $viewPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->basePath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'voltstack-make-component-' . uniqid('', true);
        $this->classPath = $this->basePath . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'View' . DIRECTORY_SEPARATOR . 'Components';
        $this->viewPath = $this->basePath . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'views';

        mkdir($this->classPath, 0777, true);
        mkdir($this->viewPath, 0777, true);
        mkdir($this->basePath . DIRECTORY_SEPARATOR . 'bootstrap', 0777, true);

        $escapedBasePath = var_export($this->basePath, true);
        $escapedClassPath = var_export($this->classPath, true);
        $escapedViewPath = var_export($this->viewPath, true);

        file_put_contents(
            $this->basePath . DIRECTORY_SEPARATOR . 'bootstrap' . DIRECTORY_SEPARATOR . 'app.php',
            <<<PHP
<?php

declare(strict_types=1);

use Quantum\Config\ConfigRepository;
use VoltStack\Framework\Application;

\$app = new Application({$escapedBasePath});
\$app->make(ConfigRepository::class)->set('ui-reactive.class_view_components', [{$escapedClassPath}, {$escapedViewPath}]);

return \$app;
PHP
        );
    }

    protected function tearDown(): void
    {
        $this->deleteDirectory($this->basePath);

        parent::tearDown();
    }

    public function test_it_generates_a_component_from_the_framework_stub(): void
    {
        $command = new MakeComponentCommand($this->basePath);

        $exitCode = $command->handle(
            Input::fromArgv([
                'volt',
                'make:component',
                'Admin/UserCard',
            ]),
            new Output(),
        );

        $generatedClass = $this->classPath . DIRECTORY_SEPARATOR . 'Admin' . DIRECTORY_SEPARATOR . 'UserCard.php';
        $generatedView = $this->viewPath . DIRECTORY_SEPARATOR . 'Admin' . DIRECTORY_SEPARATOR . 'user_card.volt.php';

        self::assertSame(0, $exitCode);
        self::assertFileExists($generatedClass);
        self::assertFileExists($generatedView);

        $contents = file_get_contents($generatedClass);
        $viewContents = file_get_contents($generatedView);

        self::assertIsString($contents);
        self::assertIsString($viewContents);
        self::assertStringContainsString('namespace App\\View\\Components\\Admin;', $contents);
        self::assertStringContainsString('final class UserCard extends Component', $contents);
        self::assertStringContainsString("public string \$title = 'Admin User Card';", $contents);
        self::assertStringContainsString("return view('admin.user_card'", $contents);
        self::assertStringContainsString('php volt make:component', $viewContents);
        self::assertStringContainsString('<?= volt_runtime_script() ?>', $viewContents);
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