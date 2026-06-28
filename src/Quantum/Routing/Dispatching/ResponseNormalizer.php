<?php

declare(strict_types=1);

namespace Quantum\Routing\Dispatching;

use Quantum\Http\JsonResponse;
use Quantum\Http\Response;
use Quantum\View\View;
use VoltStack\Runtime\Component\Component;
use VoltStack\Runtime\Component\ComponentManager;

final class ResponseNormalizer
{
    public function __construct(private readonly ComponentManager $components) {}

    public function normalize(mixed $response): Response
    {
        if ($response instanceof Response) {
            return $response;
        }

        if (is_array($response)) {
            return new JsonResponse($response);
        }

        if ($response instanceof View) {
            return new Response($response->render());
        }

        if ($response instanceof Component) {
            return new Response($this->components->renderRoot($response));
        }

        if (is_string($response) || is_numeric($response)) {
            return new Response((string) $response);
        }

        if ($response === null) {
            return new Response('');
        }

        return new JsonResponse($response);
    }
}
