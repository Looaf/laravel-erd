# Testing

This package includes comprehensive tests to ensure the model analysis and ERD generation functionality works correctly.

## Test Structure

The test suite is organized into:

- **Unit Tests** (`tests/Unit/`) - Test individual service classes in isolation
- **Integration Tests** (`tests/Integration/`) - Test the complete workflow from model discovery to ERD generation

## Running Tests

### Prerequisites

Make sure you have the development dependencies installed:

```bash
composer install
```

### Running All Tests

```bash
./vendor/bin/phpunit
```

### Running Specific Test Suites

Run only unit tests:
```bash
./vendor/bin/phpunit --testsuite Unit
```

Run only integration tests:
```bash
./vendor/bin/phpunit --testsuite Integration
```

### Running Individual Test Files

```bash
./vendor/bin/phpunit tests/Unit/ModelAnalyzerTest.php
./vendor/bin/phpunit tests/Unit/RelationshipDetectorTest.php
./vendor/bin/phpunit tests/Unit/ErdDataGeneratorTest.php
./vendor/bin/phpunit tests/Integration/ModelAnalysisIntegrationTest.php
```

### Test Output Options

For detailed test descriptions:
```bash
./vendor/bin/phpunit --testdox
```

For code coverage (requires Xdebug):
```bash
./vendor/bin/phpunit --coverage-html coverage
```

## Test Coverage

The test suite covers:

### ModelAnalyzer Service
- ✅ Model discovery in configured paths
- ✅ Model metadata extraction (tables, columns, relationships)
- ✅ Caching functionality
- ✅ Error handling for missing tables and invalid models
- ✅ Configuration respect (excluded models, paths, etc.)

### RelationshipDetector Service
- ✅ Detection of all relationship types (hasOne, hasMany, belongsTo, belongsToMany, morphTo)
- ✅ Relationship metadata extraction (foreign keys, pivot tables, etc.)
- ✅ Method filtering (excludes getters, setters, non-relationship methods)
- ✅ Relationship validation
- ✅ Caching and error handling

### ErdDataGenerator Service
- ✅ Complete ERD data structure generation
- ✅ Model-to-table transformation
- ✅ Relationship-to-connection transformation
- ✅ Data normalization for frontend consumption
- ✅ Error handling and fallback responses
- ✅ Caching and refresh functionality

### Integration Tests
- ✅ Complete workflow from model discovery to ERD generation
- ✅ Complex relationship handling (many-to-many, polymorphic)
- ✅ Cross-service caching
- ✅ Error scenarios and edge cases

## Test Configuration

Tests use an in-memory SQLite database and array cache driver for isolation. The test configuration is automatically set up in `tests/TestCase.php`.

## Writing New Tests

When adding new functionality:

1. **Unit Tests**: Test individual methods and classes in isolation using mocks
2. **Integration Tests**: Test complete workflows with real database interactions
3. **Follow Naming**: Use descriptive test method names starting with `it_`
4. **Use Fixtures**: Create test models and database schemas in test methods
5. **Clean Up**: Ensure tests clean up after themselves (files, database, cache)

## Continuous Integration

The test suite is designed to run in CI environments with:
- No external dependencies
- In-memory database
- Automatic cleanup
- Comprehensive error reporting

## Troubleshooting Tests

### Common Issues

**Service Provider Not Found**: Make sure the namespace in `composer.json` matches the actual service provider class.

**Database Errors**: Tests use SQLite in-memory database. Ensure your test environment supports SQLite.

**File Permission Errors**: Tests create temporary model files. Ensure write permissions in the test directory.

**Cache Issues**: Tests use array cache driver. If you see cache-related failures, check that the cache is properly cleared between tests.