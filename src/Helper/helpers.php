<?php

declare(strict_types=1);

use Quantum\Config\ConfigRepository;
use Quantum\Http\Response;
use Quantum\Http\ResponseFactory;
use Quantum\View\View;
use Quantum\View\ViewFactory;
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

if (! function_exists('view')) {
    function view(string $name, array $data = []): View
    {
        /** @var ViewFactory $factory */
        $factory = app(ViewFactory::class);

        return $factory->make($name, $data);
    }
}

if (! function_exists('response')) {
    function response(string $content = '', int $statusCode = 200, array $headers = []): ResponseFactory|Response
    {
        /** @var ResponseFactory $factory */
        $factory = app(ResponseFactory::class);

        if (func_num_args() === 0) {
            return $factory;
        }

        return $factory->make($content, $statusCode, $headers);
    }
}

if (! function_exists('e')) {
    function e(string|int|float|null $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
