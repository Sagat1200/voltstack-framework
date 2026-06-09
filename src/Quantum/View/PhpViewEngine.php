<?php

declare(strict_types=1);

namespace Quantum\View;

use Quantum\View\Cache\CompiledViewStore;
use Quantum\View\Exceptions\TemplateCompilerException;
use Quantum\View\Exceptions\ViewRenderException;
use Quantum\View\Runtime\ViewRuntime;
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

            if ($exception instanceof TemplateCompilerException || $exception instanceof ViewRenderException) {
                throw $exception;
            }

            throw new ViewRenderException($path, $exception);
        }

        $output = (string) ob_get_clean();

        if ($runtime->hasParentView()) {
            $output = $runtime->renderParent();
        }

        if ($runtime->hasParentComponent()) {
            return $runtime->renderParentComponent($output);
        }

        return $output;
    }
}
