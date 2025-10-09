# Contributing Guide

Thank you for your interest in contributing to the UploadThing PHP Client! This guide will help you get started with contributing to the project.

## Getting Started

### Prerequisites

- PHP 8.1 or higher
- Composer
- Git

### Setting Up the Development Environment

1. Fork the repository on GitHub
2. Clone your fork locally:
   ```bash
   git clone https://github.com/your-username/uploadthing-php.git
   cd uploadthing-php
   ```

3. Install dependencies:
   ```bash
   composer install
   ```

4. Run the development checks:
   ```bash
   ./bin/dev-check
   ```

## Development Workflow

### Branch Strategy

- `main`: Production-ready code
- `develop`: Integration branch for features
- `feature/*`: Feature branches
- `bugfix/*`: Bug fix branches
- `hotfix/*`: Hotfix branches

### Making Changes

1. Create a new branch from `develop`:
   ```bash
   git checkout develop
   git pull origin develop
   git checkout -b feature/your-feature-name
   ```

2. Make your changes following the coding standards
3. Write tests for your changes
4. Run the development checks:
   ```bash
   ./bin/dev-check
   ```

5. Commit your changes:
   ```bash
   git add .
   git commit -m "Add feature: your feature description"
   ```

6. Push your branch:
   ```bash
   git push origin feature/your-feature-name
   ```

7. Create a pull request on GitHub

## Coding Standards

### PHP Code Style

We use PHP-CS-Fixer with PSR-12 rules. Run the following to check and fix code style:

```bash
# Check code style
./vendor/bin/php-cs-fixer fix --dry-run --diff

# Fix code style
./vendor/bin/php-cs-fixer fix
```

### Static Analysis

We use PHPStan and Psalm for static analysis:

```bash
# Run PHPStan
./vendor/bin/phpstan analyse

# Run Psalm
./vendor/bin/psalm
```

### Type Declarations

- Use strict typing (`declare(strict_types=1);`)
- Add type declarations for all parameters and return types
- Use `readonly` properties where appropriate (PHP 8.2+)
- Use `final` classes where appropriate

### Documentation

- Add PHPDoc comments for all public methods
- Include parameter types, return types, and exceptions
- Use descriptive method and variable names
- Add examples in documentation when helpful

## Testing

### Running Tests

```bash
# Run all tests
./vendor/bin/phpunit

# Run tests with coverage
./vendor/bin/phpunit --coverage-html coverage

# Run specific test suite
./vendor/bin/phpunit tests/Unit
./vendor/bin/phpunit tests/Integration
```

### Writing Tests

- Write unit tests for individual classes and methods
- Write integration tests for API interactions
- Aim for high test coverage (90%+)
- Use descriptive test method names
- Test both success and failure scenarios

### Test Structure

```
tests/
├── Unit/           # Unit tests
├── Integration/     # Integration tests
└── Fixtures/       # Test fixtures and data
```

## Pull Request Process

### Before Submitting

1. Ensure all tests pass
2. Run static analysis tools
3. Check code style compliance
4. Update documentation if needed
5. Add tests for new functionality

### Pull Request Template

When creating a pull request, please include:

- Description of changes
- Related issues (if any)
- Testing instructions
- Breaking changes (if any)
- Documentation updates

### Review Process

1. Automated checks must pass
2. Code review by maintainers
3. Address feedback and make requested changes
4. Maintainers will merge when ready

## Release Process

### Versioning

We follow [Semantic Versioning](https://semver.org/):

- `MAJOR`: Breaking changes
- `MINOR`: New features (backward compatible)
- `PATCH`: Bug fixes (backward compatible)

### Release Steps

1. Update version in `composer.json`
2. Update `CHANGELOG.md`
3. Create git tag
4. Push tag to trigger release workflow
5. Update documentation if needed

## Issue Reporting

### Bug Reports

When reporting bugs, please include:

- PHP version
- Package version
- Steps to reproduce
- Expected behavior
- Actual behavior
- Error messages/logs

### Feature Requests

When requesting features, please include:

- Use case description
- Proposed API design
- Benefits and rationale
- Potential implementation approach

## Code of Conduct

Please read and follow our [Code of Conduct](CODE_OF_CONDUCT.md). We are committed to providing a welcoming and inclusive environment for all contributors.

## Getting Help

- Check existing issues and pull requests
- Join our community discussions
- Contact maintainers for questions

## Recognition

Contributors will be recognized in:

- `CHANGELOG.md`
- Release notes
- Contributor list (if applicable)

Thank you for contributing to the UploadThing PHP Client!
