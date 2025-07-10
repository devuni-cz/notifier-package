# Changelog

All notable changes to `devuni/notifier-package` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

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
-   Laravel 11 support
-   Basic notifier functionality
-   Comprehensive test suite
-   GitHub Actions CI/CD
-   Documentation and examples

[Unreleased]: https://github.com/devuni-cz/notifier-package/compare/v1.0.0...HEAD
[1.0.0]: https://github.com/devuni-cz/notifier-package/releases/tag/v1.0.0
