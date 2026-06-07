<?php

declare(strict_types=1);

namespace Quantum\Console\Commands;

use Quantum\Console\Command;
use Quantum\Console\Input;
use Quantum\Console\Output;
use RuntimeException;

final class MakeLayoutCommand extends Command
{
    public function name(): string
    {
        return 'make:layout';
    }

    public function description(): string
    {
        return 'Crea un layout nuevo dentro de resources/views/layouts.';
    }

    public function usage(): string
    {
        return 'make:layout <name>';
    }

    public function category(): string
    {
        return 'Generators';
    }

    public function argumentsHelp(): array
    {
        return [
            'name' => 'Nombre del layout, por ejemplo `app` o `admin/dashboard`.',
        ];
    }

    public function handle(Input $input, Output $output): int
    {
        $name = $input->arguments()[0] ?? null;

        if (! is_string($name) || trim($name) === '') {
            $output->error('Debes indicar el nombre del layout. Ejemplo: php volt make:layout app');

            return 1;
        }

        $descriptor = $this->buildDescriptor($name);
        $layoutPath = $descriptor['layout_path'];

        if (is_file($layoutPath)) {
            $output->error(sprintf('El layout ya existe en [%s].', $layoutPath));

            return 1;
        }

        if (! is_dir($descriptor['layout_directory']) && ! mkdir($descriptor['layout_directory'], 0777, true) && ! is_dir($descriptor['layout_directory'])) {
            throw new RuntimeException(sprintf('No se pudo crear el directorio [%s].', $descriptor['layout_directory']));
        }

        $contents = strtr($this->stub(), [
            '{{ title }}' => $descriptor['title'],
        ]);

        file_put_contents($layoutPath, $contents);

        $output->writeln('Layout creado correctamente.');
        $output->writeln(sprintf('  Nombre: %s', $descriptor['layout_name']));
        $output->writeln(sprintf('  Archivo: %s', $layoutPath));

        return 0;
    }

    /**
     * @return array{layout_directory: string, layout_path: string, layout_name: string, title: string}
     */
    private function buildDescriptor(string $name): array
    {
        $normalized = trim(str_replace(['\\', '.'], '/', $name), '/ ');
        $segments = array_values(array_filter(explode('/', $normalized)));

        if ($segments === []) {
            throw new RuntimeException('El nombre del layout no es valido.');
        }

        $layoutDirectory = $this->layoutDirectory();
        $targetDirectory = $layoutDirectory;

        if (count($segments) > 1) {
            $targetDirectory .= DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, array_slice($segments, 0, -1));
        }

        $layoutFile = array_pop($segments);
        $layoutFile = preg_replace('/\.php$/i', '', (string) $layoutFile) ?? (string) $layoutFile;
        $layoutFile = strtolower(str_replace('-', '_', $layoutFile));

        $layoutNameSegments = array_map(
            static fn(string $segment): string => strtolower(str_replace('-', '_', $segment)),
            [...$segments, $layoutFile],
        );

        return [
            'layout_directory' => $targetDirectory,
            'layout_path' => $targetDirectory . DIRECTORY_SEPARATOR . $layoutFile . '.php',
            'layout_name' => 'layouts.' . implode('.', $layoutNameSegments),
            'title' => $this->title($layoutFile),
        ];
    }

    private function stub(): string
    {
        $stubPath = dirname(__DIR__, 4) . DIRECTORY_SEPARATOR . 'stubs' . DIRECTORY_SEPARATOR . 'Layout' . DIRECTORY_SEPARATOR . 'Layout.stub';

        if (! is_file($stubPath)) {
            throw new RuntimeException(sprintf('El stub del layout no existe en [%s].', $stubPath));
        }

        $contents = file_get_contents($stubPath);

        if ($contents === false) {
            throw new RuntimeException(sprintf('No se pudo leer el stub [%s].', $stubPath));
        }

        return $contents;
    }

    private function layoutDirectory(): string
    {
        return $this->basePath
            . DIRECTORY_SEPARATOR . 'resources'
            . DIRECTORY_SEPARATOR . 'views'
            . DIRECTORY_SEPARATOR . 'layouts';
    }

    private function title(string $layoutFile): string
    {
        $spaced = str_replace(['-', '_'], ' ', $layoutFile);

        return ucwords($spaced);
    }
}
