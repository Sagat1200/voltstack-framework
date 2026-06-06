<?php

declare(strict_types=1);

namespace VoltStack\Runtime\Component;

use VoltStack\Framework\Application;

final class InlinePageLoader
{
    /**
     * @var array<string, string>
     */
    private array $templates = [];

    /**
     * @var array<string, string>
     */
    private array $sourceFiles = [];

    private bool $registered = false;

    public function __construct(private readonly Application $app) {}

    public function register(): void
    {
        if ($this->registered) {
            return;
        }

        spl_autoload_register($this->autoload(...), true, true);
        $this->registered = true;
    }

    public function templateFor(string $class): ?string
    {
        return $this->templates[$class] ?? null;
    }

    public function sourceFileFor(string $class): ?string
    {
        return $this->sourceFiles[$class] ?? null;
    }

    private function autoload(string $class): void
    {
        if (class_exists($class, false) || interface_exists($class, false) || trait_exists($class, false)) {
            return;
        }

        $file = $this->fileForClass($class);

        if ($file === null || ! is_file($file)) {
            return;
        }

        $contents = file_get_contents($file);

        if (! is_string($contents)) {
            return;
        }

        $separator = strpos($contents, '?>');

        if ($separator === false) {
            return;
        }

        $php = substr($contents, 0, $separator);
        $template = substr($contents, $separator + 2);

        if ($php === false || $template === false) {
            return;
        }

        $compiledPath = $this->compiledPath($file, $php);
        $directory = dirname($compiledPath);

        if (! is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        if (! is_file($compiledPath)) {
            file_put_contents($compiledPath, rtrim($php) . PHP_EOL);
        }

        require $compiledPath;

        if (! class_exists($class, false)) {
            return;
        }

        $trimmedTemplate = ltrim($template, "\r\n");

        if (trim($trimmedTemplate) !== '') {
            $this->templates[$class] = $trimmedTemplate;
        }

        $this->sourceFiles[$class] = $file;
    }

    private function fileForClass(string $class): ?string
    {
        [$namespace, $directory] = $this->namespaceAndDirectory();

        if (! str_starts_with($class, $namespace . '\\') && $class !== $namespace) {
            return null;
        }

        $relative = $class === $namespace
            ? ''
            : substr($class, strlen($namespace) + 1);

        $path = $directory;

        if ($relative !== '') {
            $path .= DIRECTORY_SEPARATOR . str_replace('\\', DIRECTORY_SEPARATOR, $relative);
        }

        return $path . '.php';
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function namespaceAndDirectory(): array
    {
        $configured = $this->app->config('ui-reactive.single_page_components');
        $directory = is_string($configured) && trim($configured) !== ''
            ? $this->normalizeDirectory($configured)
            : $this->app->basePath('app/Pages');

        $baseAppPath = $this->normalizeDirectory($this->app->basePath('app'));

        if (str_starts_with($directory, $baseAppPath)) {
            $relative = trim(substr($directory, strlen($baseAppPath)), '\\/');
            $namespace = 'App';

            if ($relative !== '') {
                $namespace .= '\\' . str_replace(['/', '\\'], '\\', $relative);
            }

            return [$namespace, $directory];
        }

        return ['App\\Pages', $directory];
    }

    private function normalizeDirectory(string $path): string
    {
        $normalized = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);

        if ($this->isAbsolutePath($normalized)) {
            return rtrim($normalized, '\\/');
        }

        return rtrim($this->app->basePath($normalized), '\\/');
    }

    private function isAbsolutePath(string $path): bool
    {
        return preg_match('/^[A-Za-z]:\\\\/', $path) === 1
            || str_starts_with($path, DIRECTORY_SEPARATOR);
    }

    private function compiledPath(string $file, string $php): string
    {
        $basePath = $this->app->config('cache.compiled.pages', $this->app->cachePath('compiled/pages'));

        return rtrim((string) $basePath, '\\/')
            . DIRECTORY_SEPARATOR
            . sha1($file . '|' . $php)
            . '.php';
    }
}
