# Changelog

All notable changes to `devuni/notifier-package` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).


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

[Unreleased]: https://github.com/devuni-cz/notifier-package/compare/v1.0.0...HEAD
[1.0.0]: https://github.com/devuni-cz/notifier-package/releases/tag/v1.0.0
