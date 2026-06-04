<?php

declare(strict_types=1);

namespace VoltStack\Framework;

use Quantum\Config\ConfigRepository;
use Quantum\Container\Container;
use Quantum\Container\Contracts\ContainerInterface;
use Quantum\Http\ResponseFactory;
use Quantum\HttpKernel\HttpKernel;
use Quantum\Routing\Router;
use Quantum\View\PhpViewEngine;
use Quantum\View\ViewFactory;
use VoltStack\Runtime\Component\ComponentManager;
use VoltStack\Runtime\Hydration\Dehydrator;
use VoltStack\Runtime\Hydration\Hydrator;
use VoltStack\Runtime\Protocol\Checksum;
use VoltStack\Runtime\Protocol\ProtocolController;
class Application extends Container
{
    protected static ?self $instance = null;

    /**
     * @var array<class-string<ServiceProvider>, ServiceProvider>
     */
    protected array $providers = [];

    protected bool $booted = false;

    public function __construct(protected string $basePath)
    {
        $this->basePath = rtrim($basePath, '\\/');

        static::setInstance($this);
        $this->registerBaseBindings();
    }

    public static function setInstance(self $app): void
    {
        static::$instance = $app;
    }

    public static function getInstance(): ?self
    {
        return static::$instance;
    }

    public function basePath(string $path = ''): string
    {
        return $this->joinPath($this->basePath, $path);
    }

    public function configPath(string $path = ''): string
    {
        return $this->joinPath($this->basePath('config'), $path);
    }

    public function resourcePath(string $path = ''): string
    {
        return $this->joinPath($this->basePath('resources'), $path);
    }

    public function viewPath(string $path = ''): string
    {
        return $this->joinPath($this->resourcePath('views'), $path);
    }

    public function registerBaseBindings(): void
    {
        $this->instance(self::class, $this);
        $this->instance(Container::class, $this);
        $this->instance(ContainerInterface::class, $this);
        $this->instance('path.base', $this->basePath);
        $this->instance('path.resources', $this->resourcePath());
        $this->instance('path.views', $this->viewPath());

        if (! isset($this->instances[ConfigRepository::class])) {
            $this->instance(ConfigRepository::class, new ConfigRepository());
        }

        if (! isset($this->bindings[PhpViewEngine::class])) {
            $this->singleton(PhpViewEngine::class);
        }

        if (! isset($this->bindings[ViewFactory::class])) {
            $this->singleton(ViewFactory::class, fn (Application $app) => new ViewFactory(
                $app->make(PhpViewEngine::class),
                [$app->viewPath()],
            ));
        }

        if (! isset($this->bindings[ResponseFactory::class])) {
            $this->singleton(ResponseFactory::class);
        }

        if (! isset($this->bindings[Checksum::class])) {
            $this->singleton(Checksum::class, fn (Application $app) => new Checksum($app));
        }

        if (! isset($this->bindings[Dehydrator::class])) {
            $this->singleton(Dehydrator::class, fn (Application $app) => new Dehydrator(
                $app->make(Checksum::class),
            ));
        }

        if (! isset($this->bindings[Hydrator::class])) {
            $this->singleton(Hydrator::class, fn (Application $app) => new Hydrator(
                $app->make(Dehydrator::class),
            ));
        }

        if (! isset($this->bindings[ComponentManager::class])) {
            $this->singleton(ComponentManager::class, fn (Application $app) => new ComponentManager(
                $app,
                $app->make(Hydrator::class),
                $app->make(Dehydrator::class),
            ));
        }

        if (! isset($this->bindings[Router::class])) {
            $this->singleton(Router::class, function (Application $app): Router {
                $router = new Router($app);
                $router->post('/_volt/action', ProtocolController::class);

                return $router;
            });
        }

        if (! isset($this->bindings[HttpKernel::class])) {
            $this->singleton(HttpKernel::class, fn (Application $app) => new HttpKernel(
                $app,
                $app->make(Router::class),
            ));
        }
    }

    public function config(?string $key = null, mixed $default = null): mixed
    {
        /** @var ConfigRepository $config */
        $config = $this->make(ConfigRepository::class);

        return $config->get($key, $default);
    }

    public function register(ServiceProvider|string $provider): ServiceProvider
    {
        if (is_string($provider)) {
            /** @var ServiceProvider $provider */
            $provider = $this->make($provider);
        }

        $className = $provider::class;

        if (isset($this->providers[$className])) {
            return $this->providers[$className];
        }

        $provider->register();
        $this->providers[$className] = $provider;

        if ($this->booted) {
            $provider->boot();
        }

        return $provider;
    }

    public function boot(): void
    {
        if ($this->booted) {
            return;
        }

        foreach ($this->providers as $provider) {
            $provider->boot();
        }

        $this->booted = true;
    }

    public function isBooted(): bool
    {
        return $this->booted;
    }

    /**
     * @return array<class-string<ServiceProvider>, ServiceProvider>
     */
    public function getProviders(): array
    {
        return $this->providers;
    }

    protected function joinPath(string $basePath, string $path = ''): string
    {
        if ($path === '') {
            return $basePath;
        }

        return $basePath . DIRECTORY_SEPARATOR . ltrim($path, '\\/');
    }
}
