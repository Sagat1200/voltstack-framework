<?php

declare(strict_types=1);

namespace VoltStack\Test\Unit;

use PHPUnit\Framework\TestCase;
use Quantum\Routing\CollectionArtifactStore;
use Quantum\Routing\MetadataArtifactStore;
use Quantum\Routing\PipelineArtifactStore;
use Quantum\Routing\Router;
use Quantum\Routing\TreeArtifactStore;
use Quantum\Routing\VersionArtifactStore;
use RuntimeException;
use VoltStack\Framework\Application;

final class VersionArtifactStoreTest extends TestCase
{
    private string $basePath;
    private Application $app;

    protected function setUp(): void
    {
        parent::setUp();

        $this->basePath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'voltstack-version-artifact-' . uniqid('', true);

        if (! mkdir($concurrentDirectory = $this->basePath, 0777, true) && ! is_dir($concurrentDirectory)) {
            throw new RuntimeException(sprintf('Unable to create test directory [%s].', $this->basePath));
        }

        $this->app = new Application($this->basePath);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->basePath);

        parent::tearDown();
    }

    public function test_it_writes_and_loads_a_version_artifact_that_validates_routing_artifacts(): void
    {
        $router = $this->app->make(Router::class);
        $router->get('/versioned', TestSerializedVersionController::class . '@show')
            ->name('versioned.route')
            ->meta('auth', 'session');

        $collectionStore = $this->app->make(CollectionArtifactStore::class);
        $treeStore = $this->app->make(TreeArtifactStore::class);
        $metadataStore = $this->app->make(MetadataArtifactStore::class);
        $pipelineStore = $this->app->make(PipelineArtifactStore::class);

        $collectionStore->compileAndWrite($router);
        $treeStore->compileAndWrite($router);
        $metadataStore->compileAndWrite($router);
        $pipelineStore->compileAndWrite($router);

        $store = $this->app->make(VersionArtifactStore::class);
        $path = $store->compileAndWrite($router);
        $artifact = $store->load();

        self::assertSame($this->app->cachePath('routes/version.php'), $path);
        self::assertNotNull($artifact);
        self::assertSame(1, $artifact->version());
        self::assertTrue($artifact->validates('collection', $collectionStore->path(), $collectionStore->artifactVersion()));
        self::assertTrue($artifact->validates('tree', $treeStore->path(), $treeStore->artifactVersion()));
        self::assertTrue($artifact->validates('metadata', $metadataStore->path(), $metadataStore->artifactVersion()));
        self::assertTrue($artifact->validates('pipeline', $pipelineStore->path(), $pipelineStore->artifactVersion()));

        file_put_contents($collectionStore->path(), file_get_contents($collectionStore->path()) . "\n");

        self::assertFalse($artifact->validates('collection', $collectionStore->path(), $collectionStore->artifactVersion()));
    }

    private function removeDirectory(string $directory): void
    {
        if (! is_dir($directory)) {
            return;
        }

        $items = scandir($directory);

        if ($items === false) {
            return;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = $directory . DIRECTORY_SEPARATOR . $item;

            if (is_dir($path)) {
                $this->removeDirectory($path);
                continue;
            }

            @unlink($path);
        }

        @rmdir($directory);
    }
}

final class TestSerializedVersionController
{
    public function show(): string
    {
        return 'serialized-version-controller';
    }
}
