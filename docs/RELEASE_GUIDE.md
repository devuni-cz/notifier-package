# Release Management Guide

This guide explains how to manage versions and releases for the Devuni Notifier Package.

## Version Strategy

We follow [Semantic Versioning (SemVer)](https://semver.org/):

-   **PATCH** (1.0.1): Bug fixes and small improvements
-   **MINOR** (1.1.0): New features that are backwards compatible
-   **MAJOR** (2.0.0): Breaking changes

## Release Process

### Option 1: Automated Version Bump (Recommended)

1. Go to your GitHub repository
2. Navigate to **Actions** tab
3. Select **Version Bump** workflow
4. Click **Run workflow**
5. Choose version type (patch/minor/major)
6. Click **Run workflow**

This will:

-   Bump the version automatically
-   Update CHANGELOG.md
-   Create and push a git tag
-   Trigger the release workflow

### Option 2: Manual Release

1. **Update CHANGELOG.md**:

    ```markdown
    ## [1.2.3] - 2025-07-10

    ### Added

    -   New feature description

    ### Fixed

    -   Bug fix description
    ```

2. **Commit changes**:

    ```bash
    git add CHANGELOG.md
    git commit -m "Prepare release v1.2.3"
    git push origin main
    ```

3. **Create and push tag**:

    ```bash
    git tag v1.2.3
    git push origin v1.2.3
    ```

4. **GitHub will automatically**:
    - Run tests
    - Create GitHub release
    - Update Packagist

## Pre-release Checklist

Before creating a release:

-   [ ] All tests pass (`composer test`)
-   [ ] Code analysis passes (`composer analyse`)
-   [ ] Code is formatted (`composer format`)
-   [ ] CHANGELOG.md is updated
-   [ ] Documentation is current
-   [ ] Version number follows SemVer
-   [ ] Breaking changes are documented

## Packagist Integration

### Initial Setup

1. **Register on Packagist**:

    - Go to [packagist.org](https://packagist.org)
    - Submit your package: `https://github.com/devuni-cz/notifier-package`

2. **Set up Auto-Update**:
    - In GitHub: Settings → Webhooks
    - Add webhook: `https://packagist.org/api/github`
    - Events: Just the push event

### Package Installation

Users can install your package with:

```bash
composer require devuni/notifier-package
```

## Release Types

### Patch Release (1.0.1)

For bug fixes and small improvements:

```bash
# Automated
GitHub Actions → Version Bump → patch

# Manual
git tag v1.0.1
git push origin v1.0.1
```

### Minor Release (1.1.0)

For new backwards-compatible features:

```bash
# Automated
GitHub Actions → Version Bump → minor

# Manual
git tag v1.1.0
git push origin v1.1.0
```

### Major Release (2.0.0)

For breaking changes:

1. **Document breaking changes** in CHANGELOG.md
2. **Update migration guide** in documentation
3. **Create release**:

    ```bash
    # Automated
    GitHub Actions → Version Bump → major

    # Manual
    git tag v2.0.0
    git push origin v2.0.0
    ```

## Laravel Version Support

| Package Version | Laravel Version | PHP Version |
| --------------- | --------------- | ----------- |
| 1.x             | ^12.0           | ^8.4        |

## Release Notes Template

Use this template for GitHub releases:

````markdown
## What's Changed

### Added

-   New feature descriptions

### Changed

-   Changed feature descriptions

### Fixed

-   Bug fix descriptions

### Breaking Changes (for major releases)

-   Breaking change descriptions
-   Migration instructions

## Installation

```bash
composer require devuni/notifier-package:^1.2.3
```
````

## Upgrade Guide

[Link to upgrade guide if needed]

**Full Changelog**: https://github.com/devuni-cz/notifier-package/compare/v1.2.2...v1.2.3

````

## Troubleshooting

### Release Failed

1. Check GitHub Actions logs
2. Ensure all tests pass
3. Verify tag format (v1.2.3)
4. Check permissions

### Packagist Not Updating

1. Check webhook configuration
2. Manually update on Packagist
3. Verify composer.json syntax

### Version Conflicts

1. Follow SemVer strictly
2. Use `^` for compatible versions
3. Document breaking changes clearly

## Commands Quick Reference

```bash
# Run all checks before release
composer test && composer analyse && composer format

# Create manual tag
git tag v1.2.3 && git push origin v1.2.3

# View current tags
git tag -l

# Delete wrong tag (local and remote)
git tag -d v1.2.3
git push origin :refs/tags/v1.2.3
````
