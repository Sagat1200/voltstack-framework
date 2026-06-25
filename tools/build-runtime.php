<?php

declare(strict_types=1);

$frameworkRoot = dirname(__DIR__);
$sourceDirectory = $frameworkRoot . DIRECTORY_SEPARATOR . 'frontend' . DIRECTORY_SEPARATOR . 'runtime' . DIRECTORY_SEPARATOR . 'src';
$outputFile = $frameworkRoot . DIRECTORY_SEPARATOR . 'frontend' . DIRECTORY_SEPARATOR . 'runtime' . DIRECTORY_SEPARATOR . 'volt.js';

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

fwrite(STDOUT, 'Runtime bundle generated: frontend/runtime/volt.js' . PHP_EOL);
