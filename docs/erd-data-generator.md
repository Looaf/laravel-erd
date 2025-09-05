# ErdDataGenerator Service Documentation

## Overview

The `ErdDataGenerator` service combines data from the ModelAnalyzer and RelationshipDetector services to create a complete, frontend-ready ERD data structure. It transforms raw model and relationship data into a format optimized for React-based diagram visualization.

## Key Features

- **Data Transformation**: Converts model metadata into frontend-compatible table structures
- **Relationship Mapping**: Transforms relationships into visual connection data
- **Position Management**: Automatically positions tables in a grid layout
- **Error Handling**: Provides graceful fallbacks for missing or invalid data
- **Metadata Generation**: Creates comprehensive statistics about the ERD
- **Performance Optimization**: Implements caching for complete ERD data
- **Frontend Optimization**: Structures data specifically for React Flow and diagram libraries

## Dependencies

The service requires:
- `ModelAnalyzer`: For model discovery and metadata
- `RelationshipDetector`: For relationship analysis
- Laravel Cache system for performance optimization

## Public Methods

### `generateErdData(): array`

Generates the complete ERD data structure for frontend consumption.

**Returns**: Complete ERD data array with tables, relationships, and metadata

**Example**:
```php
$generator = new ErdDataGenerator($modelAnalyzer, $relationshipDetector);
$erdData = $generator->generateErdData();
```

### `refreshErdData(): array`

Clears all caches and regenerates ERD data from scratch.

**Returns**: Fresh ERD data array

**Example**:
```php
$freshData = $generator->refreshErdData();
```

### `getErdDataSafely(): array`

Gets ERD data with comprehensive error handling and fallbacks.

**Returns**: ERD data array (never throws exceptions)

**Example**:
```php
$safeData = $generator->getErdDataSafely();
```

### `clearCache(): void`

Clears the ERD data cache.

**Example**:
```php
$generator->clearCache();
```

## Data Structure

The service generates a comprehensive data structure optimized for frontend consumption:

### Complete ERD Data Structure
```php
[
    'tables' => [
        // Array of table objects (see Table Structure)
    ],
    'relationships' => [
        // Array of relationship connections (see Relationship Structure)
    ],
    'metadata' => [
        // ERD metadata and statistics (see Metadata Structure)
    ]
]
```

### Table Structure
```php
[
    'id' => 'table_users',                 // Unique table identifier
    'name' => 'users',                     // Database table name
    'model' => 'App\Models\User',          // Eloquent model class
    'columns' => [                         // Array of column objects
        [
            'name' => 'id',                // Column name
            'type' => 'integer',           // Normalized column type
            'nullable' => false,           // Whether column allows NULL
            'default' => null,             // Default value
            'primary' => false,            // Is primary key (set separately)
            'foreign' => false             // Is foreign key (determined from relationships)
        ]
    ],
    'primary_key' => 'id',                 // Primary key column name
    'timestamps' => true,                  // Whether model uses timestamps
    'position' => [                        // Position for diagram layout
        'x' => 100,                        // X coordinate
        'y' => 100                         // Y coordinate
    ],
    'fillable' => ['name', 'email'],       // Mass assignable attributes
    'guarded' => ['*'],                    // Guarded attributes
    'casts' => [                          // Attribute casting rules
        'email_verified_at' => 'datetime'
    ]
]
```

### Relationship Structure
```php
[
    'id' => 'connection_1',                // Unique connection identifier
    'source' => 'table_users',            // Source table ID
    'target' => 'table_posts',            // Target table ID
    'type' => 'hasMany',                  // Relationship type
    'method' => 'posts',                  // Method name in source model
    'label' => 'posts (has many)',       // Human-readable label
    // Type-specific metadata:
    'foreign_key' => 'user_id',          // For hasOne/hasMany/belongsTo
    'local_key' => 'id',                 // For hasOne/hasMany
    'owner_key' => 'id',                 // For belongsTo
    'pivot_table' => 'user_roles',       // For belongsToMany
    'foreign_pivot_key' => 'user_id',    // For belongsToMany
    'related_pivot_key' => 'role_id',    // For belongsToMany
    'morph_type' => 'commentable_type',  // For morphTo
]
```

### Metadata Structure
```php
[
    'generated_at' => '2024-01-15T10:30:00Z',  // ISO timestamp
    'total_tables' => 5,                        // Number of tables
    'total_relationships' => 12,                // Number of relationships
    'relationship_types' => [                   // Breakdown by type
        'hasMany' => 4,
        'belongsTo' => 6,
        'belongsToMany' => 2
    ],
    'models_analyzed' => [                      // List of analyzed models
        'App\Models\User',
        'App\Models\Post',
        'App\Models\Comment'
    ],
    'version' => '1.0.0',                      // ERD data format version
    'message' => 'ERD generated successfully'   // Status message (optional)
]
```

## Data Transformation Process

### Model to Table Transformation

