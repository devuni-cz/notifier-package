# Devuni Notifier Package

[![Latest Version on Packagist](https://img.shields.io/packagist/v/devuni/notifier-package.svg?style=flat-square)](https://packagist.org/packages/devuni/notifier-package)
[![Total Downloads](https://img.shields.io/packagist/dt/devuni/notifier-package.svg?style=flat-square)](https://packagist.org/packages/devuni/notifier-package)
[![Tests](https://github.com/devuni-cz/notifier-package/actions/workflows/tests.yml/badge.svg)](https://github.com/devuni-cz/notifier-package/actions/workflows/tests.yml)

A Laravel 12 package for automated database backups and notifications.

## Features

-   Automated database backups with mysqldump
-   Automated storage backups with ZIP compression
-   Secure backup uploads to remote servers
-   Password-protected ZIP archives
-   File exclusion configuration
-   REST API for remote backup triggers
-   Comprehensive logging and error handling
-   Easy configuration and customization

## Requirements

-   PHP ^8.4
-   Laravel ^12.2

## Installation

You can install the package via composer:

```bash
composer require devuni/notifier-package
```

Publish the configuration file:

```bash
php artisan vendor:publish --provider="Devuni\Notifier\NotifierServiceProvider" --tag="config"
```

Configure environment variables for Notifier package:

```bash
php artisan notifier:install
```
## Configuration

The configuration file will be published to `config/notifier.php`. Here you can configure:

-   Backup authentication codes and URLs
-   ZIP archive passwords
-   File exclusion patterns
-   Remote server endpoints

## Usage

### Basic Usage

```php
use Devuni\Notifier\Services\NotifierDatabaseService;
use Devuni\Notifier\Services\NotifierStorageService;

// Create a database backup
$databaseBackupPath = NotifierDatabaseService::createDatabaseBackup();
NotifierDatabaseService::sendDatabaseBackup($databaseBackupPath);

// Create a storage backup
$storageBackupPath = NotifierStorageService::createStorageBackup();
NotifierStorageService::sendStorageBackup($storageBackupPath);
```

### Artisan Commands

Create a database backup:

```bash
php artisan notifier:database-backup
```

Create a storage backup:

```bash
php artisan notifier:storage-backup
```

Install and configure the package:

```bash
php artisan notifier:install
```

### API Endpoints

The package provides a REST API endpoint for triggering backups remotely:

```bash
# Trigger database backup
GET /api/backup?param=backup_database

# Trigger storage backup
GET /api/backup?param=backup_storage
```

**Note**: The API endpoint includes rate limiting (5 requests per minute) and requires proper environment configuration.

### Configuration Options

```php
// config/notifier.php
return [
    'backup_code' => env('BACKUP_CODE') ?: env('NOTIFIER_BACKUP_CODE'),
    'backup_url' => env('BACKUP_URL') ?: env('NOTIFIER_URL'),
    'backup_zip_password' => env('BACKUP_ZIP_PASSWORD') ?: env('NOTIFIER_BACKUP_PASSWORD', 'secret123'),

    /*
    |--------------------------------------------------------------------------
    | Excluded Database Tables
    |--------------------------------------------------------------------------
    |
    | Here you may specify a list of database tables that should be
    | excluded from the database backup process.
    | Any table name listed here will be ignored when generating
    | the SQL dump.
    |
    | Examples:
    | 'telescope_entries'        -> exclude Laravel Telescope data
    | 'telescope_entries_tags'  -> exclude Telescope relation table
    | 'pulse_entries'           -> exclude Laravel Pulse data
    */
    'excluded_tables' => [
        'cache',
    ],

    /*
    |--------------------------------------------------------------------------
    | Excluded Files
    |--------------------------------------------------------------------------
    |
    | Here you may specify a list of files or files in directories that should be
    | excluded from the backup process. Any file path that
    | matches an entry in this array will not be copied into storage
    | or included inside the generated ZIP archive.
    |
    | Examples:
    | '.gitignore'       -> exclude the .gitignore file
    | 'public\text.txt'  -> exclude a specific file inside public folder
    */
    'excluded_files' => [
        '.gitignore',
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging
    |--------------------------------------------------------------------------
    |
    | Preferred logging channel for notifier.
    |
    */
    'logging_channel' => env('NOTIFIER_LOGGING_CHANNEL', 'backup'),
];
```

### Environment Variables

The package requires the following environment variables to be configured:

```bash
# Required for backup authentication and upload
BACKUP_CODE=your-secret-backup-code
BACKUP_URL=https://your-backup-server.com/upload

# Required for ZIP encryption (fallback: 'secret123')
BACKUP_ZIP_PASSWORD=your-zip-password

# Alternative environment variable names (fallbacks)
NOTIFIER_BACKUP_CODE=alternative-backup-code
NOTIFIER_URL=alternative-backup-url
NOTIFIER_BACKUP_PASSWORD=alternative-zip-password

# Optional logging configuration
NOTIFIER_LOGGING_CHANNEL=your-logging-channel
```

Use the install command to set these up interactively:

```bash
php artisan notifier:install
```

## Testing

This package uses [Pest](https://pestphp.com) for testing, providing a beautiful and expressive testing experience with comprehensive test coverage.

### Test Suite

The package includes:
- **Unit Tests**: Service classes, commands, controllers, and configuration
- **Feature Tests**: Integration testing and end-to-end workflows
- **Mocking Support**: Complex scenarios with external dependencies

```bash
# Run all tests
composer test

# Run only unit tests
composer test-unit

# Run only feature tests
composer test-feature

# Run tests with coverage
composer test-coverage
```

### Test Structure

```
tests/
├── Unit/
│   ├── Services/         # Service class testing
│   ├── Commands/         # Artisan command testing
│   ├── Controllers/      # API controller testing
│   └── NotifierServiceProviderTest.php
└── Feature/
    ├── NotifierPackageTest.php     # Core integration tests
    ├── PackageInstallationTest.php # Installation testing
    └── BackupWorkflowTest.php      # End-to-end workflows
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

-   [Ludwig Tomas](https://github.com/ludwigtomas)
-   [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
