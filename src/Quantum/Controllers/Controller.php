<?php

declare(strict_types=1);

namespace Quantum\Controllers;

use Quantum\Http\JsonResponse;
use Quantum\Http\RedirectResponse;
use Quantum\Http\Response;
use Quantum\Validation\Validator;
use Quantum\View\View;

abstract class Controller
{
    /**
     * @param array<string, mixed> $data
     */
    protected function view(string $name, array $data = []): View
    {
        return view($name, $data);
    }

    protected function response(string $content = '', int $statusCode = 200, array $headers = []): Response
    {
        return response($content, $statusCode, $headers);
    }

    protected function json(mixed $data, int $statusCode = 200, array $headers = []): JsonResponse
    {
        return response()->json($data, $statusCode, $headers);
    }

    protected function redirect(string $location, int $statusCode = 302, array $headers = []): RedirectResponse
    {
        return response()->redirect($location, $statusCode, $headers);
    }

    protected function validate(array $data, array $rules): array
    {
        return app(Validator::class)->validate($data, $rules);
    }
}
