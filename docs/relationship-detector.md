# RelationshipDetector Service Documentation

## Overview

The `RelationshipDetector` service analyzes Eloquent models to automatically detect and extract relationship information. It uses PHP reflection to examine model methods and identify Laravel's built-in relationship types, extracting metadata about foreign keys, pivot tables, and relationship configurations.

## Key Features

- **Automatic Relationship Detection**: Identifies hasOne, hasMany, belongsTo, belongsToMany, and morphTo relationships
- **Method Reflection Analysis**: Uses PHP reflection to analyze model methods
- **Metadata Extraction**: Extracts foreign keys, pivot tables, and relationship-specific data
- **Relationship Validation**: Validates detected relationships for correctness
- **Performance Caching**: Caches relationship analysis results
- **Error Resilience**: Gracefully handles broken or invalid relationships

## Supported Relationship Types

### hasOne
- **Description**: One-to-one relationship where the current model owns the relationship
- **Example**: User hasOne Profile
- **Metadata Extracted**: foreign_key, local_key, related_table

### hasMany
- **Description**: One-to-many relationship where the current model has multiple related records
- **Example**: User hasMany Posts
- **Metadata Extracted**: foreign_key, local_key, related_table

### belongsTo
- **Description**: Inverse of hasOne/hasMany, where the current model belongs to another
- **Example**: Post belongsTo User
- **Metadata Extracted**: foreign_key, owner_key, parent_table

### belongsToMany
- **Description**: Many-to-many relationship using a pivot table
- **Example**: User belongsToMany Roles
- **Metadata Extracted**: pivot_table, foreign_pivot_key, related_pivot_key, parent_key, related_key

### morphTo
- **Description**: Polymorphic relationship where the model can belong to multiple types
- **Example**: Comment morphTo Commentable (Post or Video)
- **Metadata Extracted**: morph_type, foreign_key

## Public Methods

### `detectRelationships(string $modelClass): array`

Detects all relationships for a given model class.

**Parameters**:
- `$modelClass`: Fully qualified model class name

**Returns**: Array of relationship data indexed by method name

**Example**:
```php
$detector = new RelationshipDetector();
$relationships = $detector->detectRelationships('App\Models\User');
// Returns: ['posts' => [...], 'profile' => [...]]
```

### `getRelationshipsForModels(array $modelClasses): array`

Gets relationships for multiple models at once.

**Parameters**:
- `$modelClasses`: Array of fully qualified model class names

**Returns**: Nested array with model classes as keys and relationships as values

**Example**:
```php
$allRelationships = $detector->getRelationshipsForModels([
    'App\Models\User',
    'App\Models\Post'
]);
```

### `validateRelationship(array $relationshipData): bool`

Validates that a relationship is properly configured.

**Parameters**:
- `$relationshipData`: Relationship metadata array

**Returns**: Boolean indicating if relationship is valid

**Example**:
```php
$isValid = $detector->validateRelationship($relationshipData);
```

### `clearCache(?string $modelClass = null): void`

Clears relationship cache for a specific model or all models.

**Parameters**:
- `$modelClass`: Optional model class to clear cache for

**Example**:
```php
$detector->clearCache('App\Models\User'); // Clear specific model
$detector->clearCache(); // Clear all relationship cache
```

## Data Structure

Each detected relationship returns an array with the following base structure:

```php
[
    'type' => 'hasMany',                    // Relationship type
    'related' => 'App\Models\Post',         // Related model class
    'method' => 'posts',                    // Method name in the model
    // ... type-specific metadata
]
```

### belongsTo Relationships
```php
[
    'type' => 'belongsTo',
    'related' => 'App\Models\User',
    'method' => 'user',
    'foreign_key' => 'user_id',             // Foreign key in current table
    'owner_key' => 'id',                    // Key in parent table
    'parent_table' => 'users'               // Parent table name
]
```

### hasOne/hasMany Relationships
```php
[
    'type' => 'hasMany',
    'related' => 'App\Models\Post',
    'method' => 'posts',
    'foreign_key' => 'user_id',             // Foreign key in related table
    'local_key' => 'id',                    // Local key in current table
    'related_table' => 'posts'              // Related table name
]
```

### belongsToMany Relationships
```php
[
    'type' => 'belongsToMany',
    'related' => 'App\Models\Role',
    'method' => 'roles',
    'pivot_table' => 'user_roles',          // Pivot table name
    'foreign_pivot_key' => 'user_id',       // Current model key in pivot
    'related_pivot_key' => 'role_id',       // Related model key in pivot
    'parent_key' => 'id',                   // Key in current table
    'related_key' => 'id'                   // Key in related table
]
```

