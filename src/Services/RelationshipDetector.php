<?php

namespace Looaf\LaravelErd\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Cache;
use ReflectionClass;
use ReflectionMethod;
use ReflectionException;

class RelationshipDetector
{
    protected array $config;
    protected array $supportedRelationTypes = [
        'morphTo' => MorphTo::class,        // Put morphTo first since it extends BelongsTo
        'morphMany' => MorphMany::class,    // Put morphMany before hasMany since it might extend it
        'belongsToMany' => BelongsToMany::class,
        'hasOne' => HasOne::class,
        'hasMany' => HasMany::class,
        'belongsTo' => BelongsTo::class,    // Put belongsTo last since other types might extend it
    ];

    public function __construct()
    {
        $this->config = config('erd', []);
    }

    /**
     * Detect all relationships for a given model class
     */
    public function detectRelationships(string $modelClass): array
    {
        $cacheKey = $this->getCacheKey("relationships_{$modelClass}");
        
        if ($this->isCacheEnabled() && Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        try {
            $reflection = new ReflectionClass($modelClass);
            
            if (!$reflection->isSubclassOf(Model::class)) {
                return [];
            }

            $relationships = [];
            $methods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);
            
            foreach ($methods as $method) {
                if ($this->isRelationshipMethod($method, $modelClass)) {
                    $relationshipData = $this->analyzeMethod($method, $modelClass);
                    if ($relationshipData) {
                        $relationships[$method->getName()] = $relationshipData;
                    }
                }
            }
            
            if ($this->isCacheEnabled()) {
                Cache::put($cacheKey, $relationships, $this->getCacheTtl());
            }

            return $relationships;
            
        } catch (ReflectionException $e) {
            \Log::warning("Failed to detect relationships for model {$modelClass}: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Analyze a method to extract relationship information
     */
    protected function analyzeMethod(ReflectionMethod $method, string $modelClass): ?array
    {
        try {
            $model = app($modelClass);
            $relationship = $model->{$method->getName()}();
            
            if (!$relationship instanceof Relation) {
                return null;
            }

            return $this->extractRelationshipMetadata($relationship, $method->getName());
            
        } catch (\Exception $e) {
            \Log::warning("Failed to analyze relationship method {$method->getName()} in {$modelClass}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Extract metadata from a relationship instance
     */
    protected function extractRelationshipMetadata(Relation $relationship, string $methodName): array
    {
        $relationType = $this->getRelationType($relationship);
        $relatedModel = get_class($relationship->getRelated());
        
        $metadata = [
            'type' => $relationType,
            'related' => $relatedModel,
            'method' => $methodName,
        ];

        // Extract specific metadata based on relationship type
        switch ($relationType) {
            case 'belongsTo':
                $metadata = array_merge($metadata, $this->extractBelongsToMetadata($relationship));
                break;
                
            case 'hasOne':
            case 'hasMany':
                $metadata = array_merge($metadata, $this->extractHasMetadata($relationship));
                break;
                
            case 'belongsToMany':
                $metadata = array_merge($metadata, $this->extractBelongsToManyMetadata($relationship));
                break;
                
            case 'morphTo':
                $metadata = array_merge($metadata, $this->extractMorphToMetadata($relationship));
                break;
                
            case 'morphMany':
                $metadata = array_merge($metadata, $this->extractMorphManyMetadata($relationship));
                break;
        }

        return $metadata;
    }

    /**
     * Extract metadata for belongsTo relationships
     */
    protected function extractBelongsToMetadata(BelongsTo $relationship): array
    {
        return [
            'foreign_key' => $relationship->getForeignKeyName(),
            'owner_key' => $relationship->getOwnerKeyName(),
            'parent_table' => $relationship->getRelated()->getTable(),
        ];
    }

    /**
     * Extract metadata for hasOne/hasMany relationships
     */
    protected function extractHasMetadata($relationship): array
    {
        return [
            'foreign_key' => $relationship->getForeignKeyName(),
            'local_key' => $relationship->getLocalKeyName(),
            'related_table' => $relationship->getRelated()->getTable(),
        ];
    }

    /**
     * Extract metadata for belongsToMany relationships
     */
    protected function extractBelongsToManyMetadata(BelongsToMany $relationship): array
    {
        return [
            'pivot_table' => $relationship->getTable(),
            'foreign_pivot_key' => $relationship->getForeignPivotKeyName(),
            'related_pivot_key' => $relationship->getRelatedPivotKeyName(),
            'parent_key' => $relationship->getParentKeyName(),
            'related_key' => $relationship->getRelatedKeyName(),
            'related_table' => $relationship->getRelated()->getTable(),
        ];
    }

    /**
     * Extract metadata for morphTo relationships
     */
    protected function extractMorphToMetadata(MorphTo $relationship): array
    {
        return [
            'morph_type' => $relationship->getMorphType(),
            'foreign_key' => $relationship->getForeignKeyName(),
        ];
    }

    /**
     * Extract metadata for morphMany relationships
     */
    protected function extractMorphManyMetadata(MorphMany $relationship): array
    {
        return [
            'morph_type' => $relationship->getMorphType(),
            'foreign_key' => $relationship->getForeignKeyName(),
            'local_key' => $relationship->getLocalKeyName(),
            'related_table' => $relationship->getRelated()->getTable(),
        ];
    }

    /**
     * Determine the relationship type from a relationship instance
     */
    protected function getRelationType(Relation $relationship): string
    {
        $relationshipClass = get_class($relationship);
        

        
        foreach ($this->supportedRelationTypes as $type => $class) {
            if ($relationship instanceof $class) {
                return $type;
            }
        }

        return 'unknown';
    }

    /**
     * Check if a method is likely a relationship method
     */
    protected function isRelationshipMethod(ReflectionMethod $method, string $modelClass): bool
    {
        // Skip if method is not public or is static
        if (!$method->isPublic() || $method->isStatic()) {
            return false;
        }

        // Skip if method has parameters (relationships shouldn't have required parameters)
        if ($method->getNumberOfRequiredParameters() > 0) {
            return false;
        }

        // Skip magic methods and common model methods
        if ($this->isExcludedMethod($method->getName())) {
            return false;
        }

        // Skip if method is defined in the base Model class or its parents
        if ($this->isBaseModelMethod($method, $modelClass)) {
            return false;
        }

        // Try to execute the method to see if it returns a Relation
        try {
            $model = app($modelClass);
            $result = $model->{$method->getName()}();
            return $result instanceof Relation;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Check if method name should be excluded from relationship detection
     */
    protected function isExcludedMethod(string $methodName): bool
    {
        $excludedMethods = [
            '__construct', '__destruct', '__call', '__callStatic', '__get', '__set',
            '__isset', '__unset', '__sleep', '__wakeup', '__toString', '__invoke',
            '__set_state', '__clone', '__debugInfo',
            'getTable', 'getKeyName', 'getKey', 'getRouteKeyName', 'getRouteKey',
            'getFillable', 'getGuarded', 'getCasts', 'getDates', 'getDateFormat',
            'getConnection', 'getConnectionName', 'setConnection', 'resolveConnection',
            'save', 'update', 'delete', 'destroy', 'create', 'find', 'findOrFail',
            'first', 'firstOrFail', 'get', 'all', 'paginate', 'simplePaginate',
            'chunk', 'each', 'pluck', 'count', 'min', 'max', 'sum', 'avg',
            'toArray', 'toJson', 'jsonSerialize', 'fresh', 'refresh', 'replicate',
            'is', 'isNot', 'getOriginal', 'only', 'except', 'makeHidden', 'makeVisible',
            'setAppends', 'hasGetMutator', 'hasSetMutator', 'hasAttributeMutator',
            'getDirty', 'getChanges', 'wasChanged', 'wasRecentlyCreated',
            'touch', 'push', 'saveOrFail', 'finishSave', 'performInsert', 'performUpdate',
        ];

        return in_array($methodName, $excludedMethods) || 
               str_starts_with($methodName, 'get') ||
               str_starts_with($methodName, 'set') ||
               str_starts_with($methodName, 'is') ||
               str_starts_with($methodName, 'has');
    }

    /**
     * Check if method is defined in base Model class
     */
    protected function isBaseModelMethod(ReflectionMethod $method, string $modelClass): bool
    {
        $declaringClass = $method->getDeclaringClass()->getName();
        
        // If method is declared in the model class itself, it's not a base method
        if ($declaringClass === $modelClass) {
            return false;
        }

        // Check if it's declared in Model or its parent classes
        $baseClasses = [
            Model::class,
            'Illuminate\Database\Eloquent\Model',
            'Illuminate\Database\Eloquent\Builder',
            'Illuminate\Database\Query\Builder',
        ];

        return in_array($declaringClass, $baseClasses);
    }

    /**
     * Validate that a relationship is properly configured
     */
    public function validateRelationship(array $relationshipData): bool
    {
        // Check required fields
        if (!isset($relationshipData['type'], $relationshipData['related'])) {
            return false;
        }

        // Check if related model class exists
        if (!class_exists($relationshipData['related'])) {
            return false;
        }

        // Validate specific relationship types
        switch ($relationshipData['type']) {
            case 'belongsTo':
                return isset($relationshipData['foreign_key'], $relationshipData['owner_key']);
                
            case 'hasOne':
            case 'hasMany':
                return isset($relationshipData['foreign_key'], $relationshipData['local_key']);
                
            case 'belongsToMany':
                return isset($relationshipData['pivot_table'], $relationshipData['foreign_pivot_key'], $relationshipData['related_pivot_key']);
                
            case 'morphTo':
                return isset($relationshipData['morph_type'], $relationshipData['foreign_key']);
                
            case 'morphMany':
                return isset($relationshipData['morph_type'], $relationshipData['foreign_key'], $relationshipData['local_key']);
                
            default:
                return true;
        }
    }

    /**
     * Get all relationships for multiple models
     */
    public function getRelationshipsForModels(array $modelClasses): array
    {
        $allRelationships = [];
        
        foreach ($modelClasses as $modelClass) {
            $relationships = $this->detectRelationships($modelClass);
            if (!empty($relationships)) {
                $allRelationships[$modelClass] = $relationships;
            }
        }

        return $allRelationships;
    }

    /**
     * Check if caching is enabled
     */
    protected function isCacheEnabled(): bool
    {
        return $this->config['cache']['enabled'] ?? true;
    }

    /**
     * Get cache TTL
     */
    protected function getCacheTtl(): int
    {
        return $this->config['cache']['ttl'] ?? 3600;
    }

    /**
     * Generate cache key
     */
    protected function getCacheKey(string $suffix): string
    {
        $prefix = $this->config['cache']['key'] ?? 'laravel_erd_data';
        return "{$prefix}_{$suffix}";
    }

    /**
     * Clear relationship cache for a specific model or all models
     */
    public function clearCache(?string $modelClass = null): void
    {
        if ($modelClass) {
            Cache::forget($this->getCacheKey("relationships_{$modelClass}"));
        } else {
            // This would require knowing all model classes, which we can get from ModelAnalyzer
            // For now, we'll just clear by pattern if the cache driver supports it
            $prefix = $this->config['cache']['key'] ?? 'laravel_erd_data';
            
            // Note: This is a simplified approach. In production, you might want to
            // maintain a list of cached keys or use cache tags if available
            try {
                if (method_exists(Cache::getStore(), 'flush')) {
                    // Only flush if we can do it safely
                    \Log::info('Clearing all ERD relationship cache');
                }
            } catch (\Exception $e) {
                \Log::warning('Could not clear relationship cache: ' . $e->getMessage());
            }
        }
    }
}