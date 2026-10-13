# Configuration reference

All classes live in the `yii2\extensions\bootstrap6` namespace; enumerations in `yii2\extensions\bootstrap6\enum` and
asset bundles in `yii2\extensions\bootstrap6\asset`. Properties typed with an enumeration also accept the plain
`string` value of a case, for example `'theme' => 'primary'` instead of `'theme' => Theme::PRIMARY`.

## Asset bundles

| Property       | Bundles                | Type         | Default                        | Description                                                      |
| -------------- | ---------------------- | ------------ | ------------------------------ | ---------------------------------------------------------------- |
| `cdn`          | all                    | `bool`       | `false`                        | Link the files from the CDN instead of publishing them.          |
| `cdnUrl`       | all                    | `string`     | `https://cdn.jsdelivr.net/npm` | CDN base URL, `{cdnUrl}/{package}@{version}/{directory}/{file}`. |
| `minified`     | all                    | `bool\|null` | `null`                         | Serve `.min` files; `null` means "outside debug mode".           |
| `exposeGlobal` | `BootstrapPluginAsset` | `bool`       | `false`                        | Assign the module namespace to `window.bootstrap`.               |

Configure them through the asset manager `bundles` array, or seed the delivery mode with
`BundleClass::registerWith($view, cdn: true)`. `BootstrapPluginAsset` also exposes two helpers for custom scripts:

| Method                                       | Returns                                                                 |
| -------------------------------------------- | ----------------------------------------------------------------------- |
| `moduleUrl(View $view): string`              | The bundle script URL exactly as the view registers it.                 |
| `importJs(View $view, string $code): string` | `import("url").then((bootstrap) => { code });`, with `bootstrap` bound. |

