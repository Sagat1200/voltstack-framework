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
}
