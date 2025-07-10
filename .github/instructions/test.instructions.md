---
applyTo: "**"
---

# Laravel 12 Package Development Standards

## Code Standards

-   Follow PSR-12 coding standards strictly
-   Use Laravel 12 conventions and best practices
-   Implement proper dependency injection throughout the package
-   Use type hints for all method parameters and return types
-   Write descriptive method and variable names following Laravel conventions
-   Use strict types declaration in all PHP files

## Package Structure Guidelines

-   Place all source code in `src/` directory with proper namespace `Devuni\Notifier`
-   Configuration files go in `config/` directory
-   Commands should extend `Illuminate\Console\Command` and be registered in service provider
-   Services should be properly bound in the service provider with interfaces when applicable
-   Use proper Laravel directory structure even within package

## Laravel 12 Specific Guidelines

-   Use Laravel's built-in validation rules and custom validation when needed
-   Implement comprehensive error handling with Laravel's exception handling
-   Use Laravel collections and eloquent relationships where appropriate
-   Follow Laravel's naming conventions for methods, classes, and database tables
-   Use dependency injection instead of facades in package source code
-   Leverage Laravel 12's new features like improved validation and enhanced collections

## Service Provider Best Practices

-   Register services in the `register()` method
-   Bootstrap services in the `boot()` method
-   Publish configuration files, migrations, and views properly
-   Register commands and event listeners correctly
-   Use proper binding for interfaces and concrete implementations

## Database and Migrations

-   Use Laravel migration conventions
-   Include proper foreign key constraints
-   Use descriptive migration and table names
-   Provide rollback functionality in all migrations

## Testing Requirements

-   Write comprehensive PHPUnit tests for all functionality
-   Use Laravel's testing helpers and assertions
-   Mock external dependencies properly
-   Test both success and failure scenarios
-   Include feature tests for commands and services
-   Maintain high test coverage (minimum 80%)

## Documentation Standards

-   Include comprehensive README.md with installation and usage instructions
-   Document all public methods with PHPDoc comments
-   Provide clear usage examples for all features
-   Include configuration options documentation
-   Document any package dependencies and requirements

## Error Handling

-   Use Laravel's exception handling patterns
-   Create custom exceptions for package-specific errors
-   Provide meaningful error messages
-   Log errors appropriately using Laravel's logging system

## Configuration

-   Use Laravel's config system for all package settings
-   Provide sensible defaults for all configuration options
-   Use environment variables where appropriate
-   Group related configuration options logically