1. **Table Creation**: Converts each model into a table object
2. **Column Processing**: Transforms column metadata with type normalization
3. **Position Assignment**: Automatically positions tables in a grid layout
4. **ID Generation**: Creates consistent table IDs for frontend use

### Relationship to Connection Transformation

1. **Connection Creation**: Converts each relationship into a connection object
2. **Metadata Extraction**: Extracts type-specific relationship data
3. **Label Generation**: Creates human-readable relationship labels
4. **Validation**: Ensures relationships are valid before inclusion

### Position Management

Tables are automatically positioned using a grid layout:
- **Grid Size**: 4 tables per row by default
- **Spacing**: 300px between tables
- **Starting Position**: (100, 100)
- **Algorithm**: Sequential placement with row wrapping

## Column Type Normalization

The service normalizes database column types for consistent frontend display:

```php
$typeMap = [
    'bigint' => 'integer',
    'varchar' => 'string',
    'text' => 'text',
    'datetime' => 'datetime',
    'timestamp' => 'datetime',
    'decimal' => 'decimal',
    'boolean' => 'boolean',
    'json' => 'json'
];
```

## Error Handling

### No Models Found
- **Response**: Returns empty ERD data with helpful message
- **Metadata**: Includes explanation about model discovery
- **Recovery**: Frontend can display instructions to user

### Model Analysis Failures
- **Response**: Excludes problematic models, continues with others
- **Logging**: Logs detailed error information
- **Recovery**: Partial ERD generation with available data

### Relationship Validation Failures
- **Response**: Excludes invalid relationships, keeps valid ones
- **Logging**: Logs relationship validation errors
- **Recovery**: ERD displays with available relationships

### Critical Errors
- **Response**: Returns error ERD data structure
- **Logging**: Logs critical errors with full stack trace
- **Recovery**: Frontend displays error message with troubleshooting hints

## Caching Strategy

The service implements intelligent caching:

### Cache Key Structure
- **Complete ERD**: `{prefix}_complete_erd_data`
- **Dependencies**: Relies on ModelAnalyzer and RelationshipDetector caches

### Cache Invalidation
- **Manual**: Via `clearCache()` or `refreshErdData()`
- **Automatic**: When underlying service caches are cleared
- **TTL**: Configurable time-to-live (default 1 hour)

## Performance Considerations

### Memory Usage
- **Large Applications**: May use significant memory with many models
- **Optimization**: Consider excluding unused models
- **Monitoring**: Monitor memory usage in production

### Generation Time
- **First Run**: May take time to analyze all models and relationships
- **Cached Runs**: Subsequent requests are very fast
- **Optimization**: Enable caching for production use

### Frontend Optimization
- **Data Structure**: Optimized for React Flow and diagram libraries
- **Position Data**: Pre-calculated positions reduce frontend computation
- **Type Normalization**: Consistent types simplify frontend logic

## Usage Examples

### Basic Usage
```php
use Looaf\LaravelErd\Services\ErdDataGenerator;

$generator = app(ErdDataGenerator::class);

// Generate complete ERD data
$erdData = $generator->generateErdData();

// Use in controller
return response()->json($erdData);
```

### With Error Handling
```php
// Safe generation (never throws exceptions)
$erdData = $generator->getErdDataSafely();

if (isset($erdData['metadata']['error'])) {
    // Handle error case
    Log::error('ERD generation failed: ' . $erdData['metadata']['message']);
}
```

### Cache Management
```php
// Refresh data after model changes
$freshData = $generator->refreshErdData();

// Clear cache manually
$generator->clearCache();
```

### Frontend Integration
```php
// In Laravel controller
public function erdData()
{
    $generator = app(ErdDataGenerator::class);
    $data = $generator->getErdDataSafely();
    
    return response()->json($data);
}
```

## Integration with Frontend

### React Flow Integration
The data structure is optimized for React Flow:
- **Tables**: Convert to React Flow nodes
- **Relationships**: Convert to React Flow edges
- **Positions**: Ready-to-use coordinates

### Example Frontend Usage
```javascript
// Fetch ERD data
const response = await fetch('/erd/data');
const erdData = await response.json();

// Convert to React Flow format
const nodes = erdData.tables.map(table => ({
    id: table.id,
    type: 'tableNode',
    position: table.position,
    data: table
}));

const edges = erdData.relationships.map(rel => ({
    id: rel.id,
    source: rel.source,
    target: rel.target,
    label: rel.label,
    data: rel
}));
```

## Troubleshooting

### Empty ERD Data
1. Check if models exist in configured paths
2. Verify ModelAnalyzer configuration
3. Ensure database connection is working
4. Check model discovery logs

### Missing Relationships
1. Verify RelationshipDetector is working
2. Check relationship method implementations
3. Ensure related models exist
4. Review relationship validation logs

### Performance Issues
1. Enable caching in production
2. Exclude unused models from analysis
3. Monitor memory usage
4. Consider increasing cache TTL

### Frontend Integration Issues
1. Verify data structure matches frontend expectations
2. Check for JavaScript errors in browser console
3. Validate API response format
4. Ensure proper error handling in frontend