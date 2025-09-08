# GitHub Copilot Setup for Laravel 12 Package Development

This document explains the GitHub Copilot configuration for the Devuni\Notifier Laravel package.

## Directory Structure

```
.github/
├── chatmodes/
│   └── laravel-package.chatmode.md        # Laravel package development mode
├── instructions/
│   └── laravel-package.instructions.md    # Comprehensive coding standards
└── prompts/
    ├── general-component.prompt.md        # General package component creation
    ├── service.prompt.md                  # Service class creation
    ├── command.prompt.md                  # Artisan command creation
    ├── config.prompt.md                   # Configuration file creation
    ├── phpunit-test.prompt.md             # PHPUnit test creation
    ├── provider.prompt.md          # Service provider updates
    ├── exception.prompt.md         # Custom exception creation
    └── migration.prompt.md         # Database migration creation
```

## Instructions File (`laravel-package.instructions.md`)

Provides comprehensive guidelines for:

-   PSR-12 coding standards
-   Laravel 12 best practices
-   Dependency injection patterns
-   Testing requirements
-   Documentation standards
-   Error handling patterns

## Chat Mode (`laravel-package.chatmode.md`)

Optimized for Laravel package development with:

-   Laravel 12 compatibility focus
-   Service provider implementation
-   Command registration
-   Configuration publishing
-   PHPUnit testing

## Prompt Files

### General Prompts

-   `general-component.prompt.md` - General Laravel package component creation
-   `provider.prompt.md` - Service provider updates and registration

### Specific Component Prompts

-   `service.prompt.md` - Service class creation with DI
-   `command.prompt.md` - Artisan command creation
-   `config.prompt.md` - Configuration file structure
-   `phpunit-test.prompt.md` - PHPUnit test creation
-   `exception.prompt.md` - Custom exception classes
-   `migration.prompt.md` - Database migration creation

## How to Use

1. **General Development**: Use the chat mode for overall guidance
2. **Specific Components**: Reference specific prompt files when creating:

    - Services: Use `service.prompt.md`
    - Commands: Use `command.prompt.md`
    - Tests: Use `phpunit-test.prompt.md`
    - etc.

3. **Ask Copilot**: Use natural language referencing the component type:
    - "Create a new service for handling notifications"
    - "Generate a command for database backup"
    - "Write tests for the NotifierService class"

## Package Structure Compliance

The setup ensures all generated code follows:

-   `Devuni\Notifier` namespace convention
-   Laravel 12 best practices
-   Proper dependency injection
-   Comprehensive testing
-   PSR-12 coding standards

## Testing Setup

-   PHPUnit configuration included
-   Orchestra Testbench for Laravel package testing
-   GitHub Actions CI/CD pipeline
-   Code coverage reporting

## Next Steps

1. Run `composer install` to install dependencies
2. Use the prompt files to generate package components
3. Run tests with `composer test`
4. Follow the instructions for consistent code quality
