# Laravel ERD Package

A Laravel package that automatically generates interactive entity relationship diagrams from your Eloquent models.

## Installation

Install the package via Composer:

```bash
composer require looaf/laravel-erd
```

The package will automatically register its service provider.

## Configuration

Publish the configuration file:

```bash
php artisan vendor:publish --tag=erd-config
```

This will create a `config/erd.php` file where you can customize the package settings.

## Usage

Once installed, visit `/erd` in your browser to view the interactive ERD of your application's models.

## Testing

This package includes comprehensive tests. To run the test suite:

```bash
# Run all tests
./vendor/bin/phpunit

# Run with detailed output
./vendor/bin/phpunit --testdox

# Run specific test suites
./vendor/bin/phpunit --testsuite Unit
./vendor/bin/phpunit --testsuite Integration
```

For more detailed testing information, see [docs/testing.md](docs/testing.md).

## Requirements

- PHP 8.1+
- Laravel 9.0+

## License

MIT License