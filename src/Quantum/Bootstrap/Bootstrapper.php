<?php

declare(strict_types=1);

namespace Quantum\Bootstrap;

use Quantum\Config\ConfigRepository;
use VoltStack\Framework\Application;
use VoltStack\Framework\ServiceProvider;

final class Bootstrapper
{
    public function __construct(private readonly Application $app)
    {
    }

    /**
     * @param array<int, class-string<ServiceProvider>|ServiceProvider> $providers
     */
    public function bootstrap(array $providers = []): Application
    {
        $this->app->registerBaseBindings();
        $this->loadConfiguration();

        foreach ($providers as $provider) {
            $this->app->register($provider);
        }

        $this->app->boot();

        return $this->app;
    }

    public function loadConfiguration(?string $configPath = null): void
    {
        /** @var ConfigRepository $config */
        $config = $this->app->make(ConfigRepository::class);
        $config->loadPath($configPath ?? $this->app->configPath());
    }

    public function loadRoutes(string|callable $routes): void
    {
        $router = $this->app->make(\Quantum\Routing\Router::class);

        if ($router->canServeCompiledRoutesWithoutLiveRegistration()) {
            return;
        }

        $loader = is_string($routes) ? require $routes : $routes;

        if (! is_callable($loader)) {
            throw new \RuntimeException('Bootstrapper::loadRoutes expects a route file that returns a callable or a callable loader.');
        }

        $closure = \Closure::fromCallable($loader);
        $parameters = (new \ReflectionFunction($closure))->getNumberOfParameters() === 0
            ? []
            : [$router];

        $loader(...$parameters);
    }
}
