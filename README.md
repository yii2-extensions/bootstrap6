<!-- markdownlint-disable MD041 -->
<p align="center">
    <picture>
        <source media="(prefers-color-scheme: dark)" srcset="https://www.yiiframework.com/image/design/logo/yii3_full_for_dark.svg">
        <source media="(prefers-color-scheme: light)" srcset="https://www.yiiframework.com/image/design/logo/yii3_full_for_light.svg">
        <img src="https://www.yiiframework.com/image/design/logo/yii3_full_for_light.svg" alt="Yii Framework" width="80%">
    </picture>
    <h1 align="center">Bootstrap 6</h1>
    <br>
</p>
<!-- markdownlint-enable MD041 -->

<p align="center">
    <a href="https://github.com/yii2-extensions/bootstrap6/actions/workflows/build.yml" target="_blank">
        <img src="https://img.shields.io/github/actions/workflow/status/yii2-extensions/bootstrap6/build.yml?style=for-the-badge&label=PHPUnit&logo=github" alt="PHPUnit">
    </a>
    <a href="https://dashboard.stryker-mutator.io/reports/github.com/yii2-extensions/bootstrap6/main" target="_blank">
        <img src="https://img.shields.io/endpoint?style=for-the-badge&url=https%3A%2F%2Fbadge-api.stryker-mutator.io%2Fgithub.com%2Fyii2-extensions%2Fbootstrap6%2Fmain" alt="Mutation Testing">
    </a>
    <a href="https://github.com/yii2-extensions/bootstrap6/actions/workflows/static.yml" target="_blank">
        <img src="https://img.shields.io/github/actions/workflow/status/yii2-extensions/bootstrap6/static.yml?style=for-the-badge&label=PHPStan&logo=github" alt="PHPStan">
    </a>
    <a href="https://github.com/yii2-extensions/bootstrap6/actions/workflows/security.yml" target="_blank">
        <img src="https://img.shields.io/github/actions/workflow/status/yii2-extensions/bootstrap6/security.yml?style=for-the-badge&label=Security&logo=github" alt="Security">
    </a>
</p>

<p align="center">
    <strong>Bootstrap 6 widgets, forms, and asset bundles for Yii2</strong><br>
    <em>ESM-only Bootstrap bundle installed through Foxy, plugins loaded without a global</em>
</p>

> [!IMPORTANT]
> This package pins Bootstrap `6.0.0-alpha.1`. Its classes, markup, and plugin options may change before beta.
> Supported browsers start at Chrome/Edge 130, Firefox 132, and Safari 18.

## Features

<picture>
    <source media="(min-width: 768px)" srcset="./docs/svgs/features.svg">
    <img src="./docs/svgs/features-mobile.svg" alt="Feature Overview" style="width: 100%;">
</picture>

## Quick start

### Installation

Select the native manager in the application's root `composer.json`:

```json
{
  "config": {
    "foxy": {
      "manager": "native"
    }
  }
}
```

Then install the extension:

```bash
composer config allow-plugins.php-forge/foxy true
composer require php-forge/foxy:^0.4 yii2-extensions/bootstrap6:^0.1
```

Foxy's native manager installs the frontend assets with PHP and `ext-zlib`; Node.js is unnecessary. Configure it
in the application's root `composer.json` before requiring the extension. Its default install directory is
`node_modules`. Point `@npm` to that actual directory; use `@app/node_modules` only when the application base path
is the Composer project root. For Yii2 `2.0.x`, native installation into `vendor/npm` matches its default alias.
See [installation](docs/installation.md) for the complete configuration and optional package managers.

### Basic usage

Place this in a Yii view. The button opens a native dialog with a title, content, and a working close action:

```php
use yii2\extensions\bootstrap6\{Dialog, Html};
use yii2\extensions\bootstrap6\enum\DialogSize;

Dialog::begin(
    [
        'title' => 'Welcome to your next project',
        'size' => DialogSize::LG,
        'toggleButton' => ['label' => 'Preview welcome'],
        'footer' => Html::button(
            'Start exploring',
            [
                'class' => 'btn-solid theme-primary',
                'data' => ['bs-dismiss' => 'dialog'],
            ]
        ),
    ],
);

echo '<p class="fs-5">Build clear interfaces with <strong>Yii2 and Bootstrap 6</strong>.</p>';

Dialog::end();
```

