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
            policy: [
                'document' => $this->documentContract($request),
                'navigation' => $this->navigationMode($request),
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

    private function documentContract(Request $request): ?string
    {
        $document = $request->routeRuntimeMeta('document');

        if (! is_string($document) || trim($document) === '') {
            $document = $request->routeRuntimeMeta('contract');
        }

        if (! is_string($document) || trim($document) === '') {
            $mode = $request->routeRuntimeMeta('mode');

            if (is_string($mode) && $this->supportsDocumentContract($mode)) {
                $document = $mode;
            }
        }

        if (! is_string($document) || trim($document) === '') {
            return null;
        }

        return $this->normalizeDocumentContract($document);
    }

    private function navigationMode(Request $request): ?string
    {
        $navigationMode = $request->routeRuntimeMeta('navigation');

        if (! is_string($navigationMode) || trim($navigationMode) === '') {
            $navigationMode = $request->routeRuntimeMeta('navigationMode');
        }

        if (! is_string($navigationMode) || trim($navigationMode) === '') {
            return null;
        }

        return strtolower(trim($navigationMode));
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
     * @return array{code: int, message: string, reason?: string}|null
     */
    private function error(Response $response): ?array
    {
        $status = $response->statusCode();

        if ($status < 400) {
            return null;
        }

        $error = [
            'code' => $status,
            'message' => $this->messageForStatus($status),
        ];

        $reason = $response->headers()['X-Volt-Error-Code'] ?? null;

        if (is_string($reason) && trim($reason) !== '') {
            $error['reason'] = trim($reason);
        }

        return $error;
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

    private function supportsDocumentContract(string $mode): bool
    {
        return in_array(strtolower(trim($mode)), [
            'spa',
            'reload',
            'reload-only',
            'static',
            'non-spa',
            'document',
            'interactive',
            'reactive',
        ], true);
    }

    private function normalizeDocumentContract(string $document): string
    {
        return match (strtolower(trim($document))) {
            'reload-only', 'static', 'non-spa', 'document' => 'reload',
            'interactive', 'reactive' => 'spa',
            default => strtolower(trim($document)),
        };
    }
}
