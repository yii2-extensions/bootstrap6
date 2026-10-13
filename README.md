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
> Bootstrap 6 is an alpha. The extension pins `6.0.0-alpha.1`, the only Bootstrap 6 release so far, and its API may
> change before beta. Bootstrap 6 supports Chrome and Edge 130, Firefox 132, and Safari 18 or later.

## Features

<picture>
    <source media="(min-width: 768px)" srcset="./docs/svgs/features.svg">
    <img src="./docs/svgs/features-mobile.svg" alt="Feature Overview" style="width: 100%;">
</picture>

## Quick start

### Installation

Requires PHP 8.3 or newer and Yii2 `2.0.54` or `22.x`.

```bash
composer config allow-plugins.php-forge/foxy true
composer require php-forge/foxy:^0.4 yii2-extensions/bootstrap6:^0.1
```

Yii2 `2.0.x` applications must point the `@npm` alias to the Foxy install directory, or install into `vendor/npm`
with the `native` manager; see the [installation guide](docs/installation.md).

### Basic usage

```php
use yii2\extensions\bootstrap6\Alert;
use yii2\extensions\bootstrap6\enum\Theme;

echo Alert::widget(['body' => 'Profile saved.', 'theme' => Theme::SUCCESS]);
```

```php
use yii2\extensions\bootstrap6\ActiveForm;

$form = ActiveForm::begin(['layout' => ActiveForm::LAYOUT_FLOATING, 'nativeValidation' => true]);

echo $form->field($model, 'email')->textInput(['type' => 'email']);
echo $form->field($model, 'notify')->checkbox(['switch' => true]);

ActiveForm::end();
```

```php
use yii2\extensions\bootstrap6\Dialog;

Dialog::begin(['title' => 'Delete post', 'toggleButton' => ['label' => 'Delete']]);

echo '<p>This action cannot be undone.</p>';

Dialog::end();
```

The widgets register the stylesheet and the `type="module"` bundle on demand. Plugins initialize through `data-bs-*`
attributes, or through a dynamic `import()` of the bundle when `clientOptions` are given.

## Documentation

For detailed configuration options and advanced usage.

- 📚 [Installation Guide](docs/installation.md)
- ⚙️ [Configuration Reference](docs/configuration.md)
- 💡 [Usage Examples](docs/examples.md)
- 🔄 [Migration Guide](docs/migration.md)
- 🧪 [Testing Guide](docs/testing.md)

## Package information

[![PHP](https://img.shields.io/badge/%3E%3D8.3-777BB4.svg?style=for-the-badge&logo=php&logoColor=white)](https://www.php.net/releases/8.3/en.php)
[![Yii 2.0.x](https://img.shields.io/badge/2.0.54+-0073AA.svg?style=for-the-badge&logo=yii&logoColor=white)](https://github.com/yiisoft/yii2/tree/master)
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
