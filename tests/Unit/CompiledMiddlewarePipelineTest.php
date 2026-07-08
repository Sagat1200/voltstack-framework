<?php

declare(strict_types=1);

namespace VoltStack\Test\Unit;

use PHPUnit\Framework\TestCase;
use Quantum\Http\Request;
use Quantum\HttpKernel\CompiledMiddlewarePipeline;
use Quantum\HttpKernel\Contracts\MiddlewareInterface;
use VoltStack\Framework\Application;

final class CompiledMiddlewarePipelineTest extends TestCase
{
    public function test_it_generates_a_stable_identifier_for_the_same_stack(): void
    {
        $first = CompiledMiddlewarePipeline::compile([
            TestCompiledFirstMiddleware::class,
            TestCompiledSecondMiddleware::class,
        ]);
        $second = CompiledMiddlewarePipeline::compile([
            TestCompiledFirstMiddleware::class,
            TestCompiledSecondMiddleware::class,
        ]);

        self::assertSame($first->id(), $second->id());
    }

    public function test_it_reuses_the_same_compiled_instance_for_cacheable_class_string_stacks(): void
    {
        $first = CompiledMiddlewarePipeline::compile([
            TestCompiledFirstMiddleware::class,
            TestCompiledSecondMiddleware::class,
        ]);
        $second = CompiledMiddlewarePipeline::compile([
            TestCompiledFirstMiddleware::class,
            TestCompiledSecondMiddleware::class,
        ]);

        self::assertSame($first, $second);
    }

    public function test_it_does_not_share_non_cacheable_pipeline_stacks(): void
    {
        $closure = static fn(Request $request, \Closure $next): mixed => $next($request);

        $first = CompiledMiddlewarePipeline::compile([$closure]);
        $second = CompiledMiddlewarePipeline::compile([$closure]);

        self::assertNotSame($first, $second);
        self::assertSame($first->id(), $second->id());
    }

    public function test_it_executes_the_compiled_pipeline_in_declared_order(): void
    {
        TestCompiledPipelineTrace::reset();

        $app = new Application(sys_get_temp_dir());
        $pipeline = CompiledMiddlewarePipeline::compile([
            TestCompiledFirstMiddleware::class,
            TestCompiledSecondMiddleware::class,
        ]);

        $result = $pipeline->handle(
            $app,
            Request::create('/compiled-pipeline'),
            function (): string {
                TestCompiledPipelineTrace::push('destination');

                return 'ok';
            },
        );

        self::assertSame('ok', $result);
        self::assertSame([
            'first-before',
            'second-before',
            'destination',
            'second-after',
            'first-after',
        ], TestCompiledPipelineTrace::all());
    }
}

final class TestCompiledPipelineTrace
{
    /**
     * @var array<int, string>
     */
    private static array $entries = [];

    public static function reset(): void
    {
        self::$entries = [];
    }

    public static function push(string $entry): void
    {
        self::$entries[] = $entry;
    }

    /**
     * @return array<int, string>
     */
    public static function all(): array
    {
        return self::$entries;
    }
}

final class TestCompiledFirstMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, \Closure $next): mixed
    {
        TestCompiledPipelineTrace::push('first-before');
        $response = $next($request);
        TestCompiledPipelineTrace::push('first-after');

        return $response;
    }
}

final class TestCompiledSecondMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, \Closure $next): mixed
    {
        TestCompiledPipelineTrace::push('second-before');
        $response = $next($request);
        TestCompiledPipelineTrace::push('second-after');

        return $response;
    }
}
