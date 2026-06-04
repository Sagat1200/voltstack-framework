<?php

declare(strict_types=1);

namespace VoltStack\Framework\Exceptions;

use Quantum\Http\JsonResponse;
use Quantum\Http\Request;
use Quantum\Http\Response;
use Quantum\Routing\Exceptions\RouteNotFoundException;
use Quantum\Validation\Exceptions\ValidationException;
use Throwable;
use VoltStack\Framework\Contracts\ExceptionHandler as ExceptionHandlerContract;

final class ExceptionHandler implements ExceptionHandlerContract
{
    public function render(Request $request, Throwable $exception): Response
    {
        $status = $this->statusCode($exception);

        if ($request->expectsJson()) {
            return $this->jsonResponse($exception, $status);
        }

        return new Response($this->htmlResponse($exception, $status), $status, [
            'Content-Type' => 'text/html; charset=UTF-8',
        ]);
    }

    private function statusCode(Throwable $exception): int
    {
        return match (true) {
            $exception instanceof RouteNotFoundException => 404,
            $exception instanceof ValidationException => 422,
            default => 500,
        };
    }

    private function jsonResponse(Throwable $exception, int $status): JsonResponse
    {
        $payload = [
            'message' => $exception instanceof ValidationException
                ? $exception->getMessage()
                : ($status === 404 ? 'Not Found' : 'Server Error'),
        ];

        if ($exception instanceof ValidationException) {
            $payload['errors'] = $exception->errors();
        }

        return new JsonResponse($payload, $status);
    }

    private function htmlResponse(Throwable $exception, int $status): string
    {
        $title = match ($status) {
            404 => 'Page Not Found',
            422 => 'Validation Failed',
            default => 'Server Error',
        };

        $body = $exception instanceof ValidationException
            ? $this->renderValidationErrors($exception)
            : '<p>' . ($status === 404 ? 'The requested page could not be found.' : 'An unexpected error occurred while processing the request.') . '</p>';

        return sprintf(
            '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>%1$s</title><style>body{font-family:Arial,sans-serif;background:#0f172a;color:#e2e8f0;padding:40px;}main{max-width:720px;margin:0 auto;background:#111827;border:1px solid #334155;border-radius:12px;padding:32px;}h1{margin-top:0;}ul{padding-left:20px;}code{background:#1e293b;padding:2px 6px;border-radius:4px;}</style></head><body><main><h1>%1$s</h1>%2$s</main></body></html>',
            htmlspecialchars($title, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            $body,
        );
    }

    private function renderValidationErrors(ValidationException $exception): string
    {
        $items = [];

        foreach ($exception->errors() as $field => $messages) {
            $items[] = sprintf(
                '<li><strong>%s</strong>: %s</li>',
                htmlspecialchars($field, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
                htmlspecialchars(implode(' ', $messages), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            );
        }

        return '<p>The submitted data did not pass validation.</p><ul>' . implode('', $items) . '</ul>';
    }
}
