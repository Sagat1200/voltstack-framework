<?php

declare(strict_types=1);

namespace Quantum\Console\Commands;

use Quantum\Console\Command;
use Quantum\Console\Input;
use Quantum\Console\Output;
use Quantum\Routing\RouteArtifactManager;
use Quantum\Routing\Router;

final class RouteCacheCommand extends Command
{
    public function name(): string
    {
        return 'route:cache';
    }

    public function description(): string
    {
        return 'Compila y guarda los artifacts AOT del sistema de rutas.';
    }

    public function usage(): string
    {
        return 'route:cache [--verbose]';
    }

    public function category(): string
    {
        return 'Routing';
    }

    public function aliases(): array
    {
        return ['routes:cache'];
    }

    public function optionsHelp(): array
    {
        return [
            '--verbose' => 'Muestra cada artifact generado y su path final.',
        ];
    }

    public function handle(Input $input, Output $output): int
    {
        $app = $this->bootstrapApplication();
        $router = $app->make(Router::class);
        $manager = $app->make(RouteArtifactManager::class);
        $verbose = $input->hasOption('verbose');
        $paths = $manager->compileAndWrite($router);
        $report = $manager->pipelineOptimizationReport($router);

        if ($verbose) {
            foreach ($paths as $name => $path) {
                $output->writeln(sprintf('  [%s] %s', $name, $path));
            }
        }

        $output->writeln('Artifacts de rutas compilados correctamente.');
        $output->writeln(sprintf('  Artifacts escritos: %d', count($paths)));

        if ($verbose) {
            $output->writeln('  Pipeline optimizer:');
            $output->writeln(sprintf('    Rutas analizadas: %d', $report->totalRoutes()));
            $output->writeln(sprintf('    Pipelines unicos: %d', $report->uniquePipelines()));
            $output->writeln(sprintf('    Rutas reutilizando pipeline: %d', $report->sharedRouteCount()));
            $output->writeln(sprintf(
                '    Pipeline mas largo: %s (%d middleware)',
                $report->longestRouteUri() ?? '-',
                $report->longestPipelineLength(),
            ));
        }

        if ($report->hasWarnings()) {
            $output->writeln('  Advertencias del pipeline optimizer:');

            foreach ($report->warnings() as $warning) {
                $output->writeln(sprintf('    - %s', $warning));
            }
        }

        return 0;
    }
}
