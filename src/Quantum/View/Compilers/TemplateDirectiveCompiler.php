<?php

declare(strict_types=1);

namespace Quantum\View\Compilers;

use Quantum\View\Directives\DirectiveRegistry;
use RuntimeException;

final class TemplateDirectiveCompiler
{
    /**
     * @var array<int, array{type: string, empty_var?: string, has_empty?: bool}>
     */
    private array $directiveStack = [];

    private int $forelseCounter = 0;

    public function __construct(
        private readonly DirectiveRegistry $directives,
    ) {
    }

    public function reset(): void
    {
        $this->directiveStack = [];
        $this->forelseCounter = 0;
    }

    public function compile(TemplateNode $node): string
    {
        $name = $node->name();

        if (! is_string($name) || $name === '') {
            throw new RuntimeException('Template directive nodes require a name.');
        }

        return match ($name) {
            'if', 'unless', 'isset', 'foreach', 'for', 'while', 'section' => $this->compileOpeningDirective($name, $node->expression()),
            'endif', 'endunless', 'endisset', 'endempty', 'endforeach', 'endfor', 'endwhile', 'endsection' =>
                $this->compileClosingDirective($name),
            'forelse' => $this->compileForelse($node->expression()),
            'empty' => $this->compileEmptyDirective($node->expression()),
            'endforelse' => $this->compileEndForelse(),
            default => $this->compileSimpleDirective($name, $node->expression()),
        };
    }

    public function assertBalanced(): void
    {
        if ($this->directiveStack === []) {
            return;
        }

        $last = end($this->directiveStack);
        $type = is_array($last) ? $last['type'] : 'directive';

        throw new RuntimeException(sprintf('Unclosed @%s directive.', $type));
    }

    private function compileOpeningDirective(string $name, ?string $expression): string
    {
        $this->directiveStack[] = ['type' => $name];

        return $this->compileSimpleDirective($name, $expression);
    }

    private function compileClosingDirective(string $name): string
    {
        $expected = match ($name) {
            'endif' => 'if',
            'endunless' => 'unless',
            'endisset' => 'isset',
            'endempty' => 'empty',
            'endforeach' => 'foreach',
            'endfor' => 'for',
            'endwhile' => 'while',
            'endsection' => 'section',
            default => throw new RuntimeException(sprintf('Unknown directive [%s].', $name)),
        };

        $current = array_pop($this->directiveStack);

        if (! is_array($current) || $current['type'] !== $expected) {
            throw new RuntimeException(sprintf('Unexpected @%s directive.', $name));
        }

        return $this->compileSimpleDirective($name, null);
    }

    private function compileForelse(?string $expression): string
    {
        $emptyVar = sprintf('$__empty_%d', ++$this->forelseCounter);
        $this->directiveStack[] = [
            'type' => 'forelse',
            'empty_var' => $emptyVar,
            'has_empty' => false,
        ];

        return sprintf('<?php %s = true; foreach(%s): %s = false; ?>', $emptyVar, $this->expression($expression), $emptyVar);
    }

    private function compileEmptyDirective(?string $expression): string
    {
        if ($expression !== null && trim($expression) !== '') {
            $this->directiveStack[] = ['type' => 'empty'];

            return $this->compileSimpleDirective('empty', $expression);
        }

        $index = array_key_last($this->directiveStack);

        if ($index === null || $this->directiveStack[$index]['type'] !== 'forelse') {
            throw new RuntimeException('Unexpected @empty directive.');
        }

        if (($this->directiveStack[$index]['has_empty'] ?? false) === true) {
            throw new RuntimeException('Duplicate @empty directive inside @forelse.');
        }

        $this->directiveStack[$index]['has_empty'] = true;
        $emptyVar = $this->directiveStack[$index]['empty_var'];

        return sprintf('<?php endforeach; if(%s): ?>', $emptyVar);
    }

    private function compileEndForelse(): string
    {
        $current = array_pop($this->directiveStack);

        if (! is_array($current) || $current['type'] !== 'forelse') {
            throw new RuntimeException('Unexpected @endforelse directive.');
        }

        if (($current['has_empty'] ?? false) !== true) {
            throw new RuntimeException('The @forelse directive requires an @empty block.');
        }

        return '<?php endif; ?>';
    }

    private function compileSimpleDirective(string $name, ?string $expression): string
    {
        $directive = $this->directives->resolve($name);

        if ($directive === null) {
            throw new RuntimeException(sprintf('Unknown directive [%s].', $name));
        }

        return $directive->compile($expression);
    }

    private function expression(?string $expression): string
    {
        $expression = trim((string) $expression);

        if ($expression === '') {
            throw new RuntimeException('The directive requires an expression.');
        }

        return $expression;
    }
}
