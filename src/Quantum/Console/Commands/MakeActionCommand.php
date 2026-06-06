<?php

declare(strict_types=1);

namespace Quantum\Console\Commands;

use Quantum\Console\Command;
use Quantum\Console\Input;
use Quantum\Console\Output;
use RuntimeException;

final class MakeActionCommand extends Command
{
    public function name(): string
    {
        return 'make:action';
    }

    public function description(): string
    {
        return 'Crea una action nueva a partir del stub del framework.';
    }

    public function handle(Input $input, Output $output): int
    {
        $name = $input->arguments()[0] ?? null;

        if (! is_string($name) || trim($name) === '') {
            $output->error('Debes indicar el nombre de la action. Ejemplo: php volt make:action Admin/CreateUserAction');

            return 1;
        }

        $descriptor = $this->buildDescriptor($name);
        $targetPath = $descriptor['path'];

        if (is_file($targetPath)) {
            $output->error(sprintf('La action ya existe en [%s].', $targetPath));

            return 1;
        }

        if (! is_dir($descriptor['directory']) && ! mkdir($descriptor['directory'], 0777, true) && ! is_dir($descriptor['directory'])) {
            throw new RuntimeException(sprintf('No se pudo crear el directorio [%s].', $descriptor['directory']));
        }

        $contents = strtr($this->stub(), [
            '{{ namespace }}' => $descriptor['namespace'],
            '{{ class }}' => $descriptor['class'],
        ]);

        file_put_contents($targetPath, $contents);

        $output->writeln('Action creada correctamente.');
        $output->writeln(sprintf('  Clase: %s\\%s', $descriptor['namespace'], $descriptor['class']));
        $output->writeln(sprintf('  Archivo: %s', $targetPath));

        return 0;
    }

    /**
     * @return array{class: string, namespace: string, directory: string, path: string}
     */
    private function buildDescriptor(string $name): array
    {
        $normalized = trim(str_replace('/', '\\', $name), '\\ ');
        $segments = array_values(array_filter(explode('\\', $normalized)));

        if ($segments === []) {
            throw new RuntimeException('El nombre de la action no es valido.');
        }

        $class = array_pop($segments);
        $class = preg_replace('/\.php$/i', '', (string) $class) ?? (string) $class;

        if (! str_ends_with($class, 'Action')) {
            $class .= 'Action';
        }

        $namespace = 'App\\Actions';
        $directory = $this->basePath . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'Actions';

        if ($segments !== []) {
            $namespace .= '\\' . implode('\\', $segments);
            $directory .= DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, $segments);
        }

        return [
            'class' => $class,
            'namespace' => $namespace,
            'directory' => $directory,
            'path' => $directory . DIRECTORY_SEPARATOR . $class . '.php',
        ];
    }

    private function stub(): string
    {
        $stubPath = dirname(__DIR__, 4) . DIRECTORY_SEPARATOR . 'stubs' . DIRECTORY_SEPARATOR . 'Action' . DIRECTORY_SEPARATOR . 'Action.stub';

        if (! is_file($stubPath)) {
            throw new RuntimeException(sprintf('El stub de la action no existe en [%s].', $stubPath));
        }

        $contents = file_get_contents($stubPath);

        if ($contents === false) {
            throw new RuntimeException(sprintf('No se pudo leer el stub [%s].', $stubPath));
        }

        return $contents;
    }
}