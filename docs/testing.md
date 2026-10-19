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
`composer install` runs [`php-forge/foxy`](https://github.com/php-forge/foxy) with the native manager, which installs
them from the root `package.json` using PHP and `ext-zlib`; Node.js is unnecessary for this step:

```bash
composer install
```

Keep the development `foxy.lock` when updating frontend dependencies. It is excluded from Composer distribution
archives because consuming applications resolve their own frontend requirements and maintain their own lockfiles.
Foxy also adds generated `@composer-asset/*` dependencies with local `file:` paths to the checkout's root
`package.json`. Keep those generated entries local: the distributed manifest must declare only registry packages,
so it can be consumed without paths into this checkout's `vendor` directory. Inspect `git diff -- package.json`
after Composer operations and remove generated entries before committing or packaging the library.

## Automated refactoring (Rector)

Run Rector to apply automated code refactoring.

```bash
composer rector
```

## Repository quality checks

Make the CLI tools used by the shared quality workflow available on `PATH`:
actionlint with its ShellCheck helper, codespell, EditorConfig Checker, markdownlint-cli2, Prettier and
yamllint. The command reads the repository's linter, formatter and ignore files. It skips ignored local dependency, runtime and private artifact trees that are absent from a clean CI checkout.

```bash
composer quality
```

## Workflow security checks

Make zizmor and Gitleaks available on `PATH`. Audit the current workflow files
with the same pedantic persona used by the shared security workflow:

```bash
composer zizmor
```

Run that audit and scan Git history with the repository's Gitleaks configuration:

```bash
composer security
```

Zizmor's online audits require GitHub API access. Report offline limitations separately; do not count a skipped audit as passed.

## Coding standards (ECS)

Check coding standards without modifying files:

```bash
composer ecs-check
```

`composer ecs` applies fixes. Review its changes before keeping them.

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

[Back to documentation](../README.md#documentation)
