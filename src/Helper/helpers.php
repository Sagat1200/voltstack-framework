<?php

declare(strict_types=1);

use Quantum\Config\ConfigRepository;
use VoltStack\Framework\Application;

if (! function_exists('app')) {
    function app(?string $abstract = null, array $parameters = []): mixed
    {
        $app = Application::getInstance();

        if ($app === null) {
            throw new RuntimeException('The VoltStack application instance has not been bootstrapped.');
        }

        if ($abstract === null) {
            return $app;
        }

        return $app->make($abstract, $parameters);
    }
}

if (! function_exists('config')) {
    function config(?string $key = null, mixed $default = null): mixed
    {
        /** @var Application $app */
        $app = app();
        /** @var ConfigRepository $repository */
        $repository = $app->make(ConfigRepository::class);

        if ($key === null) {
            return $repository->all();
        }

        return $repository->get($key, $default);
    }
}
