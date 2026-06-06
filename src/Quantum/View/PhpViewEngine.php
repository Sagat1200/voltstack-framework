<?php

declare(strict_types=1);

namespace Quantum\View;

use Quantum\View\Cache\CompiledViewStore;
use Quantum\View\Runtime\ViewRuntime;
use RuntimeException;
use Throwable;

final class PhpViewEngine
{
    public function __construct(
        private readonly CompiledViewStore $compiledViews,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public function render(string $path, array $data = [], ?ViewRuntime $runtime = null): string
    {
        $compiledPath = $this->compiledViews->ensureCompiled($path);
        $runtime ??= new ViewRuntime(app(ViewFactory::class), $data);

        ob_start();
        extract($data, EXTR_SKIP);
        $__volt = $runtime;

        try {
            require $compiledPath;
        } catch (Throwable $exception) {
            ob_end_clean();

            throw new RuntimeException(sprintf('Unable to render view [%s].', $path), 0, $exception);
        }

        $output = (string) ob_get_clean();

        if ($runtime->hasParentView()) {
            return $runtime->renderParent();
        }

        return $output;
    }
}