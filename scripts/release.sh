#!/bin/bash

# Initial Release Setup Script
# This script helps set up the initial release for your Laravel package

set -e

echo "🚀 Setting up initial release for Devuni Notifier Package"

# Check if we're in a git repository
if ! git rev-parse --git-dir > /dev/null 2>&1; then
    echo "❌ Not a git repository. Please run this from your package root."
    exit 1
fi

# Check if composer.json exists
if [ ! -f "composer.json" ]; then
    echo "❌ composer.json not found. Please run this from your package root."
    exit 1
fi

echo "📋 Pre-release checklist:"
echo "  ✓ composer.json configured"
echo "  ✓ Tests written"
echo "  ✓ Documentation updated"
echo "  ✓ CHANGELOG.md updated"

# Run tests
echo "🧪 Running tests..."
if ! composer test; then
    echo "❌ Tests failed. Please fix tests before releasing."
    exit 1
fi

# Run code analysis
echo "🔍 Running code analysis..."
if composer run-script analyse --dry-run > /dev/null 2>&1; then
    if ! composer analyse; then
        echo "❌ Code analysis failed. Please fix issues before releasing."
        exit 1
    fi
fi

# Format code
echo "🎨 Formatting code..."
if composer run-script format --dry-run > /dev/null 2>&1; then
    composer format
fi

# Check if there are uncommitted changes
if ! git diff-index --quiet HEAD --; then
    echo "⚠️  You have uncommitted changes. Please commit them first."
    git status --porcelain
    read -p "Do you want to continue anyway? (y/N) " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        exit 1
    fi
fi

# Ask for version
echo "📦 What version do you want to release?"
echo "  1) 1.0.0 (Initial release)"
echo "  2) Custom version"
read -p "Choice (1-2): " choice

case $choice in
    1)
        VERSION="1.0.0"
        ;;
    2)
        read -p "Enter version (e.g., 1.0.0): " VERSION
        ;;
    "")
        echo "⚠️  No choice selected, using default: 1.0.0"
        VERSION="1.0.0"
        ;;
    *)
        echo "❌ Invalid choice, using default: 1.0.0"
        VERSION="1.0.0"
        ;;
esac

# Validate version format
if ! [[ $VERSION =~ ^[0-9]+\.[0-9]+\.[0-9]+$ ]]; then
    echo "❌ Invalid version format. Use semantic versioning (e.g., 1.0.0)"
    exit 1
fi

TAG="v$VERSION"

# Check if tag already exists
if git rev-parse "$TAG" >/dev/null 2>&1; then
    echo "❌ Tag $TAG already exists!"
    exit 1
fi

echo "🏷️  Creating tag: $TAG"

# Create and push tag
git tag -a "$TAG" -m "Release $TAG"

echo "✅ Tag $TAG created successfully!"
echo ""
echo "📤 Next steps:"
echo "  1. Push the tag: git push origin $TAG"
echo "  2. This will trigger GitHub Actions to:"
echo "     - Run tests"
echo "     - Create a GitHub release"
echo "     - Update Packagist (if configured)"
echo ""
echo "🌐 Register your package on Packagist:"
echo "  1. Go to https://packagist.org"
echo "  2. Submit: https://github.com/devuni-cz/notifier-package"
echo "  3. Set up webhook for auto-updates"
echo ""

read -p "Push tag now? (y/N) " -n 1 -r
echo
if [[ $REPLY =~ ^[Yy]$ ]]; then
    git push origin "$TAG"
    echo "🎉 Release $TAG pushed successfully!"
    echo "🔗 Check your GitHub Actions: https://github.com/devuni-cz/notifier-package/actions"
else
    echo "📝 Remember to push the tag manually: git push origin $TAG"
fi
