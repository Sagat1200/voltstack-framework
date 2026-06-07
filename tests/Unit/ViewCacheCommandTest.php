<?php

declare(strict_types=1);

namespace VoltStack\Test\Unit;

use PHPUnit\Framework\TestCase;
use Quantum\Console\Commands\ViewCacheCommand;
use Quantum\Console\Commands\ViewClearCommand;
use Quantum\Console\Input;
use Quantum\Console\Output;
use Quantum\View\Cache\CompiledViewStore;
use VoltStack\Framework\Application;

final class ViewCacheCommandTest extends TestCase
{
    private string $basePath;
    private string $viewsPath;
    private string $compiledPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->basePath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'voltstack-view-cache-command-' . uniqid('', true);
        $this->viewsPath = $this->basePath . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'views';
        $this->compiledPath = $this->basePath . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'framework' . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'compiled' . DIRECTORY_SEPARATOR . 'views';

        mkdir($this->viewsPath . DIRECTORY_SEPARATOR . 'partials', 0777, true);
        mkdir($this->basePath . DIRECTORY_SEPARATOR . 'bootstrap', 0777, true);

        file_put_contents(
            $this->viewsPath . DIRECTORY_SEPARATOR . 'home.php',
            <<<'PHP'
<h1>{{ $title }}</h1>
@include('partials.note')
PHP
        );

        file_put_contents(
            $this->viewsPath . DIRECTORY_SEPARATOR . 'partials' . DIRECTORY_SEPARATOR . 'note.php',
            <<<'PHP'
<small>{{ $note }}</small>
PHP
        );

        $escapedBasePath = var_export($this->basePath, true);
        $escapedCompiledPath = var_export($this->compiledPath, true);

        file_put_contents(
            $this->basePath . DIRECTORY_SEPARATOR . 'bootstrap' . DIRECTORY_SEPARATOR . 'app.php',
            <<<PHP
<?php

declare(strict_types=1);

use Quantum\Config\ConfigRepository;
use VoltStack\Framework\Application;

\$app = new Application({$escapedBasePath});
\$app->make(ConfigRepository::class)->set('cache.compiled.views', {$escapedCompiledPath});

return \$app;
PHP
        );
    }

    protected function tearDown(): void
    {
        $this->deleteDirectory($this->basePath);

        parent::tearDown();
    }

    public function test_it_compiles_all_views_into_the_compiled_cache_directory(): void
    {
        $command = new ViewCacheCommand($this->basePath);
        $output = new Output();

        $exitCode = $command->handle(
            Input::fromArgv([
                'volt',
                'view:cache',
            ]),
            $output,
        );

        $app = $this->bootstrappedApplication();
        $store = $app->make(CompiledViewStore::class);

        self::assertSame(0, $exitCode);
        self::assertFileExists($store->compiledPathFor($this->viewsPath . DIRECTORY_SEPARATOR . 'home.php'));
        self::assertFileExists($store->compiledPathFor($this->viewsPath . DIRECTORY_SEPARATOR . 'partials' . DIRECTORY_SEPARATOR . 'note.php'));
        self::assertStringContainsString('Vistas compiladas: 2', $output->stdout());
    }

    public function test_it_can_print_each_compiled_view_in_verbose_mode(): void
    {
        $command = new ViewCacheCommand($this->basePath);
        $output = new Output();

        $exitCode = $command->handle(
            Input::fromArgv([
                'volt',
                'view:cache',
                '--verbose',
            ]),
            $output,
        );

        self::assertSame(0, $exitCode);
        self::assertStringContainsString($this->viewsPath . DIRECTORY_SEPARATOR . 'home.php', $output->stdout());
        self::assertStringContainsString($this->viewsPath . DIRECTORY_SEPARATOR . 'partials' . DIRECTORY_SEPARATOR . 'note.php', $output->stdout());
        self::assertStringContainsString('->', $output->stdout());
    }

    public function test_it_clears_the_compiled_view_cache_directory(): void
    {
        $app = $this->bootstrappedApplication();
        $store = $app->make(CompiledViewStore::class);
        $store->ensureCompiled($this->viewsPath . DIRECTORY_SEPARATOR . 'home.php');
        $store->ensureCompiled($this->viewsPath . DIRECTORY_SEPARATOR . 'partials' . DIRECTORY_SEPARATOR . 'note.php');

        $command = new ViewClearCommand($this->basePath);
        $output = new Output();

        $exitCode = $command->handle(
            Input::fromArgv([
                'volt',
                'view:clear',
            ]),
            $output,
        );

        self::assertSame(0, $exitCode);
        self::assertDirectoryDoesNotExist($this->compiledPath);
        self::assertStringContainsString('Archivos eliminados: 2', $output->stdout());
    }

    public function test_it_can_print_each_deleted_compiled_view_in_verbose_mode(): void
    {
        $app = $this->bootstrappedApplication();
        $store = $app->make(CompiledViewStore::class);
        $homeCompiled = $store->ensureCompiled($this->viewsPath . DIRECTORY_SEPARATOR . 'home.php');
        $noteCompiled = $store->ensureCompiled($this->viewsPath . DIRECTORY_SEPARATOR . 'partials' . DIRECTORY_SEPARATOR . 'note.php');

        $command = new ViewClearCommand($this->basePath);
        $output = new Output();

        $exitCode = $command->handle(
            Input::fromArgv([
                'volt',
                'view:clear',
                '--verbose',
            ]),
            $output,
        );

        self::assertSame(0, $exitCode);
        self::assertStringContainsString($homeCompiled, $output->stdout());
        self::assertStringContainsString($noteCompiled, $output->stdout());
        self::assertStringContainsString('Archivos eliminados: 2', $output->stdout());
    }

    private function bootstrappedApplication(): Application
    {
        $app = require $this->basePath . DIRECTORY_SEPARATOR . 'bootstrap' . DIRECTORY_SEPARATOR . 'app.php';

        self::assertInstanceOf(Application::class, $app);

        return $app;
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
