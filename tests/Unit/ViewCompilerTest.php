<?php

declare(strict_types=1);

namespace VoltStack\Test\Unit;

use PHPUnit\Framework\TestCase;
use Quantum\View\Compilers\ViewCompiler;
use Quantum\View\Directives\DirectiveRegistry;
use Quantum\View\Directives\Support\CallbackDirective;
use Quantum\View\Exceptions\DirectiveBalanceException;
use RuntimeException;

final class ViewCompilerTest extends TestCase
{
    public function test_it_reports_unclosed_if_blocks(): void
    {
        $compiler = new ViewCompiler(new DirectiveRegistry());

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unclosed @if directive at line 1, column 1.');

        $compiler->compileString('@if($user)<span>Hola</span>');
    }

    public function test_it_reports_unclosed_forelse_blocks(): void
    {
        $compiler = new ViewCompiler(new DirectiveRegistry());

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unclosed @forelse directive at line 1, column 1.');

        $compiler->compileString('@forelse($users as $user)<span>{{ $user }}</span>');
    }

    public function test_it_reports_forelse_without_empty_block(): void
    {
        $compiler = new ViewCompiler(new DirectiveRegistry());

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The @forelse directive requires an @empty block at line 1, column 1.');

        $compiler->compileString('@forelse($users as $user)<span>{{ $user }}</span>@endforelse');
    }

    public function test_it_compiles_forelse_blocks(): void
    {
        $compiler = new ViewCompiler(new DirectiveRegistry());

        $compiled = $compiler->compileString("@forelse(\$users as \$user){{ \$user }}\n@empty\nNada\n@endforelse");

        self::assertStringContainsString('$__empty_1 = true; foreach($users as $user): $__empty_1 = false;', $compiled);
        self::assertStringContainsString('<?php endforeach; if($__empty_1): ?>', $compiled);
        self::assertStringContainsString('<?= e($user) ?>', $compiled);
        self::assertStringContainsString('Nada', $compiled);
        self::assertStringContainsString('<?php endif; ?>', $compiled);
    }

    public function test_it_supports_custom_directives_with_hyphens(): void
    {
        $registry = new DirectiveRegistry();
        $registry->register('tailwind-vite', new CallbackDirective(
            fn(): string => '<?php echo tailwind_vite()->render(); ?>',
        ));
        $compiler = new ViewCompiler($registry);

        $compiled = $compiler->compileString('@tailwind-vite');

        self::assertSame('<?php echo tailwind_vite()->render(); ?>', $compiled);
    }

    public function test_it_attaches_the_source_path_to_specialized_compiler_exceptions(): void
    {
        $compiler = new ViewCompiler(new DirectiveRegistry());

        try {
            $compiler->compileString('@if($user)<span>Hola</span>', 'resources/views/home.volt.php');
            self::fail('Expected compiler exception was not thrown.');
        } catch (DirectiveBalanceException $exception) {
            self::assertSame('resources/views/home.volt.php', $exception->sourcePath());
            self::assertSame(1, $exception->sourceLine());
            self::assertSame(1, $exception->sourceColumn());
            self::assertSame(
                'Unclosed @if directive at line 1, column 1 in [resources/views/home.volt.php].',
                $exception->getMessage(),
            );
        }
    }
}
