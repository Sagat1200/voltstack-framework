<?php

declare(strict_types=1);

namespace Quantum\View\Directives;

use Quantum\View\Directives\Contracts\DirectiveContract;
use Quantum\View\Directives\Support\CallbackDirective;
use RuntimeException;

final class DirectiveRegistry
{
    /**
     * @var array<string, DirectiveContract>
     */
    private array $directives = [];

    public function __construct()
    {
        $this->registerCoreDirectives();
    }

    public function register(string $name, DirectiveContract $directive, bool $overwrite = false): void
    {
        $name = strtolower(trim($name));

        if ($name === '') {
            throw new RuntimeException('Directive names cannot be empty.');
        }

        if (isset($this->directives[$name]) && ! $overwrite) {
            throw new RuntimeException(sprintf('Directive [%s] is already registered.', $name));
        }

        $this->directives[$name] = $directive;
    }

    public function has(string $name): bool
    {
        return isset($this->directives[strtolower($name)]);
    }

    public function resolve(string $name): ?DirectiveContract
    {
        return $this->directives[strtolower($name)] ?? null;
    }

    private function registerCoreDirectives(): void
    {
        $this->register('if', new CallbackDirective(
            fn(?string $expression): string => sprintf('<?php if(%s): ?>', $this->expression($expression)),
            true,
        ));

        $this->register('elseif', new CallbackDirective(
            fn(?string $expression): string => sprintf('<?php elseif(%s): ?>', $this->expression($expression)),
            true,
        ));

        $this->register('else', new CallbackDirective(
            fn(): string => '<?php else: ?>',
        ));

        $this->register('endif', new CallbackDirective(
            fn(): string => '<?php endif; ?>',
        ));

        $this->register('unless', new CallbackDirective(
            fn(?string $expression): string => sprintf('<?php if(! (%s)): ?>', $this->expression($expression)),
            true,
        ));

        $this->register('endunless', new CallbackDirective(
            fn(): string => '<?php endif; ?>',
        ));

        $this->register('isset', new CallbackDirective(
            fn(?string $expression): string => sprintf('<?php if(isset(%s)): ?>', $this->expression($expression)),
            true,
        ));

        $this->register('endisset', new CallbackDirective(
            fn(): string => '<?php endif; ?>',
        ));

        $this->register('empty', new CallbackDirective(
            fn(?string $expression): string => sprintf('<?php if(empty(%s)): ?>', $this->expression($expression)),
            true,
        ));

        $this->register('endempty', new CallbackDirective(
            fn(): string => '<?php endif; ?>',
        ));

        $this->register('include', new CallbackDirective(
            fn(?string $expression): string => sprintf('<?php echo $__volt->render(%s); ?>', $this->expression($expression)),
            true,
        ));

        $this->register('component', new CallbackDirective(
            fn(?string $expression): string => $this->compileComponentDirective($expression),
            true,
        ));

        $this->register('endcomponent', new CallbackDirective(
            fn(): string => '<?php echo $__volt->endComponent(); ?>',
        ));

        $this->register('props', new CallbackDirective(
            fn(?string $expression): string => sprintf(
                '<?php extract($__volt->normalizeProps(%s) + get_defined_vars(), EXTR_SKIP); ?>',
                $this->expression($expression),
            ),
            true,
        ));

        $this->register('slot', new CallbackDirective(
            fn(?string $expression): string => sprintf('<?php $__volt->startSlot(%s); ?>', $this->expression($expression)),
            true,
        ));

        $this->register('endslot', new CallbackDirective(
            fn(): string => '<?php $__volt->endSlot(); ?>',
        ));

        $this->register('dynamic', new CallbackDirective(
            fn(?string $expression): string => $this->compileDynamicDirective($expression),
            true,
        ));

        $this->register('extendscomponent', new CallbackDirective(
            fn(?string $expression): string => $this->compileExtendsComponentDirective($expression),
            true,
        ));

        $this->register('attributes', new CallbackDirective(
            fn(?string $expression): string => sprintf(
                '<?php $attributes = (($attributes ?? new \VoltStack\Runtime\Component\ComponentAttributeBag())->merge(%s)); ?>',
                $this->expression($expression),
            ),
            true,
        ));

        $this->register('class', new CallbackDirective(
            fn(?string $expression): string => sprintf('<?php echo e($__volt->classList(%s)); ?>', $this->expression($expression)),
            true,
        ));

        $this->register('style', new CallbackDirective(
            fn(?string $expression): string => sprintf('<?php echo e($__volt->styleList(%s)); ?>', $this->expression($expression)),
            true,
        ));

        $this->register('scope', new CallbackDirective(
            fn(): string => '<?php (function (array $__volt_scope_vars) use ($__volt) { extract($__volt_scope_vars, EXTR_SKIP); ?>',
        ));

        $this->register('endscope', new CallbackDirective(
            fn(): string => '<?php })(get_defined_vars()); ?>',
        ));

        $this->register('rendermode', new CallbackDirective(
            fn(?string $expression): string => sprintf('<?php $__volt->setRenderMode(%s); ?>', $this->expression($expression)),
            true,
        ));

        $this->register('foreach', new CallbackDirective(
            fn(?string $expression): string => sprintf('<?php foreach(%s): ?>', $this->expression($expression)),
            true,
        ));

        $this->register('endforeach', new CallbackDirective(
            fn(): string => '<?php endforeach; ?>',
        ));

        $this->register('for', new CallbackDirective(
            fn(?string $expression): string => sprintf('<?php for(%s): ?>', $this->expression($expression)),
            true,
        ));

        $this->register('endfor', new CallbackDirective(
            fn(): string => '<?php endfor; ?>',
        ));

        $this->register('while', new CallbackDirective(
            fn(?string $expression): string => sprintf('<?php while(%s): ?>', $this->expression($expression)),
            true,
        ));

        $this->register('endwhile', new CallbackDirective(
            fn(): string => '<?php endwhile; ?>',
        ));

        $this->register('php', new CallbackDirective(
            fn(): string => '<?php ',
        ));

        $this->register('endphp', new CallbackDirective(
            fn(): string => '?>',
        ));

        $this->register('extends', new CallbackDirective(
            fn(?string $expression): string => sprintf('<?php $__volt->extend(%s); ?>', $this->expression($expression)),
            true,
        ));

        $this->register('section', new CallbackDirective(
            fn(?string $expression): string => sprintf('<?php $__volt->startSection(%s); ?>', $this->expression($expression)),
            true,
        ));

        $this->register('endsection', new CallbackDirective(
            fn(): string => '<?php $__volt->endSection(); ?>',
        ));

        $this->register('yield', new CallbackDirective(
            fn(?string $expression): string => sprintf('<?php echo $__volt->yieldContent(%s); ?>', $this->expression($expression)),
            true,
        ));
    }

