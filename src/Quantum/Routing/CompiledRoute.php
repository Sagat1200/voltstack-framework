<?php

declare(strict_types=1);

namespace Quantum\Routing;

use Quantum\Http\Request;
use Quantum\HttpKernel\CompiledMiddlewarePipeline;

class CompiledRoute
{
    private RouteDefinition $definition;
    private RouteMetadata $metadata;
    private CompiledMiddlewarePipeline $pipeline;

    private string $pattern;
    private ?string $domainPattern = null;

    /**
     * @var array<int, string>
     */
    private array $parameterNames;

    /**
     * @var array<int, string>
     */
    private array $domainParameterNames = [];

    /**
     * @var array<string, string>
     */
    private array $compiledConstraints = [];

    public function __construct(RouteDefinition $definition)
    {
        $this->definition = $definition;
        $this->recompile();
    }

    public function definition(): RouteDefinition
    {
        return $this->definition;
    }

    /**
     * @return array<int, string>
     */
    public function methods(): array
    {
        return $this->definition->methods();
    }

    public function uri(): string
    {
        return $this->definition->uri();
    }

    public function action(): mixed
    {
        return $this->definition->action();
    }

    public function routeName(): ?string
    {
        return $this->definition->name();
    }

    public function routeDomain(): ?string
    {
        return $this->definition->domain();
    }

    /**
     * @return array<int, mixed>
     */
    public function routeMiddlewares(): array
    {
        return $this->pipeline->middlewares();
    }

    public function routeMetadata(): RouteMetadata
    {
        return $this->metadata;
    }

    public function replaceRouteMetadata(RouteMetadata $metadata): void
    {
        $this->metadata = $metadata;
    }

    public function routePipeline(): CompiledMiddlewarePipeline
    {
        return $this->pipeline;
    }

    public function pattern(): string
    {
        return $this->pattern;
    }

    public function domainPattern(): ?string
    {
        return $this->domainPattern;
    }

    /**
     * @return array<int, string>
     */
    public function parameterNames(): array
    {
        return $this->parameterNames;
    }

    /**
     * @return array<string, string>
     */
    public function compiledConstraints(): array
    {
        return $this->compiledConstraints;
    }

    public function allowsMethod(string $method): bool
    {
        return in_array(strtoupper($method), $this->methods(), true);
    }

    /**
     * @return array<string, string>|null
     */
    public function matches(Request $request): ?array
    {
        if (! $this->allowsMethod($request->method())) {
            return null;
        }

        return $this->matchTarget($request->host(), $request->path());
    }

    /**
     * @return array<string, string>|null
     */
    public function matchPath(string $path): ?array
    {
        if (! preg_match($this->pattern, $path, $matches)) {
            return null;
        }

        array_shift($matches);

        $parameters = [];

        foreach ($this->parameterNames as $index => $name) {
            $parameters[$name] = $matches[$index] ?? null;
        }

        return $parameters;
    }

    /**
     * @return array<string, string>|null
     */
    public function matchHost(string $host): ?array
    {
        if ($this->domainPattern === null) {
            return [];
        }

        if (! preg_match($this->domainPattern, strtolower($host), $matches)) {
            return null;
        }

        array_shift($matches);

        $parameters = [];

        foreach ($this->domainParameterNames as $index => $name) {
            $parameters[$name] = $matches[$index] ?? null;
        }

        return $parameters;
    }

    /**
     * @return array<string, string>|null
     */
    public function matchTarget(string $host, string $path): ?array
    {
        $domainParameters = $this->matchHost($host);

        if ($domainParameters === null) {
            return null;
        }

        $pathParameters = $this->matchPath($path);

        if ($pathParameters === null) {
            return null;
        }

        return [
            ...$domainParameters,
            ...$pathParameters,
        ];
    }

    protected function replaceDefinition(RouteDefinition $definition): void
    {
        $this->definition = $definition;
        $this->recompile();
    }

    /**
     * @return array{0: string, 1: array<int, string>}
     */
    private function compilePattern(): array
    {
        $uri = $this->definition->uri();
        $constraints = $this->compiledConstraints;

        preg_match_all('/\{([^}]+)\}/', $uri, $parameterMatches);
        $parameterNames = $parameterMatches[1];

        $segments = explode('/', trim($uri, '/'));
        $compiledSegments = [];

        foreach ($segments as $segment) {
            if ($segment === '') {
                continue;
            }

            if (preg_match('/^\{([^}]+)\}$/', $segment) === 1) {
                preg_match('/^\{([^}]+)\}$/', $segment, $parameterMatch);
                $parameterName = $parameterMatch[1];
                $constraint = $constraints[$parameterName] ?? '[^\/]+';
                $compiledSegments[] = '(' . $constraint . ')';
                continue;
            }

            $compiledSegments[] = preg_quote($segment, '/');
        }

        $pattern = '/^';
        $pattern .= $compiledSegments === [] ? '\/' : '\/' . implode('\/', $compiledSegments);
        $pattern .= '$/';

        return [$pattern, $parameterNames];
    }

    private function recompile(): void
    {
        $this->compiledConstraints = (new ConstraintCompiler())->compile(
            $this->definition->constraints(),
            $this->definition->uri(),
        );
        [$pattern, $parameterNames] = $this->compilePattern();
        $this->pattern = $pattern;
        $this->parameterNames = $parameterNames;
        [$domainPattern, $domainParameterNames] = $this->compileDomainPattern();
        $this->domainPattern = $domainPattern;
        $this->domainParameterNames = $domainParameterNames;
        $this->metadata = RouteMetadata::fromDefinition($this->definition);
        $this->pipeline = CompiledMiddlewarePipeline::compile($this->definition->middlewares());
    }

    /**
     * @return array{0: string|null, 1: array<int, string>}
     */
    private function compileDomainPattern(): array
    {
        $domain = $this->definition->domain();

        if ($domain === null) {
            return [null, []];
        }

        $constraints = $this->compiledConstraints;
        preg_match_all('/\{([^}]+)\}/', $domain, $parameterMatches);
        $parameterNames = $parameterMatches[1];
        $segments = explode('.', $domain);
        $compiledSegments = [];

        foreach ($segments as $segment) {
            if (preg_match('/^\{([^}]+)\}$/', $segment, $parameterMatch) === 1) {
                $parameterName = $parameterMatch[1];
                $constraint = $constraints[$parameterName] ?? '[^\.]+';
                $compiledSegments[] = '(' . $constraint . ')';
                continue;
            }

            $compiledSegments[] = preg_quote($segment, '/');
        }

        return ['/^' . implode('\.', $compiledSegments) . '$/i', $parameterNames];
    }
}
