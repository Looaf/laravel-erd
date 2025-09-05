<?php

namespace Looaf\LaravelErd\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ErdDataGenerator
{
    protected ModelAnalyzer $modelAnalyzer;
    protected RelationshipDetector $relationshipDetector;
    protected array $config;

    public function __construct(ModelAnalyzer $modelAnalyzer, RelationshipDetector $relationshipDetector)
    {
        $this->modelAnalyzer = $modelAnalyzer;
        $this->relationshipDetector = $relationshipDetector;
        $this->config = config('erd', []);
    }

    /**
     * Generate complete ERD data structure for frontend consumption
     */
    public function generateErdData(): array
    {
        $cacheKey = $this->getCacheKey('complete_erd_data');
        
        if ($this->isCacheEnabled() && Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        try {
            // Get all model metadata
            $modelsMetadata = $this->modelAnalyzer->getModelMetadata();
            
            if (empty($modelsMetadata)) {
                return $this->generateEmptyErdData();
            }

            // Get all relationships
            $modelClasses = array_keys($modelsMetadata);
            $relationshipsData = $this->relationshipDetector->getRelationshipsForModels($modelClasses);

            // Transform data for frontend
            $erdData = [
                'tables' => $this->transformModelsToTables($modelsMetadata),
                'relationships' => $this->transformRelationshipsToConnections($relationshipsData, $modelsMetadata),
                'metadata' => $this->generateMetadata($modelsMetadata, $relationshipsData),
            ];

            if ($this->isCacheEnabled()) {
                Cache::put($cacheKey, $erdData, $this->getCacheTtl());
            }

            return $erdData;
            
        } catch (\Exception $e) {
            Log::error('Failed to generate ERD data: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString()
            ]);
            
            return $this->generateErrorErdData($e->getMessage());
        }
    }

    /**
     * Transform model metadata into table data for frontend
     */
    protected function transformModelsToTables(array $modelsMetadata): array
    {
        $tables = [];
        $position = ['x' => 100, 'y' => 100];
        $spacing = 300;
        $currentRow = 0;
        $tablesPerRow = 4;

        foreach ($modelsMetadata as $modelClass => $metadata) {
            $tableId = $this->generateTableId($metadata['table']);
            
            $tables[] = [
                'id' => $tableId,
                'name' => $metadata['table'],
                'model' => $modelClass,
                'columns' => $this->transformColumns($metadata['columns']),
                'primary_key' => $metadata['primary_key'],
                'timestamps' => $metadata['timestamps'],
                'position' => [
                    'x' => $position['x'] + (($currentRow % $tablesPerRow) * $spacing),
                    'y' => $position['y'] + (intval($currentRow / $tablesPerRow) * $spacing)
                ],
                'fillable' => $metadata['fillable'] ?? [],
                'guarded' => $metadata['guarded'] ?? [],
                'casts' => $metadata['casts'] ?? [],
            ];
            
            $currentRow++;
        }

        return $tables;
    }

    /**
     * Transform column data for frontend consumption
     */
    protected function transformColumns(array $columns): array
    {
        return array_map(function ($column) {
            return [
                'name' => $column['name'],
                'type' => $this->normalizeColumnType($column['type']),
                'nullable' => $column['nullable'] ?? false,
                'default' => $column['default'],
                'primary' => false, // Will be set separately based on primary_key
                'foreign' => false, // Will be determined from relationships
            ];
        }, $columns);
    }

    /**
     * Transform relationships into connection data for frontend
     */
    protected function transformRelationshipsToConnections(array $relationshipsData, array $modelsMetadata): array
    {
        $connections = [];
        $connectionId = 1;

        foreach ($relationshipsData as $modelClass => $relationships) {
            $sourceTable = $this->getTableNameFromModel($modelClass, $modelsMetadata);
            
            if (!$sourceTable) {
                continue;
            }

            foreach ($relationships as $methodName => $relationshipData) {
                try {
                    $connection = $this->createConnection(
                        $connectionId++,
                        $sourceTable,
                        $relationshipData,
                        $modelsMetadata,
                        $methodName
                    );
                    
                    if ($connection && $this->relationshipDetector->validateRelationship($relationshipData)) {
                        $connections[] = $connection;
                    }
                } catch (\Exception $e) {
                    Log::warning("Failed to create connection for relationship {$methodName} in {$modelClass}: " . $e->getMessage());
                    continue;
                }
            }
        }

        return $connections;
    }

    /**
     * Create a single connection from relationship data
     */
    protected function createConnection(int $id, string $sourceTable, array $relationshipData, array $modelsMetadata, string $methodName): ?array
    {
        $targetTable = $this->getTableNameFromModel($relationshipData['related'], $modelsMetadata);
        
        if (!$targetTable) {
            return null;
        }

        $connection = [
            'id' => "connection_{$id}",
            'source' => $this->generateTableId($sourceTable),
            'target' => $this->generateTableId($targetTable),
            'type' => $relationshipData['type'],
            'method' => $methodName,
            'label' => $this->generateRelationshipLabel($relationshipData['type'], $methodName),
        ];

        // Add type-specific metadata
        switch ($relationshipData['type']) {
            case 'belongsTo':
                $connection['foreign_key'] = $relationshipData['foreign_key'] ?? null;
                $connection['owner_key'] = $relationshipData['owner_key'] ?? null;
                break;
                
            case 'hasOne':
            case 'hasMany':
                $connection['foreign_key'] = $relationshipData['foreign_key'] ?? null;
                $connection['local_key'] = $relationshipData['local_key'] ?? null;
                break;
                
            case 'belongsToMany':
                $connection['pivot_table'] = $relationshipData['pivot_table'] ?? null;
                $connection['foreign_pivot_key'] = $relationshipData['foreign_pivot_key'] ?? null;
                $connection['related_pivot_key'] = $relationshipData['related_pivot_key'] ?? null;
                break;
                
            case 'morphTo':
                $connection['morph_type'] = $relationshipData['morph_type'] ?? null;
                $connection['foreign_key'] = $relationshipData['foreign_key'] ?? null;
                break;
        }

        return $connection;
    }

