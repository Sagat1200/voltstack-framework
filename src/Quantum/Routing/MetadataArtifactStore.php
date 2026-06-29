<?php

declare(strict_types=1);

namespace Quantum\Routing;

use RuntimeException;
use VoltStack\Framework\Application;

final class MetadataArtifactStore
{
    private const ARTIFACT_VERSION = 1;

    public function __construct(
        private readonly Application $app,
    ) {}

    public function path(): string
    {
        return $this->app->cachePath('routes/metadata.php');
    }

    public function artifactVersion(): int
    {
        return self::ARTIFACT_VERSION;
    }

    public function compile(Router $router): MetadataArtifact
    {
        $metadata = [];

        foreach ($router->routes() as $route) {
            $metadata[] = $this->serializeMetadataBag($route->routeMetadata()->all(), $route->uri());
        }

        return new MetadataArtifact(self::ARTIFACT_VERSION, count($metadata), $metadata);
    }

    public function write(MetadataArtifact $artifact): string
    {
        $path = $this->path();
        $directory = dirname($path);

        if (! is_dir($directory) && ! mkdir($directory, 0777, true) && ! is_dir($directory)) {
            throw new RuntimeException(sprintf('Unable to create metadata artifact directory [%s].', $directory));
        }

        $contents = "<?php\n\nreturn " . var_export($artifact->toArray(), true) . ";\n";

        if (file_put_contents($path, $contents) === false) {
            throw new RuntimeException(sprintf('Unable to write metadata artifact [%s].', $path));
        }

        return $path;
    }

    public function compileAndWrite(Router $router): string
    {
        return $this->write($this->compile($router));
    }

    public function load(): ?MetadataArtifact
    {
        $path = $this->path();

        if (! is_file($path)) {
            return null;
        }

        /** @var mixed $payload */
        $payload = require $path;

        if (! is_array($payload)) {
            throw new RuntimeException(sprintf('Metadata artifact [%s] must return an array payload.', $path));
        }

        return MetadataArtifact::fromArray($payload);
    }

    /**
     * @param array<string, mixed> $metadata
     * @return array<string, mixed>
     */
    private function serializeMetadataBag(array $metadata, string $routeUri): array
    {
        $serialized = [];

        foreach ($metadata as $key => $value) {
            if (! is_string($key) || trim($key) === '') {
                throw new RuntimeException(sprintf(
                    'Route [%s] contains a non-serializable metadata key.',
                    $routeUri,
                ));
            }

            $serialized[$key] = $this->serializeMetadataValue($value, $routeUri, $key);
        }

        return $serialized;
    }

    private function serializeMetadataValue(mixed $value, string $routeUri, string $key): mixed
    {
        if (is_null($value) || is_string($value) || is_int($value) || is_float($value) || is_bool($value)) {
            return $value;
        }

        if (is_array($value)) {
            $serialized = [];

            foreach ($value as $nestedKey => $nestedValue) {
                if (! is_int($nestedKey) && ! is_string($nestedKey)) {
                    throw new RuntimeException(sprintf(
                        'Route [%s] contains non-serializable metadata at [%s].',
                        $routeUri,
                        $key,
                    ));
                }

                $serialized[$nestedKey] = $this->serializeMetadataValue($nestedValue, $routeUri, $key);
            }

            return $serialized;
        }

        throw new RuntimeException(sprintf(
            'Route [%s] contains non-serializable metadata at [%s].',
            $routeUri,
            $key,
        ));
    }
}