Widgets load their CSS and JavaScript on demand. The JavaScript bundle uses `type="module"` and exposes no
`window.bootstrap` by default; use the registered bundle's `importJs()` helper for custom plugin code. Titles and
labels are encoded by default, while body and footer content require encoding when they contain user data.

## Documentation

Practical guides and complete examples, with API details generated from PHPDoc.

- 📚 [Installation Guide](docs/installation.md): packages, aliases, assets, and CDN delivery.
- ⚙️ [Configuration Guide](docs/configuration.md): practical choices for plugins, items, forms, and icons.
- 💡 [Usage Examples](docs/examples.md): widget gallery and complete view recipes.
- 🔄 [Migration from Bootstrap 5](docs/migration.md)
- 🧪 [Testing Guide](docs/testing.md)
- 📖 [Generated API Reference](https://yii2-framework.github.io/docs/extensions/bootstrap6/reference/)

## AI assistance

AI agents: read <https://yii2-framework.github.io/docs/llms.txt>, the index of the documentation site, and fetch the
Markdown version of a page by appending `index.md` to its URL, for example
<https://yii2-framework.github.io/docs/extensions/bootstrap6/installation/index.md>. For the Yii conventions this
package builds on, install the
[`yii2-framework` skill](https://github.com/yii2-extensions/skill/tree/main/skills/yii2-framework)
through the [shared Composer setup](https://yii2-framework.github.io/docs/project/ai-agents/#composer-installation).

- 🤖 [yii2-bootstrap6-widgets skill](docs/agents.md): installation for Claude Code, Codex, and OpenCode.

If the hosted sources are unavailable, use the repository guides and locally available skills.

## Package information

[![PHP](https://img.shields.io/badge/%3E%3D8.3-777BB4.svg?style=for-the-badge&logo=php&logoColor=white)](https://www.php.net/releases/8.3/en.php)
[![Yii 2.0.x](https://img.shields.io/badge/2.0.55+-0073AA.svg?style=for-the-badge&logo=yii&logoColor=white)](https://github.com/yiisoft/yii2/tree/master)
[![Yii 22.0.x](https://img.shields.io/badge/22.0.x-0073AA.svg?style=for-the-badge&logo=yii&logoColor=white)](https://github.com/yiisoft/yii2/tree/22.0)
[![Latest Stable Version](https://img.shields.io/packagist/v/yii2-extensions/bootstrap6.svg?style=for-the-badge&logo=packagist&logoColor=white&label=Stable)](https://packagist.org/packages/yii2-extensions/bootstrap6)
[![Total Downloads](https://img.shields.io/packagist/dt/yii2-extensions/bootstrap6.svg?style=for-the-badge&logo=composer&logoColor=white&label=Downloads)](https://packagist.org/packages/yii2-extensions/bootstrap6)

## Project status

[![Codecov](https://img.shields.io/codecov/c/github/yii2-extensions/bootstrap6.svg?style=for-the-badge&logo=codecov&logoColor=white&label=Coverage)](https://codecov.io/github/yii2-extensions/bootstrap6)
[![PHPStan Level Max](https://img.shields.io/badge/PHPStan-Level%20Max-4F5D95.svg?style=for-the-badge&logo=github&logoColor=white)](https://github.com/yii2-extensions/bootstrap6/actions/workflows/static.yml)
[![Quality](https://img.shields.io/github/actions/workflow/status/yii2-extensions/bootstrap6/quality.yml?style=for-the-badge&label=Quality&logo=github)](https://github.com/yii2-extensions/bootstrap6/actions/workflows/quality.yml)
[![StyleCI](https://img.shields.io/badge/StyleCI-Passed-44CC11.svg?style=for-the-badge&logo=github&logoColor=white)](https://github.styleci.io/)

## Our social networks

[![Follow on X](https://img.shields.io/badge/-Follow%20on%20X-1DA1F2.svg?style=for-the-badge&logo=x&logoColor=white&labelColor=000000)](https://x.com/Terabytesoftw)

## License

[![License](https://img.shields.io/badge/License-BSD--3--Clause-brightgreen.svg?style=for-the-badge&logo=opensourceinitiative&logoColor=white&labelColor=555555)](LICENSE)
