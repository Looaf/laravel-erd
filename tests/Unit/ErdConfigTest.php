<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\App;
use Looaf\LaravelErd\Support\ErdConfig;
use Mockery;

class ErdConfigTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Set up default configuration using Laravel's config helper
        config([
            'erd.enabled' => true,
            'erd.environments' => ['local', 'testing'],
            'erd.route.path' => 'erd',
            'erd.route.middleware' => ['web'],
            'erd.route.name' => 'erd',
            'erd.cache.enabled' => true,
            'erd.cache.ttl' => 3600,
            'erd.cache.key' => 'laravel_erd_data',
            'erd.models.paths' => ['app/Models'],
            'erd.models.namespace' => 'App\\Models',
            'erd.models.exclude' => [],
        ]);
    }

    public function test_is_enabled_returns_true_when_enabled()
    {
        $this->assertTrue(ErdConfig::isEnabled());
    }

    public function test_is_allowed_in_current_environment_returns_true_for_local()
    {
        // Set environment to local (which is in the allowed environments)
        app()->detectEnvironment(function () {
            return 'local';
        });

        $this->assertTrue(ErdConfig::isAllowedInCurrentEnvironment());
    }

    public function test_is_allowed_in_current_environment_returns_false_for_production()
    {
        // Set environment to production (which is not in the allowed environments)
        app()->detectEnvironment(function () {
            return 'production';
        });

        $this->assertFalse(ErdConfig::isAllowedInCurrentEnvironment());
    }

    public function test_should_be_available_returns_true_when_enabled_and_allowed()
    {
        // Set environment to testing (which is in the allowed environments)
        app()->detectEnvironment(function () {
            return 'testing';
        });

        $this->assertTrue(ErdConfig::shouldBeAvailable());
    }

    public function test_get_route_path_returns_configured_path()
    {
        $this->assertEquals('erd', ErdConfig::getRoutePath());
    }

    public function test_get_model_paths_filters_empty_strings()
    {
        // Set config with empty strings that should be filtered out
        config(['erd.models.paths' => ['app/Models', '', 'app/CustomModels', '']]);

        $paths = ErdConfig::getModelPaths();
        $this->assertEquals(['app/Models', 'app/CustomModels'], array_values($paths));
    }

    public function test_get_excluded_models_filters_empty_strings()
    {
        // Set config with empty strings that should be filtered out
        config(['erd.models.exclude' => ['Model1', '', 'Model2', '']]);

        $excluded = ErdConfig::getExcludedModels();
        $this->assertEquals(['Model1', 'Model2'], array_values($excluded));
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