### morphTo Relationships
```php
[
    'type' => 'morphTo',
    'related' => 'App\Models\Post', // Note: This varies based on actual data
    'method' => 'commentable',
    'morph_type' => 'commentable_type',     // Column storing model type
    'foreign_key' => 'commentable_id'       // Column storing model ID
]
```

## Detection Algorithm

### Method Identification Process

1. **Reflection Analysis**: Gets all public methods from the model class
2. **Parameter Filtering**: Excludes methods with required parameters
3. **Name Filtering**: Excludes magic methods, getters, setters, and base model methods
4. **Inheritance Filtering**: Excludes methods defined in base Model class
5. **Execution Testing**: Attempts to execute method to check if it returns a Relation

### Excluded Methods

The detector automatically excludes:
- Magic methods (`__construct`, `__call`, etc.)
- Getter/setter methods (starting with `get`, `set`, `is`, `has`)
- Base Eloquent methods (`save`, `update`, `delete`, etc.)
- Methods with required parameters
- Static methods
- Methods defined in the base Model class

### Relationship Validation

Each detected relationship is validated to ensure:
- Related model class exists
- Required metadata fields are present
- Relationship configuration is valid

## Caching Strategy

The service caches relationship analysis results using the pattern:
- Cache Key: `{prefix}_relationships_{ModelClass}`
- TTL: Configurable (default 1 hour)
- Storage: Uses Laravel's configured cache driver

## Error Handling

### Broken Relationships
- **Scenario**: Relationship method throws exception when executed
- **Handling**: Logs warning, skips relationship, continues analysis
- **Recovery**: Other valid relationships are still detected

### Missing Related Models
- **Scenario**: Related model class doesn't exist
- **Handling**: Relationship fails validation, excluded from results
- **Recovery**: Continues processing other relationships

### Reflection Errors
- **Scenario**: Cannot reflect on model class or methods
- **Handling**: Logs error, returns empty relationship array
- **Recovery**: Model is processed without relationship information

## Performance Considerations

### Reflection Overhead
- Reflection operations are cached to avoid repeated analysis
- Method filtering reduces the number of methods tested
- Smart exclusion rules prevent unnecessary method execution

### Memory Usage
- Relationships are analyzed on-demand
- Results are cached to prevent re-analysis
- Large models with many relationships may use significant memory

### Database Impact
- Relationship detection doesn't query the database
- Only analyzes relationship definitions, not data
- Minimal performance impact on database

## Usage Examples

### Basic Usage
```php
use Looaf\LaravelErd\Services\RelationshipDetector;

$detector = app(RelationshipDetector::class);

// Detect relationships for a single model
$userRelationships = $detector->detectRelationships('App\Models\User');

// Detect relationships for multiple models
$allRelationships = $detector->getRelationshipsForModels([
    'App\Models\User',
    'App\Models\Post',
    'App\Models\Comment'
]);

// Validate a relationship
$isValid = $detector->validateRelationship($relationshipData);
```

### Working with Results
```php
foreach ($userRelationships as $methodName => $relationship) {
    echo "Method: {$methodName}\n";
    echo "Type: {$relationship['type']}\n";
    echo "Related: {$relationship['related']}\n";
    
    if ($relationship['type'] === 'belongsTo') {
        echo "Foreign Key: {$relationship['foreign_key']}\n";
    }
}
```

### Cache Management
```php
// Clear cache for specific model
$detector->clearCache('App\Models\User');

// Clear all relationship cache
$detector->clearCache();
```

## Integration with Other Services

The RelationshipDetector integrates with:

- **ModelAnalyzer**: Receives model classes to analyze
- **ErdDataGenerator**: Provides relationship data for ERD generation
- **Laravel Cache**: Uses Laravel's caching system
- **Laravel Container**: Resolves model instances for analysis

## Troubleshooting

### No Relationships Detected
1. Verify model methods return Eloquent Relation instances
2. Check that methods are public and non-static
3. Ensure methods don't have required parameters
4. Verify related model classes exist

### Incorrect Relationship Data
1. Check relationship method implementation
2. Verify foreign key configurations
3. Ensure pivot table exists for belongsToMany
4. Validate related model class names

### Performance Issues
1. Enable caching in configuration
2. Increase cache TTL for stable applications
3. Monitor memory usage with complex models
4. Consider excluding problematic models

### Cache Issues
1. Clear relationship cache after model changes
2. Verify cache driver configuration
3. Check cache permissions and storage
4. Monitor cache hit rates for effectiveness