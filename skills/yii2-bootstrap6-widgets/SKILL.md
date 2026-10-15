---
name: yii2-bootstrap6-widgets
description: Use when implementing or migrating Yii2 views, forms, or assets with yii2-extensions/bootstrap6.
metadata:
  guide: https://yii2-framework.github.io/docs/extensions/bootstrap6/
license: BSD-3-Clause
---

# Bootstrap 6 widgets for Yii2

Use the package's widgets and HTML helper for Bootstrap 6 output. The namespace is
`yii2\extensions\bootstrap6`; assets, enums, and immutable items live in its `asset`, `enum`, and `item`
subnamespaces. This package targets Bootstrap `6.0.0-alpha.1`, with PHP 8.3 or later and Yii2 `2.0.55` or `22.x`.
Confirm the installed package version before using a recipe from a different release.

## Find only the documentation needed

1. If the workspace supplies `.eval-docs/INDEX.md` or another local documentation index, read it first and use the listed local pages. Respect an evaluation's offline restriction.
2. In a package checkout, use its `docs/` guides and `src/` PHPDoc. In an application, locate the package through Composer before looking for those files; distribution archives may omit `docs/`.
3. A distributed skill may contain generated guide snapshots in `references/`; use them when present. Otherwise use the published Markdown pages below, or the repository guides at `https://github.com/yii2-extensions/bootstrap6/tree/main/docs`. Do not assume a copied skill's parent directories still contain the package.

| Task                                                         | Guide page under `https://yii2-framework.github.io/docs/` |
| ------------------------------------------------------------ | --------------------------------------------------------- |
| Install packages, configure `@npm`, deliver local/CDN assets | `extensions/bootstrap6/installation/index.md`             |
| Combine widget options, items, icons, forms, or plugins      | `extensions/bootstrap6/configuration/index.md`            |
| Select a widget or adapt a complete view recipe              | `extensions/bootstrap6/examples/index.md`                 |
| Replace Bootstrap 5 widgets, options, selectors, or scripts  | `extensions/bootstrap6/migration/index.md`                |
| Confirm a specific property's type/default or a signature    | `extensions/bootstrap6/reference/<class>/index.md`        |

Read relevant sections and the specific class reference, rather than the entire gallery or API. For example, a
Dialog task needs configuration's `Dialog` section and `reference/dialog/index.md`; an automatically shown Toast
needs the `Toast` and asset sections, with migration guidance if the existing view uses Bootstrap 5.

## Apply the package's constraints

- Widgets use Yii configuration arrays and public properties. Widget `widget()` returns HTML; attribute methods
  require an instance and mutate it. Item `with*()` methods return new immutable objects, so keep their results.
- Prefer enum cases for widget choices. Valid string values also work; do not infer Bootstrap 6 values or class
  names from Bootstrap 5.
- Prefer Foxy `^0.4` with the application's root `config.foxy.manager: native` for published assets without Node.js;
  it needs PHP `ext-zlib`. The application selects its manager and maintains its own `foxy.lock`.
- Point `@npm` to Foxy's actual install directory. Yii2 `2.0.x` defaults to `@vendor/npm`; Yii2 `22.x` defaults to
  `@app/node_modules`. Follow the installation guide for root/base-path differences, dedicated native directories,
  and optional build managers. Extension bootstrap registration supplies `yii.bootstrap6` translations.
- `BootstrapPluginAsset` delivers an ES module, without `window.bootstrap` by default. Register the bundle, retain
  the returned instance, and call `$bundle->importJs($view, $code)` for custom plugin code. `importJs()` and
  `moduleUrl()` are instance methods; do not call them statically or add a classic script tag.
- A Toast plugin instance stays hidden until `show()` is called. For automatic display, use the bundle helper to
  call `bootstrap.Toast.getOrCreateInstance(element).show()`.
- Menu plugin settings belong on `ButtonMenu` or its toggle, rather than a standalone `Menu` panel. A Popover's
  captured `begin()`/`end()` content is its trigger; its popup body is the `content` option.
- Call matching `begin()` and `end()` as statements in views/layouts: both return widget instances, and `end()` echoes
  the rendered HTML. Titles and labels are encoded by default; body, footer, item content, and captured content are
  HTML. Encode user-controlled text in those slots with `yii2\extensions\bootstrap6\Html::encode()`.
- Use this package's `ActiveForm` for Bootstrap 6 fields. Browser validation styling does not replace server
  validation. A switch uses `checkbox(['switch' => true])`; floating forms have different label order from v5.

For migration, start with the migration guide rather than namespace replacement alone. `Modal`, `Offcanvas`,
`Dropdown`, and `ButtonDropdown` become `Dialog`, `Drawer`, `Menu`, and `ButtonMenu`; plugin names, event suffixes,
responsive classes, and other markup change too. `Toast::dateTime` uses `null` to omit the time, not v5 `false`.

## Verify the requested behavior

Lint changed PHP and use the application's rendering/checking tools when available. Check the actual output and
interaction: dialog backdrop/scrolling, toast display and dismissal, menu triggers, and form feedback as relevant.
For unencoded slots, verify that user text containing markup renders as text. Report the files changed, the pages
consulted, and checks that could not run. A read of `SKILL.md` alone is not evidence of native skill activation.
