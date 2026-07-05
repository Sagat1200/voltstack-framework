<?php

declare(strict_types=1);

namespace Quantum\Routing;

use InvalidArgumentException;
use Quantum\Routing\Attributes\Domain;
use Quantum\Routing\Attributes\HttpRouteAttribute;
use Quantum\Routing\Attributes\Middleware;
use Quantum\Routing\Attributes\Name;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionMethod;

final class AttributeRouteRegistrar
{
    public function __construct(private readonly Router $router) {}

    public function register(string|array $controllers): void
    {
        $controllers = is_array($controllers) ? array_values($controllers) : [$controllers];

        foreach ($controllers as $controller) {
            $this->registerController((string) $controller);
        }
    }

    private function registerController(string $controller): void
    {
        $normalizedController = trim($controller);

        if ($normalizedController === '') {
            throw new InvalidArgumentException('Attribute route controller cannot be empty.');
        }

        if (! class_exists($normalizedController)) {
            throw new InvalidArgumentException(sprintf(
                'Attribute route controller [%s] does not exist.',
                $normalizedController,
            ));
        }

        $reflection = new ReflectionClass($normalizedController);

        if ($reflection->isAbstract()) {
            throw new InvalidArgumentException(sprintf(
                'Attribute route controller [%s] must be instantiable.',
                $normalizedController,
            ));
        }

        $classDomain = $this->domainAttribute($reflection)?->value();
        $classMiddlewares = $this->middlewareValues($reflection);

        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            if ($method->isConstructor() || $method->isDestructor() || $method->isStatic() || $method->isAbstract()) {
                continue;
            }

            $attributes = $method->getAttributes(HttpRouteAttribute::class, ReflectionAttribute::IS_INSTANCEOF);

            foreach ($attributes as $attribute) {
                $this->registerAttributeRoute($normalizedController, $method, $attribute, $classDomain, $classMiddlewares);
            }
        }
    }

    private function registerAttributeRoute(
        string $controller,
        ReflectionMethod $method,
        ReflectionAttribute $attribute,
        ?string $classDomain,
        array $classMiddlewares,
    ): void {
        $routeAttribute = $attribute->newInstance();

        if (! $routeAttribute instanceof HttpRouteAttribute) {
            throw new InvalidArgumentException(sprintf(
                'Route attribute on [%s::%s] must extend [%s].',
                $controller,
                $method->getName(),
                HttpRouteAttribute::class,
            ));
        }

        $action = $method->getName() === '__invoke'
            ? $controller
            : $controller . '@' . $method->getName();

        $route = $this->router->match($routeAttribute->methods(), $routeAttribute->uri(), $action);

        $name = $this->nameAttribute($method)?->value();

        if ($name !== null) {
            $route->name($name);
        }

        $domain = $this->domainAttribute($method)?->value() ?? $classDomain;

        if ($domain !== null) {
            $route->domain($domain);
        }

        $middlewares = [
            ...$classMiddlewares,
            ...$this->middlewareValues($method),
        ];

        if ($middlewares !== []) {
            $route->middleware($middlewares);
        }
    }

    private function nameAttribute(ReflectionMethod $method): ?Name
    {
        $attributes = $method->getAttributes(Name::class);
        $attribute = $attributes[0] ?? null;

        return $attribute?->newInstance();
    }

    private function domainAttribute(ReflectionClass|ReflectionMethod $reflection): ?Domain
    {
        $attributes = $reflection->getAttributes(Domain::class);
        $attribute = $attributes[0] ?? null;

        return $attribute?->newInstance();
    }

    /**
     * @return array<int, string>
     */
    private function middlewareValues(ReflectionClass|ReflectionMethod $reflection): array
    {
        $middlewares = [];

        foreach ($reflection->getAttributes(Middleware::class) as $attribute) {
            $instance = $attribute->newInstance();

            if ($instance instanceof Middleware) {
                $middlewares = [
                    ...$middlewares,
                    ...$instance->values(),
                ];
            }
        }

        return $middlewares;
    }
}