    /**
     * Generate metadata about the ERD
     */
    protected function generateMetadata(array $modelsMetadata, array $relationshipsData): array
    {
        $totalRelationships = 0;
        $relationshipTypes = [];

        foreach ($relationshipsData as $relationships) {
            foreach ($relationships as $relationship) {
                $totalRelationships++;
                $type = $relationship['type'];
                $relationshipTypes[$type] = ($relationshipTypes[$type] ?? 0) + 1;
            }
        }

        return [
            'generated_at' => now()->toISOString(),
            'total_tables' => count($modelsMetadata),
            'total_relationships' => $totalRelationships,
            'relationship_types' => $relationshipTypes,
            'models_analyzed' => array_keys($modelsMetadata),
            'version' => '1.0.0',
        ];
    }

    /**
     * Generate empty ERD data when no models are found
     */
    protected function generateEmptyErdData(): array
    {
        return [
            'tables' => [],
            'relationships' => [],
            'metadata' => [
                'generated_at' => now()->toISOString(),
                'total_tables' => 0,
                'total_relationships' => 0,
                'relationship_types' => [],
                'models_analyzed' => [],
                'version' => '1.0.0',
                'message' => 'No Eloquent models found. Please ensure your models are in the configured paths.',
            ],
        ];
    }

    /**
     * Generate error ERD data when generation fails
     */
    protected function generateErrorErdData(string $errorMessage): array
    {
        return [
            'tables' => [],
            'relationships' => [],
            'metadata' => [
                'generated_at' => now()->toISOString(),
                'total_tables' => 0,
                'total_relationships' => 0,
                'relationship_types' => [],
                'models_analyzed' => [],
                'version' => '1.0.0',
                'error' => true,
                'message' => 'Failed to generate ERD: ' . $errorMessage,
            ],
        ];
    }

    /**
     * Get table name from model class using metadata
     */
    protected function getTableNameFromModel(string $modelClass, array $modelsMetadata): ?string
    {
        return $modelsMetadata[$modelClass]['table'] ?? null;
    }

    /**
     * Generate a consistent table ID for frontend use
     */
    protected function generateTableId(string $tableName): string
    {
        return 'table_' . str_replace(['-', ' '], '_', $tableName);
    }

    /**
     * Generate a human-readable label for relationships
     */
    protected function generateRelationshipLabel(string $type, string $methodName): string
    {
        $labels = [
            'hasOne' => 'has one',
            'hasMany' => 'has many',
            'belongsTo' => 'belongs to',
            'belongsToMany' => 'many to many',
            'morphTo' => 'morph to',
        ];

        $label = $labels[$type] ?? $type;
        return "{$methodName} ({$label})";
    }

    /**
     * Normalize column types for consistent frontend display
     */
    protected function normalizeColumnType(string $type): string
    {
        $typeMap = [
            'bigint' => 'integer',
            'int' => 'integer',
            'tinyint' => 'integer',
            'smallint' => 'integer',
            'mediumint' => 'integer',
            'varchar' => 'string',
            'char' => 'string',
            'text' => 'text',
            'longtext' => 'text',
            'mediumtext' => 'text',
            'tinytext' => 'text',
            'datetime' => 'datetime',
            'timestamp' => 'datetime',
            'date' => 'date',
            'time' => 'time',
            'decimal' => 'decimal',
            'float' => 'float',
            'double' => 'float',
            'boolean' => 'boolean',
            'json' => 'json',
        ];

        return $typeMap[$type] ?? $type;
    }

    /**
     * Refresh ERD data by clearing cache and regenerating
     */
    public function refreshErdData(): array
    {
        $this->clearCache();
        $this->modelAnalyzer->clearCache();
        $this->relationshipDetector->clearCache();
        
        return $this->generateErdData();
    }

    /**
     * Get ERD data with error handling and fallbacks
     */
    public function getErdDataSafely(): array
    {
        try {
            return $this->generateErdData();
        } catch (\Exception $e) {
            Log::error('Critical error generating ERD data: ' . $e->getMessage());
            
            return [
                'tables' => [],
                'relationships' => [],
                'metadata' => [
                    'generated_at' => now()->toISOString(),
                    'error' => true,
                    'message' => 'Unable to generate ERD. Please check your models and configuration.',
                ],
            ];
        }
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
     * Clear ERD data cache
     */
    public function clearCache(): void
    {
        Cache::forget($this->getCacheKey('complete_erd_data'));
    }
}