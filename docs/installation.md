# Installation guide

## Requirements

- PHP 8.3 or later.
- Yii2 `2.0.54` or later, or the Yii2 `22.x` development line.
- A frontend package manager supported by [`php-forge/foxy`](https://github.com/php-forge/foxy) (npm, pnpm, Yarn,
  Bun, or Deno) to install the `bootstrap` npm package.
- Browsers supported by Bootstrap 6: Chrome and Edge 130, Firefox 132, Safari 18 (macOS, iOS, and iPadOS) or later.

> [!IMPORTANT]
> The extension pins Bootstrap `6.0.0-alpha.1`, the only Bootstrap 6 release published so far. Bootstrap announced
> more alphas before beta, so class names, plugin options, and markup may still change. Widgets follow the alpha.1
> documentation at <https://getbootstrap.com/docs/6.0/>.

## Install the extension

Authorize Foxy in the application, so Composer installs the frontend packages declared by the extension:

```bash
composer config allow-plugins.php-forge/foxy true
composer require php-forge/foxy:^0.4 yii2-extensions/bootstrap6:^0.1
```

By default, Foxy merges `bootstrap@6.0.0-alpha.1` into the Composer project's `package.json` and runs the selected
manager, so the files end up in that project's `node_modules` directory. Select the manager explicitly for
reproducible installs. The `native` manager resolves the packages against the npm registry and extracts them with
PHP alone, so the application needs no Node.js tooling:

```json
{
  "config": {
    "foxy": {
      "manager": "native"
    }
  }
}
```

`npm`, `pnpm`, `yarn`, `bun`, and `deno` are accepted as well. The native manager installs a flat `node_modules` with
one version per package and runs no lifecycle scripts, which is all Bootstrap 6 needs: its bundle inlines Floating UI
and Vanilla Calendar Pro, so no other npm package is needed for menus, tooltips, popovers, comboboxes, or the
datepicker.

## Point the `@npm` alias to the install directory

Yii2 `22.x` defaults `@npm` to `@app/node_modules`; Yii2 `2.0.x` defaults it to `@vendor/npm`.
Point the alias to the directory where Foxy actually installed the frontend packages. Use `@app/node_modules`
only when the application base path is the Composer project root and Foxy uses its default directory.
For an advanced template or a shared Composer root, configure each application with the shared directory's
absolute path (replace the example path below):

```php
// config/web.php
return [
    'aliases' => [
        '@npm' => '/absolute/path/to/composer-project/node_modules',
    ],
];
```

Alternatively, with Yii2 `2.0.x` and the default Composer vendor directory, configure the `native` manager to
install into `vendor/npm`:

```json
{
  "config": {
    "foxy": {
      "manager": "native",
      "native-install-dir": "vendor/npm"
    }
  }
}
```

The asset bundles publish their files from that alias.

## Register the bootstrap

The package declares `yii2\extensions\bootstrap6\Bootstrap` in its Composer metadata, so applications that load
`@vendor/yiisoft/extensions.php` register the `yii.bootstrap6` message source automatically. Applications that disable
extension loading register it explicitly:

```php
// config/web.php
return [
    'bootstrap' => [\yii2\extensions\bootstrap6\Bootstrap::class],
];
```

The bootstrap never overrides a `yii.bootstrap6` translation source configured by the application. The widget labels
(`Close`, `Next`, `Previous`, `Toggle navigation`, and others) ship translated into 52 languages.

## Load the assets

Every widget registers the bundle it needs, so most applications do not register anything by hand. Register the
bundles in the layout to load Bootstrap on pages without widgets:

```php
use yii2\extensions\bootstrap6\asset\BootstrapPluginAsset;

// views/layouts/main.php
BootstrapPluginAsset::register($this);
```

| Bundle                 | Publishes                   | Files                                              |
| ---------------------- | --------------------------- | -------------------------------------------------- |
| `BootstrapAsset`       | `@npm/bootstrap/dist/css`   | `bootstrap.css` or `bootstrap.min.css`             |
| `BootstrapPluginAsset` | `@npm/bootstrap/dist/js`    | `bootstrap.bundle.js` or `bootstrap.bundle.min.js` |
| `BootstrapIconAsset`   | `@npm/bootstrap-icons/font` | `bootstrap-icons.css` and the `fonts/` web fonts   |

