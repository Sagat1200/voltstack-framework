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

    /*
    |---------------------------------------------------------------------------
    | Volt Navigate Progress Bar
    |---------------------------------------------------------------------------
    |
    | Here you can specify whether to show a progress bar when navigating between 
    | SPA links.
    |
    */
    'volt_navigate' => [
        'progress_bar' => true,
        'progress_bar_color' => '#2299dd',
    ],
];