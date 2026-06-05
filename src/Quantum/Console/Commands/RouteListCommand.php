<?php

declare(strict_types=1);

namespace Quantum\Console\Commands;

use Closure;
use Quantum\Console\Command;
use Quantum\Console\Input;
use Quantum\Console\Output;
use Quantum\Routing\Route;
use Quantum\Routing\Router;
use RuntimeException;
use VoltStack\Framework\Application;
use VoltStack\Runtime\Component\Component;

final class RouteListCommand extends Command
{
    public function name(): string
    {
        return 'route:list';
    }

    public function description(): string
    {
        return 'Muestra las rutas registradas por la aplicacion.';
    }

    public function handle(Input $input, Output $output): int
    {
        $app = $this->bootstrapApplication();
        $router = $app->make(Router::class);
        $routes = $router->routes();

        if ($routes === []) {
            $output->writeln('No hay rutas registradas.');

            return 0;
        }

        $output->writeln('VoltStack Routes');
        $output->writeln();
        $output->writeln(sprintf('%-18s %-24s %s', 'Method', 'URI', 'Action'));
        $output->writeln(str_repeat('-', 78));

        foreach ($routes as $route) {
            $output->writeln(sprintf(
                '%-18s %-24s %s',
                implode('|', $route->methods()),
                $route->uri(),
                $this->formatAction($route),
            ));
        }

        $output->writeln();
        $output->writeln(sprintf('Total routes: %d', count($routes)));

        return 0;
    }

    private function bootstrapApplication(): Application
    {
        $bootstrapPath = $this->basePath . DIRECTORY_SEPARATOR . 'bootstrap' . DIRECTORY_SEPARATOR . 'app.php';

        if (! is_file($bootstrapPath)) {
            throw new RuntimeException(sprintf('The application bootstrap file could not be found at [%s].', $bootstrapPath));
        }

        $app = require $bootstrapPath;

        if (! $app instanceof Application) {
            throw new RuntimeException('The application bootstrap file must return a VoltStack application instance.');
        }

        return $app;
    }

    private function formatAction(Route $route): string
    {
        $action = $route->action();

        if ($action instanceof Closure) {
            return 'Closure';
        }

        if (is_array($action) && count($action) === 2) {
            $class = is_object($action[0]) ? $action[0]::class : (string) $action[0];

            return sprintf('%s@%s', $class, (string) $action[1]);
        }

        if (is_string($action)) {
            if (class_exists($action) && is_subclass_of($action, Component::class)) {
                return sprintf('%s [component]', $action);
            }

            if (class_exists($action)) {
                return sprintf('%s@__invoke', $action);
            }

            return $action;
        }

        if (is_object($action)) {
            return $action::class;
        }

        return get_debug_type($action);
    }
}