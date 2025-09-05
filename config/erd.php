<?php

return [
    /*
    |--------------------------------------------------------------------------
    | ERD Package Enabled
    |--------------------------------------------------------------------------
    |
    | This option controls whether the ERD functionality is enabled.
    | You can disable it entirely by setting this to false.
    |
    */
    'enabled' => env('ERD_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Route Configuration
    |--------------------------------------------------------------------------
    |
    | Configure the route settings for accessing the ERD interface.
    |
    */
    'route' => [
        'path' => env('ERD_ROUTE_PATH', 'erd'),
        'middleware' => ['web'],
        'name' => 'erd.index'
    ],

    /*
    |--------------------------------------------------------------------------
    | Allowed Environments
    |--------------------------------------------------------------------------
    |
    | Specify which environments the ERD should be available in.
    | This is a security feature to prevent access in production.
    |
    */
    'environments' => ['local', 'testing'],

    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    |
    | Configure caching for ERD data to improve performance.
    |
    */
    'cache' => [
        'enabled' => true,
        'ttl' => 3600, // 1 hour
        'key' => 'laravel_erd_data'
    ],

    /*
    |--------------------------------------------------------------------------
    | Model Configuration
    |--------------------------------------------------------------------------
    |
    | Configure how the package discovers and analyzes Eloquent models.
    |
    */
    'models' => [
        'paths' => ['app/Models'],
        'namespace' => 'App\\Models',
        'exclude' => []
    ]
];