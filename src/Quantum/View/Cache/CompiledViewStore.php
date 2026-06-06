<?php

declare(strict_types=1);

namespace Quantum\View\Cache;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Quantum\View\Compilers\ViewCompiler;
use RuntimeException;

final class CompiledViewStore
{
    public function __construct(
        private readonly ViewCompiler $compiler,
        private readonly string $compiledPath,
    ) {
    }

    public function compiledPathFor(string $sourcePath): string
    {
        return rtrim($this->compiledPath, '\\/')
            . DIRECTORY_SEPARATOR
            . md5($sourcePath . '|' . $this->compiler->version())
            . '.php';
    }

    public function directory(): string
    {
        return rtrim($this->compiledPath, '\\/');
    }

    public function ensureCompiled(string $sourcePath): string
    {
        $compiledPath = $this->compiledPathFor($sourcePath);

        if (! $this->isExpired($sourcePath, $compiledPath)) {
            return $compiledPath;
        }

        $directory = dirname($compiledPath);

        if (! is_dir($directory) && ! mkdir($directory, 0777, true) && ! is_dir($directory)) {
            throw new RuntimeException(sprintf('Unable to create compiled view directory [%s].', $directory));
        }

        $contents = file_get_contents($sourcePath);

        if ($contents === false) {
            throw new RuntimeException(sprintf('Unable to read source view [%s].', $sourcePath));
        }

        $compiled = $this->compiler->compileString($contents);
        $header = $this->header($sourcePath);

        if (file_put_contents($compiledPath, $header . $compiled) === false) {
            throw new RuntimeException(sprintf('Unable to write compiled view [%s].', $compiledPath));
        }

        return $compiledPath;
    }

    public function clear(): int
    {
        $directory = $this->directory();

        if (! is_dir($directory)) {
            return 0;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST,
        );

        $deleted = 0;

        foreach ($iterator as $item) {
            $path = $item->getPathname();

            if ($item->isDir()) {
                if (! @rmdir($path) && is_dir($path)) {
                    throw new RuntimeException(sprintf('Unable to remove compiled view directory [%s].', $path));
                }

                continue;
            }

            if (! @unlink($path) && is_file($path)) {
                throw new RuntimeException(sprintf('Unable to remove compiled view file [%s].', $path));
            }

            $deleted++;
        }

        if (! @rmdir($directory) && is_dir($directory)) {
            throw new RuntimeException(sprintf('Unable to remove compiled view root directory [%s].', $directory));
        }

        return $deleted;
    }

    private function isExpired(string $sourcePath, string $compiledPath): bool
    {
        if (! is_file($compiledPath)) {
            return true;
        }

        $sourceModifiedAt = filemtime($sourcePath);
        $compiledModifiedAt = filemtime($compiledPath);

        if ($sourceModifiedAt === false || $compiledModifiedAt === false) {
            return true;
        }

        return $sourceModifiedAt >= $compiledModifiedAt;
    }

    private function header(string $sourcePath): string
    {
        return sprintf(
            "<?php\n/**\n * VoltStack Compiled View\n * Source: %s\n * Compiler: %s\n */\n?>",
            str_replace('\\', '\\\\', $sourcePath),
            $this->compiler->version(),
        );
    }
}
