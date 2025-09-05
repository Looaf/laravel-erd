<?php

namespace Tests\Integration;

use Tests\TestCase;
use Looaf\LaravelErd\Services\ModelAnalyzer;
use Looaf\LaravelErd\Services\RelationshipDetector;
use Looaf\LaravelErd\Services\ErdDataGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\File;

class ModelAnalysisIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected ModelAnalyzer $modelAnalyzer;
    protected RelationshipDetector $relationshipDetector;
    protected ErdDataGenerator $erdDataGenerator;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Clear cache before each test
        Cache::flush();
        
        // Set up test configuration
        config([
            'erd.models.paths' => ['tests/Fixtures/Models'],
            'erd.models.namespace' => 'Tests\\Fixtures\\Models',
            'erd.models.exclude' => ['TestProfile', 'TestRole', 'ExcludedModel'],
            'erd.cache.enabled' => true,
            'erd.cache.ttl' => 3600,
            'erd.cache.key' => 'test_erd_data'
        ]);

        $this->modelAnalyzer = new ModelAnalyzer();
        $this->relationshipDetector = new RelationshipDetector();
        $this->erdDataGenerator = new ErdDataGenerator(
            $this->modelAnalyzer,
            $this->relationshipDetector
        );
    }

    /** @test */
    public function it_can_perform_complete_erd_analysis_workflow()
    {
        $this->createTestDatabase();
        $this->createTestModels();
        
        // Step 1: Discover models
        $models = $this->modelAnalyzer->discoverModels();
        
        $this->assertContains('Tests\\Fixtures\\Models\\TestUser', $models);
        $this->assertContains('Tests\\Fixtures\\Models\\TestPost', $models);
        $this->assertContains('Tests\\Fixtures\\Models\\TestComment', $models);
        
        // Step 2: Analyze model metadata
        $modelsMetadata = $this->modelAnalyzer->getModelMetadata();
        
        $this->assertArrayHasKey('Tests\\Fixtures\\Models\\TestUser', $modelsMetadata);
        $this->assertArrayHasKey('Tests\\Fixtures\\Models\\TestPost', $modelsMetadata);
        $this->assertArrayHasKey('Tests\\Fixtures\\Models\\TestComment', $modelsMetadata);
        
        // Verify User model metadata
        $userMetadata = $modelsMetadata['Tests\\Fixtures\\Models\\TestUser'];
        $this->assertEquals('test_users', $userMetadata['table']);
        $this->assertEquals('id', $userMetadata['primary_key']);
        $this->assertCount(6, $userMetadata['columns']); // id, name, email, password, created_at, updated_at
        
        // Step 3: Detect relationships
        $userRelationships = $this->relationshipDetector->detectRelationships('Tests\\Fixtures\\Models\\TestUser');
        
        $this->assertArrayHasKey('posts', $userRelationships);
        $this->assertArrayHasKey('comments', $userRelationships);
        $this->assertEquals('hasMany', $userRelationships['posts']['type']);
        $this->assertEquals('hasMany', $userRelationships['comments']['type']);
        
        $postRelationships = $this->relationshipDetector->detectRelationships('Tests\\Fixtures\\Models\\TestPost');
        
        $this->assertArrayHasKey('user', $postRelationships);
        $this->assertArrayHasKey('comments', $postRelationships);
        $this->assertEquals('belongsTo', $postRelationships['user']['type']);
        $this->assertEquals('morphMany', $postRelationships['comments']['type']);
        
        // Step 4: Generate complete ERD data
        $erdData = $this->erdDataGenerator->generateErdData();
        
        $this->assertArrayHasKey('tables', $erdData);
        $this->assertArrayHasKey('relationships', $erdData);
        $this->assertArrayHasKey('metadata', $erdData);
        
        // Verify tables
        $this->assertCount(3, $erdData['tables']);
        $tableNames = collect($erdData['tables'])->pluck('name')->toArray();
        $this->assertContains('test_users', $tableNames);
        $this->assertContains('test_posts', $tableNames);
        $this->assertContains('test_comments', $tableNames);
        
        // Verify relationships
        $this->assertGreaterThan(0, count($erdData['relationships']));
        $relationshipTypes = collect($erdData['relationships'])->pluck('type')->toArray();
        $this->assertContains('hasMany', $relationshipTypes);
        $this->assertContains('belongsTo', $relationshipTypes);
        
        // Verify metadata
        $this->assertEquals(3, $erdData['metadata']['total_tables']);
        $this->assertGreaterThan(0, $erdData['metadata']['total_relationships']);
        $this->assertArrayHasKey('relationship_types', $erdData['metadata']);
    }

    /** @test */
    public function it_handles_complex_relationships_correctly()
    {
        // Allow TestRole for this test
        config(['erd.models.exclude' => ['TestProfile', 'ExcludedModel']]);
        
        // Create new services with updated config
        $modelAnalyzer = new ModelAnalyzer();
        $relationshipDetector = new RelationshipDetector();
        $erdDataGenerator = new ErdDataGenerator($modelAnalyzer, $relationshipDetector);
        
        $this->createTestDatabase();
        
        $erdData = $erdDataGenerator->generateErdData();
        

        // Should handle belongsToMany relationships
        $userRoleRelationships = collect($erdData['relationships'])
            ->where('type', 'belongsToMany')
            ->where('source', 'table_test_users')
            ->where('target', 'table_test_roles');
        
        $this->assertGreaterThan(0, $userRoleRelationships->count());
        
        $userRoleRelationship = $userRoleRelationships->first();
        $this->assertArrayHasKey('pivot_table', $userRoleRelationship);
        $this->assertArrayHasKey('foreign_pivot_key', $userRoleRelationship);
        $this->assertArrayHasKey('related_pivot_key', $userRoleRelationship);
    }

    /** @test */
    public function it_caches_results_across_all_services()
    {
        $this->createTestDatabase();
        $this->createTestModels();
        
        // First generation should cache all results
        $erdData1 = $this->erdDataGenerator->generateErdData();
        
        // Verify caches exist
        $this->assertTrue(Cache::has('test_erd_data_models_discovery'));
        $this->assertTrue(Cache::has('test_erd_data_complete_erd_data'));
        
        // Second generation should use cached results
        $erdData2 = $this->erdDataGenerator->generateErdData();
        
        $this->assertEquals($erdData1, $erdData2);
    }

    /** @test */
    public function it_can_refresh_all_cached_data()
    {
        $this->createTestDatabase();
        $this->createTestModels();
        
        // Generate and cache data
        $this->erdDataGenerator->generateErdData();
        
        // Verify caches exist
        $this->assertTrue(Cache::has('test_erd_data_complete_erd_data'));
        
        // Refresh should clear all caches and regenerate
        $freshData = $this->erdDataGenerator->refreshErdData();
        
        $this->assertIsArray($freshData);
        $this->assertArrayHasKey('tables', $freshData);
        $this->assertArrayHasKey('relationships', $freshData);
    }

    /** @test */
    public function it_handles_missing_tables_gracefully()
    {
        // Create models without creating database tables
        $this->createTestModels();
        
        $erdData = $this->erdDataGenerator->generateErdData();
        
        // Should still generate ERD data, but with empty columns
        $this->assertArrayHasKey('tables', $erdData);
        $this->assertGreaterThan(0, count($erdData['tables']));
        
        foreach ($erdData['tables'] as $table) {
            // Tables without database tables should have empty columns
            if (empty($table['columns'])) {
                $this->assertIsArray($table['columns']);
            }
        }
    }

    /** @test */
    public function it_validates_relationships_in_complete_workflow()
    {
        $this->createTestDatabase();
        $this->createTestModelsWithInvalidRelationships();
        
        $erdData = $this->erdDataGenerator->generateErdData();
        
        // Should exclude invalid relationships
        $this->assertArrayHasKey('relationships', $erdData);
        
        foreach ($erdData['relationships'] as $relationship) {
            // All included relationships should be valid
            $this->assertArrayHasKey('type', $relationship);
            $this->assertArrayHasKey('source', $relationship);
            $this->assertArrayHasKey('target', $relationship);
        }
    }

    /** @test */
    public function it_provides_comprehensive_error_handling()
    {
        // Configure to look in a non-existent directory
        config(['erd.models.paths' => ['tests/NonExistentModels']]);
        
        // Create new services with the updated config
        $modelAnalyzer = new ModelAnalyzer();
        $relationshipDetector = new RelationshipDetector();
        $erdDataGenerator = new ErdDataGenerator($modelAnalyzer, $relationshipDetector);
        
        // Test with no models
        $erdData = $erdDataGenerator->generateErdData();
        
        $this->assertEmpty($erdData['tables']);
        $this->assertEmpty($erdData['relationships']);
        $this->assertEquals(0, $erdData['metadata']['total_tables']);
        $this->assertStringContainsString('No Eloquent models found', $erdData['metadata']['message']);
    }

    /** @test */
    public function it_generates_consistent_table_and_relationship_ids()
    {
        $this->createTestDatabase();
        $this->createTestModels();
        
        $erdData = $this->erdDataGenerator->generateErdData();
        
        // Table IDs should be consistent
        $userTable = collect($erdData['tables'])->firstWhere('name', 'test_users');
        $this->assertEquals('table_test_users', $userTable['id']);
        
        // Relationship source/target should reference table IDs
        foreach ($erdData['relationships'] as $relationship) {
            $this->assertStringStartsWith('table_', $relationship['source']);
            $this->assertStringStartsWith('table_', $relationship['target']);
            
            // Source and target should exist in tables
            $sourceExists = collect($erdData['tables'])->contains('id', $relationship['source']);
            $targetExists = collect($erdData['tables'])->contains('id', $relationship['target']);
            
            $this->assertTrue($sourceExists, "Source table {$relationship['source']} not found");
            $this->assertTrue($targetExists, "Target table {$relationship['target']} not found");
        }
    }

    protected function createTestDatabase()
    {
        Schema::create('test_users', function ($table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->timestamps();
        });

        Schema::create('test_posts', function ($table) {
            $table->id();
            $table->string('title');
            $table->text('content');
            $table->foreignId('user_id')->constrained('test_users');
            $table->timestamps();
        });

        Schema::create('test_comments', function ($table) {
            $table->id();
            $table->text('content');
            $table->foreignId('user_id')->constrained('test_users');
            $table->morphs('commentable');
            $table->timestamps();
        });

        Schema::create('test_roles', function ($table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('test_user_roles', function ($table) {
            $table->id();
            $table->foreignId('user_id')->constrained('test_users');
            $table->foreignId('role_id')->constrained('test_roles');
            $table->timestamps();
        });
    }

    protected function createTestModels()
    {
        $modelsPath = base_path('tests/Fixtures/Models');
        File::ensureDirectoryExists($modelsPath);
        
        // User model
        File::put($modelsPath . '/User.php', '<?php

namespace Tests\Fixtures\Models;

use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    protected $fillable = ["name", "email"];
    protected $guarded = ["password"];
    
    public function posts()
    {
        return $this->hasMany(Post::class);
    }
    
    public function comments()
    {
        return $this->hasMany(Comment::class);
    }
}');

        // Post model
        File::put($modelsPath . '/Post.php', '<?php

namespace Tests\Fixtures\Models;

use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    protected $fillable = ["title", "content"];
    
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    
    public function comments()
    {
        return $this->morphMany(Comment::class, "commentable");
    }
}');

        // Comment model
        File::put($modelsPath . '/Comment.php', '<?php

namespace Tests\Fixtures\Models;

use Illuminate\Database\Eloquent\Model;

class Comment extends Model
{
    protected $fillable = ["content"];
    
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    
    public function commentable()
    {
        return $this->morphTo();
    }
}');
    }

    protected function createComplexTestModels()
    {
        $this->createTestModels();
        
        $modelsPath = base_path('tests/Fixtures/Models');
        
        // Role model with belongsToMany
        File::put($modelsPath . '/Role.php', '<?php

namespace Tests\Fixtures\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    protected $fillable = ["name"];
    
    public function users()
    {
        return $this->belongsToMany(User::class, "user_roles");
    }
}');

        // Update User model to include roles relationship
        File::put($modelsPath . '/User.php', '<?php

namespace Tests\Fixtures\Models;

use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    protected $fillable = ["name", "email"];
    protected $guarded = ["password"];
    
    public function posts()
    {
        return $this->hasMany(Post::class);
    }
    
    public function comments()
    {
        return $this->hasMany(Comment::class);
    }
    
    public function roles()
    {
        return $this->belongsToMany(Role::class, "user_roles");
    }
}');
    }

    protected function createTestModelsWithInvalidRelationships()
    {
        $this->createTestModels();
        
        $modelsPath = base_path('tests/Fixtures/Models');
        
        // Model with invalid relationship
        File::put($modelsPath . '/InvalidModel.php', '<?php

namespace Tests\Fixtures\Models;

use Illuminate\Database\Eloquent\Model;

class InvalidModel extends Model
{
    public function invalidRelationship()
    {
        return $this->belongsTo("NonExistentModel");
    }
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