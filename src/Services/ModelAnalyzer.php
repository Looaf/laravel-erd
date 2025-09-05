<?php

namespace Looaf\LaravelErd\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use ReflectionClass;
use ReflectionException;

class ModelAnalyzer
{
    protected array $config;
    protected array $discoveredModels = [];

    public function __construct()
    {
        $this->config = config('erd', []);
    }

    /**
     * Discover all Eloquent models in the application
     */
    public function discoverModels(): array
    {
        $cacheKey = $this->getCacheKey('models_discovery');
        
        if ($this->isCacheEnabled() && Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        $models = [];
        $modelPaths = $this->getModelPaths();
        
        foreach ($modelPaths as $path) {
            $models = array_merge($models, $this->scanDirectory($path));
        }

        $models = $this->filterExcludedModels($models);
        
        if ($this->isCacheEnabled()) {
            Cache::put($cacheKey, $models, $this->getCacheTtl());
        }

        return $models;
    }

    /**
     * Analyze a specific model and extract its metadata
     */
    public function analyzeModel(string $modelClass): array
    {
        $cacheKey = $this->getCacheKey("model_analysis_{$modelClass}");
        
        if ($this->isCacheEnabled() && Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        try {
            $reflection = new ReflectionClass($modelClass);
            
            if (!$reflection->isSubclassOf(Model::class)) {
                return [];
            }

            $model = app($modelClass);
            $metadata = $this->extractModelMetadata($model, $reflection);
            
            if ($this->isCacheEnabled()) {
                Cache::put($cacheKey, $metadata, $this->getCacheTtl());
            }

            return $metadata;
            
        } catch (ReflectionException $e) {
            \Log::warning("Failed to analyze model {$modelClass}: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get metadata for all discovered models
     */
    public function getModelMetadata(): array
    {
        $models = $this->discoverModels();
        $metadata = [];

        foreach ($models as $modelClass) {
            $modelMetadata = $this->analyzeModel($modelClass);
            if (!empty($modelMetadata)) {
                $metadata[$modelClass] = $modelMetadata;
            }
        }

        return $metadata;
    }

    /**
     * Extract metadata from a model instance
     */
    protected function extractModelMetadata(Model $model, ReflectionClass $reflection): array
    {
        $tableName = $model->getTable();
        
        return [
            'class' => $reflection->getName(),
            'table' => $tableName,
            'columns' => $this->getTableColumns($tableName),
            'primary_key' => $model->getKeyName(),
            'timestamps' => $model->timestamps,
            'fillable' => $model->getFillable(),
            'guarded' => $model->getGuarded(),
            'casts' => $model->getCasts(),
        ];
    }

    /**
     * Get column information for a table
     */
    protected function getTableColumns(string $tableName): array
    {
        try {
            if (!Schema::hasTable($tableName)) {
                return [];
            }

            $columns = [];
            $columnListing = Schema::getColumnListing($tableName);
            
            foreach ($columnListing as $columnName) {
                $columnType = Schema::getColumnType($tableName, $columnName);
                
                $columns[] = [
                    'name' => $columnName,
                    'type' => $columnType,
                    'nullable' => $this->isColumnNullable($tableName, $columnName),
                    'default' => $this->getColumnDefault($tableName, $columnName),
                ];
            }

            return $columns;
            
        } catch (\Exception $e) {
            \Log::warning("Failed to get columns for table {$tableName}: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Check if a column is nullable
     */
    protected function isColumnNullable(string $tableName, string $columnName): bool
    {
        try {
            $connection = Schema::getConnection();
            $doctrine = $connection->getDoctrineSchemaManager();
            $table = $doctrine->listTableDetails($tableName);
            $column = $table->getColumn($columnName);
            
            return !$column->getNotnull();
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get column default value
     */
    protected function getColumnDefault(string $tableName, string $columnName): mixed
    {
        try {
            $connection = Schema::getConnection();
            $doctrine = $connection->getDoctrineSchemaManager();
            $table = $doctrine->listTableDetails($tableName);
            $column = $table->getColumn($columnName);
            
            return $column->getDefault();
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Scan a directory for PHP model files
     */
    protected function scanDirectory(string $path): array
    {
        $models = [];
        
        // In testing environment, use the package root instead of Laravel's base_path
        if (app()->environment('testing')) {
            $packageRoot = dirname(dirname(__DIR__)); // Go up from src/Services to package root
            $fullPath = $packageRoot . '/' . $path;
        } else {
            $fullPath = base_path($path);
        }



        if (!File::isDirectory($fullPath)) {
            return $models;
        }

        $files = File::allFiles($fullPath);
        

        
        foreach ($files as $file) {
            if ($file->getExtension() === 'php') {
                $className = $this->getClassNameFromFile($file->getPathname(), $path, $fullPath);
                if ($className && $this->isEloquentModel($className)) {
                    $models[] = $className;
                }
            }
        }

        return $models;
    }

    /**
     * Extract class name from file path
     */
    protected function getClassNameFromFile(string $filePath, string $basePath, string $fullBasePath = null): ?string
    {
        if ($fullBasePath === null) {
            $fullBasePath = base_path($basePath);
        }
        
        $relativePath = str_replace($fullBasePath . '/', '', $filePath);
        $relativePath = str_replace('.php', '', $relativePath);
        $relativePath = str_replace('/', '\\', $relativePath);
        
        $namespace = $this->config['models']['namespace'] ?? 'App\\Models';
        

        
        return $namespace . '\\' . $relativePath;
    }

    /**
     * Check if a class is an Eloquent model
     */
    protected function isEloquentModel(string $className): bool
    {
        try {
            if (!class_exists($className)) {
                return false;
            }

            $reflection = new ReflectionClass($className);
            return $reflection->isSubclassOf(Model::class) && !$reflection->isAbstract();
            
        } catch (ReflectionException $e) {
            return false;
        }
    }

    /**
     * Filter out excluded models
     */
    protected function filterExcludedModels(array $models): array
    {
        $excluded = $this->config['models']['exclude'] ?? [];
        $excluded = array_filter($excluded); // Remove empty strings
        
        if (empty($excluded)) {
            return $models;
        }

        return array_filter($models, function ($model) use ($excluded) {
            $className = class_basename($model);
            return !in_array($className, $excluded) && !in_array($model, $excluded);
        });
    }

    /**
     * Get model paths from configuration
     */
    protected function getModelPaths(): array
    {
        return $this->config['models']['paths'] ?? ['app/Models'];
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
     * Clear all cached model analysis data
     */
    public function clearCache(): void
    {
        $prefix = $this->config['cache']['key'] ?? 'laravel_erd_data';
        
        // Clear main discovery cache
        Cache::forget($this->getCacheKey('models_discovery'));
        
        // Clear individual model analysis caches by discovering models without cache
        $originalCacheEnabled = $this->config['cache']['enabled'] ?? true;
        $this->config['cache']['enabled'] = false;
        
        try {
            $models = $this->discoverModels();
            foreach ($models as $modelClass) {
                Cache::forget($this->getCacheKey("model_analysis_{$modelClass}"));
            }
        } finally {
            // Restore original cache setting
            $this->config['cache']['enabled'] = $originalCacheEnabled;
        }
    }
}