# Migrating from yii2-bootstrap5

This guide maps `yiisoft/yii2-bootstrap5` to `yii2-extensions/bootstrap6`. Bootstrap 6 is a rewrite of many
components, so expect markup changes in custom views as well as in widget calls. Read the upstream
[migration guide](https://getbootstrap.com/docs/6.0/guides/migration/) for the CSS changes that do not involve widgets.

> [!IMPORTANT]
> Bootstrap 6 is an alpha (`6.0.0-alpha.1`) and drops browsers below Chrome/Edge 130, Firefox 132, and Safari 18.
> Keep `yii2-bootstrap5` for applications that must support older browsers.

## Package and namespace

```bash
composer remove yiisoft/yii2-bootstrap5
composer config allow-plugins.php-forge/foxy true
composer require php-forge/foxy:^0.3 yii2-extensions/bootstrap6:^0.1
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
The new widgets are `Avatar`, `Badge`, `Chips`, `Collapse`, `Combobox`, `Datepicker`, `NavOverflow`, `OtpInput`,
`Placeholder`, `Range`, `Spinner`, `Stepper`, `Strength`, `Toggler`, and `Tooltip`.

## JavaScript

- `BootstrapPluginAsset` registers `bootstrap.bundle.js` with `type="module"`. The bundle has no `window.bootstrap`, so
  inline scripts such as `new bootstrap.Modal(...)` fail. Use `BootstrapPluginAsset::importJs()` or a dynamic
  `import()` of `moduleUrl()`, or set `exposeGlobal` to `true` while migrating; see the
  [examples](examples.md#javascript).
- Popper is gone; Floating UI is inlined in the bundle. Rename `popperConfig` plugin options to `floatingConfig`.
- Plugin names and events follow the components: `Modal` becomes `Dialog` (`*.bs.dialog`), `Offcanvas` becomes
  `Drawer` (`*.bs.drawer`), and `Dropdown` becomes `Menu` (`*.bs.menu`).
- `clientOptions` are passed to `getOrCreateInstance()` only when they are not empty; most widgets rely on the
  `data-bs-*` API. `clientEvents` handlers are attached with `addEventListener` at `View::POS_END`.
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

Properties not listed keep their name and meaning. Properties typed with enumerations also accept the string value.

Item arrays of `Accordion`, `Breadcrumbs`, `Carousel`, `Menu`, `Nav`, `Progress` (`bars`), `Stepper`, and `Tabs` keep
working unchanged; the `AccordionItem`, `BreadcrumbLink`, `CarouselItem`, `MenuItem`, `NavItem`, `ProgressBar`,
`StepItem`, and `TabItem` value objects (`yii2\extensions\bootstrap6\item`) are the canonical form, and both can be
mixed in one list.

### Accordion

| yii2-bootstrap5       | bootstrap6                      | Notes                                  |
| --------------------- | ------------------------------- | -------------------------------------- |
| `itemToggleOptions`   | item `headerOptions`            | The toggle is the `<summary>` element. |
| item `contentOptions` | item `bodyOptions`              | Attributes of `.accordion-body`.       |
| item `expand`         | item `open`                     | Renders the `open` attribute.          |
| —                     | `flush`, `gap`, `icon`, `small` | New.                                   |

`autoCloseItems` now shares a `name` attribute between `<details>` elements; no JavaScript is involved.

### Alert

| yii2-bootstrap5 | bootstrap6                                              | Notes                                     |
| --------------- | ------------------------------------------------------- | ----------------------------------------- |
| `options` class | `theme`                                                 | `alert-success` becomes `Theme::SUCCESS`. |
| —               | `heading`, `headingOptions`, `encodeHeading`, `instant` | New.                                      |

`.alert-dismissible` is no longer rendered; the close button is a plain `.btn-close` inside the alert.

### Breadcrumbs

| yii2-bootstrap5      | bootstrap6                  | Notes                                                 |
| -------------------- | --------------------------- | ----------------------------------------------------- |
| `activeItemTemplate` | `activeItemTemplate`        | `active` moved from the `<li>` to `.breadcrumb-link`. |
| —                    | `divider`, `dividerOptions` | Explicit `.breadcrumb-divider` items.                 |

The widget extends `yii2\extensions\bootstrap6\Widget` instead of `yii\widgets\Breadcrumbs`; `links`, `homeLink`,
`encodeLabels`, `itemTemplate`, `navOptions`, and `tag` keep their meaning.

### Button

| yii2-bootstrap5            | bootstrap6                 | Notes                                        |
| -------------------------- | -------------------------- | -------------------------------------------- |
| `label` (default `Button`) | `label` (default `null`)   | `null` renders the translated `Button`.      |
| `options` class            | `variant`, `theme`, `size` | Default `variant` is `ButtonVariant::SOLID`. |
| —                          | `toggle`                   | New.                                         |

### ButtonDropdown to ButtonMenu

| ButtonDropdown             | ButtonMenu                                                               | Notes                                            |
| -------------------------- | ------------------------------------------------------------------------ | ------------------------------------------------ |
| `dropdown`                 | `menu`                                                                   | `Menu` configuration.                            |
| `dropdownClass`            | `menuClass`                                                              | Default `Menu::class`.                           |
| `direction`                | `placement`                                                              | `DIRECTION_*` constants replaced by `Placement`. |
| `renderContainer` (`true`) | `renderContainer` (`false`)                                              | Split buttons always render the container.       |
| —                          | `autoClose`, `containerOptions`, `size`, `splitIcon`, `theme`, `variant` | New.                                             |

`label`, `buttonOptions`, `split`, `tagName`, and `encodeLabel` are unchanged.

### ButtonGroup

New properties: `divider`, `size`, and `vertical`.

### Carousel

| yii2-bootstrap5                  | bootstrap6                                                                                           | Notes                                                        |
| -------------------------------- | ---------------------------------------------------------------------------------------------------- | ------------------------------------------------------------ |
| `controls` (HTML), `null`        | `controls` (labels), `false`                                                                         | Visually hidden labels `[previous, next]`.                   |
| `options` `data-bs-ride`         | `autoplay`                                                                                           | Renders `data-bs-autoplay="true"`.                           |
| item `caption`, `captionOptions` | —                                                                                                    | Removed with `.carousel-caption`; put captions in `content`. |
| —                                | `controlsOptions`, `ends`, `indicatorLabel`, `interval`, `keyboard`, `overlay`, `pause`, `playPause` | New.                                                         |

### Dropdown to Menu

`items`, `encodeLabels`, and `submenuOptions` are unchanged. Items gain `header`, `text`, and `divider` keys; items
without `url` render `<button>` elements.

### LinkPager

All properties are unchanged. New: `size` and `theme`.

### Modal to Dialog

| Modal                                  | Dialog                                                           | Notes                                         |
| -------------------------------------- | ---------------------------------------------------------------- | --------------------------------------------- |
| `size` (`SIZE_*`)                      | `size` (`DialogSize`)                                            | `Modal::SIZE_LARGE` becomes `DialogSize::LG`. |
| `dialogOptions`                        | `options`                                                        | There is no inner `.modal-dialog` element.    |
| `centerVertical`                       | —                                                                | Removed.                                      |
| `clientOptions` `backdrop`, `keyboard` | `backdrop`, `keyboard`                                           | Rendered as `data-bs-*` attributes.           |
| —                                      | `encodeTitle`, `fullscreenBelow`, `instant`, `nonModal`, `slide` | New.                                          |

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

| yii2-bootstrap5                       | bootstrap6         | Notes                                                                  |
| ------------------------------------- | ------------------ | ---------------------------------------------------------------------- |
| `collapseOptions`, `offcanvasOptions` | `drawerOptions`    | The content always collapses into a drawer; `false` renders it inline. |
| `options` `navbar-expand-*`           | `expand`           | `Breakpoint::LG` by default (`lg:navbar-expand`).                      |
| `clientOptions`                       | —                  | The drawer uses the data API; the property has no effect.              |
| —                                     | `encodeBrandLabel` | New.                                                                   |

### Offcanvas to Drawer

| Offcanvas                   | Drawer                                                                                    | Notes                                       |
| --------------------------- | ----------------------------------------------------------------------------------------- | ------------------------------------------- |
| `placement` (`PLACEMENT_*`) | `placement` (`DrawerPlacement`)                                                           | `'start'` becomes `DrawerPlacement::START`. |
| `scrolling`                 | `scroll`                                                                                  | Renders `data-bs-scroll="true"`.            |
| —                           | `encodeTitle`, `footer`, `footerOptions`, `fullscreen`, `keyboard`, `responsive`, `sheet` | New.                                        |

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
| —                                                              | `customClass`, `html`, `tagName`          | New.                                     |

### Progress

New properties: `animated`, `ariaLabel`, `encodeLabel`, `max`, `min`, `striped`, and `theme`; `bars` segments accept
the same keys.

### Tabs

| yii2-bootstrap5      | bootstrap6             | Notes                     |
| -------------------- | ---------------------- | ------------------------- |
| `dropdownClass`      | `menuClass`            | Default `Menu::class`.    |
| `navType` (`string`) | `navType` (`NavStyle`) | Default `NavStyle::TABS`. |

### Toast

| yii2-bootstrap5      | bootstrap6               | Notes                  |
| -------------------- | ------------------------ | ---------------------- |
| `dateTime` (`false`) | `dateTime` (`null`)      | `null` omits the time. |
| —                    | `encodeTitle`, `instant` | New.                   |

### ToggleButtonGroup

New properties: `inputOptions`, `size`, `theme`, and `variant` (default `ButtonVariant::OUTLINE`). The input is nested
in its `.btn-check` label.

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

## Next steps

- 📚 [Installation Guide](installation.md)
- ⚙️ [Configuration Reference](configuration.md)
- 💡 [Usage Examples](examples.md)
- 🧪 [Testing Guide](testing.md)
