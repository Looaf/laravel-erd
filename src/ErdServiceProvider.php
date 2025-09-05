<?php

namespace LaravelErd;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;

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
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register routes
        $this->registerRoutes();

        // Publish configuration
        $this->publishes([
            __DIR__ . '/../config/erd.php' => config_path('erd.php'),
        ], 'erd-config');

        // Load views
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'erd');

        // Publish views
        $this->publishes([
            __DIR__ . '/../resources/views' => resource_path('views/vendor/erd'),
        ], 'erd-views');
    }

    /**
     * Register the package routes.
     */
    protected function registerRoutes(): void
    {
        if (config('erd.enabled', true)) {
            Route::group([
                'prefix' => config('erd.route.path', 'erd'),
                'middleware' => config('erd.route.middleware', ['web']),
            ], function () {
                $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
            });
        }
    }
}
