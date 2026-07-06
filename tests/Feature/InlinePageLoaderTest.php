<?php

declare(strict_types=1);

namespace VoltStack\Test\Feature;

use PHPUnit\Framework\TestCase;
use Quantum\Config\ConfigRepository;
use VoltStack\Framework\Application;
use VoltStack\Runtime\Component\ComponentManager;

final class InlinePageLoaderTest extends TestCase
{
    private string $basePath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->basePath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'voltstack-inline-page-' . uniqid('', true);
        mkdir($this->basePath . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'Pages', 0777, true);
        mkdir($this->basePath . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'views', 0777, true);

        file_put_contents(
            $this->basePath . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'Pages' . DIRECTORY_SEPARATOR . 'HelloPage.php',
            <<<'PHP'
<?php

declare(strict_types=1);

namespace App\Pages;

use VoltStack\Runtime\Component\Component;

final class HelloPage extends Component
{
    public string $title = 'Inline Hello';
}
?>
<section>
    <h1>{{ $title }}</h1>
</section>
PHP
        );
    }

    protected function tearDown(): void
    {
        $this->deleteDirectory($this->basePath);

        parent::tearDown();
    }

    public function test_it_loads_an_inline_page_using_the_php_close_tag_as_separator(): void
    {
        $app = new Application($this->basePath);
        $app->make(ConfigRepository::class)->set(
            'ui-reactive.single_page_components',
            $this->basePath . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'Pages',
        );

        ob_start();
        $component = $app->make(ComponentManager::class)->mount('App\\Pages\\HelloPage');
        $autoloadOutput = ob_get_clean();

        $html = $app->make(ComponentManager::class)->renderRoot($component);

        self::assertSame('', $autoloadOutput);
        self::assertStringContainsString('<h1>Inline Hello</h1>', $html);
        self::assertStringContainsString('data-volt-root="true"', $html);
        self::assertStringNotContainsString('<script', $html);
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
