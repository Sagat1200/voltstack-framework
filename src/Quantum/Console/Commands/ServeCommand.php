<?php

declare(strict_types=1);

namespace Quantum\Console\Commands;

use Quantum\Console\Command;
use Quantum\Console\Input;
use Quantum\Console\Output;
use RuntimeException;

final class ServeCommand extends Command
{
    /**
     * @param null|callable(string): int $runner
     */
    public function __construct(
        string $basePath,
        private readonly mixed $runner = null,
    ) {
        parent::__construct($basePath);
    }

    public function name(): string
    {
        return 'serve';
    }

    public function description(): string
    {
        return 'Levanta el servidor de desarrollo de VoltStack.';
    }

    public function usage(): string
    {
        return 'serve [--host=127.0.0.1] [--port=8000] [--dry-run]';
    }

    public function category(): string
    {
        return 'Development';
    }

    public function optionsHelp(): array
    {
        return [
            '--host=' => 'Define la interfaz o IP del servidor de desarrollo.',
            '--port=' => 'Define el puerto HTTP a utilizar.',
            '--dry-run' => 'Imprime el comando `php -S` sin ejecutarlo.',
        ];
    }

    public function handle(Input $input, Output $output): int
    {
        $host = (string) $input->option('host', '127.0.0.1');
        $port = $this->normalizePort($input->option('port', '8000'));
        $publicPath = $this->publicPath();
        $routerPath = $this->routerPath();
        $command = $this->serverCommand($host, $port, $publicPath, $routerPath);

        $output->writeln('VoltStack development server');
        $output->writeln(sprintf('  URL: http://%s:%d', $host, $port));
        $output->writeln(sprintf('  Public path: %s', $publicPath));
        $output->writeln('  Stop: Ctrl+C');
        $output->writeln();

        if ($input->hasOption('dry-run')) {
            $output->writeln($command);

            return 0;
        }

        if (is_callable($this->runner)) {
            return (int) call_user_func($this->runner, $command);
        }

        passthru($command, $exitCode);

        return $exitCode;
    }

    private function publicPath(): string
    {
        $publicPath = $this->basePath . DIRECTORY_SEPARATOR . 'public';
        $indexPath = $publicPath . DIRECTORY_SEPARATOR . 'index.php';

        if (! is_dir($publicPath)) {
            throw new RuntimeException(sprintf('The public directory could not be found at [%s].', $publicPath));
        }

        if (! is_file($indexPath)) {
            throw new RuntimeException(sprintf('The public index file could not be found at [%s].', $indexPath));
        }

        return $publicPath;
    }

    private function routerPath(): string
    {
        $routerPath = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'Support' . DIRECTORY_SEPARATOR . 'server.php';

        if (! is_file($routerPath)) {
            throw new RuntimeException(sprintf('The development server router could not be found at [%s].', $routerPath));
        }

        return $routerPath;
    }

    private function normalizePort(string|bool|null $port): int
    {
        $value = is_string($port) ? $port : '8000';

        if (! ctype_digit($value)) {
            throw new RuntimeException(sprintf('The given port [%s] is invalid.', $value));
        }

        $portNumber = (int) $value;

        if ($portNumber < 1 || $portNumber > 65535) {
            throw new RuntimeException(sprintf('The given port [%d] is outside the valid range.', $portNumber));
        }

        return $portNumber;
    }

    private function serverCommand(string $host, int $port, string $publicPath, string $routerPath): string
    {
        return sprintf(
            '%s -S %s -t %s %s',
            escapeshellarg(PHP_BINARY),
            escapeshellarg(sprintf('%s:%d', $host, $port)),
            escapeshellarg($publicPath),
            escapeshellarg($routerPath),
        );
    }
}
