# ModelAnalyzer Service Documentation

## Overview

The `ModelAnalyzer` service is responsible for discovering and analyzing Eloquent models in a Laravel application. It scans configured directories, extracts model metadata, and provides detailed information about database tables, columns, and model properties.

## Key Features

- **Automatic Model Discovery**: Scans application directories for Eloquent models
- **Metadata Extraction**: Extracts comprehensive model information including tables, columns, keys, and properties
- **Performance Optimization**: Implements intelligent caching to avoid repeated analysis
- **Error Handling**: Graceful handling of missing models, invalid classes, and database issues
- **Configuration-Driven**: Behavior controlled through package configuration

## Configuration

The service uses the following configuration options from `config/erd.php`:

```php
'models' => [
    'paths' => ['app/Models'],           // Directories to scan for models
    'namespace' => 'App\\Models',        // Base namespace for models
    'exclude' => []                      // Model class names to exclude
],
'cache' => [
    'enabled' => true,                   // Enable/disable caching
    'ttl' => 3600,                      // Cache time-to-live in seconds
    'key' => 'laravel_erd_data'         // Cache key prefix
]
```

## Public Methods

### `discoverModels(): array`

Discovers all Eloquent models in the configured directories.

**Returns**: Array of fully qualified model class names

**Example**:
```php
$analyzer = new ModelAnalyzer();
$models = $analyzer->discoverModels();
// Returns: ['App\Models\User', 'App\Models\Post', 'App\Models\Comment']
```

### `analyzeModel(string $modelClass): array`

Analyzes a specific model and extracts its metadata.

**Parameters**:
- `$modelClass`: Fully qualified model class name

**Returns**: Array containing model metadata

**Example**:
```php
$metadata = $analyzer->analyzeModel('App\Models\User');
// Returns detailed model information (see Data Structure section)
```

### `getModelMetadata(): array`

Gets metadata for all discovered models.

**Returns**: Associative array with model classes as keys and metadata as values

**Example**:
```php
$allMetadata = $analyzer->getModelMetadata();
// Returns: ['App\Models\User' => [...], 'App\Models\Post' => [...]]
```

### `clearCache(): void`

Clears all cached model analysis data.

**Example**:
```php
$analyzer->clearCache();
```

## Data Structure

The `analyzeModel()` method returns an array with the following structure:

```php
[
    'class' => 'App\Models\User',           // Fully qualified class name
    'table' => 'users',                     // Database table name
    'columns' => [                          // Array of column information
        [
            'name' => 'id',                 // Column name
            'type' => 'bigint',             // Column type
            'nullable' => false,            // Whether column allows NULL
            'default' => null               // Default value
        ],
        // ... more columns
    ],
    'primary_key' => 'id',                  // Primary key column name
    'timestamps' => true,                   // Whether model uses timestamps
    'fillable' => ['name', 'email'],        // Mass assignable attributes
    'guarded' => ['*'],                     // Guarded attributes
    'casts' => [                           // Attribute casting rules
        'email_verified_at' => 'datetime'
    ]
]
```

## Internal Methods

### Model Discovery Process

1. **Directory Scanning**: Scans configured paths for PHP files
2. **Class Name Extraction**: Converts file paths to fully qualified class names
3. **Model Validation**: Checks if classes extend Eloquent Model
4. **Filtering**: Removes excluded models and abstract classes

### Column Analysis Process

1. **Table Verification**: Checks if database table exists
2. **Column Listing**: Gets all columns from the table
3. **Type Detection**: Determines column data types
4. **Constraint Analysis**: Identifies nullable columns and default values
5. **Metadata Compilation**: Combines all column information

## Caching Strategy

The service implements a two-level caching strategy:

1. **Discovery Cache**: Caches the list of discovered models
2. **Analysis Cache**: Caches individual model analysis results

Cache keys follow the pattern:
- Discovery: `{prefix}_models_discovery`
- Analysis: `{prefix}_model_analysis_{ModelClass}`

## Error Handling

The service handles various error scenarios:

### Missing Models
- **Scenario**: No models found in configured directories
- **Handling**: Returns empty array, logs warning
- **Recovery**: Continues operation, displays helpful message in UI

### Invalid Model Classes
- **Scenario**: PHP files that don't contain valid Eloquent models
- **Handling**: Skips invalid classes, continues scanning
- **Recovery**: Processes remaining valid models

### Database Connection Issues
- **Scenario**: Cannot connect to database or table doesn't exist
- **Handling**: Returns empty column array, logs warning
- **Recovery**: Model is included but without column information

### Reflection Errors
- **Scenario**: Cannot instantiate or reflect on model class
- **Handling**: Skips problematic model, logs error
- **Recovery**: Continues with other models

## Performance Considerations

### Caching Benefits
- Avoids repeated file system scanning
- Prevents redundant database schema queries
- Reduces reflection overhead

### Memory Usage
- Models are analyzed on-demand
- Results are cached to prevent re-analysis
- Large applications should monitor memory usage

### Database Impact
- Schema queries are cached
- Uses Laravel's schema builder for efficiency
- Minimal impact on database performance

## Usage Examples

### Basic Usage
```php
use Looaf\LaravelErd\Services\ModelAnalyzer;

$analyzer = app(ModelAnalyzer::class);

// Discover all models
$models = $analyzer->discoverModels();

// Analyze specific model
$userMetadata = $analyzer->analyzeModel('App\Models\User');

// Get all model metadata
$allMetadata = $analyzer->getModelMetadata();
```

### Custom Configuration
```php
// In config/erd.php
return [
    'models' => [
        'paths' => ['app/Models', 'app/Domain/Models'],
        'namespace' => 'App\\Models',
        'exclude' => ['App\\Models\\BaseModel', 'TestModel']
    ]
];
```

### Cache Management
```php
// Clear cache when models change
$analyzer->clearCache();

// Disable caching for development
// In config/erd.php
'cache' => ['enabled' => false]
```

## Integration with Other Services

The ModelAnalyzer works closely with:

- **RelationshipDetector**: Provides model classes for relationship analysis
- **ErdDataGenerator**: Supplies model metadata for ERD generation
- **Laravel Cache**: Uses Laravel's caching system for performance
- **Laravel Schema**: Leverages Laravel's schema builder for column information

## Troubleshooting

### No Models Found
1. Check configured paths in `config/erd.php`
2. Verify namespace configuration
3. Ensure models extend Eloquent Model
4. Check file permissions

### Missing Column Information
1. Verify database connection
2. Check if tables exist
3. Ensure proper database permissions
4. Review Laravel database configuration

### Performance Issues
1. Enable caching in configuration
2. Increase cache TTL for stable applications
3. Consider excluding unused models
4. Monitor memory usage with large model sets