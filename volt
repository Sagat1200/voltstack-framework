<?php

declare(strict_types=1);

use Quantum\Console\ConsoleApplication;

$autoloadCandidates = [
    getcwd() . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php',
    __DIR__ . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php',
];

foreach ($autoloadCandidates as $autoloadPath) {
    if (is_file($autoloadPath)) {
        require_once $autoloadPath;
        break;
    }
}

if (! class_exists(ConsoleApplication::class)) {
    fwrite(STDERR, "VoltStack console bootstrap failed: autoload.php was not found." . PHP_EOL);

    exit(1);
}

$basePath = defined('VOLT_BASE_PATH') ? VOLT_BASE_PATH : getcwd();
$console = new ConsoleApplication($basePath);

exit($console->run($_SERVER['argv'] ?? []));