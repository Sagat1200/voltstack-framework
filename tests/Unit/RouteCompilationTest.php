<?php

declare(strict_types=1);

namespace VoltStack\Test\Unit;

use PHPUnit\Framework\TestCase;
use Quantum\Routing\CompiledRoute;
use Quantum\Routing\Exceptions\DuplicateRouteException;
use Quantum\Routing\Route;
use Quantum\Routing\RouteCollection;
use Quantum\Routing\RouteDefinition;

final class RouteCompilationTest extends TestCase
{
    public function test_route_definition_normalizes_methods_and_uri(): void
    {
        $definition = RouteDefinition::make(['get', 'post', 'GET'], 'users/{id}', 'handler');

        self::assertSame(['GET', 'POST'], $definition->methods());
        self::assertSame('/users/{id}', $definition->uri());
        self::assertSame('handler', $definition->action());
    }

    public function test_compiled_route_exposes_precompiled_pattern_and_parameter_names(): void
    {
        $route = new CompiledRoute(RouteDefinition::make(['GET'], '/users/{id}/posts/{post}', 'handler'));

        self::assertSame('/^\/users\/([^\/]+)\/posts\/([^\/]+)$/', $route->pattern());
        self::assertSame(['id', 'post'], $route->parameterNames());
        self::assertSame([
            'id' => '42',
            'post' => '100',
        ], $route->matchPath('/users/42/posts/100'));
    }

    public function test_compiled_route_returns_null_when_the_path_does_not_match(): void
    {
        $route = new CompiledRoute(RouteDefinition::make(['GET'], '/users/{id}', 'handler'));

        self::assertNull($route->matchPath('/teams/42'));
    }

    public function test_route_collection_registers_routes_in_deterministic_order(): void
    {
        $collection = new RouteCollection();
        $first = new Route(RouteDefinition::make(['GET'], '/first', 'first'));
        $second = new Route(RouteDefinition::make(['POST'], '/second', 'second'));

        $collection->add($first);
        $collection->add($second);

        self::assertCount(2, $collection);
        self::assertSame([$first, $second], $collection->all());
    }

    public function test_route_collection_rejects_duplicate_method_and_uri_pairs(): void
    {
        $collection = new RouteCollection();
        $collection->add(new Route(RouteDefinition::make(['GET'], '/users', 'first')));

        $this->expectException(DuplicateRouteException::class);
        $this->expectExceptionMessage('A route is already registered for [GET] /users.');

        $collection->add(new Route(RouteDefinition::make(['GET'], '/users', 'second')));
    }

    public function test_route_collection_rejects_overlapping_methods_from_multi_method_routes(): void
    {
        $collection = new RouteCollection();
        $collection->add(new Route(RouteDefinition::make(['GET', 'POST'], '/users', 'first')));

        $this->expectException(DuplicateRouteException::class);
        $this->expectExceptionMessage('A route is already registered for [POST] /users.');

        $collection->add(new Route(RouteDefinition::make(['POST', 'PUT'], '/users', 'second')));
    }
}