All bundles live under `yii2\extensions\bootstrap6\asset`. `BootstrapPluginAsset` depends on `BootstrapAsset`. The
minified files are served outside debug mode (`YII_DEBUG` set to `false`), together with their source maps.

### The JavaScript bundle is an ES module

Bootstrap 6 ships `bootstrap.bundle.js` as an ES module only, so `BootstrapPluginAsset` always registers it with
`type="module"`:

```html
<link href="/assets/1a2b3c4d/bootstrap.css" rel="stylesheet">
<script type="module" src="/assets/5e6f7a8b/bootstrap.bundle.js"></script>
```

The module defines no `window.bootstrap` global. Widgets initialize their plugins with a dynamic `import()` of the
same module URL, so the browser evaluates the bundle once:

```js
import("/assets/5e6f7a8b/bootstrap.bundle.js").then((bootstrap) => {
  bootstrap.Toast.getOrCreateInstance(document.getElementById("w0"), {});
});
```

Scripts written against the Bootstrap 5 global keep working when the bundle exposes it:

```php
// config/web.php
return [
    'components' => [
        'assetManager' => [
            'bundles' => [
                \yii2\extensions\bootstrap6\asset\BootstrapPluginAsset::class => [
                    'exposeGlobal' => true,
                ],
            ],
        ],
    ],
];
```

With `exposeGlobal` the bundle adds `import("...").then((bootstrap) => { window.bootstrap = bootstrap; });` at the end
of the body. The global is assigned asynchronously, so code that reads it must run after that promise resolves, for
example in a `load` event listener.

## Load the assets from a CDN

Set `cdn` on the bundles to link the files from jsDelivr instead of publishing them. The URL is pinned to the version
installed in `node_modules`, so both delivery modes always serve the same release:

```php
// config/web.php
return [
    'components' => [
        'assetManager' => [
            'bundles' => [
                \yii2\extensions\bootstrap6\asset\BootstrapAsset::class => ['cdn' => true],
                \yii2\extensions\bootstrap6\asset\BootstrapPluginAsset::class => ['cdn' => true],
            ],
        ],
    ],
];
```

Outside debug mode this renders:

```html
<link href="https://cdn.jsdelivr.net/npm/bootstrap@6.0.0-alpha.1/dist/css/bootstrap.min.css" rel="stylesheet">
<script type="module" src="https://cdn.jsdelivr.net/npm/bootstrap@6.0.0-alpha.1/dist/js/bootstrap.bundle.min.js"></script>
```

`cdnUrl` changes the CDN base URL (files resolve as `{cdnUrl}/{package}@{version}/{directory}/{file}`), and `minified`
forces or disables the `.min` files regardless of `YII_DEBUG`. The CDN mode still reads the version from
`node_modules/<package>/package.json`, so the npm packages must be installed.

`registerWith()` selects the mode for a bundle and its npm-backed dependencies from a view, unless the asset manager
already configures them:

```php
use yii2\extensions\bootstrap6\asset\BootstrapPluginAsset;

BootstrapPluginAsset::registerWith($this, cdn: true);
```

## Optional Bootstrap Icons

`BootstrapIconAsset` publishes the [Bootstrap Icons](https://icons.getbootstrap.com/) web font. The extension does not
require the package, so add it to the application `package.json` and reinstall:

```json
{
  "dependencies": {
    "bootstrap-icons": "^1.13.2"
  }
}
```

```php
use yii2\extensions\bootstrap6\asset\BootstrapIconAsset;

BootstrapIconAsset::register($this);
```

## Next steps

- ⚙️ [Configuration Reference](configuration.md)
- 💡 [Usage Examples](examples.md)
- 🔄 [Migration Guide](migration.md)
- 🧪 [Testing Guide](testing.md)
