<?php

declare(strict_types=1);

$frameworkRoot = dirname(__DIR__);
$sourceDirectory = $frameworkRoot . DIRECTORY_SEPARATOR . 'frontend' . DIRECTORY_SEPARATOR . 'runtime' . DIRECTORY_SEPARATOR . 'src';
$outputFile = $frameworkRoot . DIRECTORY_SEPARATOR . 'frontend' . DIRECTORY_SEPARATOR . 'runtime' . DIRECTORY_SEPARATOR . 'volt.js';

function projectRootCandidates(string $frameworkRoot): array
{
    $candidates = [
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
            $nativeMatches = glob(
                $candidateRoot
                    . DIRECTORY_SEPARATOR . 'node_modules'
                    . DIRECTORY_SEPARATOR . '@esbuild'
                    . DIRECTORY_SEPARATOR . '*'
                    . DIRECTORY_SEPARATOR . 'esbuild.exe'
            );

            if (is_array($nativeMatches) && $nativeMatches !== []) {
                return $nativeMatches[0];
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

function minifyRuntimeBundleFile(string $bundleFile, string $frameworkRoot): bool
{
    $esbuild = locateEsbuildBinary($frameworkRoot);

    if ($esbuild === null) {
        return false;
    }

    $tempOutputBase = tempnam(sys_get_temp_dir(), 'volt-runtime-out-');

    if ($tempOutputBase === false) {
        if (is_string($tempOutputBase) && is_file($tempOutputBase)) {
            @unlink($tempOutputBase);
        }

        return false;
    }

    $tempOutput = $tempOutputBase . '.js';

    if (! @rename($tempOutputBase, $tempOutput)) {
        @unlink($tempOutputBase);
        return false;
    }

    $command = sprintf(
        '%s %s --minify --charset=utf8 --legal-comments=none --outfile=%s 2>&1',
        escapeshellarg($esbuild),
        escapeshellarg($bundleFile),
        escapeshellarg($tempOutput),
    );

    exec($command, $outputLines, $exitCode);

    if ($exitCode !== 0 || ! is_file($tempOutput)) {
        fwrite(STDERR, "esbuild minification failed, falling back to concatenated output." . PHP_EOL);

        if ($outputLines !== []) {
            fwrite(STDERR, implode(PHP_EOL, $outputLines) . PHP_EOL);
        }

        @unlink($tempOutput);

        return false;
    }

    $minified = file_get_contents($tempOutput);

    @unlink($tempOutput);

    if (! is_string($minified) || file_put_contents($bundleFile, $minified) === false) {
        return false;
    }

    return true;
}

if (! is_dir($sourceDirectory)) {
    fwrite(STDERR, "Runtime source directory not found: {$sourceDirectory}" . PHP_EOL);
    exit(1);
}

$sourceFiles = glob($sourceDirectory . DIRECTORY_SEPARATOR . '*.js');

if ($sourceFiles === false || $sourceFiles === []) {
    fwrite(STDERR, "No runtime source files found in {$sourceDirectory}" . PHP_EOL);
    exit(1);
}

sort($sourceFiles, SORT_NATURAL);

$chunks = [];

foreach ($sourceFiles as $sourceFile) {
    $contents = file_get_contents($sourceFile);

    if (! is_string($contents)) {
        fwrite(STDERR, "Unable to read runtime source file: {$sourceFile}" . PHP_EOL);
        exit(1);
    }

    $normalizedContents = preg_replace("/^\xEF\xBB\xBF/", '', $contents);

    if (! is_string($normalizedContents)) {
        fwrite(STDERR, "Unable to normalize runtime source file: {$sourceFile}" . PHP_EOL);
        exit(1);
    }

    $chunks[] = rtrim($normalizedContents, "\r\n");
}

$banner = [
    '// Generated file. Do not edit directly.',
    '// Source: frontend/runtime/src/*.js',
    '// Rebuild: php tools/build-runtime.php',
    '',
];

$output = implode(PHP_EOL, $banner) . implode(PHP_EOL . PHP_EOL, $chunks) . PHP_EOL;

if (file_put_contents($outputFile, $output) === false) {
    fwrite(STDERR, "Unable to write runtime bundle: {$outputFile}" . PHP_EOL);
    exit(1);
}

$minified = minifyRuntimeBundleFile($outputFile, $frameworkRoot);

if (! $minified && PHP_OS_FAMILY === 'Windows') {
    usleep(50_000);
    minifyRuntimeBundleFile($outputFile, $frameworkRoot);
}

fwrite(STDOUT, 'Runtime bundle generated: frontend/runtime/volt.js' . PHP_EOL);
