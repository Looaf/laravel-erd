<?php

namespace Tests\Unit;

use Tests\TestCase;
use Looaf\LaravelErd\Services\ErdDataGenerator;
use Looaf\LaravelErd\Services\ModelAnalyzer;
use Looaf\LaravelErd\Services\RelationshipDetector;
use Illuminate\Support\Facades\Cache;
use Mockery;

class ErdDataGeneratorTest extends TestCase
{

    protected ErdDataGenerator $generator;
    protected $mockModelAnalyzer;
    protected $mockRelationshipDetector;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Clear cache before each test
        Cache::flush();
        
        // Set up test configuration
        config([
            'erd.cache.enabled' => true,
            'erd.cache.ttl' => 3600,
            'erd.cache.key' => 'test_erd_data'
        ]);

        // Create mocks
        $this->mockModelAnalyzer = Mockery::mock(ModelAnalyzer::class);
        $this->mockRelationshipDetector = Mockery::mock(RelationshipDetector::class);
        
        $this->generator = new ErdDataGenerator(
            $this->mockModelAnalyzer,
            $this->mockRelationshipDetector
        );
    }

    /** @test */
    public function it_generates_complete_erd_data_structure()
    {
        $this->setupMockData();
        
        $erdData = $this->generator->generateErdData();
        
        $this->assertIsArray($erdData);
        $this->assertArrayHasKey('tables', $erdData);
        $this->assertArrayHasKey('relationships', $erdData);
        $this->assertArrayHasKey('metadata', $erdData);
    }

    /** @test */
    public function it_transforms_models_to_tables_correctly()
    {
        $this->setupMockData();
        
        $erdData = $this->generator->generateErdData();
        $tables = $erdData['tables'];
        
        $this->assertCount(2, $tables);
        
        $userTable = collect($tables)->firstWhere('name', 'users');
        $this->assertNotNull($userTable);
        $this->assertEquals('table_users', $userTable['id']);
        $this->assertEquals('users', $userTable['name']);
        $this->assertEquals('App\\Models\\User', $userTable['model']);
        $this->assertEquals('id', $userTable['primary_key']);
        $this->assertTrue($userTable['timestamps']);
        $this->assertArrayHasKey('position', $userTable);
        $this->assertArrayHasKey('columns', $userTable);
    }

    /** @test */
    public function it_transforms_columns_correctly()
    {
        $this->setupMockData();
        
        $erdData = $this->generator->generateErdData();
        $userTable = collect($erdData['tables'])->firstWhere('name', 'users');
        $columns = $userTable['columns'];
        
        $this->assertCount(3, $columns);
        
        $idColumn = collect($columns)->firstWhere('name', 'id');
        $this->assertEquals('id', $idColumn['name']);
        $this->assertEquals('integer', $idColumn['type']); // Normalized from bigint
        $this->assertFalse($idColumn['nullable']);
        $this->assertFalse($idColumn['primary']); // Set separately
        $this->assertFalse($idColumn['foreign']); // Determined from relationships
    }

    /** @test */
    public function it_transforms_relationships_to_connections_correctly()
    {
        $this->setupMockData();
        
        $erdData = $this->generator->generateErdData();
        $relationships = $erdData['relationships'];
        
        $this->assertCount(2, $relationships);
        
        $hasManyConnection = collect($relationships)->firstWhere('type', 'hasMany');
        $this->assertNotNull($hasManyConnection);
        $this->assertEquals('table_users', $hasManyConnection['source']);
        $this->assertEquals('table_posts', $hasManyConnection['target']);
        $this->assertEquals('hasMany', $hasManyConnection['type']);
        $this->assertEquals('posts', $hasManyConnection['method']);
        $this->assertEquals('posts (has many)', $hasManyConnection['label']);
        $this->assertArrayHasKey('foreign_key', $hasManyConnection);
        $this->assertArrayHasKey('local_key', $hasManyConnection);
    }

    /** @test */
    public function it_generates_metadata_correctly()
    {
        $this->setupMockData();
        
        $erdData = $this->generator->generateErdData();
        $metadata = $erdData['metadata'];
        
        $this->assertEquals(2, $metadata['total_tables']);
        $this->assertEquals(2, $metadata['total_relationships']);
        $this->assertArrayHasKey('relationship_types', $metadata);
        $this->assertEquals(1, $metadata['relationship_types']['hasMany']);
        $this->assertEquals(1, $metadata['relationship_types']['belongsTo']);
        $this->assertArrayHasKey('generated_at', $metadata);
        $this->assertArrayHasKey('models_analyzed', $metadata);
        $this->assertEquals('1.0.0', $metadata['version']);
    }

    /** @test */
    public function it_positions_tables_in_grid_layout()
    {
        $this->setupMockData();
        
        $erdData = $this->generator->generateErdData();
        $tables = $erdData['tables'];
        
        $userTable = collect($tables)->firstWhere('name', 'users');
        $postTable = collect($tables)->firstWhere('name', 'posts');
        
        // First table should be at starting position
        $this->assertEquals(100, $userTable['position']['x']);
        $this->assertEquals(100, $userTable['position']['y']);
        
        // Second table should be spaced horizontally
        $this->assertEquals(400, $postTable['position']['x']); // 100 + 300 spacing
        $this->assertEquals(100, $postTable['position']['y']);
    }

    /** @test */
    public function it_normalizes_column_types()
    {
        $this->setupMockData();
        
        $erdData = $this->generator->generateErdData();
        $userTable = collect($erdData['tables'])->firstWhere('name', 'users');
        $columns = $userTable['columns'];
        
        $idColumn = collect($columns)->firstWhere('name', 'id');
        $this->assertEquals('integer', $idColumn['type']); // bigint -> integer
        
        $nameColumn = collect($columns)->firstWhere('name', 'name');
        $this->assertEquals('string', $nameColumn['type']); // varchar -> string
    }

    /** @test */
    public function it_caches_erd_data()
    {
        $this->setupMockData();
        
        // First call should cache the results
        $erdData1 = $this->generator->generateErdData();
        
        // Second call should return cached results (mocks won't be called again)
        $erdData2 = $this->generator->generateErdData();
        
        $this->assertEquals($erdData1, $erdData2);
        $this->assertTrue(Cache::has('test_erd_data_complete_erd_data'));
    }

    /** @test */
    public function it_returns_empty_erd_data_when_no_models_found()
    {
        $this->mockModelAnalyzer
            ->shouldReceive('getModelMetadata')
            ->once()
            ->andReturn([]);
        
        $erdData = $this->generator->generateErdData();
        
        $this->assertEmpty($erdData['tables']);
        $this->assertEmpty($erdData['relationships']);
        $this->assertEquals(0, $erdData['metadata']['total_tables']);
        $this->assertEquals(0, $erdData['metadata']['total_relationships']);
        $this->assertStringContainsString('No Eloquent models found', $erdData['metadata']['message']);
    }

    /** @test */
    public function it_handles_errors_gracefully()
    {
        $this->mockModelAnalyzer
            ->shouldReceive('getModelMetadata')
            ->once()
            ->andThrow(new \Exception('Test error'));
        
        $erdData = $this->generator->generateErdData();
        
        $this->assertEmpty($erdData['tables']);
        $this->assertEmpty($erdData['relationships']);
        $this->assertTrue($erdData['metadata']['error']);
        $this->assertStringContainsString('Failed to generate ERD', $erdData['metadata']['message']);
    }

    /** @test */
    public function it_can_refresh_erd_data()
    {
        // Set up mock data with expectations for multiple calls
        $modelsMetadata = $this->getTestModelsMetadata();
        $relationshipsData = $this->getTestRelationshipsData();
        
        $this->mockModelAnalyzer
            ->shouldReceive('getModelMetadata')
            ->twice() // Called once for generateErdData, once for refreshErdData
            ->andReturn($modelsMetadata);
        
        $this->mockRelationshipDetector
            ->shouldReceive('getRelationshipsForModels')
            ->twice()
            ->with(array_keys($modelsMetadata))
            ->andReturn($relationshipsData);
        
        $this->mockRelationshipDetector
            ->shouldReceive('validateRelationship')
            ->andReturn(true);
        
        // Generate and cache data
        $this->generator->generateErdData();
        $this->assertTrue(Cache::has('test_erd_data_complete_erd_data'));
        
        // Mock clear cache methods
        $this->mockModelAnalyzer->shouldReceive('clearCache')->once();
        $this->mockRelationshipDetector->shouldReceive('clearCache')->once();
        
        // Refresh should clear cache and regenerate
        $freshData = $this->generator->refreshErdData();
        
        $this->assertIsArray($freshData);
        $this->assertArrayHasKey('tables', $freshData);
    }

    /** @test */
    public function it_provides_safe_erd_data_generation()
    {
        $this->mockModelAnalyzer
            ->shouldReceive('getModelMetadata')
            ->once()
            ->andThrow(new \Exception('Critical error'));
        
        // Should not throw exception
        $erdData = $this->generator->getErdDataSafely();
        
        $this->assertIsArray($erdData);
        $this->assertArrayHasKey('metadata', $erdData);
        $this->assertTrue($erdData['metadata']['error']);
    }

    /** @test */
    public function it_can_clear_cache()
    {
        $this->setupMockData();
        
        // Cache some data
        $this->generator->generateErdData();
        $this->assertTrue(Cache::has('test_erd_data_complete_erd_data'));
        
        // Clear cache
        $this->generator->clearCache();
        
        $this->assertFalse(Cache::has('test_erd_data_complete_erd_data'));
    }

    /** @test */
    public function it_validates_relationships_before_including_them()
    {
        $modelsMetadata = $this->getTestModelsMetadata();
        $relationshipsData = [
            'App\\Models\\User' => [
                'validRelationship' => [
                    'type' => 'hasMany',
                    'related' => 'App\\Models\\Post',
                    'method' => 'posts',
                    'foreign_key' => 'user_id',
                    'local_key' => 'id',
                    'related_table' => 'posts'
                ],
                'invalidRelationship' => [
                    'type' => 'hasMany',
                    'related' => 'NonExistentModel',
                    'method' => 'invalid'
                ]
            ]
        ];
        
        $this->mockModelAnalyzer
            ->shouldReceive('getModelMetadata')
            ->once()
            ->andReturn($modelsMetadata);
        
        $this->mockRelationshipDetector
            ->shouldReceive('getRelationshipsForModels')
            ->once()
            ->andReturn($relationshipsData);
        
        $this->mockRelationshipDetector
            ->shouldReceive('validateRelationship')
            ->with($relationshipsData['App\\Models\\User']['validRelationship'])
            ->once()
            ->andReturn(true);
        
        // Note: validateRelationship is not called for invalidRelationship because
        // createConnection returns null for non-existent related models
        
        $erdData = $this->generator->generateErdData();
        
        // Should only include valid relationship
        $this->assertCount(1, $erdData['relationships']);
        $this->assertEquals('hasMany', $erdData['relationships'][0]['type']);
    }

    /** @test */
    public function it_respects_cache_configuration()
    {
        config(['erd.cache.enabled' => false]);
        $generator = new ErdDataGenerator($this->mockModelAnalyzer, $this->mockRelationshipDetector);
        
        $this->setupMockData();
        
        $generator->generateErdData();
        
        // Should not cache when caching is disabled
        $this->assertFalse(Cache::has('test_erd_data_complete_erd_data'));
    }

    protected function setupMockData()
    {
        $modelsMetadata = $this->getTestModelsMetadata();
        $relationshipsData = $this->getTestRelationshipsData();
        
        $this->mockModelAnalyzer
            ->shouldReceive('getModelMetadata')
            ->once()
            ->andReturn($modelsMetadata);
        
        $this->mockRelationshipDetector
            ->shouldReceive('getRelationshipsForModels')
            ->once()
            ->with(array_keys($modelsMetadata))
            ->andReturn($relationshipsData);
        
        // Mock validation calls
        $this->mockRelationshipDetector
            ->shouldReceive('validateRelationship')
            ->andReturn(true);
    }

    protected function getTestModelsMetadata(): array
    {
        return [
            'App\\Models\\User' => [
                'class' => 'App\\Models\\User',
                'table' => 'users',
                'columns' => [
                    ['name' => 'id', 'type' => 'bigint', 'nullable' => false, 'default' => null],
                    ['name' => 'name', 'type' => 'varchar', 'nullable' => false, 'default' => null],
                    ['name' => 'email', 'type' => 'varchar', 'nullable' => false, 'default' => null]
                ],
                'primary_key' => 'id',
                'timestamps' => true,
                'fillable' => ['name', 'email'],
                'guarded' => ['*'],
                'casts' => ['email_verified_at' => 'datetime']
            ],
            'App\\Models\\Post' => [
                'class' => 'App\\Models\\Post',
                'table' => 'posts',
                'columns' => [
                    ['name' => 'id', 'type' => 'bigint', 'nullable' => false, 'default' => null],
                    ['name' => 'title', 'type' => 'varchar', 'nullable' => false, 'default' => null],
                    ['name' => 'user_id', 'type' => 'bigint', 'nullable' => false, 'default' => null]
                ],
                'primary_key' => 'id',
                'timestamps' => true,
                'fillable' => ['title', 'content'],
                'guarded' => ['*'],
                'casts' => []
            ]
        ];
    }

    protected function getTestRelationshipsData(): array
    {
        return [
            'App\\Models\\User' => [
                'posts' => [
                    'type' => 'hasMany',
                    'related' => 'App\\Models\\Post',
                    'method' => 'posts',
                    'foreign_key' => 'user_id',
                    'local_key' => 'id',
                    'related_table' => 'posts'
                ]
            ],
            'App\\Models\\Post' => [
                'user' => [
                    'type' => 'belongsTo',
                    'related' => 'App\\Models\\User',
                    'method' => 'user',
                    'foreign_key' => 'user_id',
                    'owner_key' => 'id',
                    'parent_table' => 'users'
                ]
            ]
        ];
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}