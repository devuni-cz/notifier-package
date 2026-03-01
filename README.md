# Devuni Notifier Package

[![Latest Version on Packagist](https://img.shields.io/packagist/v/devuni/notifier-package.svg?style=flat-square)](https://packagist.org/packages/devuni/notifier-package)
[![Total Downloads](https://img.shields.io/packagist/dt/devuni/notifier-package.svg?style=flat-square)](https://packagist.org/packages/devuni/notifier-package)
[![Tests](https://github.com/devuni-cz/notifier-package/actions/workflows/tests.yml/badge.svg)](https://github.com/devuni-cz/notifier-package/actions/workflows/tests.yml)

A Laravel 12 package for automated database and storage backups with secure remote uploads.

## Features

-   Automated database backups with mysqldump (`--single-transaction`, `--quick`)
-   Automated storage backups with ZIP compression (AES-256 encryption)
-   Pluggable ZIP strategy — CLI 7z (recommended) with PHP ZipArchive fallback
-   Secure backup uploads to remote servers with retry and timeout
-   Token-based API authentication via `X-Notifier-Token` header
-   Password-protected ZIP archives
-   File and table exclusion configuration
-   REST API for remote backup triggers (rate-limited)
-   Configurable routes (prefix, enable/disable)
-   Comprehensive logging with configurable channels
-   Automatic backup file cleanup after upload

## Requirements

-   PHP ^8.4
-   Laravel ^12.2
-   `mysqldump` for database backups
-   `7z` (p7zip-full) recommended for storage backups, or PHP `zip` extension as fallback

## Installation

Install the package via composer:

```bash
composer require devuni/notifier-package
```

Publish the configuration file:

```bash
php artisan vendor:publish --provider="Devuni\Notifier\NotifierServiceProvider" --tag="config"
```

Run the interactive installer to configure environment variables:

```bash
php artisan notifier:install
```

Verify your setup:

```bash
php artisan notifier:check
```

## Configuration

The configuration file will be published to `config/notifier.php`. You can configure:

-   Backup authentication token and upload URL
-   ZIP archive password and encryption strategy
-   Database table exclusions
-   File exclusion patterns
-   Logging channel
-   Route prefix and toggle

### Configuration Options

```php
// config/notifier.php
return [
    // Authentication token for API requests
    'backup_code' => env('NOTIFIER_BACKUP_CODE', env('BACKUP_CODE')),

    // URL where backups will be uploaded
    'backup_url' => env('NOTIFIER_URL', env('BACKUP_URL')),

    // Password for ZIP encryption (AES-256)
    'backup_zip_password' => env('NOTIFIER_BACKUP_PASSWORD', env('BACKUP_ZIP_PASSWORD')),

    // Database tables to exclude from backup
    'excluded_tables' => [],

    // Files/directories to exclude from storage backup (relative to storage/app/public)
    'excluded_files' => [
        '.gitignore',
    ],

    // Logging channel (falls back to 'daily' if not found)
    'logging_channel' => env('NOTIFIER_LOGGING_CHANNEL', 'backup'),

    // Route configuration
    'routes_enabled' => env('NOTIFIER_ROUTES_ENABLED', true),
    'route_prefix' => env('NOTIFIER_ROUTE_PREFIX', 'api/notifier'),

    // ZIP strategy: 'auto' (default), 'cli' (force 7z), 'php' (force ZipArchive)
    'zip_strategy' => env('NOTIFIER_ZIP_STRATEGY', 'auto'),
];
```

### Environment Variables

```bash
# Required — authentication and upload
NOTIFIER_BACKUP_CODE=your-secret-token
NOTIFIER_URL=https://your-backup-server.com/api/receive-backup
NOTIFIER_BACKUP_PASSWORD=your-strong-zip-password

# Optional — logging
NOTIFIER_LOGGING_CHANNEL=backup

# Optional — route customization
NOTIFIER_ROUTES_ENABLED=true
NOTIFIER_ROUTE_PREFIX=api/notifier

# Optional — ZIP strategy (auto, cli, php)
NOTIFIER_ZIP_STRATEGY=auto
```

## Usage

### Basic Usage

```php
use Devuni\Notifier\Services\NotifierDatabaseService;
use Devuni\Notifier\Services\NotifierStorageService;

// Resolve from the container (or inject via constructor)
$databaseService = app(NotifierDatabaseService::class);
$storageService = app(NotifierStorageService::class);

// Create and send a database backup
$databaseBackupPath = $databaseService->createDatabaseBackup();
$databaseService->sendDatabaseBackup($databaseBackupPath);

// Create and send a storage backup
$storageBackupPath = $storageService->createStorageBackup();
$storageService->sendStorageBackup($storageBackupPath);
```

### Artisan Commands

```bash
# Create and upload a database backup
php artisan notifier:database-backup

# Create and upload a storage backup
php artisan notifier:storage-backup

# Check package configuration and system requirements
php artisan notifier:check

# Interactive environment setup
php artisan notifier:install
```

### API Endpoint

The package provides a REST API endpoint for triggering backups remotely:

```bash
# Trigger database backup
curl -X POST https://your-app.com/api/notifier/backup \
  -H "X-Notifier-Token: your-secret-token" \
  -d "type=backup_database"

# Trigger storage backup
curl -X POST https://your-app.com/api/notifier/backup \
  -H "X-Notifier-Token: your-secret-token" \
  -d "type=backup_storage"
```

The endpoint is rate-limited to 5 requests per minute and requires the `X-Notifier-Token` header for authentication.

### ZIP Strategy

The package supports two ZIP creation strategies for storage backups:

| Strategy | Tool | Encryption | Memory | Best for |
|----------|------|------------|--------|----------|
| `cli` | 7z (p7zip-full) | AES-256 | Low (separate process) | Production servers |
| `php` | PHP ZipArchive | AES-256 | Higher (PHP memory) | Local development |
| `auto` | Auto-detect | AES-256 | — | Default (recommended) |

With `auto` (default), the package uses CLI 7z when available and falls back to PHP ZipArchive. Install 7z for best performance:

```bash
sudo apt install p7zip-full
```

## Testing

This package uses [Pest](https://pestphp.com) for testing.

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
