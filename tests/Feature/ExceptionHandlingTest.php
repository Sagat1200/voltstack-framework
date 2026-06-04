<?php

declare(strict_types=1);

namespace VoltStack\Test\Feature;

use PHPUnit\Framework\TestCase;
use Quantum\Http\Request;
use Quantum\HttpKernel\HttpKernel;
use Quantum\Routing\Router;
use Quantum\Validation\Validator;
use VoltStack\Framework\Application;
use VoltStack\Framework\Contracts\Kernel as KernelContract;

final class ExceptionHandlingTest extends TestCase
{
    public function test_it_renders_a_html_404_response_for_missing_routes(): void
    {
        $app = new Application(sys_get_temp_dir());

        $response = $app->make(HttpKernel::class)->handle(Request::create('/missing'));

        self::assertSame(404, $response->statusCode());
        self::assertStringContainsString('Page Not Found', $response->content());
        self::assertSame('text/html; charset=UTF-8', $response->headers()['Content-Type']);
    }

    public function test_it_renders_json_validation_errors_when_the_request_expects_json(): void
    {
        $app = new Application(sys_get_temp_dir());
        $router = $app->make(Router::class);
        $router->post('/validate', function (Validator $validator): array {
            $validator->validate([
                'title' => '',
            ], [
                'title' => ['required', 'string', 'min:3'],
            ]);

            return ['ok' => true];
        });

        $response = $app->make(KernelContract::class)->handle(Request::create(
            '/validate',
            'POST',
            [],
            ['title' => ''],
            [],
            [],
            [],
            [
                'HTTP_ACCEPT' => 'application/json',
            ],
        ));

        self::assertSame(422, $response->statusCode());
        self::assertSame('application/json; charset=UTF-8', $response->headers()['Content-Type']);

        /** @var array<string, mixed> $payload */
        $payload = json_decode($response->content(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame('The given data was invalid.', $payload['message']);
        self::assertArrayHasKey('title', $payload['errors']);
    }
}
