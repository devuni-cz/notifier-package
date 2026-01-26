# Release Management Guide

This guide explains how to manage versions and releases for the Devuni Notifier Package.

## Version Strategy

We follow [Semantic Versioning (SemVer)](https://semver.org/):

-   **PATCH** (1.0.1): Bug fixes and small improvements
-   **MINOR** (1.1.0): New features that are backwards compatible
-   **MAJOR** (2.0.0): Breaking changes (API changes, removed features)

## Branch Strategy

-   **main**: Production-ready code, releases are tagged here
-   **staging**: Development branch for testing before release

## Release Process

### Standard Release Flow

1. **Develop on staging branch**
2. **Create Pull Request** from staging → main
3. **Merge PR** after review
4. **Create tag on main**:

    ```bash
    git checkout main
    git pull origin main
    git tag -a v1.2.3 -m "Release v1.2.3 - Description"
    git push origin v1.2.3
    ```

5. **GitHub Actions** will automatically:
    - Run tests
    - Create GitHub release
    - Update Packagist

### Quick Patch Release

For urgent fixes directly on main:

```bash
git checkout main
git pull origin main
# Make fix
git add -A && git commit -m "fix: description"
git push origin main
git tag -a v1.0.1 -m "Release v1.0.1 - Hotfix"
git push origin v1.0.1
```

## Pre-release Checklist

Before creating a release:

-   [ ] All tests pass (`composer test`)
-   [ ] Code analysis passes (`composer analyse`)
-   [ ] Code is formatted (`composer format`)
-   [ ] CHANGELOG.md is updated
-   [ ] Documentation is current
-   [ ] Version number follows SemVer
-   [ ] Breaking changes are documented (for major releases)

## CHANGELOG Format

```markdown
## [2.0.0] - 2026-01-26

### ⚠️ BREAKING CHANGES

-   Description of breaking change
-   Migration instructions

### Added

-   New feature description

### Changed

-   Changed behavior description

### Fixed

-   Bug fix description

### Removed

-   Removed feature description
```

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

```bash
composer require devuni/notifier-package
```

## Release Types

### Patch Release (1.0.x)

Bug fixes, no API changes:

```bash
git tag -a v1.0.1 -m "Release v1.0.1 - Bug fixes"
git push origin v1.0.1
```

### Minor Release (1.x.0)

New features, backwards compatible:

```bash
git tag -a v1.1.0 -m "Release v1.1.0 - New features"
git push origin v1.1.0
```

### Major Release (x.0.0)

Breaking changes:

1. **Document breaking changes** in CHANGELOG.md with migration guide
2. **Update README.md** with new API examples
3. **Create release**:

    ```bash
    git tag -a v2.0.0 -m "Release v2.0.0 - Breaking changes"
    git push origin v2.0.0
    ```

## Laravel Version Support

| Package Version | Laravel Version | PHP Version |
| --------------- | --------------- | ----------- |
| 2.x             | ^12.0           | ^8.4        |
| 1.x             | ^12.0           | ^8.4        |

## Troubleshooting

### Tag on Wrong Branch

```bash
# Delete wrong tag
git tag -d v1.2.3
git push origin :refs/tags/v1.2.3

# Create on correct branch
git checkout main
git tag -a v1.2.3 -m "Release v1.2.3"
git push origin v1.2.3
```

### Packagist Not Updating

1. Check webhook configuration in GitHub
2. Manually trigger update on Packagist
3. Verify composer.json syntax

### Release Workflow Failed

1. Check GitHub Actions logs
2. Ensure all tests pass locally
3. Verify tag format (must be `v1.2.3`)

## Commands Quick Reference

```bash
# Run all checks before release
composer test && composer analyse && composer format

# View current tags
git tag -l --sort=-v:refname | head -5

# Create annotated tag
git tag -a v1.2.3 -m "Release v1.2.3 - Description"

# Push tag
git push origin v1.2.3

# Delete tag (local + remote)
git tag -d v1.2.3 && git push origin :refs/tags/v1.2.3
```
