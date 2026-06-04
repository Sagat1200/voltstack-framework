<?php

declare(strict_types=1);

namespace Quantum\Http;

final class Request
{
    /**
     * @param array<string, mixed> $query
     * @param array<string, mixed> $request
     * @param array<string, mixed> $attributes
     * @param array<string, mixed> $cookies
     * @param array<string, mixed> $files
     * @param array<string, mixed> $server
     */
    public function __construct(
        private array $query = [],
        private array $request = [],
        private array $attributes = [],
        private array $cookies = [],
        private array $files = [],
        private array $server = [],
        private ?string $content = null,
    ) {}

    public static function capture(): self
    {
        $content = file_get_contents('php://input');

        return new self(
            $_GET,
            self::normalizeInput($_POST, $content ?: null, $_SERVER),
            [],
            $_COOKIE,
            $_FILES,
            $_SERVER,
            $content === false ? null : $content,
        );
    }

    /**
     * @param array<string, mixed> $query
     * @param array<string, mixed> $request
     * @param array<string, mixed> $attributes
     * @param array<string, mixed> $cookies
     * @param array<string, mixed> $files
     * @param array<string, mixed> $server
     */
    public static function create(
        string $uri,
        string $method = 'GET',
        array $query = [],
        array $request = [],
        array $attributes = [],
        array $cookies = [],
        array $files = [],
        array $server = [],
        ?string $content = null,
    ): self {
        $server = array_change_key_case($server, CASE_UPPER);
        $server['REQUEST_METHOD'] = strtoupper($method);
        $server['REQUEST_URI'] = $uri;

        return new self($query, $request, $attributes, $cookies, $files, $server, $content);
    }

    public function method(): string
    {
        return strtoupper((string) ($this->server['REQUEST_METHOD'] ?? 'GET'));
    }

    public function uri(): string
    {
        return (string) ($this->server['REQUEST_URI'] ?? '/');
    }

    public function path(): string
    {
        $path = parse_url($this->uri(), PHP_URL_PATH) ?: '/';

        return $path === '' ? '/' : $path;
    }

    /**
     * @return array<string, mixed>
     */
    public function query(): array
    {
        return $this->query;
    }

    /**
     * @return array<string, mixed>
     */
    public function request(): array
    {
        return $this->request;
    }

    /**
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return array_replace($this->query, $this->request, $this->attributes);
    }

    public function input(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $this->all();
        }

        return $this->all()[$key] ?? $default;
    }

    public function queryParam(string $key, mixed $default = null): mixed
    {
        return $this->query[$key] ?? $default;
    }

    public function post(string $key, mixed $default = null): mixed
    {
        return $this->request[$key] ?? $default;
    }

    public function attribute(string $key, mixed $default = null): mixed
    {
        return $this->attributes[$key] ?? $default;
    }

    public function setAttribute(string $key, mixed $value): void
    {
        $this->attributes[$key] = $value;
    }

    /**
     * @param array<string, mixed> $parameters
     */
    public function setRouteParameters(array $parameters): void
    {
        $this->attributes['_route_params'] = $parameters;

        foreach ($parameters as $key => $value) {
            $this->attributes[$key] = $value;
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function routeParameters(): array
    {
        $parameters = $this->attributes['_route_params'] ?? [];

        return is_array($parameters) ? $parameters : [];
    }

    public function routeParameter(string $key, mixed $default = null): mixed
    {
        return $this->routeParameters()[$key] ?? $default;
    }

    public function header(string $key, mixed $default = null): mixed
    {
        $upperKey = strtoupper(str_replace('-', '_', $key));
        $normalized = 'HTTP_' . strtoupper(str_replace('-', '_', $key));

        if ($upperKey === 'CONTENT_TYPE' || $upperKey === 'CONTENT_LENGTH') {
            return $this->server[$upperKey] ?? $default;
        }

        return $this->server[$normalized] ?? $default;
    }

    public function server(string $key, mixed $default = null): mixed
    {
        return $this->server[strtoupper($key)] ?? $default;
    }

    public function content(): ?string
    {
        return $this->content;
    }

    public function isJson(): bool
    {
        $contentType = strtoupper((string) $this->header('Content-Type', ''));

        return str_contains($contentType, 'APPLICATION/JSON');
    }

    public function expectsJson(): bool
    {
        $accept = strtoupper((string) $this->header('Accept', ''));
        $requestedWith = strtoupper((string) $this->header('X-Requested-With', ''));

        return $this->isJson()
            || str_contains($accept, 'APPLICATION/JSON')
            || in_array($requestedWith, ['XMLHTTPREQUEST', 'VOLTSTACK'], true);
    }

    /**
     * @param array<string, mixed> $input
     * @param array<string, mixed> $server
     * @return array<string, mixed>
     */
    private static function normalizeInput(array $input, ?string $content, array $server): array
    {
        if ($input !== []) {
            return $input;
        }

        $contentType = strtoupper((string) ($server['CONTENT_TYPE'] ?? ''));

        if ($content === null || ! str_contains($contentType, 'APPLICATION/JSON')) {
            return $input;
        }

        $decoded = json_decode($content, true);

        return is_array($decoded) ? $decoded : $input;
    }
}
