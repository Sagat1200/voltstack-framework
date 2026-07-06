<?php

declare(strict_types=1);

namespace Quantum\Routing\Dispatching;

use Quantum\Http\Request;
use Quantum\Http\ResponseFactory;
use Quantum\Routing\Exceptions\MissingRouteBindingException;
use Quantum\Routing\RouteMatch;
use Quantum\Routing\Router;

final class MissingRouteHandler
{
    public function __construct(
        private readonly Router $router,
        private readonly ResponseFactory $responses,
    ) {}

    public function handle(RouteMatch $match, Request $request, MissingRouteBindingException $exception): mixed
    {
        $missing = $match->route()->routeMetadata()->get('missing');

        if (! is_array($missing) || ! is_string($missing['type'] ?? null)) {
            throw $exception;
        }

        return match ($missing['type']) {
            'status' => $this->statusResponse($missing, $exception),
            'route' => $this->redirectResponse($missing, $request, $exception),
            default => throw $exception,
        };
    }

    /**
     * @param array<string, mixed> $missing
     */
    private function statusResponse(array $missing, MissingRouteBindingException $exception): mixed
    {
        $status = $missing['status'] ?? null;

        if (! is_int($status) || $status < 100 || $status > 599) {
            throw $exception;
        }

        return $this->responses->make('', $status);
    }

    /**
     * @param array<string, mixed> $missing
     */
    private function redirectResponse(array $missing, Request $request, MissingRouteBindingException $exception): mixed
    {
        $name = $missing['name'] ?? null;
        $status = $missing['status'] ?? 302;

        if (! is_string($name) || trim($name) === '' || ! is_int($status) || $status < 300 || $status > 399) {
            throw $exception;
        }

        return $this->responses->redirect(
            $this->router->route($name, $request->routeParameters()),
            $status,
        );
    }
}
