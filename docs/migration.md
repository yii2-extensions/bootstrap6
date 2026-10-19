# Migrating from yii2-bootstrap5

This guide maps `yiisoft/yii2-bootstrap5` to `yii2-extensions/bootstrap6`. Bootstrap 6 is a rewrite of many
components, so expect markup changes in custom views as well as in widget calls. Read the upstream
[migration guide](https://getbootstrap.com/docs/6.0/guides/migration/) for the CSS changes that do not involve widgets.

> [!IMPORTANT]
> Bootstrap 6 is an alpha (`6.0.0-alpha.1`) and drops browsers below Chrome/Edge 130, Firefox 132, and Safari 18.
> Keep `yii2-bootstrap5` for applications that must support older browsers.

## Migration sequence

1. Check the browser requirements and migrate on a branch with representative views available for testing.
2. Replace the package, namespace, and asset classes, then configure the `@npm` alias as described in [installation](installation.md#point-the-npm-alias-to-the-install-directory).
3. Replace renamed widget options and custom CSS selectors; migrate plugin scripts to the ES module helper.
4. Render and exercise dialogs, drawers, menus, tabs, toasts, and form validation before removing legacy styles.

## Package and namespace

```bash
composer remove yiisoft/yii2-bootstrap5
composer config allow-plugins.php-forge/foxy true
composer require php-forge/foxy:^0.4 yii2-extensions/bootstrap6:^0.1
```

Replace the namespace `yii\bootstrap5\` with `yii2\extensions\bootstrap6\` in every `use` statement and configuration
array. Enumerations are new and live in `yii2\extensions\bootstrap6\enum`; asset bundles moved to
`yii2\extensions\bootstrap6\asset`.

## Renamed classes

| yii2-bootstrap5                       | yii2-extensions/bootstrap6                              | Notes                                     |
| ------------------------------------- | ------------------------------------------------------- | ----------------------------------------- |
| `yii\bootstrap5\Modal`                | `yii2\extensions\bootstrap6\Dialog`                     | Native `<dialog class="dialog">`.         |
| `yii\bootstrap5\Offcanvas`            | `yii2\extensions\bootstrap6\Drawer`                     | Native `<dialog class="drawer">`.         |
| `yii\bootstrap5\Dropdown`             | `yii2\extensions\bootstrap6\Menu`                       | `.menu` panel with `.menu-item` elements. |
| `yii\bootstrap5\ButtonDropdown`       | `yii2\extensions\bootstrap6\ButtonMenu`                 | `data-bs-toggle="menu"`.                  |
| `yii\bootstrap5\Popover`              | `yii2\extensions\bootstrap6\Popover`                    | Now wraps the trigger; see below.         |
| `yii\bootstrap5\BootstrapAsset`       | `yii2\extensions\bootstrap6\asset\BootstrapAsset`       | CSS only.                                 |
| `yii\bootstrap5\BootstrapPluginAsset` | `yii2\extensions\bootstrap6\asset\BootstrapPluginAsset` | ES module.                                |
| `yii\bootstrap5\BootstrapIconAsset`   | `yii2\extensions\bootstrap6\asset\BootstrapIconAsset`   | Same files.                               |

`Accordion`, `ActiveField`, `ActiveForm`, `Alert`, `Breadcrumbs`, `Button`, `ButtonGroup`, `ButtonToolbar`,
`Carousel`, `Html`, `LinkPager`, `Nav`, `NavBar`, `Progress`, `Tabs`, `Toast`, and `ToggleButtonGroup` keep their names.
See the [gallery](examples.md) for components added in Bootstrap 6.

## JavaScript

Do not replace `bootstrap.Modal` with `bootstrap.Dialog` in a classic inline script alone: the module still provides
no global. Register the bundle and call its instance helper, as shown in the [Toast migration](#toast). A global
exposure is a temporary compatibility option and does not restore removed Bootstrap 5 plugin names.

- `BootstrapPluginAsset` registers `bootstrap.bundle.js` with `type="module"`. The bundle has no `window.bootstrap`, so
  inline scripts such as `new bootstrap.Modal(...)` fail. Capture the instance returned by
  `BootstrapPluginAsset::register($view)` and call `$bundle->importJs($view, $code)`, use a dynamic `import()` of
  `$bundle->moduleUrl($view)`, or set `exposeGlobal` to `true` while migrating; see the
  [examples](examples.md#javascript).
- Popper is gone; Floating UI is inlined in the bundle. Rename `popperConfig` plugin options to `floatingConfig`.
- Plugin names and events follow the components: `Modal` becomes `Dialog` (`*.bs.dialog`), `Offcanvas` becomes
  `Drawer` (`*.bs.drawer`), and `Dropdown` becomes `Menu` (`*.bs.menu`).
- A non-empty `clientOptions` array requests `getOrCreateInstance()` initialization; some widgets also initialize with
  empty options. See [Plugin initialization](configuration.md#plugin-initialization).
  `clientEvents` handlers are attached with `addEventListener` at `View::POS_END`.
- The Menu plugin binds to the toggle, so options and events of a menu belong on `ButtonMenu` (or the toggle element),
  not on a standalone `Menu`.
- `Toast` creates the plugin instance but does not show the toast; call `show()` on the instance.

## Markup changes

| Bootstrap 5                                 | Bootstrap 6                                                              |
| ------------------------------------------- | ------------------------------------------------------------------------ |
| `btn btn-primary`                           | `btn-solid theme-primary`                                                |
| `btn btn-outline-primary`                   | `btn-outline theme-primary`                                              |
| `badge bg-primary`, `alert alert-danger`    | `badge theme-primary`, `alert theme-danger`                              |
| `text-primary`, `text-bg-primary`           | `fg-primary`, `bg-primary fg-contrast-primary`                           |
| `navbar-expand-lg`, `col-md-6`, `d-md-none` | `lg:navbar-expand`, `md:col-6`, `md:d-none`                              |
| `xxl` breakpoint                            | `2xl` (`Breakpoint::XXL`)                                                |
| `mb-3` (1rem)                               | `mb-5`; the spacer scale has 13 steps and `mb-3` is 0.5rem               |
| `.form-check` + `.form-check-input`         | `<input class="check">` or `<input class="radio">` in a `.form-field`    |
| `.form-switch`                              | `<div class="switch"><input type="checkbox" role="switch" switch></div>` |
| `.form-select`                              | `.form-control` on `<select>`                                            |
| `.form-floating` with the control first     | `.form-floating` with the label first                                    |
| `.was-validated`                            | `data-bs-validate` on the form (`ActiveForm::$nativeValidation`)         |
| `.modal`, `.modal-dialog`, `.modal-content` | `<dialog class="dialog">`                                                |
| `.offcanvas`, `data-bs-toggle="offcanvas"`  | `<dialog class="drawer">`, `data-bs-toggle="drawer"`                     |
| `.dropdown-menu`, `.dropdown-item`          | `.menu`, `.menu-item`                                                    |
| `.btn-check` on the input                   | `.btn-check` on the `<label>` that wraps the input                       |
| `data-bs-ride="carousel"`                   | `data-bs-autoplay="true"` (`Carousel::$autoplay`)                        |

The default field container is `form-field mb-3`: `mb-3` is now 0.5rem, so add `mb-5` through
`fieldConfig['options']` to keep the v5 spacing.

## Translations

The message category changed from `yii/bootstrap5` (gettext `.po` files) to `yii.bootstrap6` (PHP message files in
`messages/<language>/yii.bootstrap6.php`), registered by `yii2\extensions\bootstrap6\Bootstrap`. Rename the category in
custom `i18n` configurations and in `Yii::t()` calls that reused the extension messages. `Toggle Dropdown` became
`Toggle menu`.

## Property changes

Apply the replacements below to existing configurations. New options and their exact contracts are documented in
the [generated API](https://yii2-framework.github.io/docs/extensions/bootstrap6/reference/). Enum-typed properties
also accept a valid string value.

Item arrays remain supported, but update renamed keys such as Accordion `expand` and `contentOptions`. Immutable
item objects are an optional typed alternative and can be mixed with arrays; when chaining a `with*()` method, keep
its returned copy. See [configuration](configuration.md#widgets).

### Accordion

| yii2-bootstrap5       | bootstrap6           | Notes                                  |
| --------------------- | -------------------- | -------------------------------------- |
| `itemToggleOptions`   | item `headerOptions` | The toggle is the `<summary>` element. |
| item `contentOptions` | item `bodyOptions`   | Attributes of `.accordion-body`.       |
| item `expand`         | item `open`          | Renders the `open` attribute.          |

`autoCloseItems` now shares a `name` attribute between `<details>` elements; no JavaScript is involved.

### Alert

| yii2-bootstrap5 | bootstrap6 | Notes                                     |
| --------------- | ---------- | ----------------------------------------- |
| `options` class | `theme`    | `alert-success` becomes `Theme::SUCCESS`. |

`.alert-dismissible` is no longer rendered; the close button is a plain `.btn-close` inside the alert.

### Breadcrumbs

| yii2-bootstrap5      | bootstrap6           | Notes                                                 |
| -------------------- | -------------------- | ----------------------------------------------------- |
| `activeItemTemplate` | `activeItemTemplate` | `active` moved from the `<li>` to `.breadcrumb-link`. |

The widget extends `yii2\extensions\bootstrap6\Widget` instead of `yii\widgets\Breadcrumbs`; `links`, `homeLink`,
`encodeLabels`, `itemTemplate`, `navOptions`, and `tag` keep their meaning.

### Button

| yii2-bootstrap5            | bootstrap6                 | Notes                                        |
| -------------------------- | -------------------------- | -------------------------------------------- |
| `label` (default `Button`) | `label` (default `null`)   | `null` renders the translated `Button`.      |
| `options` class            | `variant`, `theme`, `size` | Default `variant` is `ButtonVariant::SOLID`. |

### ButtonDropdown to ButtonMenu

| ButtonDropdown             | ButtonMenu                  | Notes                                            |
| -------------------------- | --------------------------- | ------------------------------------------------ |
| `dropdown`                 | `menu`                      | `Menu` configuration.                            |
| `dropdownClass`            | `menuClass`                 | Default `Menu::class`.                           |
| `direction`                | `placement`                 | `DIRECTION_*` constants replaced by `Placement`. |
| `renderContainer` (`true`) | `renderContainer` (`false`) | Split buttons always render the container.       |

`label`, `buttonOptions`, `split`, `tagName`, and `encodeLabel` are unchanged.

### Carousel

| yii2-bootstrap5                  | bootstrap6                   | Notes                                                        |
| -------------------------------- | ---------------------------- | ------------------------------------------------------------ |
| `controls` (HTML), `null`        | `controls` (labels), `false` | Visually hidden labels `[previous, next]`.                   |
| `options` `data-bs-ride`         | `autoplay`                   | Renders `data-bs-autoplay="true"`.                           |
| item `caption`, `captionOptions` | —                            | Removed with `.carousel-caption`; put captions in `content`. |

### Dropdown to Menu

`items`, `encodeLabels`, and `submenuOptions` are unchanged. Items gain `header`, `text`, and `divider` keys; items
without `url` render `<button>` elements.

### Modal to Dialog

Before, a Bootstrap 5 view supplied the size constant and backdrop setting to Modal:

```php
use yii\bootstrap5\Modal;

Modal::begin([
    'title' => 'Terms of service',
    'size' => Modal::SIZE_LARGE,
    'clientOptions' => ['backdrop' => 'static'],
    'scrollable' => true,
    'toggleButton' => ['label' => 'Review terms'],
]);
echo '<p>Read the terms before continuing.</p>';
Modal::end();
```

After, use `DialogSize` and the widget's `backdrop` option:

```php
use yii2\extensions\bootstrap6\Dialog;
use yii2\extensions\bootstrap6\enum\DialogSize;

Dialog::begin([
    'title' => 'Terms of service',
    'size' => DialogSize::LG,
    'backdrop' => 'static',
    'scrollable' => true,
    'toggleButton' => ['label' => 'Review terms'],
]);
echo '<p>Read the terms before continuing.</p>';
Dialog::end();
```

Verify that clicking the backdrop leaves it open and that Escape and the close button still close it. Update custom
selectors: the native `<dialog>` replaces the nested v5 modal containers.

| Modal                                  | Dialog                 | Notes                                         |
| -------------------------------------- | ---------------------- | --------------------------------------------- |
| `size` (`SIZE_*`)                      | `size` (`DialogSize`)  | `Modal::SIZE_LARGE` becomes `DialogSize::LG`. |
| `dialogOptions`                        | `options`              | There is no inner `.modal-dialog` element.    |
| `centerVertical`                       | —                      | Removed.                                      |
| `clientOptions` `backdrop`, `keyboard` | `backdrop`, `keyboard` | Rendered as `data-bs-*` attributes.           |

`title`, `titleOptions`, `headerOptions`, `bodyOptions`, `footer`, `footerOptions`, `closeButton`, `toggleButton`, and
`scrollable` are unchanged. The title tag defaults to `h1` (v5 rendered `h5`) and accepts a `tag` key in
`titleOptions`.

### Nav

| yii2-bootstrap5        | bootstrap6         | Notes                    |
| ---------------------- | ------------------ | ------------------------ |
| `dropdownClass`        | `menuClass`        | Default `Menu::class`.   |
| item `dropdownOptions` | item `menuOptions` | Attributes of `.menu`.   |
| `options` class        | `style`, `layout`  | `NavStyle`, `NavLayout`. |

### NavBar

| yii2-bootstrap5                       | bootstrap6      | Notes                                                                  |
| ------------------------------------- | --------------- | ---------------------------------------------------------------------- |
| `collapseOptions`, `offcanvasOptions` | `drawerOptions` | The content always collapses into a drawer; `false` renders it inline. |
| `options` `navbar-expand-*`           | `expand`        | `Breakpoint::LG` by default (`lg:navbar-expand`).                      |
| `clientOptions`                       | —               | The drawer uses the data API; the property has no effect.              |

### Offcanvas to Drawer

| Offcanvas                   | Drawer                          | Notes                                       |
| --------------------------- | ------------------------------- | ------------------------------------------- |
| `placement` (`PLACEMENT_*`) | `placement` (`DrawerPlacement`) | `'start'` becomes `DrawerPlacement::START`. |
| `scrolling`                 | `scroll`                        | Renders `data-bs-scroll="true"`.            |

`backdrop`, `title`, `titleOptions`, `headerOptions`, `bodyOptions`, `closeButton`, and `toggleButton` are unchanged.

### Popover

The v5 widget rendered the popover markup and an optional toggle button. The v6 widget wraps the trigger captured
between `begin()` and `end()` and lets Bootstrap build the popover.

| yii2-bootstrap5                                                | bootstrap6                                | Notes                                    |
| -------------------------------------------------------------- | ----------------------------------------- | ---------------------------------------- |
| content between `begin()` and `end()`                          | `content`                                 | The captured content is now the trigger. |
| `placement` (`PLACEMENT_*`, default `auto`)                    | `placement` (`Placement`, default `null`) | Also responsive values.                  |
| `TRIGGER_*` constants                                          | `trigger`                                 | Space-separated string.                  |
| `toggleButton`, `headerOptions`, `bodyOptions`, `arrowOptions` | —                                         | Removed.                                 |

### Tabs

| yii2-bootstrap5      | bootstrap6             | Notes                     |
| -------------------- | ---------------------- | ------------------------- |
| `dropdownClass`      | `menuClass`            | Default `Menu::class`.    |
| `navType` (`string`) | `navType` (`NavStyle`) | Default `NavStyle::TABS`. |

### Toast

For a legacy view receiving a user-supplied `$model->title`, replace the global plugin access and encode the body:

```php
use yii2\extensions\bootstrap6\{Html, Toast};
use yii2\extensions\bootstrap6\asset\BootstrapPluginAsset;

echo Toast::widget([
    'options' => ['id' => 'saved-toast'],
    'title' => 'Post saved',
    'body' => Html::encode($model->title),
    'dateTime' => null,
]);

$bundle = BootstrapPluginAsset::register($this);
$this->registerJs($bundle->importJs(
    $this,
    'bootstrap.Toast.getOrCreateInstance(document.getElementById("saved-toast")).show();',
));
```

Verify that the notification appears on load, can be dismissed, and renders a body containing `<script>` as text.
A `show` class displays markup but does not exercise custom module access. The `importJs()` call above must use the
registered bundle instance, not a static call on its class.

| yii2-bootstrap5      | bootstrap6          | Notes                  |
| -------------------- | ------------------- | ---------------------- |
| `dateTime` (`false`) | `dateTime` (`null`) | `null` omits the time. |

### ToggleButtonGroup

Move any custom `.btn-check` selectors from the input to its enclosing label. The input is now nested inside that
label; use `inputOptions` for attributes of the actual input.

### ActiveForm and ActiveField

| Class         | yii2-bootstrap5                                                        | bootstrap6                                 | Notes                               |
| ------------- | ---------------------------------------------------------------------- | ------------------------------------------ | ----------------------------------- |
| `ActiveForm`  | `errorSummaryCssClass` `alert alert-danger`                            | `alert theme-danger`                       | Default changed.                    |
| `ActiveForm`  | —                                                                      | `nativeValidation`                         | Renders `data-bs-validate`.         |
| `ActiveField` | `options` class `mb-3`                                                 | `form-field mb-3`                          | `.form-field` grid wrapper.         |
| `ActiveField` | `switchTemplate`, `switchHorizontalTemplate`, `switchEnclosedTemplate` | —                                          | Use `checkbox(['switch' => true])`. |
| `ActiveField` | `radioTemplate`, `radioHorizontalTemplate`                             | `checkTemplate`, `checkHorizontalTemplate` | Shared by checkboxes and radios.    |

The floating layout renders the label before the control and marks the `.form-floating` wrapper `is-invalid`, and the
horizontal grid classes use prefixes (`sm:col-2`, `sm:col-10`, `sm:offset-2`). Client-side floating feedback is
displayed by a CSS `:has()` rule registered once per form, so it needs no jQuery.

## Verify the migration

- Search application views, assets, and configuration for `yii\bootstrap5`, `bootstrap.Modal`, `bootstrap.Offcanvas`,
  `bootstrap.Dropdown`, and old event suffixes; review each remaining occurrence.
- Check rendered markup and custom selectors against [markup changes](#markup-changes), including responsive classes,
  spacing, floating labels, and menu triggers.
- Submit invalid forms and verify feedback in the layouts used by the application. Keep server-side validation.
- Open and dismiss each migrated interaction in a supported browser; check the console for module or asset errors.

[Back to documentation](../README.md#documentation)
