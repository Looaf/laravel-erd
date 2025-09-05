<?php

namespace Looaf\LaravelErd\Support;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;

class ErdConfig
{
    /**
     * Check if ERD is enabled globally.
     */
    public static function isEnabled(): bool
    {
        return Config::get('erd.enabled', true);
    }

    /**
     * Check if ERD is allowed in the current environment.
     */
    public static function isAllowedInCurrentEnvironment(): bool
    {
        $allowedEnvironments = Config::get('erd.environments', ['local', 'testing']);
        $currentEnvironment = App::environment();

        // Handle wildcard (allow all environments)
        if (in_array('*', $allowedEnvironments)) {
            return true;
        }

        return in_array($currentEnvironment, $allowedEnvironments);
    }

    /**
     * Check if ERD should be available (enabled and in allowed environment).
     */
    public static function shouldBeAvailable(): bool
    {
        return self::isEnabled() && self::isAllowedInCurrentEnvironment();
    }

    /**
     * Get the ERD route path.
     */
    public static function getRoutePath(): string
    {
        return Config::get('erd.route.path', 'erd');
    }

    /**
     * Get the ERD route middleware.
     */
    public static function getRouteMiddleware(): array
    {
        return Config::get('erd.route.middleware', ['web']);
    }

    /**
     * Get the ERD route name prefix.
     */
    public static function getRouteName(): string
    {
        return Config::get('erd.route.name', 'erd');
    }

    /**
     * Check if caching is enabled.
     */
    public static function isCacheEnabled(): bool
    {
        return Config::get('erd.cache.enabled', true);
    }

    /**
     * Get the cache TTL in seconds.
     */
    public static function getCacheTtl(): int
    {
        return Config::get('erd.cache.ttl', 3600);
    }

    /**
     * Get the cache key prefix.
     */
    public static function getCacheKey(): string
    {
        return Config::get('erd.cache.key', 'laravel_erd_data');
    }

    /**
     * Get model discovery paths.
     */
    public static function getModelPaths(): array
    {
        $paths = Config::get('erd.models.paths', ['app/Models']);

        // Filter out empty strings that might come from environment variables
        return array_filter($paths, function ($path) {
            return !empty(trim($path));
        });
    }

    /**
     * Get the model namespace.
     */
    public static function getModelNamespace(): string
    {
        return Config::get('erd.models.namespace', 'App\\Models');
    }

    /**
     * Get excluded model classes.
     */
    public static function getExcludedModels(): array
    {
        $excluded = Config::get('erd.models.exclude', []);

        // Filter out empty strings that might come from environment variables
        return array_filter($excluded, function ($model) {
            return !empty(trim($model));
        });
    }

    /**
     * Get all configuration as an array.
     */
    public static function all(): array
    {
        return Config::get('erd', []);
    }
}
