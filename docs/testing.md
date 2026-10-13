# Testing

This package provides a consistent set of [Composer](https://getcomposer.org/) scripts for local validation.

Tool references:

- [Composer Require Checker](https://github.com/maglnet/ComposerRequireChecker) for dependency definition checks.
- [Easy Coding Standard (ECS)](https://github.com/easy-coding-standard/easy-coding-standard) for coding standards.
- [Infection](https://infection.github.io/) for mutation testing.
- [PHPStan](https://phpstan.org/) for static analysis.
- [PHPUnit](https://phpunit.de/) for unit tests.
- [Rector](https://github.com/rectorphp/rector) for automated refactoring.

## Prerequisites

The asset tests publish the real `bootstrap` and `bootstrap-icons` packages from the package `node_modules` directory.
`composer install` runs [`php-forge/foxy`](https://github.com/php-forge/foxy), which installs them from the root
`package.json`, so a clean checkout only needs:

```bash
composer install
```

## Automated refactoring (Rector)

Run Rector to apply automated code refactoring.

```bash
composer rector
```

## Coding standards (ECS)

Run Easy Coding Standard (ECS) and apply fixes.

```bash
composer ecs
```

## Dependency definition check

Verify that runtime dependencies are correctly declared in `composer.json`.

```bash
composer check-dependencies
```

## Mutation testing (Infection)

Run mutation testing (minimum MSI and covered MSI of 100%).

```bash
composer mutation
```

Run mutation testing with static analysis enabled.

```bash
composer mutation-static
```

## Static analysis (PHPStan)

Run static analysis at level `max` with the strict rules.

```bash
composer static
```

## Unit tests (PHPUnit)

Run the full test suite.

```bash
composer tests
```

The test classes are grouped with `#[Group]` attributes: `asset`, `enum`, `form`, `html`, `i18n`, and `widget`.

## Passing extra arguments

Composer scripts support forwarding additional arguments using `--`.

Run PHPUnit with code coverage report generation.

```bash
composer tests -- --coverage-html code_coverage
```

Run a single test group.

```bash
composer tests -- --group form
```

Run PHPStan with a different memory limit.

```bash
composer static -- --memory-limit=512M
```

## Next steps

- 📚 [Installation Guide](installation.md)
- ⚙️ [Configuration Reference](configuration.md)
- 💡 [Usage Examples](examples.md)
- 🔄 [Migration Guide](migration.md)
