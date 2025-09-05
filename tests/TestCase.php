<?php

namespace Tests;

use Orchestra\Testbench\TestCase as BaseTestCase;
use Looaf\LaravelErd\ErdServiceProvider;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Set up the application for testing
        $this->setUpDatabase();
    }

    protected function getPackageProviders($app)
    {
        return [
            ErdServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app)
    {
        // Set up test database
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        // Set up cache for testing
        $app['config']->set('cache.default', 'array');

        // Set up ERD package configuration
        $app['config']->set('erd', [
            'enabled' => true,
            'route' => [
                'path' => 'erd',
                'middleware' => ['web'],
                'name' => 'erd'
            ],
            'environments' => ['testing'],
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
        ]);
    }

    protected function setUpDatabase()
    {
        // This method can be overridden in individual test classes
        // to set up specific database schemas
    }
}
