<?php

declare(strict_types=1);

namespace Quantum\Console\Commands;

use Quantum\Console\Command;
use Quantum\Console\Input;
use Quantum\Console\Output;
use RuntimeException;

final class MakeControllerCommand extends Command
{
    public function name(): string
    {
        return 'make:controller';
    }

    public function description(): string
    {
        return 'Crea un controller nuevo a partir del stub del framework.';
    }

    public function usage(): string
    {
        return 'make:controller <name>';
    }

    public function category(): string
    {
        return 'Generators';
    }

    public function argumentsHelp(): array
    {
        return [
            'name' => 'Nombre del controller, por ejemplo `Admin/UserController`.',
        ];
    }

    public function handle(Input $input, Output $output): int
    {
        $name = $input->arguments()[0] ?? null;

        if (! is_string($name) || trim($name) === '') {
            $output->error('Debes indicar el nombre del controller. Ejemplo: php volt make:controller Admin/UserController');

            return 1;
        }

        $descriptor = $this->buildDescriptor($name);
        $targetPath = $descriptor['path'];

        if (is_file($targetPath)) {
            $output->error(sprintf('El controller ya existe en [%s].', $targetPath));

            return 1;
        }

        if (! is_dir($descriptor['directory']) && ! mkdir($descriptor['directory'], 0777, true) && ! is_dir($descriptor['directory'])) {
            throw new RuntimeException(sprintf('No se pudo crear el directorio [%s].', $descriptor['directory']));
        }

        $contents = strtr($this->stub(), [
            '{{ namespace }}' => $descriptor['namespace'],
            '{{ class }}' => $descriptor['class'],
            '{{ view }}' => $descriptor['view'],
        ]);

        file_put_contents($targetPath, $contents);

        $output->writeln('Controller creado correctamente.');
        $output->writeln(sprintf('  Clase: %s\\%s', $descriptor['namespace'], $descriptor['class']));
        $output->writeln(sprintf('  Archivo: %s', $targetPath));

        return 0;
    }

    /**
     * @return array{class: string, namespace: string, directory: string, path: string, view: string}
     */
    private function buildDescriptor(string $name): array
    {
        $normalized = trim(str_replace('/', '\\', $name), '\\ ');
        $segments = array_values(array_filter(explode('\\', $normalized)));

        if ($segments === []) {
            throw new RuntimeException('El nombre del controller no es valido.');
        }

        $class = array_pop($segments);
        $class = preg_replace('/\.php$/i', '', (string) $class) ?? (string) $class;

        if (! str_ends_with($class, 'Controller')) {
            $class .= 'Controller';
        }

        $namespace = 'App\\Controllers';
        $directory = $this->basePath . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'Controllers';

        if ($segments !== []) {
            $namespace .= '\\' . implode('\\', $segments);
            $directory .= DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, $segments);
        }

        $resource = substr($class, 0, -strlen('Controller'));
        $resource = $resource === '' ? $class : $resource;

        return [
            'class' => $class,
            'namespace' => $namespace,
            'directory' => $directory,
            'path' => $directory . DIRECTORY_SEPARATOR . $class . '.php',
            'view' => $this->viewName($segments, $resource),
        ];
    }

    private function stub(): string
    {
        $stubPath = dirname(__DIR__, 4) . DIRECTORY_SEPARATOR . 'stubs' . DIRECTORY_SEPARATOR . 'Controller' . DIRECTORY_SEPARATOR . 'Controller.stub';

        if (! is_file($stubPath)) {
            throw new RuntimeException(sprintf('El stub del controller no existe en [%s].', $stubPath));
        }

        $contents = file_get_contents($stubPath);

        if ($contents === false) {
            throw new RuntimeException(sprintf('No se pudo leer el stub [%s].', $stubPath));
        }

        return $contents;
    }

    /**
     * @param array<int, string> $segments
     */
    private function viewName(array $segments, string $resource): string
    {
        $parts = array_map(
            static fn(string $segment): string => strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $segment) ?? $segment),
            $segments,
        );
        $parts[] = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $resource) ?? $resource);

        return implode('.', $parts);
    }
}
