# Devuni Notifier Package

[![Latest Version on Packagist](https://img.shields.io/packagist/v/devuni/notifier-package.svg?style=flat-square)](https://packagist.org/packages/devuni/notifier-package)
[![Total Downloads](https://img.shields.io/packagist/dt/devuni/notifier-package.svg?style=flat-square)](https://packagist.org/packages/devuni/notifier-package)
[![Tests](https://github.com/devuni-cz/notifier-package/actions/workflows/tests.yml/badge.svg)](https://github.com/devuni-cz/notifier-package/actions/workflows/tests.yml)

A Laravel 12 package for automated database backups and notifications.

## Features

-   Automated database backups
-   Multiple notification channels (email, Slack, etc.)
-   Configurable backup schedules
-   Backup verification and validation
-   Comprehensive logging
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

Publish and run the migrations:

```bash
php artisan vendor:publish --provider="Devuni\Notifier\NotifierServiceProvider" --tag="migrations"
php artisan migrate
```

Configure environment variables for Notifier package:

```bash
php artisan notifier:install
```
## Configuration

The configuration file will be published to `config/notifier.php`. Here you can configure:

-   Database backup settings
-   Notification channels and recipients
-   Backup storage locations
-   Cleanup policies

## Usage

### Basic Usage

```php
use Devuni\Notifier\Services\NotifierDatabaseService;

// Create a backup
$service = app(NotifierDatabaseService::class);
$backup = $service->createBackup();
```

### Artisan Commands

Create a database backup:

```bash
php artisan notifier:backup
```

### Configuration Options

```php
// config/notifier.php
return [
    'backup_code' => env('BACKUP_CODE'),
    'backup_url' => env('BACKUP_URL'),
    'backup_zip_password' => env('BACKUP_ZIP_PASSWORD', 'secret123'),

    'paths' => [
        'backup' => env('NOTIFIER_BACKUP_PATH', 'backups'),
        'storage' => env('NOTIFIER_STORAGE_PATH', 'public'),
    ],

    'log_channel' => env('NOTIFIER_LOG_CHANNEL', 'backup'),
    'default_disk' => env('NOTIFIER_DEFAULT_DISK', 'local'),
];
```

* `paths.backup` – directory relative to `storage/app` where backup files are written. Defaults to `backups`.
* `paths.storage` – directory relative to `storage/app` that will be archived for storage backups. Defaults to `public`.
* `log_channel` – log channel used for all notifier package logs. Defaults to `backup`.
* `default_disk` – filesystem disk used for backup file operations. Defaults to `local`.

## Testing

This package uses [Pest](https://pestphp.com) for testing, providing a beautiful and expressive testing experience.


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
