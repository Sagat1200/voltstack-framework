<?php

declare(strict_types=1);

namespace VoltStack\Runtime\Component;

use Quantum\Http\Request;
use Quantum\Validation\Validator;
use Quantum\View\View;
use ReflectionClass;
use RuntimeException;

abstract class Component
{
    protected ?Request $request = null;

    public function render(): View|string
    {
        $template = $this->inlineTemplate();

        if ($template === null) {
            throw new RuntimeException(sprintf(
                'Component [%s] must implement render() or declare an inline page template.',
                static::class,
            ));
        }

        return $this->interpolateTemplate($template) . volt_runtime_script();
    }

    public function setRequest(?Request $request): void
    {
        $this->request = $request;
    }

    public function request(): ?Request
    {
        return $this->request;
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