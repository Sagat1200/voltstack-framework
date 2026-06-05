<?php

declare(strict_types=1);

return [
    'name' => 'VoltStack',
    'env' => $_ENV['APP_ENV'] ?? 'local',
    'debug' => true,
    'key' => $_ENV['APP_KEY'] ?? 'voltstack-skeleton-key',
    'providers' => [],
];