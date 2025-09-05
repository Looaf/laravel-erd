# ERD Package Configuration

The Laravel ERD package provides extensive configuration options to customize its behavior for your application needs.

## Publishing Configuration

To publish the configuration file, run:

```bash
php artisan vendor:publish --tag=erd-config
```

This will create a `config/erd.php` file in your application.

## Configuration Options

### Global Enable/Disable

```php
'enabled' => env('ERD_ENABLED', true),
```

Controls whether the ERD functionality is enabled globally. Set to `false` to completely disable the package.

### Route Configuration

```php
'route' => [
    'path' => env('ERD_ROUTE_PATH', 'erd'),
    'middleware' => ['web'],
    'name' => env('ERD_ROUTE_NAME', 'erd')
],
```

- `path`: The URL path where the ERD will be accessible (default: `/erd`)
- `middleware`: Array of middleware to apply to ERD routes
- `name`: Route name prefix for ERD routes

### Environment Restrictions

```php
'environments' => explode(',', env('ERD_ENVIRONMENTS', 'local,testing')),
```

Specifies which environments the ERD should be available in. This is a security feature to prevent access in production.

- Use comma-separated values in the environment variable: `ERD_ENVIRONMENTS=local,testing,staging`
- Use `['*']` to allow in all environments (not recommended)

### Cache Configuration

```php
'cache' => [
    'enabled' => env('ERD_CACHE_ENABLED', true),
    'ttl' => env('ERD_CACHE_TTL', 3600), // 1 hour
    'key' => env('ERD_CACHE_KEY', 'laravel_erd_data')
],
```

- `enabled`: Whether to cache ERD analysis results
- `ttl`: Time to live in seconds (3600 = 1 hour)
- `key`: Cache key prefix for ERD data

### Model Discovery

```php
'models' => [
    'paths' => explode(',', env('ERD_MODEL_PATHS', 'app/Models')),
    'namespace' => env('ERD_MODEL_NAMESPACE', 'App\\Models'),
    'exclude' => explode(',', env('ERD_MODEL_EXCLUDE', ''))
]
```

- `paths`: Directories to scan for models (relative to app root)
- `namespace`: Base namespace for models
- `exclude`: Array of model class names to exclude from ERD

## Environment Variables

You can configure the package using environment variables in your `.env` file:

```env
# Enable/disable ERD
ERD_ENABLED=true

# Route settings
ERD_ROUTE_PATH=erd
ERD_ROUTE_NAME=erd

# Environment restrictions
ERD_ENVIRONMENTS=local,testing

# Cache settings
ERD_CACHE_ENABLED=true
ERD_CACHE_TTL=3600
ERD_CACHE_KEY=laravel_erd_data

# Model discovery
ERD_MODEL_PATHS=app/Models,app/CustomModels
ERD_MODEL_NAMESPACE=App\Models
ERD_MODEL_EXCLUDE=BaseModel,AbstractModel
```

## Programmatic Access

You can check ERD availability programmatically using the `ErdConfig` helper:

```php
use LaravelErd\Support\ErdConfig;

// Check if ERD is enabled
if (ErdConfig::isEnabled()) {
    // ERD is enabled
}

// Check if ERD is available in current environment
if (ErdConfig::shouldBeAvailable()) {
    // ERD is available
}

// Get configuration values
$routePath = ErdConfig::getRoutePath();
$modelPaths = ErdConfig::getModelPaths();
```

## Security Considerations

- Always restrict ERD access to non-production environments
- Use the `environments` configuration to control access
- Consider adding additional middleware for authentication if needed
- The package automatically logs when ERD is disabled due to environment restrictions