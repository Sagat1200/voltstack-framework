<?php

declare(strict_types=1);

namespace VoltStack\Runtime\Component;

use Quantum\Http\Request;
use Quantum\Validation\Validator;
use Quantum\View\PhpViewEngine;
use Quantum\View\View;
use VoltStack\Framework\Application;
use ReflectionClass;
use RuntimeException;

abstract class Component
{
    public string $slot = '';

    protected ?Request $request = null;

    /**
     * @var array<string, mixed>
     */
    private array $viewData = [];

    public function render(): View|string
    {
        $template = $this->inlineTemplate();

        if ($template === null) {
            throw new RuntimeException(sprintf(
                'Component [%s] must implement render() or declare an inline page template.',
                static::class,
            ));
        }

        return $this->renderInlineTemplate($template);
    }

    /**
     * @return array<string, mixed>
     */
    public function runtimeMetadata(): array
    {
        return [];
    }

    public function setRequest(?Request $request): void
    {
        $this->request = $request;
    }

    public function request(): ?Request
    {
        return $this->request;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function setViewData(array $data): void
    {
        $this->viewData = $data;
    }

    /**
     * @return array<string, mixed>
     */
    public function viewData(): array
    {
        return $this->viewData;
    }

    protected function view(string $name, array $data = []): View
    {
        return view($name, $data);
    }

    protected function validate(array $data, array $rules): array
    {
        return app(Validator::class)->validate($data, $rules);
    }

    protected function inlineTemplate(): ?string
    {
        $app = Application::getInstance();

        if ($app !== null) {
            $loader = $app->make(InlinePageLoader::class);
            $template = $loader->templateFor(static::class);

            if (is_string($template) && trim($template) !== '') {
                return $template;
            }
        }

        $file = (new ReflectionClass($this))->getFileName();

        if (! is_string($file) || ! is_file($file)) {
            return null;
        }

        $contents = file_get_contents($file);

        if ($contents === false) {
            return null;
        }

        $template = $this->extractTemplateFromFile($contents);

        if ($template === null || trim($template) === '') {
            return null;
        }

        return ltrim($template);
    }

    private function extractTemplateFromFile(string $contents): ?string
    {
        $haltMarker = '__halt_compiler();';
        $haltPosition = strpos($contents, $haltMarker);

        if ($haltPosition !== false) {
            $remaining = substr($contents, $haltPosition + strlen($haltMarker));
            $closeTag = strpos($remaining, '?>');

            return $closeTag === false
                ? $remaining
                : substr($remaining, $closeTag + 2);
        }

        $closeTag = strpos($contents, '?>');

        if ($closeTag === false) {
            return null;
        }

        return substr($contents, $closeTag + 2);
    }

    private function inlineTemplateSourcePath(): ?string
    {
        $app = Application::getInstance();

        if ($app !== null) {
            $loader = $app->make(InlinePageLoader::class);
            $sourcePath = $loader->sourceFileFor(static::class);

            if (is_string($sourcePath) && $sourcePath !== '') {
                return $sourcePath;
            }
        }

        $file = (new ReflectionClass($this))->getFileName();

        return is_string($file) && is_file($file) ? $file : null;
    }

    /**
     * @return array<string, mixed>
     */
    private function inlineTemplateData(): array
    {
        $data = $this->viewData();

        foreach (get_object_vars($this) as $key => $value) {
            $data[$key] = $value;
        }

        return $data;
    }

    private function renderInlineTemplate(string $template): string
    {
        $app = Application::getInstance();

        if ($app === null) {
            return $this->interpolateTemplate($template);
        }

        return $app->make(PhpViewEngine::class)->renderString(
            $template,
            $this->inlineTemplateData(),
            cacheKey: static::class,
            sourcePath: $this->inlineTemplateSourcePath(),
        );
    }

    private function interpolateTemplate(string $template): string
    {
        $variables = get_object_vars($this);

        return (string) preg_replace_callback(
            '/\{\{\s*\$([a-zA-Z_][a-zA-Z0-9_]*)\s*\}\}/',
            static function (array $matches) use ($variables): string {
                $value = $variables[$matches[1]] ?? '';

                if ($value === null) {
                    return '';
                }

                return e(is_scalar($value) ? (string) $value : json_encode($value, JSON_THROW_ON_ERROR));
            },
            $template,
        );
    }
}
