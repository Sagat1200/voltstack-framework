<?php

declare(strict_types=1);

namespace Quantum\Routing;

use VoltStack\Framework\Application;

final class RouteArtifactManager
{
    public function __construct(
        private readonly Application $app,
    ) {}

    /**
     * @return array<string, string>
     */
    public function compileAndWrite(Router $router): array
    {
        $paths = [
            'collection' => $this->app->make(CollectionArtifactStore::class)->compileAndWrite($router),
            'tree' => $this->app->make(TreeArtifactStore::class)->compileAndWrite($router),
            'metadata' => $this->app->make(MetadataArtifactStore::class)->compileAndWrite($router),
            'pipeline' => $this->app->make(PipelineArtifactStore::class)->compileAndWrite($router),
        ];

        $paths['version'] = $this->app->make(VersionArtifactStore::class)->compileAndWrite($router);
        $paths['frontend-manifest'] = $this->app->make(FrontendRouteManifestStore::class)->compileAndWrite($router);

        return $paths;
    }

    public function pipelineOptimizationReport(Router $router): PipelineOptimizationReport
    {
        return $this->app->make(PipelineArtifactStore::class)->optimizationReport($router);
    }

    /**
     * @return array<string, string>
     */
    public function paths(): array
    {
        return [
            'collection' => $this->app->make(CollectionArtifactStore::class)->path(),
            'tree' => $this->app->make(TreeArtifactStore::class)->path(),
            'metadata' => $this->app->make(MetadataArtifactStore::class)->path(),
            'pipeline' => $this->app->make(PipelineArtifactStore::class)->path(),
            'version' => $this->app->make(VersionArtifactStore::class)->path(),
            'frontend-manifest' => $this->app->make(FrontendRouteManifestStore::class)->path(),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function clear(): array
    {
        $deleted = [];

        foreach ($this->paths() as $name => $path) {
            if (! is_file($path)) {
                continue;
            }

            if (@unlink($path)) {
                $deleted[$name] = $path;
            }
        }

        return $deleted;
    }
}
