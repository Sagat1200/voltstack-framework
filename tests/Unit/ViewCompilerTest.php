<?php

declare(strict_types=1);

namespace VoltStack\Test\Unit;

use PHPUnit\Framework\TestCase;
use Quantum\View\Compilers\ViewCompiler;
use Quantum\View\Directives\DirectiveRegistry;
use RuntimeException;

final class ViewCompilerTest extends TestCase
{
    public function test_it_reports_unclosed_if_blocks(): void
    {
        $compiler = new ViewCompiler(new DirectiveRegistry());

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unclosed @if directive.');

        $compiler->compileString('@if($user)<span>Hola</span>');
    }

    public function test_it_reports_unclosed_forelse_blocks(): void
    {
        $compiler = new ViewCompiler(new DirectiveRegistry());

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unclosed @forelse directive.');

        $compiler->compileString('@forelse($users as $user)<span>{{ $user }}</span>');
    }

    public function test_it_reports_forelse_without_empty_block(): void
    {
        $compiler = new ViewCompiler(new DirectiveRegistry());

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The @forelse directive requires an @empty block.');

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
}
