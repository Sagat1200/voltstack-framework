<?php

declare(strict_types=1);

namespace VoltStack\Test\Unit;

use PHPUnit\Framework\TestCase;
use Quantum\Cache\CacheManager;
use Quantum\Config\ConfigRepository;
use Quantum\Console\Commands\CacheClearCommand;
use Quantum\Console\Input;
use Quantum\Console\Output;
use Quantum\View\Cache\CompiledViewStore;
use VoltStack\Framework\Application;

final class CacheClearCommandTest extends TestCase
{
    private string $basePath;
    private string $dataPath;
    private string $compiledViewsPath;
    private string $compiledPagesPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->basePath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'voltstack-cache-clear-' . uniqid('', true);
        $this->dataPath = $this->basePath . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'framework' . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'data';
        $this->compiledViewsPath = $this->basePath . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'framework' . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'compiled' . DIRECTORY_SEPARATOR . 'views';
        $this->compiledPagesPath = $this->basePath . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'framework' . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'compiled' . DIRECTORY_SEPARATOR . 'pages';

        mkdir($this->basePath . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'views', 0777, true);
        mkdir($this->basePath . DIRECTORY_SEPARATOR . 'bootstrap', 0777, true);

        file_put_contents(
            $this->basePath . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'home.php',
            <<<'PHP'
<h1>{{ $title }}</h1>
PHP
        );

        $escapedBasePath = var_export($this->basePath, true);
        $escapedDataPath = var_export($this->dataPath, true);
        $escapedCompiledViewsPath = var_export($this->compiledViewsPath, true);
        $escapedCompiledPagesPath = var_export($this->compiledPagesPath, true);

        file_put_contents(
            $this->basePath . DIRECTORY_SEPARATOR . 'bootstrap' . DIRECTORY_SEPARATOR . 'app.php',
            <<<PHP
<?php

declare(strict_types=1);

use Quantum\Config\ConfigRepository;
use VoltStack\Framework\Application;

\$app = new Application({$escapedBasePath});
\$config = \$app->make(ConfigRepository::class);
\$config->set('cache.stores.file.path', {$escapedDataPath});
\$config->set('cache.compiled.views', {$escapedCompiledViewsPath});
\$config->set('cache.compiled.pages', {$escapedCompiledPagesPath});

return \$app;
PHP
        );
    }

    protected function tearDown(): void
    {
        $this->deleteDirectory($this->basePath);

        parent::tearDown();
    }

    public function test_it_clears_data_cache_and_compiled_artifacts(): void
    {
        $this->seedAllCaches();

        $command = new CacheClearCommand($this->basePath);
        $output = new Output();

        $exitCode = $command->handle(
            Input::fromArgv([
                'volt',
                'cache:clear',
            ]),
            $output,
        );

        self::assertSame(0, $exitCode);
        self::assertDirectoryDoesNotExist($this->dataPath);
        self::assertDirectoryDoesNotExist($this->compiledViewsPath);
        self::assertDirectoryDoesNotExist($this->compiledPagesPath);
        self::assertStringContainsString('Datos eliminados: 1', $output->stdout());
    }

    public function test_it_can_clear_only_the_data_cache(): void
    {
        $this->seedAllCaches();

        $command = new CacheClearCommand($this->basePath);

        $exitCode = $command->handle(
            Input::fromArgv([
                'volt',
                'cache:clear',
                '--data-only',
            ]),
            new Output(),
        );

        self::assertSame(0, $exitCode);
        self::assertDirectoryDoesNotExist($this->dataPath);
        self::assertDirectoryExists($this->compiledViewsPath);
        self::assertDirectoryExists($this->compiledPagesPath);
    }

    public function test_it_can_clear_only_the_compiled_caches(): void
    {
        $this->seedAllCaches();

        $command = new CacheClearCommand($this->basePath);

        $exitCode = $command->handle(
            Input::fromArgv([
                'volt',
                'cache:clear',
                '--compiled-only',
            ]),
            new Output(),
        );

        self::assertSame(0, $exitCode);
        self::assertDirectoryExists($this->dataPath);
        self::assertDirectoryDoesNotExist($this->compiledViewsPath);
        self::assertDirectoryDoesNotExist($this->compiledPagesPath);
    }

    public function test_it_rejects_incompatible_scope_flags(): void
    {
        $command = new CacheClearCommand($this->basePath);

        $exitCode = $command->handle(
            Input::fromArgv([
                'volt',
                'cache:clear',
                '--data-only',
                '--compiled-only',
            ]),
            new Output(),
        );

        self::assertSame(1, $exitCode);
    }

    public function test_it_can_describe_each_cleared_cache_target_in_verbose_mode(): void
    {
        $this->seedAllCaches();

        $command = new CacheClearCommand($this->basePath);
        $output = new Output();

        $exitCode = $command->handle(
            Input::fromArgv([
                'volt',
                'cache:clear',
                '--verbose',
            ]),
            $output,
        );

        self::assertSame(0, $exitCode);
        self::assertStringContainsString('[data] file -> ' . $this->dataPath, $output->stdout());
        self::assertStringContainsString('[compiled.views] ' . $this->compiledViewsPath, $output->stdout());
        self::assertStringContainsString('[compiled.pages] ' . $this->compiledPagesPath, $output->stdout());
    }

    private function bootstrappedApplication(): Application
    {
        $app = require $this->basePath . DIRECTORY_SEPARATOR . 'bootstrap' . DIRECTORY_SEPARATOR . 'app.php';

        self::assertInstanceOf(Application::class, $app);

        return $app;
    }

    private function seedAllCaches(): void
    {
        $app = $this->bootstrappedApplication();
        $app->make(CacheManager::class)->store()->put('users.index', ['ok' => true]);
        $app->make(CompiledViewStore::class)->ensureCompiled(
            $this->basePath . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'home.php',
        );

        mkdir($this->compiledPagesPath, 0777, true);
        file_put_contents($this->compiledPagesPath . DIRECTORY_SEPARATOR . 'inline.php', '<?php echo "page";');
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
