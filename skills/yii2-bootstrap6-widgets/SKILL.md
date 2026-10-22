---
name: yii2-bootstrap6-widgets
description: Use when implementing or migrating Yii2 views, forms, or assets with yii2-extensions/bootstrap6.
metadata:
  guide: https://yii2-framework.github.io/docs/extensions/bootstrap6/
license: BSD-3-Clause
---

# Bootstrap 6 widgets for Yii2

Use the package's widgets and HTML helper for Bootstrap 6 output. The namespace is `yii2\extensions\bootstrap6`; assets,
enums, and immutable items live in its `asset`, `enum`, and `item` subnamespaces. This package targets Bootstrap
`6.0.0-alpha.1`, with PHP 8.3 or later and Yii2 `2.0.55` or `22.x`.

## Find only the documentation needed

In a root package checkout, use `composer show --self --format=json` for metadata. For installed dependencies, resolve
the real path and read the configured vendor directory's `composer/installed.php` (`versions[<package>]`):
`install_path`, `pretty_version` and Composer's selected `reference`. Do not blindly prefer `source.reference` over
`dist.reference`. For any live package checkout, including a symlinked path install, inspect its current `HEAD` and
files even when clean; report metadata mismatches and let the actual files govern. Uncommitted changes have no matching
SHA. Otherwise use the selected reference or a verified matching tag only after establishing its source provenance.
Compare `composer.lock`; it may differ from the inspected installation. Identify a lock-only state when no package files
are installed, and resolve dependencies separately. Use hosted pages, catalogs or snapshots only when their source
revision matches.

- If the workspace supplies `.eval-docs/INDEX.md` or another local documentation index, read it first. Respect offline
  restrictions and check its revision.
- In a package checkout, use its `docs/` guides and `src/` PHPDoc. Distribution archives may omit `docs/`.
- A distributed skill may contain guide snapshots in `references/`. Otherwise use matching published pages below or
  `docs/` at the resolved reference in <https://github.com/yii2-extensions/bootstrap6>. Do not assume a copied skill's
  parent contains the package. If matching guides are unavailable or offline, inspect the local source/PHPDoc and report
  the gap.

| Task                                                         | Guide page under `https://yii2-framework.github.io/docs/` |
| ------------------------------------------------------------ | --------------------------------------------------------- |
| Install packages, configure `@npm`, deliver local/CDN assets | `extensions/bootstrap6/installation/index.md`             |
| Combine widget options, items, icons, forms, or plugins      | `extensions/bootstrap6/configuration/index.md`            |
| Select a widget or adapt a complete view recipe              | `extensions/bootstrap6/examples/index.md`                 |
| Replace Bootstrap 5 widgets, options, selectors, or scripts  | `extensions/bootstrap6/migration/index.md`                |
| Confirm a specific property's type/default or a signature    | `extensions/bootstrap6/reference/<class>/index.md`        |

Read relevant sections and the specific class reference, rather than the entire gallery or API. For example, a Dialog
task needs configuration's `Dialog` section and `reference/dialog/index.md`; an automatically shown Toast needs the
`Toast` and asset sections, with migration guidance if the existing view uses Bootstrap 5.

## Apply the package's constraints

- Widgets use Yii configuration arrays and public properties. Widget `widget()` returns HTML; attribute methods require
  an instance and mutate it. Item `with*()` methods return new immutable objects, so keep their results.
- Prefer enum cases for widget choices. Valid string values also work; do not infer Bootstrap 6 values or class names
  from Bootstrap 5.
- Prefer Foxy `^0.4` with the application's root `config.foxy.manager: native` for published assets without Node.js; it
  needs PHP `ext-zlib`. The application selects its manager and maintains its own `foxy.lock`.
- Point `@npm` to Foxy's actual install directory; application configuration can override framework defaults. Common
  Yii2 `2.0.x` templates use `@vendor/npm-asset`, while Yii2 `22.x` defaults to `@app/node_modules`. If Foxy installs
  into `vendor/npm`, explicitly set `@npm` to `@vendor/npm`. Follow the installation guide for root/base-path
  differences, dedicated native directories, and optional build managers. Extension bootstrap registration supplies
  `yii.bootstrap6` translations.
- `BootstrapPluginAsset` delivers an ES module, without `window.bootstrap` by default. Register the bundle, retain the
  returned instance, and call `$bundle->importJs($view, $code)` for custom plugin code. `importJs()` and `moduleUrl()`
  are instance methods; do not call them statically or add a classic script tag.
- A Toast plugin instance stays hidden until `show()` is called. For automatic display, use the bundle helper to call
  `bootstrap.Toast.getOrCreateInstance(element).show()`.
- Menu plugin settings belong on `ButtonMenu` or its toggle, rather than a standalone `Menu` panel. A Popover's captured
  `begin()`/`end()` content is its trigger; its popup body is the `content` option.
- Call matching `begin()` and `end()` as statements in views/layouts: both return widget instances, and `end()` echoes
  the rendered HTML. Titles and labels are encoded by default; body, footer, item content, and captured content are
  HTML. Encode user-controlled text in those slots with `yii2\extensions\bootstrap6\Html::encode()`.
- Use this package's `ActiveForm` for Bootstrap 6 fields. Browser validation styling does not replace server validation.
  A switch uses `checkbox(['switch' => true])`; floating forms have different label order from v5.

For migration, start with the migration guide rather than namespace replacement alone. `Modal`, `Offcanvas`, `Dropdown`,
and `ButtonDropdown` become `Dialog`, `Drawer`, `Menu`, and `ButtonMenu`; plugin names, event suffixes, responsive
classes, and other markup change too. `Toast::dateTime` uses `null` to omit the time, not v5 `false`.

## Verify the requested behavior

Lint changed PHP and use the application's rendering/checking tools when available. Check the actual output and
interaction: dialog backdrop/scrolling, toast display and dismissal, menu triggers, and form feedback as relevant. For
unencoded slots, verify that user text containing markup renders as text. Report the files changed, the pages consulted,
and checks that could not run. A read of `SKILL.md` alone is not evidence of native skill activation.
