<?php

declare(strict_types=1);

$frameworkRoot = dirname(__DIR__);
$bundleFile = $frameworkRoot . DIRECTORY_SEPARATOR . 'frontend' . DIRECTORY_SEPARATOR . 'runtime' . DIRECTORY_SEPARATOR . 'volt.js';

function projectRootCandidates(string $frameworkRoot): array
{
    $candidates = [
        getcwd(),
        $frameworkRoot,
        dirname($frameworkRoot),
        dirname($frameworkRoot, 2),
        dirname($frameworkRoot, 3),
    ];

    return array_values(array_unique(array_filter($candidates, static fn(mixed $path): bool => is_string($path) && $path !== '')));
}

function locateEsbuildBinary(string $frameworkRoot): ?string
{
    foreach (projectRootCandidates($frameworkRoot) as $candidateRoot) {
        if (PHP_OS_FAMILY === 'Windows') {
            $nativeCandidates = [
                $candidateRoot . DIRECTORY_SEPARATOR . 'node_modules' . DIRECTORY_SEPARATOR . '@esbuild' . DIRECTORY_SEPARATOR . 'win32-x64' . DIRECTORY_SEPARATOR . 'esbuild.exe',
                $candidateRoot . DIRECTORY_SEPARATOR . 'node_modules' . DIRECTORY_SEPARATOR . '@esbuild' . DIRECTORY_SEPARATOR . 'win32-x64-msvc' . DIRECTORY_SEPARATOR . 'esbuild.exe',
            ];

            foreach ($nativeCandidates as $nativeCandidate) {
                if (is_file($nativeCandidate)) {
                    return $nativeCandidate;
                }
            }
        }

        $binaryNames = PHP_OS_FAMILY === 'Windows'
            ? ['node_modules' . DIRECTORY_SEPARATOR . '.bin' . DIRECTORY_SEPARATOR . 'esbuild.cmd']
            : ['node_modules' . DIRECTORY_SEPARATOR . '.bin' . DIRECTORY_SEPARATOR . 'esbuild'];

        foreach ($binaryNames as $binaryName) {
            $binaryPath = $candidateRoot . DIRECTORY_SEPARATOR . $binaryName;

            if (is_file($binaryPath)) {
                return $binaryPath;
            }
        }
    }

    return null;
}

if (! is_file($bundleFile)) {
    fwrite(STDERR, "Runtime bundle not found: {$bundleFile}" . PHP_EOL);
    exit(1);
}

$esbuild = locateEsbuildBinary($frameworkRoot);

if ($esbuild === null) {
    fwrite(STDERR, "Unable to locate esbuild for runtime minification." . PHP_EOL);
    exit(1);
}

$tempOutputBase = tempnam(sys_get_temp_dir(), 'volt-runtime-out-');

if ($tempOutputBase === false) {
    fwrite(STDERR, "Unable to allocate a temporary output file for runtime minification." . PHP_EOL);
    exit(1);
}

$tempOutput = $tempOutputBase . '.js';

if (! @rename($tempOutputBase, $tempOutput)) {
    @unlink($tempOutputBase);
    fwrite(STDERR, "Unable to prepare a JavaScript temporary output file for runtime minification." . PHP_EOL);
    exit(1);
}

$command = sprintf(
    '%s %s --minify --keep-names --charset=utf8 --legal-comments=none --outfile=%s 2>&1',
    escapeshellarg($esbuild),
    escapeshellarg($bundleFile),
    escapeshellarg($tempOutput),
);

exec($command, $outputLines, $exitCode);

if ($exitCode !== 0 || ! is_file($tempOutput)) {
    @unlink($tempOutput);

    fwrite(STDERR, "Runtime minification failed." . PHP_EOL);

    if ($outputLines !== []) {
        fwrite(STDERR, implode(PHP_EOL, $outputLines) . PHP_EOL);
    }

    exit(1);
}

$minified = file_get_contents($tempOutput);

@unlink($tempOutput);

if (! is_string($minified) || file_put_contents($bundleFile, $minified) === false) {
    fwrite(STDERR, "Unable to write the minified runtime bundle." . PHP_EOL);
    exit(1);
}

fwrite(STDOUT, 'Runtime bundle minified: frontend/runtime/volt.js' . PHP_EOL);
