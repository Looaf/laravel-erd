<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use LaravelErd\Support\ErdConfig;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\App;

class ErdConfigTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Mock the Config facade
        Config::shouldReceive('get')->andReturnUsing(function ($key, $default = null) {
            $config = [
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
                'erd' => [
                    'enabled' => true,
                    'environments' => ['local', 'testing'],
                    'route' => [
                        'path' => 'erd',
                        'middleware' => ['web'],
                        'name' => 'erd'
                    ],
                    'cache' => [
                        'enabled' => true,
                        'ttl' => 3600,
                        'key' => 'laravel_erd_data'
                    ],
                    'models' => [
                        'paths' => ['app/Models'],
                        'namespace' => 'App\\Models',
                        'exclude' => []
                    ]
                ]
            ];
            
            return $config[$key] ?? $default;
        });
    }

    public function test_is_enabled_returns_true_when_enabled()
    {
        $this->assertTrue(ErdConfig::isEnabled());
    }

    public function test_is_allowed_in_current_environment_returns_true_for_local()
    {
        App::shouldReceive('environment')->andReturn('local');
        $this->assertTrue(ErdConfig::isAllowedInCurrentEnvironment());
    }

    public function test_is_allowed_in_current_environment_returns_false_for_production()
    {
        App::shouldReceive('environment')->andReturn('production');
        $this->assertFalse(ErdConfig::isAllowedInCurrentEnvironment());
    }

    public function test_should_be_available_returns_true_when_enabled_and_allowed()
    {
        App::shouldReceive('environment')->andReturn('local');
        $this->assertTrue(ErdConfig::shouldBeAvailable());
    }

    public function test_get_route_path_returns_configured_path()
    {
        $this->assertEquals('erd', ErdConfig::getRoutePath());
    }

    public function test_get_model_paths_filters_empty_strings()
    {
        Config::shouldReceive('get')
            ->with('erd.models.paths', ['app/Models'])
            ->andReturn(['app/Models', '', 'app/CustomModels', '']);
            
        $paths = ErdConfig::getModelPaths();
        $this->assertEquals(['app/Models', 'app/CustomModels'], $paths);
    }

    public function test_get_excluded_models_filters_empty_strings()
    {
        Config::shouldReceive('get')
            ->with('erd.models.exclude', [])
            ->andReturn(['Model1', '', 'Model2', '']);
            
        $excluded = ErdConfig::getExcludedModels();
        $this->assertEquals(['Model1', 'Model2'], $excluded);
    }
}