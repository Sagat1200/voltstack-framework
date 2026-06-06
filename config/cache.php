<?php

declare(strict_types=1);

return [
    'default' => 'file',

    'prefix' => 'voltstack',

    'stores' => [
        'file' => [
            'driver' => 'file',
            'path' => storage_path('framework/cache/data'),
            'prefix' => 'voltstack',
        ],
    ],

    'compiled' => [
        'views' => storage_path('framework/cache/compiled/views'),
        'pages' => storage_path('framework/cache/compiled/pages'),
    ],
];
