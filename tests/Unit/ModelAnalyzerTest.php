<?php

namespace Tests\Unit;

use Tests\TestCase;
use Looaf\LaravelErd\Services\ModelAnalyzer;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\File;

class ModelAnalyzerTest extends TestCase
{

    protected ModelAnalyzer $analyzer;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Clear cache before each test
        Cache::flush();
        
        // Set up test configuration
        config([
            'erd.models.paths' => ['tests/Fixtures/Models'],
            'erd.models.namespace' => 'Tests\\Fixtures\\Models',
            'erd.models.exclude' => ['ExcludedModel'],
            'erd.cache.enabled' => true,
            'erd.cache.ttl' => 3600,
            'erd.cache.key' => 'test_erd_data'
        ]);
        
        // Create analyzer after config is set
        $this->analyzer = new ModelAnalyzer();
    }

    /** @test */
    public function it_can_discover_models_in_configured_paths()
    {
        $models = $this->analyzer->discoverModels();
        

        
        $this->assertIsArray($models);
        $this->assertContains('Tests\\Fixtures\\Models\\TestUser', $models);
        $this->assertContains('Tests\\Fixtures\\Models\\TestPost', $models);
        $this->assertNotContains('Tests\\Fixtures\\Models\\ExcludedModel', $models);
    }

    /** @test */
    public function it_caches_discovered_models()
    {
        // First call should cache the results
        $models1 = $this->analyzer->discoverModels();
        
        // Second call should return cached results
        $models2 = $this->analyzer->discoverModels();
        
        $this->assertEquals($models1, $models2);
        $this->assertTrue(Cache::has('test_erd_data_models_discovery'));
    }

    /** @test */
    public function it_can_analyze_model_metadata()
    {
        $this->createTestUserTable();
        
        $metadata = $this->analyzer->analyzeModel('Tests\\Fixtures\\Models\\TestUser');
        
        $this->assertIsArray($metadata);
        $this->assertEquals('Tests\\Fixtures\\Models\\TestUser', $metadata['class']);
        $this->assertEquals('test_users', $metadata['table']);
        $this->assertEquals('id', $metadata['primary_key']);
        $this->assertTrue($metadata['timestamps']);
        $this->assertIsArray($metadata['columns']);
        $this->assertIsArray($metadata['fillable']);
        $this->assertIsArray($metadata['casts']);
    }

    /** @test */
    public function it_extracts_column_information_correctly()
    {
        $this->createTestUserTable();
        $this->createTestModelFiles();
        
        $metadata = $this->analyzer->analyzeModel('Tests\\Fixtures\\Models\\TestUser');
        $columns = $metadata['columns'];
        
        $this->assertGreaterThan(0, count($columns));
        
        $idColumn = collect($columns)->firstWhere('name', 'id');
        $this->assertNotNull($idColumn);
        $this->assertEquals('id', $idColumn['name']);
        $this->assertFalse($idColumn['nullable']);
        
        $nameColumn = collect($columns)->firstWhere('name', 'name');
        $this->assertNotNull($nameColumn);
        $this->assertEquals('name', $nameColumn['name']);
    }

    /** @test */
    public function it_handles_missing_tables_gracefully()
    {
        $this->createTestModelFiles();
        
        // Analyze model without creating the table
        $metadata = $this->analyzer->analyzeModel('Tests\\Fixtures\\Models\\TestPost');
        
        $this->assertIsArray($metadata);
        $this->assertEquals('Tests\\Fixtures\\Models\\TestPost', $metadata['class']);
        $this->assertEquals('test_posts', $metadata['table']);
        $this->assertEmpty($metadata['columns']); // No columns because table doesn't exist
    }

    /** @test */
    public function it_filters_excluded_models()
    {
        $this->createTestModelFiles();
        $this->createExcludedModelFile();
        
        $models = $this->analyzer->discoverModels();
        
        $this->assertNotContains('Tests\\Fixtures\\Models\\ExcludedModel', $models);
    }

    /** @test */
    public function it_returns_empty_array_for_non_existent_model()
    {
        $metadata = $this->analyzer->analyzeModel('NonExistentModel');
        
        $this->assertEmpty($metadata);
    }

    /** @test */
    public function it_can_get_metadata_for_all_models()
    {
        $this->createTestUserTable();
        $this->createTestModelFiles();
        
        $allMetadata = $this->analyzer->getModelMetadata();
        
        $this->assertIsArray($allMetadata);
        $this->assertArrayHasKey('Tests\\Fixtures\\Models\\TestUser', $allMetadata);
        $this->assertArrayHasKey('Tests\\Fixtures\\Models\\TestPost', $allMetadata);
    }

    /** @test */
    public function it_can_clear_cache()
    {
        // Cache some data
        $this->analyzer->discoverModels();
        $this->assertTrue(Cache::has('test_erd_data_models_discovery'));
        
        // Clear cache
        $this->analyzer->clearCache();
        
        $this->assertFalse(Cache::has('test_erd_data_models_discovery'));
    }

    /** @test */
    public function it_respects_cache_configuration()
    {
        config(['erd.cache.enabled' => false]);
        $analyzer = new ModelAnalyzer();
        
        $this->createTestModelFiles();
        
        $analyzer->discoverModels();
        
        // Should not cache when caching is disabled
        $this->assertFalse(Cache::has('test_erd_data_models_discovery'));
    }

    /** @test */
    public function it_handles_invalid_model_classes_gracefully()
    {
        // Create a PHP file that doesn't contain a valid model
        $this->createInvalidModelFile();
        
        $models = $this->analyzer->discoverModels();
        
        // Should not include invalid model
        $this->assertNotContains('Tests\\Fixtures\\Models\\InvalidModel', $models);
    }

    /** @test */
    public function it_detects_model_properties_correctly()
    {
        $this->createTestUserTable();
        $this->createTestModelFiles();
        
        $metadata = $this->analyzer->analyzeModel('Tests\\Fixtures\\Models\\TestUser');
        
        $this->assertEquals(['name', 'email'], $metadata['fillable']);
        $this->assertEquals(['password'], $metadata['guarded']);
        $this->assertArrayHasKey('email_verified_at', $metadata['casts']);
    }

    protected function createTestUserTable()
    {
        Schema::create('test_users', function ($table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->timestamps();
        });
    }

    protected function createTestModelFiles()
    {
        $modelsPath = base_path('tests/Fixtures/Models');
        File::ensureDirectoryExists($modelsPath);
        
        // Create TestUser model
        File::put($modelsPath . '/TestUser.php', '<?php

namespace Tests\Fixtures\Models;

use Illuminate\Database\Eloquent\Model;

class TestUser extends Model
{
    protected $table = "test_users";
    
    protected $fillable = ["name", "email"];
    
    protected $guarded = ["password"];
    
    protected $casts = [
        "email_verified_at" => "datetime"
    ];
    
    public function posts()
    {
        return $this->hasMany(TestPost::class);
    }
}');

        // Create TestPost model
        File::put($modelsPath . '/TestPost.php', '<?php

namespace Tests\Fixtures\Models;

use Illuminate\Database\Eloquent\Model;

class TestPost extends Model
{
    protected $table = "test_posts";
    
    protected $fillable = ["title", "content"];
    
    public function user()
    {
        return $this->belongsTo(TestUser::class);
    }
}');
    }

    protected function createExcludedModelFile()
    {
        $modelsPath = base_path('tests/Fixtures/Models');
        File::ensureDirectoryExists($modelsPath);
        
        File::put($modelsPath . '/ExcludedModel.php', '<?php

namespace Tests\Fixtures\Models;

use Illuminate\Database\Eloquent\Model;

class ExcludedModel extends Model
{
    protected $table = "excluded_table";
}');
    }

    protected function createInvalidModelFile()
    {
        $modelsPath = base_path('tests/Fixtures/Models');
        File::ensureDirectoryExists($modelsPath);
        
        File::put($modelsPath . '/InvalidModel.php', '<?php

namespace Tests\Fixtures\Models;

class InvalidModel
{
    // Not an Eloquent model
}');
    }

    protected function tearDown(): void
    {
        // Clean up test files
        $modelsPath = base_path('tests/Fixtures/Models');
        if (File::exists($modelsPath)) {
            File::deleteDirectory(dirname($modelsPath));
        }
        
        parent::tearDown();
    }
}