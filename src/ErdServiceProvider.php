<?php

namespace Looaf\LaravelErd;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
use Looaf\LaravelErd\Support\ErdConfig;
use Looaf\LaravelErd\Services\ModelAnalyzer;
use Looaf\LaravelErd\Services\RelationshipDetector;
use Looaf\LaravelErd\Services\ErdDataGenerator;

class ErdServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Merge package configuration
        $this->mergeConfigFrom(
            __DIR__ . '/../config/erd.php',
            'erd'
        );

        // Register a singleton for checking ERD availability
        $this->app->singleton('erd.enabled', function () {
            return ErdConfig::shouldBeAvailable();
        });

        // Register our core services
        $this->app->singleton(ModelAnalyzer::class);
        $this->app->singleton(RelationshipDetector::class);
        $this->app->singleton(ErdDataGenerator::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Only register ERD functionality if enabled and in allowed environment
        if (ErdConfig::shouldBeAvailable()) {
            $this->registerRoutes();
            $this->loadViewsFrom(__DIR__ . '/../resources/views', 'erd');
        }

        // Always allow config publishing regardless of environment
        $this->publishes([
            __DIR__ . '/../config/erd.php' => config_path('erd.php'),
        ], 'erd-config');

        // Publish views
        $this->publishes([
            __DIR__ . '/../resources/views' => resource_path('views/vendor/erd'),
        ], 'erd-views');

        // Publish assets
        $this->publishes([
            __DIR__ . '/../resources/js' => resource_path('js/vendor/erd'),
            __DIR__ . '/../resources/css' => resource_path('css/vendor/erd'),
        ], 'erd-assets');
    }

    /**
     * Register the package routes.
     */
    protected function registerRoutes(): void
    {
        Route::group([
            'prefix' => ErdConfig::getRoutePath(),
            'middleware' => ErdConfig::getRouteMiddleware(),
            'as' => ErdConfig::getRouteName() . '.',
        ], function () {
            $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
        });
    }

    /**
     * Get the ERD configuration with validation.
     */
    public function getErdConfig(): array
    {
        $config = ErdConfig::all();

        // Validate required configuration
        if (empty(ErdConfig::getRoutePath())) {
            throw new \InvalidArgumentException('ERD route path cannot be empty');
        }

        if (empty(ErdConfig::getModelPaths())) {
            throw new \InvalidArgumentException('ERD model paths cannot be empty');
        }

        return $config;
    }
}
