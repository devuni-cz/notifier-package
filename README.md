# Devuni Notifier Package

[![Latest Version on Packagist](https://img.shields.io/packagist/v/devuni/notifier-package.svg?style=flat-square)](https://packagist.org/packages/devuni/notifier-package)
[![Total Downloads](https://img.shields.io/packagist/dt/devuni/notifier-package.svg?style=flat-square)](https://packagist.org/packages/devuni/notifier-package)
[![Tests](https://github.com/devuni-cz/notifier-package/actions/workflows/ci.yml/badge.svg)](https://github.com/devuni-cz/notifier-package/actions/workflows/ci.yml)
[![PHP 8.4](https://img.shields.io/badge/PHP-8.4-777BB4?style=flat-square&logo=php&logoColor=white)](https://www.php.net)
[![Laravel 12](https://img.shields.io/badge/Laravel-12-FF2D20?style=flat-square&logo=laravel&logoColor=white)](https://laravel.com)
[![PHPStan Level 5](https://img.shields.io/badge/PHPStan-level%205-blue?style=flat-square)](https://phpstan.org)
[![Code Style](https://img.shields.io/badge/code%20style-pint-orange?style=flat-square)](https://laravel.com/docs/pint)
[![License: MIT](https://img.shields.io/badge/license-MIT-green?style=flat-square)](LICENSE.md)

A Laravel package for automated, encrypted database and storage backups with secure remote delivery. Supports Artisan commands, HTTP API triggers, queue offloading, and AES-256 ZIP encryption out of the box.

## Features

-   **Database backups** — `mysqldump` with `--single-transaction` and `--quick` for consistent, low-memory dumps
-   **Storage backups** — Archives `storage/app/public` into password-protected ZIP (AES-256)
-   **Pluggable ZIP strategy** — CLI `7z` (recommended) with PHP `ZipArchive` fallback
-   **Chunked uploads** — Large backup files split into configurable chunks (default 20 MB) for reliable transfer
-   **Token-based authentication** — `X-Notifier-Token` header validated with constant-time comparison
-   **Queue support** — Offload backup jobs to any Laravel queue driver to avoid HTTP timeout limits
-   **Remote API trigger** — Rate-limited REST endpoint to trigger backups from an external scheduler
-   **Exclusion lists** — Ignore specific database tables and storage files/directories
-   **Configurable routing** — Custom route prefix, or disable routes entirely
-   **Comprehensive logging** — Dedicated configurable logging channel with automatic fallback
-   **Automatic cleanup** — Temporary backup files removed after successful upload

## Requirements

-   PHP `^8.4`
-   Laravel `^12.2`
-   `mysqldump` binary for database backups
-   `7z` (`p7zip-full`) recommended for storage backups, or PHP `zip` extension as fallback

## Installation

### 1. Install via Composer

```bash
composer require devuni/notifier-package
```

### 2. Publish the configuration

```bash
php artisan vendor:publish --tag="notifier-config"
```

This copies `config/notifier.php` into your application's `config/` directory.

### 3. Configure environment variables

Run the interactive installer to write the required values to your `.env`:

```bash
php artisan notifier:install
```

### 4. Verify your setup

```bash
php artisan notifier:check
```

This command validates your environment variables, database connectivity, storage access, system tools (`mysqldump`, `7z`), logging channel, and backup URL reachability.

---

## Configuration

After publishing, edit `config/notifier.php` or set values via environment variables.

### Environment Variables

```bash
# Required
NOTIFIER_BACKUP_CODE=your-secret-token       # Authentication token (must match the central notifier)
NOTIFIER_URL=https://your-backup-server.com  # Endpoint that receives backup uploads
NOTIFIER_BACKUP_PASSWORD=your-zip-password   # AES-256 ZIP encryption password

# Optional
NOTIFIER_LOGGING_CHANNEL=backup              # Laravel logging channel (default: backup)
NOTIFIER_ROUTES_ENABLED=true                 # Enable/disable the API route (default: true)
NOTIFIER_ROUTE_PREFIX=api/notifier           # API route prefix (default: api/notifier)
NOTIFIER_ZIP_STRATEGY=auto                   # ZIP strategy: auto, cli, php (default: auto)
NOTIFIER_CHUNK_SIZE=20971520                 # Upload chunk size in bytes (default: 20 MB)
NOTIFIER_QUEUE_CONNECTION=sync               # Queue driver for API-triggered backups (default: sync)
```

### All Configuration Options

| Key | Env Variable | Default | Description |
|-----|-------------|---------|-------------|
| `backup_code` | `NOTIFIER_BACKUP_CODE` | — | Authentication token matched against `X-Notifier-Token` header |
| `backup_url` | `NOTIFIER_URL` | — | Central notifier endpoint for backup uploads |
| `backup_zip_password` | `NOTIFIER_BACKUP_PASSWORD` | — | Password used to encrypt backup ZIP archives |
| `excluded_tables` | — | `[]` | Database tables excluded from backup (e.g. `telescope_entries`, `sessions`) |
| `excluded_files` | — | `['.gitignore']` | Files/dirs excluded from storage backup (relative to `storage/app/public`) |
| `logging_channel` | `NOTIFIER_LOGGING_CHANNEL` | `backup` | Laravel log channel; falls back to `daily` if channel doesn't exist |
| `routes_enabled` | `NOTIFIER_ROUTES_ENABLED` | `true` | Whether to register the package's API route |
| `route_prefix` | `NOTIFIER_ROUTE_PREFIX` | `api/notifier` | URL prefix for the API route |
| `zip_strategy` | `NOTIFIER_ZIP_STRATEGY` | `auto` | ZIP strategy: `auto` (detect), `cli` (force 7z), `php` (force ZipArchive) |
| `chunk_size` | `NOTIFIER_CHUNK_SIZE` | `20971520` | Upload chunk size in bytes. Keep under your proxy's limit (e.g. Cloudflare free: 100 MB) |
| `queue_connection` | `NOTIFIER_QUEUE_CONNECTION` | `sync` | Queue driver for API-triggered backups. Artisan commands always run synchronously. |

---

## Artisan Commands

### `notifier:install`

Interactive wizard that writes the three required environment variables to your `.env` file.

```bash
php artisan notifier:install

# Overwrite existing values
php artisan notifier:install --force
```

### `notifier:check`

Validates the full environment and system requirements. Checks:

- `NOTIFIER_BACKUP_CODE`, `NOTIFIER_URL`, `NOTIFIER_BACKUP_PASSWORD` are set
- Database connection is healthy
- `storage/app/public` is accessible
- `mysqldump` is available on `$PATH`
- ZIP tool is available (`7z` or PHP `zip` extension)
- Logging channel is configured
- Queue connection is set up
- Backup URL is reachable over HTTPS

```bash
php artisan notifier:check
```

### `notifier:database-backup`

Creates a `mysqldump` of the application database and uploads it to the central notifier.

```bash
php artisan notifier:database-backup
```

### `notifier:storage-backup`

Archives `storage/app/public` into an encrypted ZIP and uploads it to the central notifier.

```bash
php artisan notifier:storage-backup
```

---

## API Endpoint

The package registers a single POST endpoint for triggering backups remotely (e.g. from a central scheduler):

```
POST /{route_prefix}/backup
```

**Authentication:** `X-Notifier-Token` header — must match `NOTIFIER_BACKUP_CODE`.  
**Rate limit:** 10 requests per 60 seconds.

### Request

| Parameter | Type | Required | Values |
|-----------|------|----------|--------|
| `type` | string | ✓ | `backup_database`, `backup_storage` |

### Examples

```bash
# Trigger a database backup
curl -X POST https://your-app.com/api/notifier/backup \
  -H "X-Notifier-Token: your-secret-token" \
  -d "type=backup_database"

# Trigger a storage backup
curl -X POST https://your-app.com/api/notifier/backup \
  -H "X-Notifier-Token: your-secret-token" \
  -d "type=backup_storage"
```

### Response

**Success (synchronous):**
```json
{
    "success": true,
    "message": "Database backup completed successfully.",
    "backup_type": "database",
    "duration_seconds": 12.34,
    "timestamp": "2025-01-15T10:30:45+00:00"
}
```

**Success (queued):**
```json
{
    "success": true,
    "message": "Backup job dispatched to queue.",
    "backup_type": "storage",
    "queued": true,
    "timestamp": "2025-01-15T10:30:45+00:00"
}
```

**Error:**
```json
{
    "success": false,
    "message": "Database backup failed.",
    "backup_type": "database",
    "error": "mysqldump: command not found",
    "timestamp": "2025-01-15T10:30:45+00:00"
}
```

---

## ZIP Strategy

Storage backups use AES-256 encryption regardless of the strategy chosen.

| Strategy | Tool | Memory usage | Best for |
|----------|------|-------------|----------|
| `auto` *(default)* | 7z if available, else ZipArchive | — | All environments |
| `cli` | 7z (`p7zip-full`) | Low — separate process | Production servers |
| `php` | PHP `ZipArchive` | Higher — PHP heap | Environments without 7z |

Install `7z` for best performance on Linux servers:

```bash
sudo apt install p7zip-full
```

---

## Queue Support

By default, API-triggered backups run synchronously inside the HTTP request. For large backups this may hit PHP's `max_execution_time`. Set `NOTIFIER_QUEUE_CONNECTION` to any async queue driver to offload backup jobs:

```bash
NOTIFIER_QUEUE_CONNECTION=database   # requires php artisan queue:table
NOTIFIER_QUEUE_CONNECTION=redis      # requires phpredis or predis
```

> **Note:** Artisan commands (`notifier:database-backup`, `notifier:storage-backup`) always run synchronously and are not affected by this setting.

---

## Programmatic Usage

The services can be resolved from the container or injected directly:

```php
use Devuni\Notifier\Services\NotifierDatabaseService;
use Devuni\Notifier\Services\NotifierStorageService;

$db = app(NotifierDatabaseService::class);
$storage = app(NotifierStorageService::class);

// Database backup
$path = $db->createDatabaseBackup();
$db->sendDatabaseBackup($path);

// Storage backup
$path = $storage->createStorageBackup();
$storage->sendStorageBackup($path);
```

---

## Testing

This package uses [Pest](https://pestphp.com) for testing.

```bash
composer test            # Run all tests
composer test-unit       # Unit tests only
composer test-feature    # Feature tests only
composer test-coverage   # Tests with coverage report
```

### Test Structure

```
tests/
├── Unit/
│   ├── Commands/         # Artisan command tests
│   ├── Controllers/      # API controller tests
│   ├── Services/         # Service class tests
│   └── NotifierServiceProviderTest.php
└── Feature/
    ├── BackupWorkflowTest.php
    ├── NotifierPackageTest.php
    └── PackageInstallationTest.php
```

---

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
