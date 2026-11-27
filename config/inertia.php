<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Server Side Rendering
    |--------------------------------------------------------------------------
    |
    | These options configures if and how Inertia uses Server Side Rendering
    | to pre-render the initial visit made to the single-page web application.
    |
    */

    'ssr' => [
        'enabled' => true,
        'url' => 'http://127.0.0.1:13714',
    ],

    /*
    |--------------------------------------------------------------------------
    | Inertia Testing
    |--------------------------------------------------------------------------
    |
    | This option creates a mock of the Inertia response which allows you to
    | test your Inertia responses correctly, even when using SSR.
    |
    */

    'testing' => [
        'ensure_pages_exist' => false,
        'render_once' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Inertia View Finder
    |--------------------------------------------------------------------------
    |
    | The configuration resolves the path for Inertia page components. By
    | default, it expects pages to be in the `resources/js/Pages` directory.
    | Since this project uses TypeScript, pages are in `resources/ts/Pages`.
    |
    */

    'view_finder' => [
        'path' => resource_path('ts/Pages'),
    ],

];
