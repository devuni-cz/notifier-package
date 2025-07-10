# Contributing to Devuni Notifier Package

Thank you for considering contributing to the Devuni Notifier Package! We welcome contributions from everyone.

## Development Process

### 1. Fork and Clone

```bash
git clone https://github.com/YOUR_USERNAME/notifier-package.git
cd notifier-package
```

### 2. Install Dependencies

```bash
composer install
```

### 3. Create a Branch

```bash
git checkout -b feature/your-feature-name
# or
git checkout -b fix/your-fix-name
```

### 4. Make Changes

-   Follow the coding standards defined in `.github/instructions/`
-   Write tests for new functionality
-   Update documentation as needed

### 5. Run Tests

```bash
composer test
composer analyse
composer format
```

### 6. Commit Changes

Use conventional commit messages:

```bash
git commit -m "feat: add new notification channel"
git commit -m "fix: resolve database connection issue"
git commit -m "docs: update installation instructions"
```

### 7. Push and Create PR

```bash
git push origin feature/your-feature-name
```

Then create a Pull Request on GitHub.

## Versioning Strategy

We use [Semantic Versioning](https://semver.org/):

-   **MAJOR** version when you make incompatible API changes
-   **MINOR** version when you add functionality in a backwards compatible manner
-   **PATCH** version when you make backwards compatible bug fixes

### Creating a Release

1. **Automatic Version Bump**: Use the GitHub Actions workflow

    - Go to Actions → Version Bump
    - Select the version type (patch/minor/major)
    - Run the workflow

2. **Manual Release**:

    ```bash
    # Create and push a tag
    git tag v1.2.3
    git push origin v1.2.3
    ```

3. **The release workflow will automatically**:
    - Run tests
    - Create a GitHub release
    - Update Packagist (if configured)

## Release Checklist

Before creating a release:

-   [ ] All tests pass
-   [ ] CHANGELOG.md is updated
-   [ ] Documentation is up to date
-   [ ] Version bump is appropriate (patch/minor/major)
-   [ ] Breaking changes are documented

## Coding Standards

-   Follow PSR-12 coding standards
-   Use type hints for all method parameters and return types
-   Write comprehensive PHPDoc comments
-   Include tests for all new functionality
-   Use Laravel conventions and best practices

## Testing

We use PHPUnit for testing. Tests should:

-   Cover both success and failure scenarios
-   Mock external dependencies
-   Use descriptive test method names
-   Follow the AAA pattern (Arrange, Act, Assert)

```bash
# Run tests
composer test

# Run tests with coverage
composer test-coverage

# Run static analysis
composer analyse

# Format code
composer format
```

## Documentation

-   Keep README.md up to date
-   Document all public methods with PHPDoc
-   Provide usage examples
-   Update CHANGELOG.md for each release

## Getting Help

-   Check existing issues and discussions
-   Create an issue for bugs or feature requests
-   Join our community discussions

## Code of Conduct

Please note that this project is released with a Contributor Code of Conduct. By participating in this project you agree to abide by its terms.

Thank you for contributing! 🎉
