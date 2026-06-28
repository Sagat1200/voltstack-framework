<?php

declare(strict_types=1);

namespace VoltStack\Test\Unit;

use PHPUnit\Framework\TestCase;
use Quantum\Routing\CompiledRoute;
use Quantum\Routing\Exceptions\DuplicateRouteException;
use Quantum\Routing\Exceptions\DuplicateRouteNameException;
use Quantum\Routing\Route;
use Quantum\Routing\RouteCollection;
use Quantum\Routing\RouteDefinition;
use Quantum\Routing\RouteMatch;
use Quantum\Routing\RouteMatcher;
use Quantum\Http\Request;
use Quantum\Routing\Exceptions\MethodNotAllowedException;
use Quantum\Routing\Exceptions\RouteNotFoundException;

final class RouteCompilationTest extends TestCase
{
    public function test_route_definition_normalizes_methods_and_uri(): void
    {
        $definition = RouteDefinition::make(['get', 'post', 'GET'], 'users/{id}', 'handler');

        self::assertSame(['GET', 'POST'], $definition->methods());
        self::assertSame('/users/{id}', $definition->uri());
        self::assertSame('handler', $definition->action());
        self::assertNull($definition->name());
    }

    public function test_route_definition_can_return_a_named_copy(): void
    {
        $definition = RouteDefinition::make(['GET'], '/users', 'handler')->withName('users.index');

        self::assertSame('users.index', $definition->name());
    }

    public function test_route_definition_can_store_constraints(): void
    {
        $definition = RouteDefinition::make(['GET'], '/users/{id}', 'handler')
            ->withConstraint('id', '[0-9]+');

        self::assertSame(['id' => '[0-9]+'], $definition->constraints());
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

    public function test_compiled_route_uses_constraints_in_the_compiled_pattern(): void
    {
        $route = new Route(RouteDefinition::make(['GET'], '/users/{id}', 'handler'));
        $route->whereNumber('id');

        self::assertSame('/^\/users\/([0-9]+)$/', $route->pattern());
        self::assertSame(['id' => '42'], $route->matchPath('/users/42'));
        self::assertNull($route->matchPath('/users/abc'));
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

    public function test_route_collection_can_index_routes_by_name(): void
    {
        $collection = new RouteCollection();
        $route = $collection->add(new Route(RouteDefinition::make(['GET'], '/users', 'handler')));
        $route->name('users.index');

        self::assertSame('users.index', $route->name());
        self::assertSame($route, $collection->named('users.index'));
    }

    public function test_route_collection_rejects_duplicate_route_names(): void
    {
        $collection = new RouteCollection();
        $collection->add(new Route(RouteDefinition::make(['GET'], '/users', 'first')))->name('users.index');
        $route = $collection->add(new Route(RouteDefinition::make(['POST'], '/users', 'second')));

        $this->expectException(DuplicateRouteNameException::class);
        $this->expectExceptionMessage('A route is already registered with the name [users.index].');

        $route->name('users.index');
    }

    public function test_route_match_exposes_route_parameters_and_resolution_metadata(): void
    {
        $route = new Route(RouteDefinition::make(['GET'], '/users/{id}', 'handler'));
        $match = new RouteMatch($route, ['id' => '42'], 'GET');

        self::assertSame($route, $match->route());
        self::assertSame(['id' => '42'], $match->parameters());
        self::assertSame('GET', $match->resolvedMethod());
        self::assertFalse($match->usedHeadFallback());
    }

    public function test_route_matcher_returns_a_route_match_for_dynamic_routes(): void
    {
        $collection = new RouteCollection();
        $route = $collection->add(new Route(RouteDefinition::make(['GET'], '/users/{id}', 'handler')));

        $match = (new RouteMatcher())->match(Request::create('/users/42'), $collection);

        self::assertSame($route, $match->route());
        self::assertSame(['id' => '42'], $match->parameters());
        self::assertSame('GET', $match->resolvedMethod());
    }

    public function test_route_matcher_marks_head_fallback_matches(): void
    {
        $collection = new RouteCollection();
        $route = $collection->add(new Route(RouteDefinition::make(['GET'], '/users', 'handler')));

        $match = (new RouteMatcher())->match(Request::create('/users', 'HEAD'), $collection);

        self::assertSame($route, $match->route());
        self::assertSame('GET', $match->resolvedMethod());
        self::assertTrue($match->usedHeadFallback());
    }

    public function test_route_matcher_throws_method_not_allowed_with_allow_header_information(): void
    {
        $collection = new RouteCollection();
        $collection->add(new Route(RouteDefinition::make(['POST'], '/users', 'handler')));

        try {
            (new RouteMatcher())->match(Request::create('/users', 'GET'), $collection);
            self::fail('Expected MethodNotAllowedException was not thrown.');
        } catch (MethodNotAllowedException $exception) {
            self::assertSame(['POST', 'OPTIONS'], $exception->allowedMethods());
            self::assertSame('POST, OPTIONS', $exception->allowHeader());
        }
    }

    public function test_route_matcher_throws_not_found_for_unknown_paths(): void
    {
        $collection = new RouteCollection();
        $collection->add(new Route(RouteDefinition::make(['GET'], '/users', 'handler')));

        $this->expectException(RouteNotFoundException::class);

        (new RouteMatcher())->match(Request::create('/teams'), $collection);
    }

    public function test_route_matcher_rejects_dynamic_routes_that_fail_constraints(): void
    {
        $collection = new RouteCollection();
        $route = $collection->add(new Route(RouteDefinition::make(['GET'], '/users/{id}', 'handler')));
        $route->whereNumber('id');

        $this->expectException(RouteNotFoundException::class);

        (new RouteMatcher())->match(Request::create('/users/abc'), $collection);
    }

    public function test_route_matcher_supports_uuid_and_slug_constraints(): void
    {
        $collection = new RouteCollection();
        $route = $collection->add(new Route(RouteDefinition::make(['GET'], '/posts/{slug}/{uuid}', 'handler')));
        $route->whereSlug('slug')->whereUuid('uuid');

        $match = (new RouteMatcher())->match(Request::create(
            '/posts/hello-world/123e4567-e89b-12d3-a456-426614174000'
        ), $collection);

        self::assertSame([
            'slug' => 'hello-world',
            'uuid' => '123e4567-e89b-12d3-a456-426614174000',
        ], $match->parameters());
    }
}
