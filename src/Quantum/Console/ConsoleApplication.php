<?php

declare(strict_types=1);

namespace Quantum\Console;

use Quantum\Console\Commands\ServeCommand;
use Quantum\Console\Commands\RouteListCommand;
use Quantum\Console\Commands\MakeControllerCommand;
use Quantum\Console\Commands\MakeComponentCommand;
use Quantum\Console\Commands\MakePageCommand;
use Quantum\Console\Commands\MakeViewCommand;
use Quantum\Console\Commands\MakeActionCommand;
use Quantum\Console\Commands\ViewCacheCommand;
use Quantum\Console\Commands\ViewClearCommand;
use Quantum\Console\Exceptions\CommandNotFoundException;
use Throwable;

final class ConsoleApplication
{
    /**
     * @var array<string, Command>
     */
    private array $commands = [];

    /**
     * @param iterable<int, Command> $commands
     */
    public function __construct(
        private readonly string $basePath,
        iterable $commands = [],
    ) {
        $registered = false;

        foreach ($commands as $command) {
            $this->add($command);
            $registered = true;
        }

        if (! $registered) {
            $this->add(new ServeCommand($basePath));
            $this->add(new RouteListCommand($basePath));
            $this->add(new MakeControllerCommand($basePath));
            $this->add(new MakeComponentCommand($basePath));
            $this->add(new MakePageCommand($basePath));
            $this->add(new MakeViewCommand($basePath));
            $this->add(new MakeActionCommand($basePath));
            $this->add(new ViewCacheCommand($basePath));
            $this->add(new ViewClearCommand($basePath));
        }
    }

    public function add(Command $command): void
    {
        $this->commands[$command->name()] = $command;
    }

    /**
     * @param array<int, string> $argv
     */
    public function run(array $argv): int
    {
        $input = Input::fromArgv($argv);
        $output = new Output();

        if ($input->hasOption('help') || in_array($input->command(), [null, 'help', 'list'], true)) {
            $this->renderHelp($output, $input->script());

            return 0;
        }

        try {
            return $this->command($input->command())->handle($input, $output);
        } catch (CommandNotFoundException $exception) {
            $output->error($exception->getMessage());
            $output->writeln();
            $this->renderHelp($output, $input->script());

            return 1;
        } catch (Throwable $exception) {
            $output->error(sprintf('VoltStack console error: %s', $exception->getMessage()));

            return 1;
        }
    }

    private function command(?string $name): Command
    {
        if ($name === null || ! isset($this->commands[$name])) {
            throw new CommandNotFoundException(sprintf('Command [%s] is not defined.', $name ?? 'null'));
        }

        return $this->commands[$name];
    }

    private function renderHelp(Output $output, string $script): void
    {
        $output->writeln('VoltStack Console');
        $output->writeln();
        $output->writeln(sprintf('Usage: php %s <command> [options]', basename($script)));
        $output->writeln();
        $output->writeln('Available commands:');

        foreach ($this->commands as $command) {
            $output->writeln(sprintf('  %-12s %s', $command->name(), $command->description()));
        }

        $output->writeln();
        $output->writeln(sprintf('Base path: %s', $this->basePath));
    }
}
