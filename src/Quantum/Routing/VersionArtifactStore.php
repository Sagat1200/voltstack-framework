<?php

declare(strict_types=1);

namespace Quantum\Routing;

use RuntimeException;
use VoltStack\Framework\Application;

final class VersionArtifactStore
{
    private const ARTIFACT_VERSION = 1;

    public function __construct(
        private readonly Application $app,
    ) {}

    public function path(): string
    {
        return $this->app->cachePath('routes/version.php');
    }

    public function artifactVersion(): int
    {
        return self::ARTIFACT_VERSION;
    }

    public function compile(Router $router): VersionArtifact
    {
        $collectionStore = $this->app->make(CollectionArtifactStore::class);
        $treeStore = $this->app->make(TreeArtifactStore::class);
        $metadataStore = $this->app->make(MetadataArtifactStore::class);
        $pipelineStore = $this->app->make(PipelineArtifactStore::class);

        return new VersionArtifact(self::ARTIFACT_VERSION, [
            'collection' => $this->artifactPayload('collection', $collectionStore->path(), $collectionStore->artifactVersion()),
            'tree' => $this->artifactPayload('tree', $treeStore->path(), $treeStore->artifactVersion()),
            'metadata' => $this->artifactPayload('metadata', $metadataStore->path(), $metadataStore->artifactVersion()),
            'pipeline' => $this->artifactPayload('pipeline', $pipelineStore->path(), $pipelineStore->artifactVersion()),
        ]);
    }

    public function write(VersionArtifact $artifact): string
    {
        $path = $this->path();
        $directory = dirname($path);

        if (! is_dir($directory) && ! mkdir($directory, 0777, true) && ! is_dir($directory)) {
            throw new RuntimeException(sprintf('Unable to create version artifact directory [%s].', $directory));
        }

        $contents = "<?php\n\nreturn " . var_export($artifact->toArray(), true) . ";\n";

        if (file_put_contents($path, $contents) === false) {
            throw new RuntimeException(sprintf('Unable to write version artifact [%s].', $path));
        }

        return $path;
    }

    public function compileAndWrite(Router $router): string
    {
        return $this->write($this->compile($router));
    }

    public function load(): ?VersionArtifact
    {
        $path = $this->path();

        if (! is_file($path)) {
            return null;
        }

        /** @var mixed $payload */
        $payload = require $path;

        if (! is_array($payload)) {
            throw new RuntimeException(sprintf('Version artifact [%s] must return an array payload.', $path));
        }

        return VersionArtifact::fromArray($payload);
    }

    /**
     * @return array{version: int, checksum: string}
     */
    private function artifactPayload(string $name, string $path, int $version): array
    {
        if (! is_file($path)) {
            throw new RuntimeException(sprintf(
                'Routing artifact [%s] must be written before generating the version artifact.',
                $name,
            ));
        }

        $checksum = hash_file('sha256', $path);

        if (! is_string($checksum) || $checksum === '') {
            throw new RuntimeException(sprintf(
                'Unable to calculate checksum for routing artifact [%s].',
                $name,
            ));
        }

        return [
            'version' => $version,
            'checksum' => $checksum,
        ];
    }
}