    private function expression(?string $expression): string
    {
        $expression = trim((string) $expression);

        if ($expression === '') {
            throw new RuntimeException('The directive requires an expression.');
        }

        return $expression;
    }

    private function compileComponentDirective(?string $expression): string
    {
        $expression = $this->expression($expression);
        [$component, $props] = $this->componentArguments($expression, '@component');

        if ($props === null) {
            return sprintf('<?php $__volt->startComponent(%s); ?>', $component);
        }

        return sprintf('<?php $__volt->startComponent(%s, %s); ?>', $component, $props);
    }

    private function compileDynamicDirective(?string $expression): string
    {
        $expression = $this->expression($expression);
        [$component, $props] = $this->componentArguments($expression, '@dynamic');

        if ($props === null) {
            return sprintf('<?php echo $__volt->renderDynamicComponent(%s); ?>', $component);
        }

        return sprintf('<?php echo $__volt->renderDynamicComponent(%s, %s); ?>', $component, $props);
    }

    private function compileExtendsComponentDirective(?string $expression): string
    {
        $expression = $this->expression($expression);
        [$component, $props] = $this->componentArguments($expression, '@extendsComponent');

        if ($props === null) {
            return sprintf('<?php $__volt->extendComponent(%s); ?>', $component);
        }

        return sprintf('<?php $__volt->extendComponent(%s, %s); ?>', $component, $props);
    }

    /**
     * @return array{0: string, 1: ?string}
     */
    private function componentArguments(string $expression, string $directive): array
    {
        $arguments = $this->splitTopLevelArguments($expression);

        if ($arguments === [] || count($arguments) > 2) {
            throw new RuntimeException(sprintf('The %s directive accepts a component name and optional props array.', $directive));
        }

        $component = trim($arguments[0] ?? '');
        $props = isset($arguments[1]) ? trim($arguments[1]) : null;

        if ($component === '' || $props === '') {
            throw new RuntimeException(sprintf('The %s directive accepts a component name and optional props array.', $directive));
        }

        return [$component, $props];
    }

    /**
     * @return array<int, string>
     */
    private function splitTopLevelArguments(string $expression): array
    {
        $arguments = [];
        $current = '';
        $length = strlen($expression);
        $depth = 0;
        $quote = null;
        $escaping = false;

        for ($index = 0; $index < $length; $index++) {
            $character = $expression[$index];

            if ($quote !== null) {
                $current .= $character;

                if ($escaping) {
                    $escaping = false;
                    continue;
                }

                if ($character === '\\') {
                    $escaping = true;
                    continue;
                }

                if ($character === $quote) {
                    $quote = null;
                }

                continue;
            }

            if ($character === '\'' || $character === '"') {
                $quote = $character;
                $current .= $character;
                continue;
            }

            if (in_array($character, ['(', '[', '{'], true)) {
                $depth++;
                $current .= $character;
                continue;
            }

            if (in_array($character, [')', ']', '}'], true)) {
                $depth = max(0, $depth - 1);
                $current .= $character;
                continue;
            }

            if ($character === ',' && $depth === 0) {
                $arguments[] = trim($current);
                $current = '';
                continue;
            }

            $current .= $character;
        }

        if (trim($current) !== '') {
            $arguments[] = trim($current);
        }

        return $arguments;
    }
}
