<?php

declare(strict_types=1);

namespace Quantum\Console\Commands;

use Quantum\Console\Command;
use Quantum\Console\Input;
use Quantum\Console\Output;
use Quantum\View\Cache\CompiledViewStore;

final class ViewClearCommand extends Command
{
    public function name(): string
    {
        return 'view:clear';
    }

    public function description(): string
    {
        return 'Limpia la cache de vistas compiladas.';
    }

    public function handle(Input $input, Output $output): int
    {
        $app = $this->bootstrapApplication();
        $store = $app->make(CompiledViewStore::class);
        $deleted = $store->clear();

        if ($deleted === 0) {
            $output->writeln('No habia vistas compiladas para eliminar.');

            return 0;
        }

        $output->writeln('Cache de vistas limpiada correctamente.');
        $output->writeln(sprintf('  Archivos eliminados: %d', $deleted));

        return 0;
    }
}
