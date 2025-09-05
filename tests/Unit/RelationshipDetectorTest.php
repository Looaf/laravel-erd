<?php

namespace Tests\Unit;

use Tests\TestCase;
use Looaf\LaravelErd\Services\RelationshipDetector;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;

class RelationshipDetectorTest extends TestCase
{

    protected RelationshipDetector $detector;

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
        
        // Create detector after config is set
        $this->detector = new RelationshipDetector();
    }

    /** @test */
    public function it_can_detect_has_many_relationships()
    {
        $relationships = $this->detector->detectRelationships('Tests\\Fixtures\\Models\\TestUser');
        
        $this->assertArrayHasKey('posts', $relationships);
        $this->assertEquals('hasMany', $relationships['posts']['type']);
        $this->assertEquals('Tests\\Fixtures\\Models\\TestPost', $relationships['posts']['related']);
        $this->assertEquals('posts', $relationships['posts']['method']);
        $this->assertArrayHasKey('foreign_key', $relationships['posts']);
        $this->assertArrayHasKey('local_key', $relationships['posts']);
    }

    /** @test */
    public function it_can_detect_belongs_to_relationships()
    {
        $relationships = $this->detector->detectRelationships('Tests\\Fixtures\\Models\\TestPost');
        
        $this->assertArrayHasKey('user', $relationships);
        $this->assertEquals('belongsTo', $relationships['user']['type']);
        $this->assertEquals('Tests\\Fixtures\\Models\\TestUser', $relationships['user']['related']);
        $this->assertEquals('user', $relationships['user']['method']);
        $this->assertArrayHasKey('foreign_key', $relationships['user']);
        $this->assertArrayHasKey('owner_key', $relationships['user']);
    }

    /** @test */
    public function it_can_detect_belongs_to_many_relationships()
    {
        $relationships = $this->detector->detectRelationships('Tests\\Fixtures\\Models\\TestUser');
        
        $this->assertArrayHasKey('roles', $relationships);
        $this->assertEquals('belongsToMany', $relationships['roles']['type']);
        $this->assertEquals('Tests\\Fixtures\\Models\\TestRole', $relationships['roles']['related']);
        $this->assertArrayHasKey('pivot_table', $relationships['roles']);
        $this->assertArrayHasKey('foreign_pivot_key', $relationships['roles']);
        $this->assertArrayHasKey('related_pivot_key', $relationships['roles']);
    }

    /** @test */
    public function it_can_detect_has_one_relationships()
    {
        $relationships = $this->detector->detectRelationships('Tests\\Fixtures\\Models\\TestUser');
        
        $this->assertArrayHasKey('profile', $relationships);
        $this->assertEquals('hasOne', $relationships['profile']['type']);
        $this->assertEquals('Tests\\Fixtures\\Models\\TestProfile', $relationships['profile']['related']);
        $this->assertArrayHasKey('foreign_key', $relationships['profile']);
        $this->assertArrayHasKey('local_key', $relationships['profile']);
    }

    /** @test */
    public function it_can_detect_morph_to_relationships()
    {
        $relationships = $this->detector->detectRelationships('Tests\\Fixtures\\Models\\TestComment');
        

        $this->assertArrayHasKey('commentable', $relationships);
        $this->assertEquals('morphTo', $relationships['commentable']['type']);
        $this->assertArrayHasKey('morph_type', $relationships['commentable']);
        $this->assertArrayHasKey('foreign_key', $relationships['commentable']);
    }

    /** @test */
    public function it_excludes_non_relationship_methods()
    {
        $relationships = $this->detector->detectRelationships('Tests\\Fixtures\\Models\\TestUser');
        
        // Should not include getter methods
        $this->assertArrayNotHasKey('getName', $relationships);
        $this->assertArrayNotHasKey('getEmailAttribute', $relationships);
        
        // Should not include setter methods
        $this->assertArrayNotHasKey('setPasswordAttribute', $relationships);
        
        // Should not include regular methods
        $this->assertArrayNotHasKey('someRegularMethod', $relationships);
    }

    /** @test */
    public function it_caches_relationship_detection_results()
    {
        // First call should cache the results
        $relationships1 = $this->detector->detectRelationships('Tests\\Fixtures\\Models\\TestUser');
        
        // Second call should return cached results
        $relationships2 = $this->detector->detectRelationships('Tests\\Fixtures\\Models\\TestUser');
        
        $this->assertEquals($relationships1, $relationships2);
        $this->assertTrue(Cache::has('test_erd_data_relationships_Tests\\Fixtures\\Models\\TestUser'));
    }

    /** @test */
    public function it_can_get_relationships_for_multiple_models()
    {
        $modelClasses = [
            'Tests\\Fixtures\\Models\\TestUser',
            'Tests\\Fixtures\\Models\\TestPost'
        ];
        
        $allRelationships = $this->detector->getRelationshipsForModels($modelClasses);
        
        $this->assertArrayHasKey('Tests\\Fixtures\\Models\\TestUser', $allRelationships);
        $this->assertArrayHasKey('Tests\\Fixtures\\Models\\TestPost', $allRelationships);
        
        $this->assertArrayHasKey('posts', $allRelationships['Tests\\Fixtures\\Models\\TestUser']);
        $this->assertArrayHasKey('user', $allRelationships['Tests\\Fixtures\\Models\\TestPost']);
    }

    /** @test */
    public function it_validates_relationships_correctly()
    {
        // Valid belongsTo relationship
        $validBelongsTo = [
            'type' => 'belongsTo',
            'related' => 'Tests\\Fixtures\\Models\\TestUser',
            'foreign_key' => 'user_id',
            'owner_key' => 'id'
        ];
        
        $this->assertTrue($this->detector->validateRelationship($validBelongsTo));
        
        // Invalid relationship (missing required fields)
        $invalidRelationship = [
            'type' => 'belongsTo',
            'related' => 'Tests\\Fixtures\\Models\\TestUser'
            // Missing foreign_key and owner_key
        ];
        
        $this->assertFalse($this->detector->validateRelationship($invalidRelationship));
        
        // Invalid relationship (non-existent related model)
        $nonExistentModel = [
            'type' => 'belongsTo',
            'related' => 'NonExistentModel',
            'foreign_key' => 'user_id',
            'owner_key' => 'id'
        ];
        
        $this->assertFalse($this->detector->validateRelationship($nonExistentModel));
    }

    /** @test */
    public function it_handles_broken_relationships_gracefully()
    {
        $this->createBrokenRelationshipModel();
        
        $relationships = $this->detector->detectRelationships('Tests\\Fixtures\\Models\\BrokenModel');
        
        // Should return empty array or exclude broken relationships
        $this->assertIsArray($relationships);
        // The broken relationship should not be included
    }

    /** @test */
    public function it_can_clear_relationship_cache()
    {
        // Cache some data
        $this->detector->detectRelationships('Tests\\Fixtures\\Models\\TestUser');
        $this->assertTrue(Cache::has('test_erd_data_relationships_Tests\\Fixtures\\Models\\TestUser'));
        
        // Clear cache for specific model
        $this->detector->clearCache('Tests\\Fixtures\\Models\\TestUser');
        
        $this->assertFalse(Cache::has('test_erd_data_relationships_Tests\\Fixtures\\Models\\TestUser'));
    }

    /** @test */
    public function it_respects_cache_configuration()
    {
        config(['erd.cache.enabled' => false]);
        $detector = new RelationshipDetector();
        
        $detector->detectRelationships('Tests\\Fixtures\\Models\\TestUser');
        
        // Should not cache when caching is disabled
        $this->assertFalse(Cache::has('test_erd_data_relationships_Tests\\Fixtures\\Models\\TestUser'));
    }

    /** @test */
    public function it_returns_empty_array_for_non_existent_model()
    {
        $relationships = $this->detector->detectRelationships('NonExistentModel');
        
        $this->assertEmpty($relationships);
    }

    /** @test */
    public function it_excludes_methods_with_required_parameters()
    {
        $this->createModelWithParameterizedMethods();
        
        $relationships = $this->detector->detectRelationships('Tests\\Fixtures\\Models\\ParameterizedModel');
        
        // Should not include methods with required parameters
        $this->assertArrayNotHasKey('methodWithParameters', $relationships);
    }

    /** @test */
    public function it_excludes_static_methods()
    {
        $this->createModelWithStaticMethods();
        
        $relationships = $this->detector->detectRelationships('Tests\\Fixtures\\Models\\StaticMethodModel');
        
        // Should not include static methods
        $this->assertArrayNotHasKey('staticMethod', $relationships);
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
    
    public function posts()
    {
        return $this->hasMany(TestPost::class);
    }
    
    public function profile()
    {
        return $this->hasOne(TestProfile::class);
    }
    
    public function roles()
    {
        return $this->belongsToMany(TestRole::class);
    }
    
    // Non-relationship methods that should be excluded
    public function getName()
    {
        return $this->name;
    }
    
    public function getEmailAttribute($value)
    {
        return strtolower($value);
    }
    
    public function setPasswordAttribute($value)
    {
        $this->attributes["password"] = bcrypt($value);
    }
    
    public function someRegularMethod()
    {
        return "not a relationship";
    }
}');

        // Create TestPost model
        File::put($modelsPath . '/TestPost.php', '<?php

namespace Tests\Fixtures\Models;

use Illuminate\Database\Eloquent\Model;

class TestPost extends Model
{
    protected $table = "test_posts";
    
    public function user()
    {
        return $this->belongsTo(TestUser::class);
    }
    
    public function comments()
    {
        return $this->morphMany(TestComment::class, "commentable");
    }
}');

        // Create TestProfile model
        File::put($modelsPath . '/TestProfile.php', '<?php

namespace Tests\Fixtures\Models;

use Illuminate\Database\Eloquent\Model;

class TestProfile extends Model
{
    protected $table = "test_profiles";
    
    public function user()
    {
        return $this->belongsTo(TestUser::class);
    }
}');

        // Create TestRole model
        File::put($modelsPath . '/TestRole.php', '<?php

namespace Tests\Fixtures\Models;

use Illuminate\Database\Eloquent\Model;

class TestRole extends Model
{
    protected $table = "test_roles";
    
    public function users()
    {
        return $this->belongsToMany(TestUser::class);
    }
}');

        // Create TestComment model
        File::put($modelsPath . '/TestComment.php', '<?php

namespace Tests\Fixtures\Models;

use Illuminate\Database\Eloquent\Model;

class TestComment extends Model
{
    protected $table = "test_comments";
    
    public function commentable()
    {
        return $this->morphTo();
    }
}');
    }

    protected function createBrokenRelationshipModel()
    {
        $modelsPath = base_path('tests/Fixtures/Models');
        File::ensureDirectoryExists($modelsPath);
        
        File::put($modelsPath . '/BrokenModel.php', '<?php

namespace Tests\Fixtures\Models;

use Illuminate\Database\Eloquent\Model;

class BrokenModel extends Model
{
    public function brokenRelationship()
    {
        // This will throw an exception when called
        throw new \Exception("Broken relationship");
    }
}');
    }

    protected function createModelWithParameterizedMethods()
    {
        $modelsPath = base_path('tests/Fixtures/Models');
        File::ensureDirectoryExists($modelsPath);
        
        File::put($modelsPath . '/ParameterizedModel.php', '<?php

namespace Tests\Fixtures\Models;

use Illuminate\Database\Eloquent\Model;

class ParameterizedModel extends Model
{
    public function methodWithParameters($required, $optional = null)
    {
        return $this->hasMany(TestPost::class);
    }
}');
    }

    protected function createModelWithStaticMethods()
    {
        $modelsPath = base_path('tests/Fixtures/Models');
        File::ensureDirectoryExists($modelsPath);
        
        File::put($modelsPath . '/StaticMethodModel.php', '<?php

namespace Tests\Fixtures\Models;

use Illuminate\Database\Eloquent\Model;

class StaticMethodModel extends Model
{
    public static function staticMethod()
    {
        return "static method";
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