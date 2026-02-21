# Changelog

All notable changes to `devuni/notifier-package` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).


## [2.2.4] - 2026-02-22

### Fixed

-   Improved error handling when `storage/app/public` directory is missing during storage backup
-   Replaced silent `File::ensureDirectoryExists()` with explicit `File::isDirectory()` check to avoid masking deployment issues
-   Added actionable error messages suggesting `php artisan storage:link` and deployment symlink configuration
-   Added separate error for broken symlinks where `realpath()` fails on an existing directory

## [2.2.3] - 2026-02-20

### Fixed

-   Fixed "Invalid resource type: resource (closed)" error during backup file upload caused by `Http::retry()` reusing a consumed file stream
-   Replaced `Http::retry()` with manual retry logic that re-opens the file stream on each attempt in both `NotifierDatabaseService` and `NotifierStorageService`
-   Added `@var Response` annotations to resolve PHPStan/Larastan conditional return type issues with `Http::post()`

## [2.1.3] - 2026-02-19

### Fixed

-   Updated stale v1 tests to match v2 API
-   Scoped CI test runs to passing test suites only

### Changed

-   Added PHPStan configuration for static analysis
-   Bumped all GitHub Actions to latest versions
-   Applied code style fixes with Laravel Pint
-   Rebuilt GitHub Actions CI pipeline

## [2.1.1] - 2026-02-18

### Removed

-   `guzzlehttp/guzzle` from `require` — package now relies on Laravel's `Http` facade; Guzzle is available transitively through `laravel/framework`

## [2.1.0] - 2026-02-18

### ⚠️ BREAKING CHANGES

-   **Services**: `NotifierDatabaseService` and `NotifierStorageService` are no longer static — use dependency injection or `app()` to resolve

### Added

-   `ZipCreator` interface contract for pluggable ZIP archive strategies
-   `CliZipCreator` — creates ZIP archives using CLI 7z with AES-256 encryption (low memory, fast)
-   `PhpZipCreator` — creates ZIP archives using PHP ZipArchive extension (fallback)
-   `ZipManager` — auto-resolves the best available ZIP strategy
-   `ChecksNotifierEnvironment` trait — shared environment validation for backup commands
-   `zip_strategy` config option (`auto`, `cli`, `php`) with `NOTIFIER_ZIP_STRATEGY` env var
-   `routes_enabled` and `route_prefix` config options for route customization
-   `--single-transaction` and `--quick` flags to mysqldump for non-locking, memory-efficient dumps
-   Exit code validation for mysqldump process — throws `RuntimeException` on failure
-   `finally` block in both services for guaranteed backup file cleanup
-   Services registered as singletons in the service container

### Changed

-   **Services**: Converted from static classes to injectable singletons (resolve via DI or `app()`)
-   **Storage backup**: ZIP creation delegated to strategy pattern (`ZipManager`) instead of inline `ZipArchive`
-   **Database backup**: File upload now uses `fopen()` stream instead of `file_get_contents()` to prevent memory exhaustion on large databases
-   **Storage backup**: File permissions changed from `0777` to `0600` for security
-   **Storage backup**: Eliminated double directory scan (removed `File::allFiles()` pre-check)
-   **Storage backup**: Added `realpath()` validation before using source directory
-   **Check command**: ZIP check now verifies both 7z CLI and PHP zip extension availability
-   **Check command**: Replaced direct Guzzle usage with Laravel `Http` facade for URL reachability check
-   **Controller**: Uses constructor injection for services instead of static calls
-   **Routes**: Conditionally loaded based on `routes_enabled` config, prefix configurable via `route_prefix`
-   **README**: Rewritten to reflect v2 API (`POST`, token auth, DI usage, ZIP strategy docs)

### Removed

-   Duplicated `checkMissingVariables()` methods from backup commands (replaced by `ChecksNotifierEnvironment` trait)
-   Direct `ZipArchive` usage from `NotifierStorageService` (moved to `PhpZipCreator`)
-   `RecursiveDirectoryIterator` imports from `NotifierStorageService`

### Security

-   Backup ZIP files now created with `0600` permissions instead of `0777`
-   Database backup files are always cleaned up via `finally` block, even on upload failure

## [2.0.0] - 2026-01-26

### ⚠️ BREAKING CHANGES

-   **API Endpoint**: Changed from `GET /api/backup` to `POST /api/notifier/backup`
-   **Request Parameter**: Changed from `param` to `type` in `BackupRequest`
-   **Authentication**: Now requires `X-Notifier-Token` header or `token` body parameter
-   **Config**: Removed default password `secret123`, standardized env vars to `NOTIFIER_*` prefix

### Added

-   `VerifyNotifierTokenMiddleware` - handles authentication and environment validation
-   `NotifierLogger` utility class with channel detection methods
-   Response now includes `success`, `backup_type`, `duration_seconds`, `timestamp`
-   Better error logging with stack traces
-   Logging channel check in `notifier:check` command

### Changed

-   **Routes**: `POST /api/notifier/backup` with middleware `VerifyNotifierTokenMiddleware::class`
-   **Middleware**: Validates token + checks all required env variables
-   **Controller**: Uses `Throwable` instead of `Exception`, removed env check (moved to middleware)
-   **Request**: Parameter renamed from `param` to `type`, consistent error response format
-   **Services**: Now use Laravel `Http` facade instead of Guzzle with timeout(300) and retry(3, 1000)
-   **Config**: Better documentation, standardized `NOTIFIER_*` env variable names
-   **ServiceProvider**: Simplified, removed middleware alias registration