See the [installation guide](installation.md#load-the-assets) for the published directories and the rendered tags.

## Translations

`Bootstrap` registers a `PhpMessageSource` for the `yii.bootstrap6` category (constant
`Bootstrap::TRANSLATION_CATEGORY`) with source language `en-US` and the files in
`messages/<language>/yii.bootstrap6.php`. Translations ship for 52 languages. The translated messages are `Button`,
`Close`, `Loading...`, `Menu`, `Next`, `Pause`, `Play`, `Previous`, `Show`, `Toggle menu`, and `Toggle navigation`.
Configure the category in the application `i18n` component to override them; the bootstrap keeps an existing
configuration.

## Common widget properties

Every widget extends `Widget` (or `InputWidget` for form inputs) and shares these properties:

| Property        | Type                                  | Default | Description                                                 |
| --------------- | ------------------------------------- | ------- | ----------------------------------------------------------- |
| `options`       | `array<string, mixed>`                | `[]`    | HTML attributes of the main element; an `id` is generated.  |
| `clientOptions` | `array<string, mixed>\|false`         | `[]`    | Options of the plugin constructor, or `false` to skip it.   |
| `clientEvents`  | `array<string, JsExpression\|string>` | `[]`    | `addEventListener` handlers attached to the plugin element. |

`InputWidget` descendants (`Chips`, `Combobox`, `Datepicker`, `OtpInput`, `Range`, `ToggleButtonGroup`) also accept the
Yii input properties `model`, `attribute`, `name`, `value`, and `field`; their `options` default is inherited from
`yii\widgets\InputWidget`.

Enum-typed properties accept the enum case or its `string` value; `init()` validates them through `Html::enumValue()`,
so an unknown string throws `InvalidConfigException` from `widget()` and `begin()` (enum values inside item or
`drawerOptions` arrays are validated when rendered). `Placement` properties also accept responsive values such as
`bottom-start md:top-end`. Content properties (`label`, `title`, `body`, `heading`, `footer`, and the item `label` and
`content` keys) accept `string` or `Stringable`.

### Fluent attribute methods

`Widget` and `InputWidget` use `AttributesTrait`, whose methods modify `options` in place and return the same widget
instance (return type `static`), so they chain:

| Method                                   | Effect on `options`                                             |
| ---------------------------------------- | --------------------------------------------------------------- |
| `addAttributes(array $attributes)`       | Merges the attributes; a key already set is replaced.           |
| `addClass(...$class)`                    | Appends classes; enum cases add their value, `null` is ignored. |
| `addCssStyle($style, $overwrite = true)` | Merges CSS properties into `style` (rules below).               |
| `attribute(string $name, mixed $value)`  | Sets one attribute; a `null` value omits it when rendering.     |
| `class(...$class)`                       | Replaces the classes; with only `null`, removes `class`.        |

`$class` is variadic `BackedEnum|string|null`, `$style` is an `array` or a `string`, and `$overwrite` is a `bool`.

Duplicated classes are collapsed. `class()` replaces only the classes held in `options`: the classes a widget requires
(`collapse`, `navbar`, `dialog`, `theme-*`...) are added while rendering, from a copy of `options`, so they are always
present. `addCssStyle()` stores a `string` as is when no style exists (`color: red`), and renders an array as
`color: red;`. With an existing style, both are merged and normalized to `name: value;` pairs: new properties are
appended, and a property already set keeps its position; `$overwrite = true` replaces its value, `false` keeps the
existing value.

Widgets that render a toggler also use `TogglerAttributesTrait`, whose `addTogglerAttribute($name, $value)`,
`addTogglerClass(...$class)`, and `addTogglerCssStyle($style, $overwrite = true)` apply the same rules to the toggler
attributes:

| Widget                         | Toggler attributes | Enabling rule                                                |
| ------------------------------ | ------------------ | ------------------------------------------------------------ |
| `Collapse`, `Dialog`, `Drawer` | `toggleButton`     | `false` (the default) becomes `[]`, so the button renders.   |
| `NavBar`                       | `togglerOptions`   | Always an array; rendered unless `drawerOptions` is `false`. |

The toggle button of `Collapse`, `Dialog`, and `Drawer` defaults to `btn-solid theme-primary` only when it has no
`class`, so a class added with `addTogglerClass()` replaces the default classes, exactly like
`'toggleButton' => ['class' => ...]`. The `NavBar` toggler classes come before the fixed `btn-icon navbar-toggler`.
The toggler attributes are returned by the protected `togglerOptions()` method, by reference, which subclasses may
override. `ButtonMenu` has no toggler methods: its `options` are the toggle button itself, so `addClass()` already
targets it.

`widget()` returns a `string` and cannot be chained. Obtain an instance first, then render it:

```php
use yii2\extensions\bootstrap6\{Alert, Dialog};

Dialog::begin(['title' => 'Delete post'])
    ->addClass('shadow-lg')
    ->addTogglerClass('btn-outline', 'theme-danger');
echo '<p>This action cannot be undone.</p>';
Dialog::end();

echo (new Alert(['body' => 'Saved.']))->addClass('mb-5')->run();
echo Yii::createObject(['class' => Alert::class, 'body' => 'Saved.'])->attribute('data-id', '7')->run();
```

`attribute()` sets an HTML attribute; the `$attribute` property of input widgets (the model attribute) and of
`Toggler` keeps its meaning.

### Plugin initialization

Bootstrap 6 initializes most components through `data-bs-*` attributes, which the widgets render. A widget creates the
plugin instance explicitly, with `bootstrap.{Plugin}.getOrCreateInstance(element, clientOptions)` inside an `import()`
of the bundle, only when `clientOptions` is a non-empty array. `Carousel` and `Toast` always create the instance, and
`Button` creates it whenever `clientOptions` is an array (its default is `false`). Enum cases inside
`clientOptions` are encoded as their value (or name for pure enums) and `Stringable` values as their `string`.

`clientEvents` handlers are registered at `View::POS_END` as
`document.getElementById(id).addEventListener(event, handler)`, independently of `clientOptions`.

| Widget               | Plugin                           | Element that receives the instance and the events      |
| -------------------- | -------------------------------- | ------------------------------------------------------ |
| `Alert`              | `Alert`                          | `.alert`                                               |
| `Button`             | `Button`                         | the button                                             |
| `ButtonMenu`         | `Menu`                           | the toggle button (`options`)                          |
| `Carousel`           | `Carousel` (always)              | `.carousel`                                            |
| `Chips`              | `Chips`                          | `.chip-input` container (`{id}-chips`)                 |
| `Collapse`           | `Collapse`                       | `.collapse`                                            |
| `Combobox`           | `Combobox`                       | `.combobox-toggle`                                     |
| `Datepicker`         | `Datepicker`                     | the input, or the inline container (`{id}-datepicker`) |
| `Dialog`, `Drawer`   | `Dialog`, `Drawer`               | the `<dialog>` element                                 |
| `Menu`               | `Menu`                           | the `.menu` panel (see the note below)                 |
| `Nav`                | `Menu` (when an item has a menu) | the `.nav` list                                        |
| `NavOverflow`        | `NavOverflow`                    | `.nav-overflow`                                        |
| `OtpInput`           | `OtpInput`                       | `.otp` container (`{id}-otp`)                          |
| `Popover`, `Tooltip` | `Popover`, `Tooltip`             | the wrapping element                                   |
| `Range`              | `Range`                          | `.form-range` wrapper (`{id}-range`)                   |
| `Strength`           | `Strength`                       | `.strength` or `.strength-bar`                         |
| `Tabs`               | `Tab`                            | the `.nav` list                                        |
| `Toast`              | `Toast` (always)                 | `.toast`                                               |
| `Toggler`            | `Toggler`                        | the toggler element                                    |

The Menu plugin binds to the element that carries `data-bs-toggle="menu"`, not to the `.menu` panel. Configure
`clientOptions` and `clientEvents` on `ButtonMenu` (or on your own trigger) instead of a standalone `Menu`. Events of
the Menu and Tab plugins bubble, so `Nav` and `Tabs` can listen to `shown.bs.menu` and `shown.bs.tab` on the list.
`NavBar` relies on the data API of the Drawer plugin and ignores `clientOptions` and `clientEvents`; the CSS-only
widgets (`Accordion`, `Avatar`, `Badge`, `Breadcrumbs`, `ButtonGroup`, `ButtonToolbar`, `LinkPager`, `Placeholder`,
`Progress`, `Spinner`, `Stepper`, `ToggleButtonGroup`) ignore them too.

### Begin and end widgets

`Alert`, `Collapse`, `Dialog`, `Drawer`, `NavBar`, `Popover`, `Toast`, and `Tooltip` buffer the content captured
between `begin()` and `end()`, and `run()` returns the complete markup with that content inside.
`yii\base\Widget::end()` echoes the result, so call `begin()` and `end()` from views or layouts, where
`View::renderPhpFile()` buffers the output. Under worker runtimes such as RoadRunner or FrankenPHP, unbuffered output
written outside a view is not captured by the extension.

## Icons

`Accordion` and `Combobox` render SVG icons built with [`ui-awesome/html-svg`](https://github.com/ui-awesome/html-svg).
The defaults live in `IconSet`, which the widgets resolve with `Yii::createObject(IconSet::class)` on every render.

| `IconSet` property | Used by                | Structural class  |
| ------------------ | ---------------------- | ----------------- |
| `accordionChevron` | `Accordion::$icon`     | `accordion-icon`  |
| `comboboxCaret`    | `Combobox::$caretIcon` | `combobox-caret`  |
| `menuItemCheck`    | `Combobox::$checkIcon` | `menu-item-check` |

The Bootstrap stylesheet sizes, rotates, and hides the icons through the structural class, so the widgets append it to
any `UIAwesome\Html\Svg\Svg` instance. Plain strings and other `Stringable` objects are rendered verbatim and must
carry the class themselves. A widget property set to `null` (the default) uses the `IconSet` icon.

Replace an icon for the whole application through the DI container:

```php
use UIAwesome\Html\Svg\Svg;
use yii2\extensions\bootstrap6\IconSet;

return [
    'container' => [
        'definitions' => [
            IconSet::class => [
                'accordionChevron' => Svg::icon('Flowbite:chevron-down'),
            ],
        ],
    ],
];
```

Or for a single widget:

```php
use UIAwesome\Html\Svg\Svg;
use yii2\extensions\bootstrap6\{Accordion, Combobox};

echo Accordion::widget(
    [
        'icon' => Svg::icon('Flowbite:chevron-down')->width(16)->height(16),
        'items' => ['Is it free?' => 'Yes.'],
    ],
);

echo Combobox::widget(
    [
        'name' => 'fruit',
        'items' => ['apple' => 'Apple', 'pear' => 'Pear'],
        'caretIcon' => '<i class="bi bi-chevron-down combobox-caret" aria-hidden="true"></i>',
    ],
);
```

`Svg::icon()` currently bundles only the `Bootstrap5` (`globe`, `moon`, `sun`) and `Flowbite` collections.

## Enumerations

| Enumeration       | Cases (value)                                                                                                                                                                                                                                                    |
| ----------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `Breakpoint`      | `SM` (`sm`), `MD` (`md`), `LG` (`lg`), `XL` (`xl`), `XXL` (`2xl`).                                                                                                                                                                                               |
| `ButtonVariant`   | `SOLID` (`btn-solid`), `OUTLINE`, `SUBTLE`, `TEXT`, `STYLED`, `LINK`, `ICON` (`btn-{name}`).                                                                                                                                                                     |
| `CarouselEnds`    | `LOOP` (`loop`), `WRAP` (`wrap`), `STOP` (`stop`).                                                                                                                                                                                                               |
| `DialogSize`      | `SM` (`dialog-sm`), `LG` (`dialog-lg`), `XL` (`dialog-xl`), `FULLSCREEN` (`dialog-fullscreen`).                                                                                                                                                                  |
| `DrawerPlacement` | `START` (`drawer-start`), `END` (`drawer-end`), `TOP` (`drawer-top`), `BOTTOM` (`drawer-bottom`).                                                                                                                                                                |
| `MenuItemType`    | `LINK` (`link`), `BUTTON` (`button`), `SUBMENU` (`submenu`), `HEADER` (`header`), `TEXT` (`text`), `DIVIDER` (`divider`).                                                                                                                                        |
| `NavLayout`       | `FILL` (`nav-fill`), `JUSTIFIED` (`nav-justified`), `VERTICAL` (`flex-column`).                                                                                                                                                                                  |
| `NavStyle`        | `TABS` (`nav-tabs`), `PILLS` (`nav-pills`), `PILLS_VERTICAL` (`nav-pills-vertical`), `UNDERLINE` (`nav-underline`).                                                                                                                                              |
| `Placement`       | `TOP`, `TOP_START`, `TOP_END`, `RIGHT`, `RIGHT_START`, `RIGHT_END`, `BOTTOM`, `BOTTOM_START`, `BOTTOM_END`, `LEFT`, `LEFT_START`, `LEFT_END` (`top`, `top-start`, ...); logical `START`, `START_START`, `START_END`, `END`, `END_START`, `END_END` (menus only). |
| `Size`            | `XS` (`xs`), `SM` (`sm`), `LG` (`lg`), `XL` (`xl`).                                                                                                                                                                                                              |
| `Theme`           | `PRIMARY`, `SECONDARY`, `SUCCESS`, `DANGER`, `WARNING`, `INFO`, `ACCENT`, `INVERSE` (rendered as `theme-{value}`).                                                                                                                                               |

## Widgets

The tables list the properties declared by each widget, in addition to the common ones.

Item widgets (`Accordion`, `Breadcrumbs`, `Carousel`, `Menu`, `Nav`, `Progress::$bars`, `Stepper`, `Tabs`) accept value
objects from the `yii2\extensions\bootstrap6\item` namespace (`AccordionItem`, `BreadcrumbLink`, `CarouselItem`,
`MenuItem`, `NavItem`, `ProgressBar`, `StepItem`, `TabItem`), arrays, and the documented string shortcuts, mixed in one
list. Arrays are normalized through the `fromArray()` method of the value object when the widget runs (`Stepper` does
it in `init()`), so the item keys below keep working; the value objects are the canonical form. They are immutable
`final readonly` classes with public properties: a named constructor takes the required values, and every other
property is set by chaining a `with*()` method (`withActive()`, `withOptions()`, ...), which returns a modified copy.
An `encode` of `null` uses the widget `encodeLabels`, and an `active` of `null` lets the widget decide. Arrays nested
inside a value object (the `$items` of `NavItem::menu()`, `MenuItem::submenu()`, `TabItem::menu()` and of the
`withItems()` methods) are converted the same way.

### Accordion

Renders `<details class="accordion-item">` elements; no JavaScript is involved.

| Property         | Type                       | Default | Description                                                                       |
| ---------------- | -------------------------- | ------- | --------------------------------------------------------------------------------- |
| `autoCloseItems` | `bool`                     | `true`  | Share a `name` attribute (the widget `id`) so one item stays open.                |
| `encodeLabels`   | `bool`                     | `true`  | HTML-encode the labels; the item `encode` key overrides it.                       |
| `flush`          | `bool`                     | `false` | Remove borders and rounded corners (`.accordion-flush`).                          |
| `gap`            | `bool`                     | `false` | Separate the items (`.accordion-gap`).                                            |
| `icon`           | `string\|Stringable\|null` | `null`  | Markup after each label; `null` uses `IconSet::$accordionChevron`, `''` omits it. |
| `items`          | `array`                    | `[]`    | Items, see below; `label => content` pairs are accepted.                          |
| `small`          | `bool`                     | `false` | Compact variant (`.accordion-sm`).                                                |

Item keys: `label` and `content` (required), `open`, `encode`, `options` (`<details>`), `headerOptions` (`<summary>`),
`bodyOptions` (`.accordion-body`), and `visible`.

Value objects: `AccordionItem::to($label, $content)`, then `withOpen()`, `withVisible()`, `withEncode()`,
`withOptions()`, `withHeaderOptions()`, and `withBodyOptions()`. Example: `'items' =>
[AccordionItem::to('What is Yii?', 'A PHP framework.')->withOpen(true)]`.

### Alert

Content captured between `begin()` and `end()` is rendered before `body`.

| Property         | Type                       | Default                                  | Description                                                          |
| ---------------- | -------------------------- | ---------------------------------------- | -------------------------------------------------------------------- |
| `body`           | `string\|Stringable`       | `''`                                     | Alert content (not encoded).                                         |
| `closeButton`    | `array\|false`             | `[]`                                     | Dismiss button attributes; `tag` and `label` keys; `false` omits it. |
| `encodeHeading`  | `bool`                     | `true`                                   | HTML-encode `heading`.                                               |
| `heading`        | `string\|Stringable\|null` | `null`                                   | `.alert-heading` element above the body.                             |
| `headingOptions` | `array`                    | `['class' => ['alert-heading', 'mb-2']]` | Heading attributes; the `tag` key defaults to `h4`.                  |
| `instant`        | `bool`                     | `false`                                  | Remove without transition (`.alert-instant`).                        |
| `theme`          | `Theme\|string\|null`      | `null`                                   | Theme color (`theme-{value}`).                                       |

### Avatar

| Property      | Type                  | Default | Description                                                            |
| ------------- | --------------------- | ------- | ---------------------------------------------------------------------- |
| `alt`         | `string`              | `''`    | Alternative text of the image.                                         |
| `encodeLabel` | `bool`                | `true`  | HTML-encode `initials`.                                                |
| `initials`    | `string\|null`        | `null`  | Initials rendered when `src` is `null`.                                |
| `size`        | `Size\|string\|null`  | `null`  | `avatar-{value}`; strings are validated against `Size`.                |
| `src`         | `string\|null`        | `null`  | Image URL rendered as `.avatar-img`.                                   |
| `status`      | `string\|null`        | `null`  | `online`, `offline`, `busy`, or `away` indicator.                      |
| `statusLabel` | `string\|null`        | `null`  | Accessible label of the indicator; `null` uses the capitalized status. |
| `subtle`      | `bool`                | `false` | Subtle variant (`avatar-subtle`).                                      |
| `theme`       | `Theme\|string\|null` | `null`  | Theme color.                                                           |

### Badge

| Property      | Type                  | Default | Description                        |
| ------------- | --------------------- | ------- | ---------------------------------- |
| `encodeLabel` | `bool`                | `true`  | HTML-encode `label`.               |
| `label`       | `string\|Stringable`  | `''`    | Badge content.                     |
| `large`       | `bool`                | `false` | Larger badge (`badge-lg`).         |
| `outline`     | `bool`                | `false` | Outline variant (`badge-outline`). |
| `pill`        | `bool`                | `false` | Pill corners (`rounded-pill`).     |
| `subtle`      | `bool`                | `false` | Subtle variant (`badge-subtle`).   |
| `tagName`     | `string`              | `span`  | Tag name.                          |
| `theme`       | `Theme\|string\|null` | `null`  | Theme color.                       |

### Breadcrumbs

`options` applies to the list element. The widget renders nothing when `links` is empty.

| Property             | Type                           | Default                                                       | Description                                                                                                                                      |
| -------------------- | ------------------------------ | ------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------ |
| `activeItemTemplate` | `string`                       | `<li class="breadcrumb-item" aria-current="page">{link}</li>` | Template of the active item.                                                                                                                     |
| `divider`            | `string`                       | `''`                                                          | Divider content; empty renders the CSS chevron.                                                                                                  |
| `dividerOptions`     | `array`                        | `['class' => 'breadcrumb-divider']`                           | Attributes of every divider `<li>`.                                                                                                              |
| `encodeLabels`       | `bool`                         | `true`                                                        | HTML-encode the labels; item `encode` overrides it.                                                                                              |
| `homeLink`           | `array\|BreadcrumbLink\|false` | `[]`                                                          | First item; `[]` links `homeUrl` as `Home`, `false` omits it.                                                                                    |
| `itemTemplate`       | `string`                       | `<li class="breadcrumb-item">{link}</li>`                     | Template of the other items.                                                                                                                     |
| `links`              | `array`                        | `[]`                                                          | A `BreadcrumbLink`, a label string, a `url => label` pair, or an array with `label`, `url`, `active`, `encode`, `template`, and link attributes. |
| `navOptions`         | `array`                        | `['aria-label' => 'breadcrumb']`                              | Attributes of the `<nav>` container.                                                                                                             |
| `tag`                | `string`                       | `ol`                                                          | Tag name of the list.                                                                                                                            |

Value objects: `BreadcrumbLink::to($label, $url = null)`, then `withActive()`, `withEncode()`, `withTemplate()`, and
`withOptions()` (link attributes); an `active` of `null` means `$url === null`; `homeLink` accepts one as well.
Example: `'links' => [BreadcrumbLink::to('Library', ['site/library']), BreadcrumbLink::to('Data')]`.

### Button

| Property        | Type                          | Default                | Description                                                                |
| --------------- | ----------------------------- | ---------------------- | -------------------------------------------------------------------------- |
| `clientOptions` | `array\|false`                | `false`                | Button plugin options; an array (even empty) creates the instance.         |
| `encodeLabel`   | `bool`                        | `true`                 | HTML-encode `label`.                                                       |
| `label`         | `string\|Stringable\|null`    | `null`                 | Content; `null` uses the translated `Button`.                              |
| `size`          | `Size\|string\|null`          | `null`                 | `btn-{value}`, for example `btn-sm`.                                       |
| `tagName`       | `string`                      | `button`               | Tag name; `button` receives `type="button"`, `a` receives `role="button"`. |
| `theme`         | `Theme\|string\|null`         | `null`                 | Theme color.                                                               |
| `toggle`        | `bool`                        | `false`                | Toggle button (`data-bs-toggle="button"`, `aria-pressed="false"`).         |
| `variant`       | `ButtonVariant\|string\|null` | `ButtonVariant::SOLID` | Style class; `null` renders the base `btn` class.                          |

### ButtonGroup

| Property       | Type                 | Default | Description                                                    |
| -------------- | -------------------- | ------- | -------------------------------------------------------------- |
| `buttons`      | `array`              | `[]`    | HTML strings or `Button` configurations (extra key `visible`). |
| `divider`      | `bool`               | `false` | Dividers between the buttons (`btn-group-divider`).            |
| `encodeLabels` | `bool`               | `true`  | Default `encodeLabel` of the configured buttons.               |
| `size`         | `Size\|string\|null` | `null`  | `btn-group-{value}`.                                           |
| `vertical`     | `bool`               | `false` | `btn-group-vertical` instead of `btn-group`.                   |

### ButtonMenu

`options` holds the attributes of the element that carries `data-bs-toggle="menu"`; the menu `id` defaults to
`{id}-menu`.

| Property           | Type                          | Default                | Description                                                          |
| ------------------ | ----------------------------- | ---------------------- | -------------------------------------------------------------------- |
| `autoClose`        | `bool\|string\|null`          | `null`                 | `true`, `false`, `inside`, or `outside` (`data-bs-auto-close`).      |
| `buttonOptions`    | `array`                       | `[]`                   | Attributes of the action button in split mode.                       |
| `containerOptions` | `array`                       | `[]`                   | Attributes of the `.btn-group`; the `tag` key defaults to `div`.     |
| `encodeLabel`      | `bool`                        | `true`                 | HTML-encode `label`.                                                 |
| `label`            | `string\|Stringable\|null`    | `null`                 | Button label; `null` uses the translated `Button`.                   |
| `menu`             | `array`                       | `[]`                   | `Menu` configuration.                                                |
| `menuClass`        | `class-string<Menu>`          | `Menu::class`          | Menu widget class.                                                   |
| `placement`        | `Placement\|string\|null`     | `null`                 | `data-bs-placement`, also responsive (`bottom-start md:bottom-end`). |
| `renderContainer`  | `bool`                        | `false`                | Wrap a non-split button in `.btn-group`.                             |
| `size`             | `Size\|string\|null`          | `null`                 | `btn-{value}`.                                                       |
| `split`            | `bool`                        | `false`                | Action button plus a `.menu-toggle-split` toggle.                    |
| `splitIcon`        | `string`                      | `''`                   | HTML inside the split toggle before its hidden text.                 |
| `tagName`          | `string`                      | `button`               | `button` or `a` (uses the `href` of `buttonOptions` or `options`).   |
| `theme`            | `Theme\|string\|null`         | `null`                 | Theme color.                                                         |
| `variant`          | `ButtonVariant\|string\|null` | `ButtonVariant::SOLID` | Style class; `null` renders none.                                    |

### ButtonToolbar

| Property       | Type    | Default | Description                                                                         |
| -------------- | ------- | ------- | ----------------------------------------------------------------------------------- |
| `buttonGroups` | `array` | `[]`    | HTML strings or `ButtonGroup` configurations; arrays without `buttons` are skipped. |

### Carousel

The plugin is always initialized so that the indicators follow the visible slide. `items` must not be empty.

| Property          | Type                         | Default          | Description                                                   |
| ----------------- | ---------------------------- | ---------------- | ------------------------------------------------------------- |
| `autoplay`        | `bool`                       | `false`          | Cycle on page load (`data-bs-autoplay="true"`).               |
| `controls`        | `array\|false`               | `[]`             | Hidden labels `[previous, next]`; `false` omits the controls. |
| `controlsOptions` | `array`                      | `[]`             | Attributes of the element wrapping controls and indicators.   |
| `crossfade`       | `bool`                       | `false`          | Fade instead of slide (`.carousel-fade`).                     |
| `ends`            | `CarouselEnds\|string\|null` | `null`           | `data-bs-ends`; the plugin default is `loop`.                 |
| `indicatorLabel`  | `string\|Stringable`         | `Slide {number}` | Accessible label of each indicator.                           |
| `interval`        | `int\|null`                  | `null`           | Delay in milliseconds (`data-bs-interval`).                   |
| `items`           | `array`                      | `[]`             | Slide HTML strings, or arrays with `content` and `options`.   |
| `keyboard`        | `bool\|null`                 | `null`           | `data-bs-keyboard`.                                           |
| `overlay`         | `bool`                       | `false`          | Overlay the controls (`.carousel-overlay`).                   |
| `pause`           | `string\|false\|null`        | `null`           | `data-bs-pause`: `hover` or `false`.                          |
| `playPause`       | `bool`                       | `false`          | Render `.carousel-control-play-pause`.                        |
| `showIndicators`  | `bool`                       | `true`           | Render `.carousel-indicators`.                                |

Value objects: `CarouselItem::to($content)`, then `withOptions()` for the `.carousel-item` attributes. Example:
`'items' => [CarouselItem::to('<img src="/img/1.jpg" alt="First">')->withOptions(['data-bs-interval' => 3000])]`.

### Chips

`options` applies to the `.form-ghost` text input. A hidden input (`{id}-value`) submits the chips joined by the
separator and is kept in sync by a `change.bs.chips` listener; it copies `disabled` and `form` from `options`, so a
disabled field submits nothing. Initial chips come from the attribute or `value`, as an array or a joined string.

| Property           | Type           | Default | Description                                                               |
| ------------------ | -------------- | ------- | ------------------------------------------------------------------------- |
| `allowDuplicates`  | `bool\|null`   | `null`  | `data-bs-allow-duplicates`; the plugin default is `false`.                |
| `containerOptions` | `array`        | `[]`    | Attributes of `.chip-input`; its `id` defaults to `{id}-chips`.           |
| `createOnBlur`     | `bool\|null`   | `null`  | `data-bs-create-on-blur`; the plugin default is `true`.                   |
| `dismissible`      | `bool\|null`   | `null`  | `data-bs-dismissible`; the plugin default is `true`.                      |
| `encodeLabels`     | `bool`         | `true`  | HTML-encode the chip labels.                                              |
| `maxChips`         | `int\|null`    | `null`  | `data-bs-max-chips`.                                                      |
| `placeholder`      | `string\|null` | `null`  | Placeholder of the ghost input.                                           |
| `separator`        | `string\|null` | `null`  | `data-bs-separator` (plugin default `,`); also joins the submitted value. |

### Collapse

Content captured between `begin()` and `end()` is rendered inside the `.collapse` element.

| Property       | Type           | Default | Description                                                                     |
| -------------- | -------------- | ------- | ------------------------------------------------------------------------------- |
| `horizontal`   | `bool`         | `false` | Collapse the inline size (`.collapse-horizontal`).                              |
| `parent`       | `string\|null` | `null`  | `data-bs-parent` selector.                                                      |
| `show`         | `bool`         | `false` | Initially shown (`.show`).                                                      |
| `tagName`      | `string`       | `div`   | Tag name of the collapsible element.                                            |
| `toggleButton` | `array\|false` | `false` | Toggle button attributes with `label` (translated `Show`), `encode`, and `tag`. |

### Combobox

The plugin creates a hidden input from `data-bs-name`; multiple selections are submitted as a comma-separated list.
`items` must not be empty.

| Property            | Type                         | Default            | Description                                             |
| ------------------- | ---------------------------- | ------------------ | ------------------------------------------------------- |
| `caretIcon`         | `string\|Stringable\|null`   | `null`             | Toggle caret; `null` uses `IconSet::$comboboxCaret`.    |
| `check`             | `bool`                       | `false`            | Render the `.menu-item-check` icon.                     |
| `checkIcon`         | `string\|Stringable\|null`   | `null`             | Item check mark; `null` uses `IconSet::$menuItemCheck`. |
| `encodeLabels`      | `bool`                       | `true`             | HTML-encode the labels and `placeholder`.               |
| `items`             | `array<int\|string, string>` | `[]`               | Items as `value => label`.                              |
| `menuOptions`       | `array`                      | `[]`               | Attributes of the `.menu` container.                    |
| `multiple`          | `bool`                       | `false`            | Allow several selections.                               |
| `noResultsText`     | `string\|Stringable`         | `No results found` | Text shown when the search matches nothing.             |
| `placeholder`       | `string`                     | `''`               | Text shown when nothing is selected.                    |
| `placement`         | `Placement\|string\|null`    | `null`             | Menu placement; the plugin default is `bottom-start`.   |
| `search`            | `bool`                       | `false`            | Render the search field.                                |
| `searchPlaceholder` | `string\|Stringable`         | `Search...`        | Placeholder and label of the search field.              |

### Datepicker

Every option is rendered as a `data-bs-*` attribute. In input mode the field submits the locale-formatted text shown to
the user; in inline mode a hidden input keeps the `YYYY-MM-DD` value (comma-separated for several dates).

| Property             | Type           | Default | Description                                                          |
| -------------------- | -------------- | ------- | -------------------------------------------------------------------- |
| `containerOptions`   | `array`        | `[]`    | Inline container attributes; its `id` defaults to `{id}-datepicker`. |
| `dateFormat`         | `array\|null`  | `null`  | `Intl.DateTimeFormat` options for the displayed dates.               |
| `dateMax`            | `string\|null` | `null`  | Latest date, `YYYY-MM-DD`.                                           |
| `dateMin`            | `string\|null` | `null`  | Earliest date, `YYYY-MM-DD`.                                         |
| `displayMonthsCount` | `int\|null`    | `null`  | Months displayed side by side (plugin default `1`).                  |
| `firstWeekday`       | `int\|null`    | `null`  | `0` (Sunday) to `6` (Saturday); plugin default `1`.                  |
| `inline`             | `bool`         | `false` | Render the calendar inline, bound to a hidden input.                 |
| `locale`             | `string\|null` | `null`  | Locale such as `de-DE`; `null` uses the browser locale.              |
| `placement`          | `string\|null` | `null`  | `left`, `center`, `right`, or `auto`.                                |
| `selectedDates`      | `list<string>` | `[]`    | Preselected dates, `YYYY-MM-DD`.                                     |
| `selectionMode`      | `string\|null` | `null`  | `single`, `multiple`, or `multiple-ranged`.                          |

### Dialog

Renders a native `<dialog class="dialog">`. Content captured between `begin()` and `end()` goes into `.dialog-body`.

| Property          | Type                       | Default | Description                                                                                    |
| ----------------- | -------------------------- | ------- | ---------------------------------------------------------------------------------------------- |
| `backdrop`        | `bool\|string`             | `true`  | `true` closes on backdrop click, `false` hides it, `static` keeps it open.                     |
| `bodyOptions`     | `array`                    | `[]`    | Attributes of `.dialog-body`.                                                                  |
| `closeButton`     | `array\|false`             | `[]`    | Close button attributes (`Html::closeButton()`); `false` omits it.                             |
| `encodeTitle`     | `bool`                     | `true`  | HTML-encode `title`.                                                                           |
| `footer`          | `string\|Stringable\|null` | `null`  | Content of `.dialog-footer` (not encoded).                                                     |
| `footerOptions`   | `array`                    | `[]`    | Attributes of `.dialog-footer`.                                                                |
| `fullscreenBelow` | `Breakpoint\|string\|null` | `null`  | `md` renders `md-down:dialog-fullscreen`.                                                      |
| `headerOptions`   | `array`                    | `[]`    | Attributes of `.dialog-header`.                                                                |
| `instant`         | `bool`                     | `false` | Disable the animations (`.dialog-instant`).                                                    |
| `keyboard`        | `bool`                     | `true`  | Close with Escape; `false` renders `data-bs-keyboard="false"`.                                 |
| `nonModal`        | `bool`                     | `false` | Non-modal dialog (`data-bs-modal="false"`).                                                    |
| `scrollable`      | `bool`                     | `false` | Scroll the body only (`.dialog-scrollable`).                                                   |
| `size`            | `DialogSize\|string\|null` | `null`  | Size class.                                                                                    |
| `slide`           | `string\|null`             | `null`  | `up` or `down` (`.dialog-slide-{value}`).                                                      |
| `title`           | `string\|Stringable\|null` | `null`  | `.dialog-title`, referenced by `aria-labelledby` unless `aria-label`/`aria-labelledby` is set. |
| `titleOptions`    | `array`                    | `[]`    | Title attributes; `tag` defaults to `h1`, `id` to `{id}-label`.                                |
| `toggleButton`    | `array\|false`             | `false` | Toggle button with `label` (translated `Show`), `encode`, and `tag`.                           |

### Drawer

Renders a native `<dialog class="drawer">`. Content captured between `begin()` and `end()` goes into `.drawer-body`.

| Property        | Type                            | Default                  | Description                                                                                    |
| --------------- | ------------------------------- | ------------------------ | ---------------------------------------------------------------------------------------------- |
| `backdrop`      | `bool\|string`                  | `true`                   | Same values as `Dialog::$backdrop`.                                                            |
| `bodyOptions`   | `array`                         | `[]`                     | Attributes of `.drawer-body`.                                                                  |
| `closeButton`   | `array\|false`                  | `[]`                     | Close button attributes; `false` omits it.                                                     |
| `encodeTitle`   | `bool`                          | `true`                   | HTML-encode `title`.                                                                           |
| `footer`        | `string\|Stringable\|null`      | `null`                   | Content of `.drawer-footer` (not encoded).                                                     |
| `footerOptions` | `array`                         | `[]`                     | Attributes of `.drawer-footer`.                                                                |
| `fullscreen`    | `bool`                          | `false`                  | Cover the viewport; replaces the placement class.                                              |
| `headerOptions` | `array`                         | `[]`                     | Attributes of `.drawer-header`.                                                                |
| `keyboard`      | `bool`                          | `true`                   | Close with Escape.                                                                             |
| `placement`     | `DrawerPlacement\|string\|null` | `DrawerPlacement::START` | Placement class; `null` omits it.                                                              |
| `responsive`    | `Breakpoint\|string\|null`      | `null`                   | Render inline from the breakpoint up (`lg:drawer`).                                            |
| `scroll`        | `bool`                          | `false`                  | Keep the page scrollable (`data-bs-scroll="true"`).                                            |
| `sheet`         | `bool`                          | `false`                  | Flush against the viewport edge (`.drawer-sheet`).                                             |
| `title`         | `string\|Stringable\|null`      | `null`                   | `.drawer-title`, referenced by `aria-labelledby` unless `aria-label`/`aria-labelledby` is set. |
| `titleOptions`  | `array`                         | `[]`                     | Title attributes; `tag` defaults to `h5`.                                                      |
| `toggleButton`  | `array\|false`                  | `false`                  | Toggle button, same keys as `Dialog::$toggleButton`.                                           |

### LinkPager

`options` holds the container attributes; its `tag` key defaults to `nav`. Register the class as the definition of
`yii\widgets\LinkPager` in the DI container to use it in every `GridView` and `ListView`.

| Property                        | Type                  | Default                                   | Description                                            |
| ------------------------------- | --------------------- | ----------------------------------------- | ------------------------------------------------------ |
| `activePageCssClass`            | `string`              | `active`                                  | Class of the current page item.                        |
| `disableCurrentPageButton`      | `bool`                | `false`                                   | Render the current page as disabled.                   |
| `disabledListItemSubTagOptions` | `array`               | `[]`                                      | Attributes merged into disabled links.                 |
| `disabledPageCssClass`          | `string`              | `disabled`                                | Class of disabled items.                               |
| `firstPageCssClass`             | `string`              | `first`                                   | Class of the first page item.                          |
| `firstPageLabel`                | `bool\|string`        | `false`                                   | Label (not encoded), `true` for `1`, `false` omits it. |
| `hideOnSinglePage`              | `bool`                | `true`                                    | Render nothing for a single page.                      |
| `lastPageCssClass`              | `string`              | `last`                                    | Class of the last page item.                           |
| `lastPageLabel`                 | `bool\|string`        | `false`                                   | Label, `true` for the page count, `false` omits it.    |
| `linkContainerOptions`          | `array`               | `['class' => ['page-item']]`              | Item attributes; `tag` defaults to `li`.               |
| `linkOptions`                   | `array`               | `['class' => ['page-link']]`              | Link attributes.                                       |
| `listOptions`                   | `array`               | `['class' => ['pagination']]`             | List attributes; `tag` defaults to `ul`.               |
| `maxButtonCount`                | `int`                 | `10`                                      | Maximum number of page buttons.                        |
| `nextPageCssClass`              | `string`              | `next`                                    | Class of the next page item.                           |
| `nextPageLabel`                 | `false\|string`       | `<span aria-hidden="true">&raquo;</span>` | Label (not encoded), `false` omits it.                 |
| `pageCssClass`                  | `string`              | `page-item`                               | Class of the page items.                               |
| `pagination`                    | `Pagination\|null`    | `null`                                    | Required pagination object.                            |
| `prevPageCssClass`              | `string`              | `prev`                                    | Class of the previous page item.                       |
| `prevPageLabel`                 | `false\|string`       | `<span aria-hidden="true">&laquo;</span>` | Label (not encoded), `false` omits it.                 |
| `registerLinkTags`              | `bool`                | `false`                                   | Register `<link rel="...">` head tags.                 |
| `size`                          | `Size\|string\|null`  | `null`                                    | `pagination-{value}`.                                  |
| `theme`                         | `Theme\|string\|null` | `null`                                    | Theme color of the list.                               |

### Menu

Renders only the `.menu` panel; place it right after the element that carries `data-bs-toggle="menu"`, or use
`ButtonMenu` or `Nav`.

| Property         | Type    | Default | Description                                                         |
| ---------------- | ------- | ------- | ------------------------------------------------------------------- |
| `encodeLabels`   | `bool`  | `true`  | HTML-encode labels, headers, and texts; item `encode` overrides it. |
| `items`          | `array` | `[]`    | Items, see below; the string `-` renders a divider.                 |
| `submenuOptions` | `array` | `[]`    | Attributes of every nested `.menu`.                                 |

Item keys: `label`, `url` (without it the item is a `<button>`), `active`, `disabled`, `visible`, `encode`,
`linkOptions`, `items` (nested submenu), `options`, `submenuOptions`, `header` (`h6.menu-header`), `text`
(`span.menu-text`), and `divider` (`hr.menu-divider`).

Value objects: `MenuItem::link($label, $url)`, `MenuItem::button($label)`, `MenuItem::submenu($label, array $items)`,
`MenuItem::header($content)`, `MenuItem::text($content)`, and `MenuItem::divider()`, then `withActive()`,
`withDisabled()`, `withVisible()`, `withEncode()`, `withLinkOptions()`, `withOptions()`, `withSubmenuOptions()`,
`withItems()`, `withUrl()`, `withLabel()`, and `withType()`. The `type` property (`MenuItemType`) holds the kind;
header and text content lives in `label`. Example: `'items' => [MenuItem::header('Account'),
MenuItem::link('Profile', ['user/profile']), '-', MenuItem::button('Sign out')->withDisabled(true)]`.

### Nav

| Property          | Type                      | Default       | Description                                                                                                |
| ----------------- | ------------------------- | ------------- | ---------------------------------------------------------------------------------------------------------- |
| `activateItems`   | `bool`                    | `true`        | Activate items whose route and parameters match.                                                           |
| `activateParents` | `bool`                    | `false`       | Activate a parent when one of its menu items is active.                                                    |
| `encodeLabels`    | `bool`                    | `true`        | HTML-encode the labels; item `encode` overrides it.                                                        |
| `items`           | `array`                   | `[]`          | Items, see below; strings are rendered as is.                                                              |
| `layout`          | `NavLayout\|string\|null` | `null`        | Layout class.                                                                                              |
| `menuClass`       | `class-string<Menu>`      | `Menu::class` | Menu widget class.                                                                                         |
| `params`          | `array\|null`             | `null`        | Parameters for active detection, compared by value (scalars as strings); `null` uses the query parameters. |
| `route`           | `string\|null`            | `null`        | Route for active detection; `null` uses the current route.                                                 |
| `style`           | `NavStyle\|string\|null`  | `null`        | Style class; `PILLS_VERTICAL` also adds `nav-pills`.                                                       |

Item keys: `label` (required), `url` (default `#`; `null` renders no `href`), `active`, `disabled`, `visible`,
`encode`, `linkOptions`, `options`, `items` (`Menu` items or menu HTML), and `menuOptions` (attributes of the `.menu`
panel).

Value objects: `NavItem::to($label, $url = '#')` and `NavItem::menu($label, array|string $items)`, whose `$items` are
`MenuItem` objects and strings, or the menu HTML, then `withActive()`, `withDisabled()`, `withVisible()`,
`withEncode()`, `withOptions()`, `withLinkOptions()`, `withMenuOptions()`, `withItems()`, `withUrl()`, and
`withLabel()`. An `active` of `null` detects the state from the `url` route; `false` turns the detection off for that
item. Example: `'items' => [NavItem::to('Home', ['site/index']), NavItem::menu('More',
[MenuItem::link('About', ['site/about'])])]`.

### NavBar

`options` accepts a `tag` key (default `nav`). Content captured between `begin()` and `end()` is rendered inside the
`.drawer-body`, or directly in the navbar when `drawerOptions` is `false`.

| Property                 | Type                              | Default                                                        | Description                                                                                                                                                                           |
| ------------------------ | --------------------------------- | -------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `brandImage`             | `string\|false`                   | `false`                                                        | Brand image source.                                                                                                                                                                   |
| `brandImageOptions`      | `array`                           | `[]`                                                           | Brand image attributes.                                                                                                                                                               |
| `brandLabel`             | `string\|Stringable\|false`       | `false`                                                        | Brand text.                                                                                                                                                                           |
| `brandOptions`           | `array`                           | `[]`                                                           | Attributes of `.navbar-brand`.                                                                                                                                                        |
| `brandUrl`               | `array\|string\|false\|null`      | `false`                                                        | `false` uses `homeUrl`, `null` renders a `<span>`.                                                                                                                                    |
| `drawerOptions`          | `array\|false`                    | `[]`                                                           | Drawer keys `title` (translated `Menu`), `placement` (default `DrawerPlacement::END`), `options` (`id` defaults to `{id}-drawer`), `bodyOptions`; `false` renders the content inline. |
| `encodeBrandLabel`       | `bool`                            | `true`                                                         | HTML-encode `brandLabel`.                                                                                                                                                             |
| `expand`                 | `Breakpoint\|string\|false\|null` | `Breakpoint::LG`                                               | `lg:navbar-expand`; `null` always expands, `false` never.                                                                                                                             |
| `innerContainerOptions`  | `array`                           | `[]`                                                           | Inner container attributes; class `container-fluid` by default.                                                                                                                       |
| `renderInnerContainer`   | `bool`                            | `true`                                                         | Wrap the content in the inner container.                                                                                                                                              |
| `screenReaderToggleText` | `string\|Stringable\|null`        | `null`                                                         | Toggler label; `null` uses `Toggle navigation`.                                                                                                                                       |
| `togglerContent`         | `string\|Stringable`              | `<span class="navbar-toggler-icon" aria-hidden="true"></span>` | Toggler HTML.                                                                                                                                                                         |
| `togglerOptions`         | `array`                           | `[]`                                                           | Toggler attributes.                                                                                                                                                                   |

### NavOverflow

Wraps a `Nav` in `.nav-overflow`; items that do not fit move into a "More" menu. When `clientOptions` is a non-empty
array, `data-bs-toggle="nav-overflow"` is omitted and the plugin is created with those options instead.

| Property        | Type                            | Default      | Description                                            |
| --------------- | ------------------------------- | ------------ | ------------------------------------------------------ |
| `collapseBelow` | `Breakpoint\|int\|string\|null` | `null`       | `data-bs-collapse-below`: breakpoint or pixels.        |
| `moreText`      | `string\|false\|null`           | `null`       | Toggle text; `false` renders an icon-only toggle.      |
| `nav`           | `array`                         | `[]`         | `Nav` configuration.                                   |
| `navClass`      | `class-string<Nav>`             | `Nav::class` | Nav widget class.                                      |
| `placement`     | `Placement\|string\|null`       | `null`       | `data-bs-menu-placement`; plugin default `bottom-end`. |

### OtpInput

`options` applies to the `.otp-input` field inside the `.otp` container.

| Property           | Type           | Default | Description                                            |
| ------------------ | -------------- | ------- | ------------------------------------------------------ |
| `containerOptions` | `array`        | `[]`    | Container attributes; its `id` defaults to `{id}-otp`. |
| `groups`           | `list<int>`    | `[]`    | Group sizes, for example `[3, 3]`.                     |
| `length`           | `int`          | `6`     | Number of slots (`maxlength`).                         |
| `mask`             | `bool\|null`   | `null`  | Mask the characters (plugin default `false`).          |
| `separator`        | `string\|null` | `null`  | Character between groups (plugin default `·`).         |
| `type`             | `string\|null` | `null`  | `numeric`, `alphanumeric`, or `alpha`.                 |

### Placeholder

| Property           | Type                  | Default | Description                                                      |
| ------------------ | --------------------- | ------- | ---------------------------------------------------------------- |
| `animation`        | `string\|null`        | `null`  | `wave`, `pulse`, or `glow`; `pulse` and `glow` need `container`. |
| `container`        | `bool`                | `false` | Wrap the placeholder in an element that carries the animation.   |
| `containerOptions` | `array`               | `[]`    | Container attributes; `tag` defaults to `p`.                     |
| `size`             | `Size\|string\|null`  | `null`  | `placeholder-{value}`.                                           |
| `tagName`          | `string`              | `span`  | Tag name.                                                        |
| `theme`            | `Theme\|string\|null` | `null`  | Background utility `bg-{value}`.                                 |
| `width`            | `int\|string\|null`   | `null`  | Column count (`col-{value}`) or a width class such as `w-75`.    |

### Popover and Tooltip

Content captured between `begin()` and `end()` is the trigger, wrapped in an element with `data-bs-toggle`. Bootstrap
initializes both plugins on first interaction.

| Property      | Type                       | Default | Description                                            |
| ------------- | -------------------------- | ------- | ------------------------------------------------------ |
| `content`     | `string\|Stringable\|null` | `null`  | `Popover` only: body (`data-bs-content`).              |
| `customClass` | `string\|null`             | `null`  | `data-bs-custom-class`.                                |
| `html`        | `bool`                     | `false` | Render the text as HTML (`data-bs-html="true"`).       |
| `placement`   | `Placement\|string\|null`  | `null`  | `data-bs-placement`, also responsive (`top md:right`). |
| `tagName`     | `string`                   | `span`  | Tag name of the wrapping element.                      |
| `title`       | `string\|Stringable\|null` | `null`  | `data-bs-title`.                                       |
| `trigger`     | `string\|null`             | `null`  | `click`, `hover`, `focus`, `manual`, space-separated.  |

### Progress

| Property      | Type                  | Default | Description                                                                                                  |
| ------------- | --------------------- | ------- | ------------------------------------------------------------------------------------------------------------ |
| `animated`    | `bool`                | `false` | Animate the stripes (`progress-bar-animated`).                                                               |
| `ariaLabel`   | `string\|null`        | `null`  | `aria-label` of the progress bar.                                                                            |
| `barOptions`  | `array`               | `[]`    | Attributes of `.progress-bar`.                                                                               |
| `bars`        | `array`               | `[]`    | Stacked segments with `percent` (required), `label`, `theme`, `striped`, `animated`, `ariaLabel`, `options`. |
| `encodeLabel` | `bool`                | `true`  | HTML-encode the labels.                                                                                      |
| `label`       | `string\|Stringable`  | `''`    | Bar label.                                                                                                   |
| `max`         | `float\|int`          | `100`   | `aria-valuemax`; must be greater than `min`.                                                                 |
| `min`         | `float\|int`          | `0`     | `aria-valuemin`.                                                                                             |
| `percent`     | `float\|int`          | `0`     | Current value between `min` and `max`.                                                                       |
| `striped`     | `bool`                | `false` | Stripes (`progress-bar-striped`).                                                                            |
| `theme`       | `Theme\|string\|null` | `null`  | Theme color of the bar.                                                                                      |

Value objects: `ProgressBar::to($percent, $label = '')`, then `withTheme()`, `withStriped()`, `withAnimated()`,
`withAriaLabel()`, and `withOptions()` (`.progress-bar` attributes); a `theme` string is validated when the object is
created, and `percent` is checked against `min` and `max` when the widget renders it. Example: `'bars' =>
[ProgressBar::to(30)->withTheme(Theme::PRIMARY), ProgressBar::to(20)->withTheme(Theme::WARNING)]`.

### Range

`options` applies to the `.form-range-input`; the `.form-range` wrapper receives the plugin attributes.

| Property           | Type                               | Default | Description                                              |
| ------------------ | ---------------------------------- | ------- | -------------------------------------------------------- |
| `bubble`           | `bool`                             | `false` | Show the value bubble (`data-bs-bubble`).                |
| `containerOptions` | `array`                            | `[]`    | Wrapper attributes; its `id` defaults to `{id}-range`.   |
| `max`              | `float\|int\|null`                 | `null`  | Maximum value.                                           |
| `min`              | `float\|int\|null`                 | `null`  | Minimum value.                                           |
| `step`             | `float\|int\|string\|null`         | `null`  | Step, a number or `any`.                                 |
| `ticks`            | `array<int\|string, string\|null>` | `[]`    | Tick marks rendered in a `<datalist>`, `value => label`. |

### Spinner

| Property       | Type                       | Default  | Description                                                  |
| -------------- | -------------------------- | -------- | ------------------------------------------------------------ |
| `encodeLabel`  | `bool`                     | `true`   | HTML-encode `label`.                                         |
| `label`        | `string\|Stringable\|null` | `null`   | Hidden status text; `null` uses `Loading...`, `''` omits it. |
| `labelOptions` | `array`                    | `[]`     | Attributes of the `.visually-hidden` label.                  |
| `small`        | `bool`                     | `false`  | Small spinner (`spinner-sm`).                                |
| `tagName`      | `string`                   | `div`    | Tag name.                                                    |
| `theme`        | `Theme\|string\|null`      | `null`   | Text color utility `fg-{value}`.                             |
| `type`         | `string`                   | `border` | `Spinner::TYPE_BORDER` or `Spinner::TYPE_GROW`.              |

### Stepper

`items` must not be empty and every item needs a `label`.

| Property       | Type                       | Default | Description                                                                       |
| -------------- | -------------------------- | ------- | --------------------------------------------------------------------------------- |
| `encodeLabels` | `bool`                     | `true`  | HTML-encode labels and descriptions; item `encode` overrides it.                  |
| `horizontal`   | `Breakpoint\|bool\|string` | `false` | `true` for every width, or a breakpoint (wrapped in `.contains-inline`).          |
| `items`        | `list<array>`              | `[]`    | Steps with `label`, `description`, `active`, `theme`, `url`, `encode`, `options`. |

Value objects: `StepItem::to($label, $description = null)`, then `withActive()`, `withTheme()`, `withUrl()`,
`withEncode()`, and `withOptions()`; a step with a non-`null` `url` renders the stepper as links, and a `theme` string
is validated when the object is created. Example: `'items' => [StepItem::to('Cart')->withActive(true),
StepItem::to('Payment')->withUrl(['/checkout/payment'])]`.

### Strength

Without `input` the plugin evaluates the first password input of the parent element, so render the widget next to it.

| Property    | Type                    | Default | Description                                                            |
| ----------- | ----------------------- | ------- | ---------------------------------------------------------------------- |
| `input`     | `string\|null`          | `null`  | Selector of the password input (`data-bs-input`).                      |
| `messages`  | `array<string, string>` | `[]`    | Text per level `weak`, `fair`, `good`, `strong`; passed to the plugin. |
| `minLength` | `int\|null`             | `null`  | `data-bs-min-length` (plugin default `8`).                             |
| `segments`  | `bool`                  | `true`  | Four `.strength-segment` elements; `false` renders `.strength-bar`.    |
| `showText`  | `bool`                  | `false` | Render the `.strength-text` element.                                   |

### Tabs

When no item is active, the first visible, enabled pane is activated.

| Property            | Type                 | Default          | Description                                               |
| ------------------- | -------------------- | ---------------- | --------------------------------------------------------- |
| `encodeLabels`      | `bool`               | `true`           | HTML-encode the labels; item `encode` overrides it.       |
| `headerOptions`     | `array`              | `[]`             | Attributes of every `.nav-item`.                          |
| `itemOptions`       | `array`              | `[]`             | Attributes of every `.tab-pane`; `tag` defaults to `div`. |
| `items`             | `array`              | `[]`             | Items, see below.                                         |
| `linkOptions`       | `array`              | `[]`             | Attributes of every tab button.                           |
| `menuClass`         | `class-string<Menu>` | `Menu::class`    | Menu widget class for items with `items`.                 |
| `navType`           | `NavStyle\|string`   | `NavStyle::TABS` | Nav style; `PILLS_VERTICAL` adds `aria-orientation`.      |
| `renderTabContent`  | `bool`               | `true`           | Render `.tab-content` with the panes.                     |
| `tabContentOptions` | `array`              | `[]`             | Attributes of `.tab-content`.                             |

Item keys: `label` (required), `content`, `active`, `disabled`, `visible`, `encode`, `headerOptions`, `linkOptions`
(`id` defaults to `{id}-tab{n}`), `options` (`id` defaults to `{id}-pane{n}`), `url` (plain link that takes `active` and
`disabled`), and `items` (tabs inside a `Menu`; nested arrays with a `url` key, even `null`, or without a `label` are
`Menu` items, a nested item with `items` renders as a `.submenu` wrapped by its `headerOptions`, an active nested tab or
`MenuItem` activates the outer toggle only, an `active` toggle renders active even when none of its items is, and a
`disabled` toggle renders with the `disabled` attribute and is never active).

Value objects: `TabItem::to($label, $content = null)`, `TabItem::link($label, $url)`, and `TabItem::menu($label, array
$items)`, whose `$items` are `TabItem` objects (menu buttons with their panes, menu links for `TabItem::link()`,
`.submenu` panels for a nested `TabItem::menu()`), `MenuItem` objects, and strings, then `withActive()`,
`withDisabled()`, `withVisible()`, `withEncode()`, `withHeaderOptions()`, `withLinkOptions()`, `withOptions()`,
`withContent()`, `withUrl()`, `withItems()`, and `withLabel()`. An `active` of `null` lets the widget activate the first
eligible tab. Example: `'items' => [TabItem::to('Home', $home), TabItem::to('Profile', $profile)->withDisabled(true)]`.

### Toast

The plugin is always initialized, but the toast stays hidden until `show()` is called on the instance (see the
[examples](examples.md#toast)) or the `show` class is added through `options`.

| Property          | Type                                   | Default | Description                                               |
| ----------------- | -------------------------------------- | ------- | --------------------------------------------------------- |
| `body`            | `string\|Stringable\|null`             | `null`  | Content appended to `.toast-body` (not encoded).          |
| `bodyOptions`     | `array`                                | `[]`    | Attributes of `.toast-body`.                              |
| `closeButton`     | `array\|false`                         | `[]`    | Close button attributes; `false` omits it.                |
| `dateTime`        | `DateTimeInterface\|int\|string\|null` | `null`  | Relative time in the header, for example `2 minutes ago`. |
| `dateTimeOptions` | `array`                                | `[]`    | Time attributes; `tag` defaults to `small`.               |
| `encodeTitle`     | `bool`                                 | `true`  | HTML-encode `title`.                                      |
| `headerOptions`   | `array`                                | `[]`    | Attributes of `.toast-header`.                            |
| `instant`         | `bool`                                 | `false` | Disable the fade (`.toast-instant`).                      |
| `title`           | `string\|Stringable\|null`             | `null`  | Header title.                                             |
| `titleOptions`    | `array`                                | `[]`    | Title attributes; `tag` defaults to `strong`.             |

### ToggleButtonGroup

Renders `<label class="btn-check ..."><input ...>Label</label>` items. With a model, a hidden input submits an empty
value when nothing is selected; the `unselect` key of `options` changes it, and the hidden input is disabled together
with the inputs when `inputOptions` carries `disabled`.

| Property       | Type                       | Default                  | Description                                       |
| -------------- | -------------------------- | ------------------------ | ------------------------------------------------- |
| `encodeLabels` | `bool`                     | `true`                   | HTML-encode the labels.                           |
| `inputOptions` | `array`                    | `[]`                     | Attributes of every input.                        |
| `items`        | `array<array-key, string>` | `[]`                     | Buttons as `value => label`.                      |
| `labelOptions` | `array`                    | `[]`                     | Attributes of every label.                        |
| `size`         | `Size\|string\|null`       | `null`                   | `btn-group-{value}`.                              |
| `theme`        | `Theme\|string\|null`      | `null`                   | Theme color of the buttons.                       |
| `type`         | `string`                   | `checkbox`               | `TYPE_CHECKBOX` (name gets `[]`) or `TYPE_RADIO`. |
| `variant`      | `ButtonVariant\|string`    | `ButtonVariant::OUTLINE` | Style class of the labels.                        |

### Toggler

| Property      | Type                 | Default  | Description                                                        |
| ------------- | -------------------- | -------- | ------------------------------------------------------------------ |
| `attribute`   | `string`             | `class`  | Attribute toggled (`data-bs-attribute`), rendered with `value`.    |
| `encodeLabel` | `bool`               | `true`   | HTML-encode `label`.                                               |
| `label`       | `string\|Stringable` | `''`     | Content of the element.                                            |
| `tagName`     | `string`             | `button` | Tag name; `button` receives `type="button"`.                       |
| `target`      | `string\|null`       | `null`   | Selector of the toggled elements; they carry their own attributes. |
| `value`       | `string\|null`       | `null`   | Value toggled (`data-bs-value`), for example a class name.         |

## Forms

### ActiveForm

| Property               | Type                 | Default              | Description                                                                                |
| ---------------------- | -------------------- | -------------------- | ------------------------------------------------------------------------------------------ |
| `errorCssClass`        | `string`             | `is-invalid`         | Class added to invalid controls.                                                           |
| `errorSummaryCssClass` | `string`             | `alert theme-danger` | Class of the error summary.                                                                |
| `fieldClass`           | `string`             | `ActiveField::class` | Field class; it must extend `ActiveField`.                                                 |
| `layout`               | `string`             | `default`            | `LAYOUT_DEFAULT`, `LAYOUT_HORIZONTAL`, `LAYOUT_INLINE`, or `LAYOUT_FLOATING`.              |
| `nativeValidation`     | `bool\|string\|null` | `null`               | `true` renders `data-bs-validate`, `'valid'` also styles valid fields, `false` removes it. |
| `successCssClass`      | `string`             | `is-valid`           | Class added to valid controls.                                                             |
| `validationStateOn`    | `string`             | `input`              | The control receives the validation classes.                                               |

`nativeValidation` opts the form in to the Bootstrap `:user-invalid` styling, which replaces the v5 `.was-validated`
class. Server-side errors keep using `is-invalid`.

### Layouts

| Layout       | Field markup                                                                                                                                      |
| ------------ | ------------------------------------------------------------------------------------------------------------------------------------------------- |
| `default`    | `div.form-field.mb-3` with label, control, hint (`.form-text`), and error (`.invalid-feedback`) stacked.                                          |
| `horizontal` | `div.row.mb-3` with `label.sm:col-2.col-form-label` and `div.sm:col-10` wrapping control, hint, and error.                                        |
| `inline`     | The form gets `row lg:row-cols-auto g-3 align-items-center`; fields are `div.col-12` with a `visually-hidden` label, a placeholder, and no error. |
| `floating`   | `div.form-field.mb-3` with `div.form-floating` holding the label before the control, then hint and error; the wrapper receives `is-invalid`.      |

Checkboxes, radios, and switches render `.check`, `.radio`, and `.switch` with a plain label; the `.form-field` grid
lays them out in two columns. Lists render one `.form-field` per item. In the floating layout, lists use the stacked
template because they cannot float, and a CSS `:has()` rule registered once per form displays the feedback on the
client, so no JavaScript is involved.

### ActiveField

| Property                  | Type           | Default                                                            | Description                                            |
| ------------------------- | -------------- | ------------------------------------------------------------------ | ------------------------------------------------------ |
| `checkEnclosedTemplate`   | `string`       | `{beginLabel}\n{input}\n{labelTitle}\n{endLabel}\n{hint}\n{error}` | Checkbox or radio inside its label.                    |
| `checkHorizontalTemplate` | `string`       | `{beginWrapper}` + `div.form-field` + `{endWrapper}`               | Checkbox or radio in the horizontal layout.            |
| `checkOptions`            | `array`        | `[]`                                                               | Default options of `checkbox()` and list items.        |
| `checkTemplate`           | `string`       | `{input}\n{label}\n{hint}\n{error}`                                | Checkbox or radio template.                            |
| `enableError`             | `bool`         | `true`                                                             | Render the error element (`false` in inline layout).   |
| `enableLabel`             | `bool`         | `true`                                                             | Render the label.                                      |
| `errorOptions`            | `array`        | `['class' => 'invalid-feedback']`                                  | Error attributes.                                      |
| `hintOptions`             | `array`        | `['class' => 'form-text']`                                         | Hint attributes.                                       |
| `horizontalCssClasses`    | `array`        | see below                                                          | Grid classes of the horizontal layout.                 |
| `inline`                  | `bool`         | `false`                                                            | Lay out list items horizontally.                       |
| `inputOptions`            | `array`        | `['class' => 'form-control']`                                      | Control attributes.                                    |
| `inputTemplate`           | `string\|null` | `null`                                                             | Template around `{input}`, for example an input group. |
| `labelOptions`            | `array`        | `['class' => 'form-label']`                                        | Label attributes.                                      |
| `options`                 | `array`        | `['class' => 'form-field mb-3']`                                   | Field container attributes.                            |
| `radioOptions`            | `array`        | `[]`                                                               | Default options of `radio()` and list items.           |
| `wrapperOptions`          | `array`        | `[]`                                                               | `{beginWrapper}` attributes; `tag` defaults to `div`.  |

`horizontalCssClasses` keys and defaults: `field` (`row mb-3`), `label` (`sm:col-2 col-form-label`), `wrapper`
(`sm:col-10`), `offset` (`sm:col-10 sm:offset-2`, for fields without label), `error`, and `hint` (empty). Configure
them through `fieldConfig`; list values replace the defaults.

Besides the core placeholders, templates accept `{beginLabel}`, `{labelTitle}`, `{endLabel}`, `{beginWrapper}`, and
`{endWrapper}`. Additional field methods:

| Method                                   | Description                                                                       |
| ---------------------------------------- | --------------------------------------------------------------------------------- |
| `checkbox(['switch' => true])`           | Renders a switch; `switchOptions` sets the `.switch` wrapper attributes.          |
| `checkbox($options, true)`, `radio(...)` | Renders the control inside its label (`checkEnclosedTemplate`).                   |
| `inline(bool $value = true)`             | Lays out the next `checkboxList()` or `radioList()` horizontally.                 |
| `colorInput(array $options = [])`        | Color input with `form-control form-control-color`.                               |
| `rangeInput(array $options = [])`        | Range input in `.form-range`; `rangeOptions` sets the wrapper attributes.         |
| `staticControl(array $options = [])`     | Read-only `.form-control-plaintext` with the attribute value.                     |
| `dropDownList()`, `listBox()`            | Keep `form-control` when a per-call `class` is given; no inherited `placeholder`. |

## HTML helpers

`Html` extends `yii\helpers\Html` and renders Bootstrap 6 classes:

| Method                                                                         | Description                                                                                                                            |
| ------------------------------------------------------------------------------ | -------------------------------------------------------------------------------------------------------------------------------------- |
| `addCssClass(array &$options, $class)`                                         | Yii behavior; enum cases and `Stringable` values, also inside arrays, become `string`.                                                 |
| `badge(string\|Stringable $label, Theme\|string\|null $theme, array $options)` | `<span class="badge theme-*">`; the `encode` option defaults to `true`.                                                                |
| `closeButton(array $options = [])`                                             | `<button type="button" class="btn-close" aria-label="Close">`.                                                                         |
| `enumValue(string $enum, $value, string $property, bool $responsive = false)`  | Enum case or validated `string` as `string`; unknown strings throw `InvalidConfigException`.                                           |
| `escapeCssIdentifier(string $identifier)`                                      | Escapes an identifier for a CSS selector as `CSS.escape()` does; bytes from `0x80` up (UTF-8 characters) are kept.                     |
| `theme(array &$options, Theme\|string\|null $theme)`                           | Adds the `theme-{value}` class.                                                                                                        |
| `staticControl(string $value, array $options = [])`                            | Read-only `.form-control-plaintext` input.                                                                                             |
| `activeStaticControl($model, $attribute, $options)`                            | Same for a model attribute; the `value` option overrides it.                                                                           |
| `checkbox()`, `radio()`, `activeCheckbox()`, `activeRadio()`                   | `.check` or `.radio` input; `switch`, `switchOptions`, and `wrapInput` options.                                                        |
| `checkboxList()`, `radioList()`                                                | One `.form-field` per item in `.form-group`; `inline` uses `d-flex flex-wrap gap-5`; `itemOptions['wrapperOptions']` styles each item. |
| `error()`                                                                      | Defaults the class to `invalid-feedback`.                                                                                              |

## Next steps

- 📚 [Installation Guide](installation.md)
- 💡 [Usage Examples](examples.md)
- 🔄 [Migration Guide](migration.md)
- 🧪 [Testing Guide](testing.md)
