<?php

declare(strict_types=1);

namespace VoltStack\Test\Unit;

use PHPUnit\Framework\TestCase;
use Quantum\Actions\Action;
use Quantum\Http\Request;
use Quantum\Routing\Dispatching\ActionDispatcher;
use Quantum\Routing\Dispatching\ClosureDispatcher;
use Quantum\Routing\Dispatching\ComponentDispatcher;
use Quantum\Routing\Dispatching\ControllerDispatcher;
use Quantum\Routing\Dispatching\DispatcherResolver;
use Quantum\Routing\Route;
use Quantum\Routing\RouteDefinition;
use Quantum\Routing\RouteMatch;
use VoltStack\Framework\Application;
use VoltStack\Runtime\Component\Component;

final class DispatcherResolverTest extends TestCase
{
    public function test_it_resolves_the_closure_dispatcher_for_closure_actions(): void
    {
        $resolver = (new Application(sys_get_temp_dir()))->make(DispatcherResolver::class);
        $match = new RouteMatch(
            new Route(RouteDefinition::make(['GET'], '/closure', fn() => 'ok')),
            [],
            'GET',
        );

        self::assertInstanceOf(ClosureDispatcher::class, $resolver->resolve($match));
    }

    public function test_it_resolves_the_controller_dispatcher_for_controller_actions(): void
    {
        $resolver = (new Application(sys_get_temp_dir()))->make(DispatcherResolver::class);
        $match = new RouteMatch(
            new Route(RouteDefinition::make(['GET'], '/controller', TestResolverController::class . '@show')),
            [],
            'GET',
        );

        self::assertInstanceOf(ControllerDispatcher::class, $resolver->resolve($match));
    }

    public function test_it_resolves_the_component_dispatcher_for_component_routes(): void
    {
        $resolver = (new Application(sys_get_temp_dir()))->make(DispatcherResolver::class);
        $match = new RouteMatch(
            new Route(RouteDefinition::make(['GET'], '/component', TestResolverComponent::class)),
            [],
            'GET',
        );

        self::assertInstanceOf(ComponentDispatcher::class, $resolver->resolve($match));
    }

    public function test_it_resolves_the_action_dispatcher_for_action_routes(): void
    {
        $resolver = (new Application(sys_get_temp_dir()))->make(DispatcherResolver::class);
        $match = new RouteMatch(
            new Route(RouteDefinition::make(['GET'], '/action', TestResolverAction::class)),
            [],
            'GET',
        );

        self::assertInstanceOf(ActionDispatcher::class, $resolver->resolve($match));
    }
}

final class TestResolverController
{
    public function show(): string
    {
        return 'controller';
    }
}

final class TestResolverComponent extends Component
{
    public function render(): string
    {
        return '<div>component</div>';
    }
}

final class TestResolverAction extends Action
{
    public function handle(mixed ...$arguments): mixed
    {
        /** @var Request $request */
        $request = $arguments[0];

        return $request->path();
    }
}
