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
    | - path: The URL path where ERD will be accessible
    | - middleware: Array of middleware to apply to ERD routes
    | - name: Route name prefix for ERD routes
    |
    */
    'route' => [
        'path' => env('ERD_ROUTE_PATH', 'erd'),
        'middleware' => ['web'],
        'name' => env('ERD_ROUTE_NAME', 'erd')
    ],

    /*
    |--------------------------------------------------------------------------
    | Allowed Environments
    |--------------------------------------------------------------------------
    |
    | Specify which environments the ERD should be available in.
    | This is a security feature to prevent access in production.
    | Set to ['*'] to allow in all environments (not recommended).
    |
    */
    'environments' => explode(',', env('ERD_ENVIRONMENTS', 'local,testing')),

    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    |
    | Configure caching for ERD data to improve performance.
    | - enabled: Whether to cache ERD analysis results
    | - ttl: Time to live in seconds (3600 = 1 hour)
    | - key: Cache key prefix for ERD data
    |
    */
    'cache' => [
        'enabled' => env('ERD_CACHE_ENABLED', true),
        'ttl' => env('ERD_CACHE_TTL', 3600), // 1 hour
        'key' => env('ERD_CACHE_KEY', 'laravel_erd_data')
    ],

    /*
    |--------------------------------------------------------------------------
    | Model Configuration
    |--------------------------------------------------------------------------
    |
    | Configure how the package discovers and analyzes Eloquent models.
    | - paths: Directories to scan for models (relative to app root)
    | - namespace: Base namespace for models
    | - exclude: Array of model class names to exclude from ERD
    |
    */
    'models' => [
        'paths' => explode(',', env('ERD_MODEL_PATHS', 'app/Models')),
        'namespace' => env('ERD_MODEL_NAMESPACE', 'App\\Models'),
        'exclude' => explode(',', env('ERD_MODEL_EXCLUDE', ''))
    ]
];