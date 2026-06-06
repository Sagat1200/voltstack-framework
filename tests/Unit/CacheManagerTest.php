<?php

declare(strict_types=1);

namespace VoltStack\Test\Unit;

use PHPUnit\Framework\TestCase;
use Quantum\Cache\CacheManager;
use Quantum\Cache\Repository;
use Quantum\Config\ConfigRepository;
use VoltStack\Framework\Application;

final class CacheManagerTest extends TestCase
{
    private string $basePath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->basePath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'voltstack-cache-' . uniqid('', true);
        mkdir($this->basePath, 0777, true);
    }

    protected function tearDown(): void
    {
        $this->deleteDirectory($this->basePath);

        parent::tearDown();
    }

    public function test_it_uses_the_file_store_and_persists_values(): void
    {
        $app = new Application($this->basePath);
        $app->make(ConfigRepository::class)->set('cache.stores.file.path', $this->basePath . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'framework' . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'data');
        $app->make(ConfigRepository::class)->set('cache.compiled.pages', $this->basePath . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'framework' . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'compiled' . DIRECTORY_SEPARATOR . 'pages');

        $manager = $app->make(CacheManager::class);
        $store = $manager->store();

        self::assertInstanceOf(Repository::class, $store);
        self::assertFalse($store->has('views.home'));

        $store->put('views.home', ['compiled' => true]);

        self::assertTrue($store->has('views.home'));
        self::assertSame(['compiled' => true], $store->get('views.home'));
        self::assertSame(['compiled' => true], cache('views.home'));

        $store->forget('views.home');

        self::assertFalse($store->has('views.home'));
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
