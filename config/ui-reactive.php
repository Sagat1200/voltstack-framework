<?php

declare(strict_types=1);

/**
 * UI Reactive Config
 */

return [

    /*
    |---------------------------------------------------------------------------
    | Single Page Component Location
    |---------------------------------------------------------------------------
    |
    | Here you can define the directory value for the single-page component
    |
    */
    'single_page_components' => single_page_path('app/Pages'),

    /*
    |---------------------------------------------------------------------------
    | Class-View Component Location
    |---------------------------------------------------------------------------
    |
    | Here you can define the directory value for the class-view component
    |
    */
    'class_view_components' => [
        class_path('app/View/Components'),
        view_path('resources/views'),
    ],
];