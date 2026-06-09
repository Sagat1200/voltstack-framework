<?php

declare(strict_types=1);

namespace Quantum\View\Compilers;

use Quantum\View\Compilers\TemplateNode;
use Quantum\View\Directives\DirectiveRegistry;
use Quantum\View\Exceptions\DirectiveBalanceException;
use Quantum\View\Exceptions\TemplateParseException;

final class TemplateDirectiveCompiler
{
    /**
     * @var array<int, array{type: string, line: int, column: int, empty_var?: string, has_empty?: bool}>
     */
    private array $directiveStack = [];

    private int $forelseCounter = 0;

    public function __construct(
        private readonly DirectiveRegistry $directives,
    ) {}

    public function reset(): void
    {
        $this->directiveStack = [];
        $this->forelseCounter = 0;
    }

    public function compile(TemplateNode $node): string
    {
        $name = $node->name();

        if (! is_string($name) || $name === '') {
            throw new TemplateParseException('Template directive nodes require a name', $node->line(), $node->column());
        }

        return match ($name) {
            'if', 'unless', 'isset', 'foreach', 'for', 'while', 'section', 'component', 'slot', 'scope' => $this->compileOpeningDirective($node),
            'endif', 'endunless', 'endisset', 'endempty', 'endforeach', 'endfor', 'endwhile', 'endsection', 'endcomponent', 'endslot', 'endscope' =>
            $this->compileClosingDirective($name, $node),
            'forelse' => $this->compileForelse($node),
            'empty' => $this->compileEmptyDirective($node),
            'endforelse' => $this->compileEndForelse($node),
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
        $line = is_array($last) ? $last['line'] : 1;
        $column = is_array($last) ? $last['column'] : 1;

        throw new DirectiveBalanceException(sprintf('Unclosed @%s directive', $type), $line, $column);
    }

    private function compileOpeningDirective(TemplateNode $node): string
    {
        $name = (string) $node->name();
        $this->directiveStack[] = [
            'type' => $name,
            'line' => $node->line(),
            'column' => $node->column(),
        ];

        return $this->compileSimpleDirective($name, $node->expression());
    }

    private function compileClosingDirective(string $name, TemplateNode $node): string
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
            'endcomponent' => 'component',
            'endslot' => 'slot',
            'endscope' => 'scope',
            default => throw new TemplateParseException(sprintf('Unknown directive [%s]', $name), $node->line(), $node->column()),
        };

        $current = array_pop($this->directiveStack);

        if (! is_array($current) || $current['type'] !== $expected) {
            throw new DirectiveBalanceException(sprintf('Unexpected @%s directive', $name), $node->line(), $node->column());
        }

        return $this->compileSimpleDirective($name, null);
    }

    private function compileForelse(TemplateNode $node): string
    {
        $emptyVar = sprintf('$__empty_%d', ++$this->forelseCounter);
        $this->directiveStack[] = [
            'type' => 'forelse',
            'line' => $node->line(),
            'column' => $node->column(),
            'empty_var' => $emptyVar,
            'has_empty' => false,
        ];

        return sprintf(
            '<?php %s = true; foreach(%s): %s = false; ?>',
            $emptyVar,
            $this->expression($node->expression()),
            $emptyVar
        );
    }

    private function compileEmptyDirective(TemplateNode $node): string
    {
        $expression = $node->expression();

        if ($expression !== null && trim($expression) !== '') {
            $this->directiveStack[] = [
                'type' => 'empty',
                'line' => $node->line(),
                'column' => $node->column(),
            ];

            return $this->compileSimpleDirective('empty', $expression);
        }

        $index = array_key_last($this->directiveStack);

        if ($index === null || $this->directiveStack[$index]['type'] !== 'forelse') {
            throw new DirectiveBalanceException('Unexpected @empty directive', $node->line(), $node->column());
        }

        if (($this->directiveStack[$index]['has_empty'] ?? false) === true) {
            throw new DirectiveBalanceException(
                'Duplicate @empty directive inside @forelse',
                $this->directiveStack[$index]['line'],
                $this->directiveStack[$index]['column'],
            );
        }

        $this->directiveStack[$index]['has_empty'] = true;
        $emptyVar = $this->directiveStack[$index]['empty_var'];

        return sprintf('<?php endforeach; if(%s): ?>', $emptyVar);
    }

    private function compileEndForelse(TemplateNode $node): string
    {
        $current = array_pop($this->directiveStack);

        if (! is_array($current) || $current['type'] !== 'forelse') {
            throw new DirectiveBalanceException('Unexpected @endforelse directive', $node->line(), $node->column());
        }

        if (($current['has_empty'] ?? false) !== true) {
            throw new DirectiveBalanceException(
                'The @forelse directive requires an @empty block',
                $current['line'],
                $current['column'],
            );
        }

        return '<?php endif; ?>';
    }

    private function compileSimpleDirective(string $name, ?string $expression): string
    {
        $directive = $this->directives->resolve($name);

        if ($directive === null) {
            throw new TemplateParseException(sprintf('Unknown directive [%s]', $name));
        }

        return $directive->compile($expression);
    }

    private function expression(?string $expression): string
    {
        $expression = trim((string) $expression);

        if ($expression === '') {
            throw new TemplateParseException('The directive requires an expression');
        }

        return $expression;
    }
}
