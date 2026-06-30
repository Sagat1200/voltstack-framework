<?php

declare(strict_types=1);

namespace Quantum\Routing;

use Quantum\Http\Request;
use Quantum\Http\Response;

final class SpaNavigationPayloadFactory
{
    public function fromRequestAndResponse(Request $request, Response $response): SpaNavigationPayload
    {
        $redirect = $this->redirect($response);

        return new SpaNavigationPayload(
            navigation: [
                'target' => $redirect['location'] ?? $request->uri(),
                'method' => strtoupper($request->method()),
            ],
            screen: [
                'route' => $this->routeName($request),
            ],
            runtime: [
                'layout' => $this->layout($request),
                'transition' => $this->transition($request),
                'hydrate' => $this->hydrate($request),
            ],
            redirect: $redirect,
            error: $this->error($response),
        );
    }

    private function routeName(Request $request): ?string
    {
        $name = $request->routeMeta('name');

        if (! is_string($name) || trim($name) === '') {
            return null;
        }

        return trim($name);
    }

    private function layout(Request $request): ?string
    {
        $layout = $request->routeRuntimeMeta('layout');

        if (! is_string($layout) || trim($layout) === '') {
            return null;
        }

        return trim($layout);
    }

    private function transition(Request $request): ?string
    {
        $transition = $request->routeRuntimeMeta('transition');

        if (is_string($transition) && trim($transition) !== '') {
            return trim($transition);
        }

        if (! is_array($transition)) {
            return null;
        }

        $name = $transition['name'] ?? $transition['transition'] ?? null;

        if (! is_string($name) || trim($name) === '') {
            return null;
        }

        return trim($name);
    }

    private function hydrate(Request $request): ?bool
    {
        $hydrate = $request->routeRuntimeMeta('hydrate');

        if (is_bool($hydrate)) {
            return $hydrate;
        }

        if (! is_array($hydrate) || ! array_key_exists('enabled', $hydrate)) {
            return null;
        }

        return is_bool($hydrate['enabled']) ? $hydrate['enabled'] : null;
    }

    /**
     * @return array{location: string, status: int}|null
     */
    private function redirect(Response $response): ?array
    {
        $location = $response->headers()['Location'] ?? null;

        if (! is_string($location) || trim($location) === '') {
            return null;
        }

        $status = $response->statusCode();

        if ($status < 300 || $status >= 400) {
            return null;
        }

        return [
            'location' => trim($location),
            'status' => $status,
        ];
    }

    /**
     * @return array{code: int, message: string}|null
     */
    private function error(Response $response): ?array
    {
        $status = $response->statusCode();

        if ($status < 400) {
            return null;
        }

        return [
            'code' => $status,
            'message' => $this->messageForStatus($status),
        ];
    }

    private function messageForStatus(int $status): string
    {
        return match ($status) {
            400 => 'Bad Request',
            401 => 'Unauthorized',
            403 => 'Forbidden',
            404 => 'Not Found',
            405 => 'Method Not Allowed',
            419 => 'Page Expired',
            422 => 'Unprocessable Entity',
            429 => 'Too Many Requests',
            default => $status >= 500 ? 'Server Error' : 'HTTP Error',
        };
    }
}