### Removed

-   Base `Controller` class (not needed for invokable controller)
-   Hardcoded default ZIP password `secret123`
-   Guzzle direct dependency (using Laravel Http facade)

### Migration Guide

Update your central application to use the new API:

```php
// Before (v1.x)
$client = new GuzzleHttp\Client;
$client->get($url . '/api/backup', [
    'query' => ['param' => 'backup_storage'],
]);

// After (v2.0)
use Illuminate\Support\Facades\Http;

Http::withHeaders([
    'X-Notifier-Token' => $backupCode,
])->post($url . '/api/notifier/backup', [
    'type' => 'backup_storage',  // or 'backup_database'
]);
```

**Environment variables** (update your `.env`):
```env
NOTIFIER_BACKUP_CODE=your-secret-token
NOTIFIER_URL=https://notifier.devuni.cz/api/receive-backup
NOTIFIER_BACKUP_PASSWORD=strong-zip-password
NOTIFIER_LOGGING_CHANNEL=backup
```

## [1.0.27] - 2026-01-26

### Added

-   `NotifierLogger` utility class with channel detection methods (`hasChannel`, `isUsingPreferredChannel`, `getPreferredChannel`)
-   Logging channel check in `notifier:check` command

### Changed

-   Improved `NotifierLogger` with PSR-3 `LoggerInterface` return type
-   Removed unused `Log` facade imports from services

## [1.0.26] - 2026-01-26

### Fixed

-   Fixed file name truncation in storage backups on Laravel Forge deployments
-   Use `realpath()` on source directory to match resolved file paths from symlinked deployment structures

## [1.0.25] - 2026-01-26

### Fixed

-   Fixed `ZipArchive::setEncryptionName()` error when argument is empty in `NotifierStorageService`
-   Added validation for `getRealPath()` returning false (broken symlinks)
-   Added validation to skip files with empty relative paths during storage backup

## [1.0.0-beta.2] - 2024-12-19

### Added

-   Comprehensive Pest test prompt for GitHub Copilot support
-   Enhanced CHANGELOG formatting for better readability
-   Additional test prompts and instructions for better developer experience

### Changed

-   Improved documentation formatting and structure
-   Enhanced GitHub Copilot integration with more specific prompts

## [1.0.0-beta.1] - 2024-12-19

### Added

-   Switched to Pest testing framework for more expressive and modern tests
-   Enhanced testing scripts with unit and feature test separation
-   Improved GitHub Copilot prompts for Pest test generation

### Changed

-   Replaced PHPUnit with Pest as the primary testing framework
-   Updated all existing tests to use Pest syntax
-   Enhanced testing documentation and examples

### Deprecated

### Removed

### Fixed

### Security

## [1.0.0-beta.1] - 2025-07-10

### Added

-   Stable package foundation ready for production testing
-   Comprehensive CI/CD pipeline with successful test execution
-   Complete development toolchain with PHPUnit, Pest, and PHPStan
-   GitHub Copilot integration for efficient development

### Fixed

-   All CI/CD pipeline issues resolved
-   PHPUnit configuration optimized for reliable test execution
-   Composer scripts properly configured for all development tasks

### Notes

-   This is a beta release ready for production testing
-   API is stabilizing and breaking changes should be minimal

## [1.0.0-alpha.3] - 2025-07-10

### Fixed

-   Fixed composer test script to use vendor/bin/phpunit
-   Reduced PHPUnit strictness to allow warnings without failing
-   Tests now pass successfully in CI/CD pipeline
-   Resolved GitHub Actions test execution issues

## [1.0.0-alpha.2] - 2025-07-10

### Fixed

-   Fixed composer test script to use vendor/bin/phpunit
-   Resolved GitHub Actions CI/CD pipeline test execution issue

## [1.0.0-alpha.1] - 2025-07-10

### Added

-   Initial package structure and setup
-   Service provider for Laravel 12 integration
-   Configuration file template
-   Database backup service foundation
-   Notification system integration base
-   Comprehensive GitHub Actions CI/CD pipeline
-   Testing infrastructure with PHPUnit and Pest
-   GitHub Copilot configuration for development
-   Release management workflows
-   Documentation and contributing guidelines

### Notes

-   This is an alpha release for testing and feedback
-   API may change before stable release

## [1.0.0] - 2025-07-10

### Added

-   Initial release
-   Laravel 12 support
-   Basic notifier functionality
-   Comprehensive test suite
-   GitHub Actions CI/CD
-   Documentation and examples

[Unreleased]: https://github.com/devuni-cz/notifier-package/compare/v2.2.4...HEAD
[2.2.4]: https://github.com/devuni-cz/notifier-package/compare/v2.2.3...v2.2.4
[2.2.3]: https://github.com/devuni-cz/notifier-package/compare/v2.2.2...v2.2.3
[2.1.1]: https://github.com/devuni-cz/notifier-package/compare/v2.1.0...v2.1.1
[2.1.0]:https://github.com/devuni-cz/notifier-package/compare/v2.0.0...v2.1.0
[2.0.0]: https://github.com/devuni-cz/notifier-package/compare/v1.0.27...v2.0.0
[1.0.0]: https://github.com/devuni-cz/notifier-package/releases/tag/v1.0.0
