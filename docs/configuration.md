# Configuration guide

Use this guide to choose widget behavior and combine options in a Yii view. The
[generated API](https://yii2-framework.github.io/docs/extensions/bootstrap6/reference/) is the source for property
names, types, defaults, item constructors, and method signatures. The [gallery](examples.md) contains runnable view
examples. Classes live in `yii2\extensions\bootstrap6`, with `enum`, `item`, and `asset` subnamespaces.

## Asset bundles

Widgets register their own assets. For a layout that also needs Bootstrap on pages without widgets, register
`BootstrapPluginAsset`; it includes the CSS bundle. Configure application-wide delivery through
`components.assetManager.bundles`, or call `registerWith($view, cdn: true)` for a page. Existing asset-manager
configuration takes precedence. See [installation](installation.md#load-the-assets) for aliases, CDN configuration,
minification, and optional Bootstrap Icons.

Bootstrap's JavaScript is an ES module, with no `window.bootstrap` by default. For custom plugin code, capture the
registered bundle and use its **instance** helper. It imports the same URL as the module script tag:

```php
use yii2\extensions\bootstrap6\asset\BootstrapPluginAsset;

$bundle = BootstrapPluginAsset::register($this);
$this->registerJs($bundle->importJs(
    $this,
    'bootstrap.Toast.getOrCreateInstance(document.getElementById("notice")).show();',
));
```

`importJs()` and `moduleUrl()` are instance methods. For a temporary global during migration, set `exposeGlobal` on this
bundle and account for its asynchronous assignment; prefer `importJs()` for new scripts.

## Translations

The package bootstrap registers `yii.bootstrap6` translations automatically when Yii loads Composer extensions. If an
application disables extension loading, [register the bootstrap](installation.md#register-the-bootstrap). Override the
category in `components.i18n.translations` to replace widget labels; the bootstrap preserves an existing configuration.
Changing `language` selects the appropriate translation; source messages use `en-US`.

## Common widget properties

Put HTML attributes in `options`, plugin constructor settings in `clientOptions`, and event handlers in `clientEvents`.
Input widgets also support Yii's `model`/`attribute` or `name`/`value` modes. A widget's HTML `options` may address an
input, container, or trigger; the notes below identify the distinctions that matter.

Labels and titles are encoded by default. Body, footer, tab/accordion/slide content, and content echoed between
`begin()` and `end()` are HTML. Encode user-controlled text with the package's `Html::encode()` before putting it in
those slots. Disable label encoding only for trusted markup.

### Fluent attribute methods

For a configured instance, use `addClass()` to append classes or `class()` to replace user-supplied classes; the widget
still adds its structural classes when rendering. Attribute methods mutate the widget and return the same instance.
`widget()` already returns HTML, so cannot be chained:

```php
use yii2\extensions\bootstrap6\Alert;

$notice = new Alert(['body' => 'Saved.']);
echo $notice->addClass('mb-5')->attribute('data-status', 'saved')->run();
```

For `Dialog`, `Drawer`, and `Collapse`, the toggler attribute methods enable the toggle button if it was omitted.
Supplying a toggler class replaces its default button style, so include both a variant and a theme:

```php
use yii2\extensions\bootstrap6\Dialog;

Dialog::begin(['title' => 'Confirm'])
    ->addClass('shadow-lg')
    ->addTogglerClass('btn-outline', 'theme-danger');
echo '<p>Review your changes before continuing.</p>';
Dialog::end();
```

`ButtonMenu` uses `options` for its trigger: use the ordinary attribute methods there. Input widgets' `attribute`
property still identifies the model field; their `attribute()` method sets an HTML attribute.

### Plugin initialization

Widgets emit `data-bs-*` attributes, and a non-empty `clientOptions` array requests explicit initialization through the
ES module. `Chips`, `Combobox`, `NavOverflow`, `OtpInput`, `Range`, `Strength`, and inline or preselected `Datepicker`
widgets also initialize with empty options so they work in fragments inserted after page load. `Carousel` and `Toast`
initialize with empty options too; `Button` initializes when `clientOptions` is an array. Set `clientOptions` to `false`
to skip explicit initialization; rendered data attributes can still activate the Bootstrap data API.

`clientEvents` handlers use `addEventListener` independently of `clientOptions`. Menu options and events belong on
`ButtonMenu` or the trigger carrying `data-bs-toggle="menu"`, not on a standalone menu panel. Menu and tab events
bubble, allowing `Nav` and `Tabs` to listen on their lists. `NavBar` uses the drawer data API and ignores these plugin
properties; CSS-only widgets ignore them too. See [JavaScript recipes](examples.md#javascript) for handlers and custom
plugin access.

### Begin and end widgets

Call matching `begin()` and `end()` as statements in a view or layout: both return widget instances, and `end()` renders
and echoes the captured HTML. `Alert`, `Collapse`, `Dialog`, `Drawer`, `NavBar`, `Toast`, `Popover`, and `Tooltip`
support this pattern. For `Popover` and `Tooltip`, the captured content is the **trigger**, not the popup body. Keep
rendering inside a view's output buffer, including under worker runtimes.

## Icons

Use the DI container to replace the icons of `Accordion` and `Combobox` throughout the application:

```php
use UIAwesome\Html\Svg\Svg;
use yii2\extensions\bootstrap6\IconSet;

return [
    'container' => [
        'definitions' => [
            IconSet::class => ['accordionChevron' => Svg::icon('Flowbite:chevron-down')],
        ],
    ],
];
```

Widget-level `icon`, `caretIcon`, or `checkIcon` options override the shared icon. `Svg` instances receive structural
classes from the widgets; plain markup must supply `accordion-icon`, `combobox-caret`, or `menu-item-check` itself so
Bootstrap can size, rotate, and hide it. Optional font icons need `BootstrapIconAsset`.

## Enumerations

Prefer enum cases to strings when configuring themes, sizes, placement, and navigation style. Enum-typed properties also
accept a valid case value; unknown strings raise `InvalidConfigException`. The
[enum reference](https://yii2-framework.github.io/docs/extensions/bootstrap6/reference/) lists the available cases. Use
`Breakpoint::prefix()` for responsive classes rather than Bootstrap 5 suffixes such as `col-md-6`.

## Widgets

Choose a widget from the [gallery](examples.md#widgets), then consult its generated API only for the options needed. For
lists, prefer the immutable item objects, while retaining arrays where they are convenient. Each `with*()` on an item
returns a copy: assign or chain its result. This differs from the mutable widget attribute methods.

```php
use yii2\extensions\bootstrap6\Nav;
use yii2\extensions\bootstrap6\enum\NavStyle;
use yii2\extensions\bootstrap6\item\NavItem;

echo Nav::widget([
    'style' => NavStyle::PILLS,
    'items' => [
        NavItem::to('Home', ['/site/index']),
        NavItem::to('Coming soon')->withDisabled(true),
    ],
]);
```

Keep an item's `encode` unset to inherit the widget's label policy, and its `active` unset when the widget should choose
the active state. Objects and array configurations can be mixed, including nested menus.

### Accordion

Use it for expandable content without JavaScript: it renders native `<details>` elements. `autoCloseItems` groups them
so at most one item is open at a time; all items can be closed. Disable it for independently open sections. Encode user
text in item content. [Example](examples.md#accordion).

### Alert

Use `theme` for status instead of a v5 `alert-*` class. Content captured between `begin()` and `end()` precedes `body`;
encode user text in both. [Example](examples.md#alert).

### Avatar

Supply an image with meaningful alternative text, or use initials when no image is available. A status indicator can
carry its own accessible label. [Example](examples.md#avatar).

### Badge

Use it for a label or count; combine its shape and appearance options with a theme rather than supplying v5 color
classes. [Example](examples.md#badge).

### Breadcrumbs

Build route links followed by a current-page label; an empty `links` list renders nothing. Customize the divider without
rewriting the navigation wrapper. [Example](examples.md#breadcrumbs).

### Button

Choose `variant`, `theme`, and `size` for the appearance. Use `toggle` for pressed-state interaction; ordinary action
buttons do not need an explicit plugin instance. [Example](examples.md#button).

### ButtonGroup

Group related actions with an accessible group label in `options`; use `vertical` when stacking them.
[Example](examples.md#buttongroup).

### ButtonMenu

Configure menu items through `menu`. `options`, `clientOptions`, and `clientEvents` address the menu trigger;
`buttonOptions` addresses the separate action button in split mode. [Example](examples.md#buttonmenu).

### ButtonToolbar

Combine button groups rather than rebuilding each group's markup. [Example](examples.md#buttontoolbar).

### Carousel

Provide at least one slide. Use `autoplay` deliberately, and consider `playPause` for an automatically cycling carousel.
Put captions in slide content; v5 caption options are gone. [Example](examples.md#carousel).

### Chips

The text control edits chips while a synchronized hidden input submits their joined value. Validate that submitted
format on the server; the hidden input also follows the field's disabled state and form association.
[Example](examples.md#chips).

### Collapse

Use `toggleButton` for a self-contained disclosure, or provide an external trigger. Captured content becomes the
collapsible region. [Example](examples.md#collapse).

### Combobox

Supply a non-empty list and enable search for a large set. Multiple selections submit a comma-separated value; match the
server-side parsing and validation to that format. [Example](examples.md#combobox).

### Datepicker

Choose input mode when the submitted value should be the displayed locale-formatted text. Inline mode synchronizes a
hidden `YYYY-MM-DD` value, comma-separated for multiple dates. Validate the chosen format on the server.
[Example](examples.md#datepicker).

### Dialog

For a large dialog that stays open on backdrop click while only its body scrolls:

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

`backdrop: false` hides the backdrop; it does not mean "keep open on click". Escape still closes the dialog unless
`keyboard` is disabled. Supply a title or an accessible label, and encode user data echoed into the body or footer.
[More examples](examples.md#dialog).

### Drawer

Choose placement through `DrawerPlacement`. A responsive drawer becomes inline content above the selected breakpoint;
page scrolling and fullscreen display are separate choices. [Example](examples.md#drawer).

### LinkPager

For consistent pagination in every grid and list, define the package `LinkPager` as the DI replacement for
`yii\widgets\LinkPager`. Custom page labels contain HTML, so encode user text. [Example](examples.md#linkpager).

### Menu

Use `ButtonMenu` or `Nav` for a complete trigger/panel pair. Standalone `Menu` renders only the panel and must follow
its trigger; configure plugin options on that trigger. [Example](examples.md#menu).

### Nav

Use route arrays and leave `active` unset to let the widget detect the current route. Explicit active state is useful
when the navigation does not follow the current controller route. [Example](examples.md#nav).

### NavBar

Navigation collapses into a drawer. Set `drawerOptions` to `false` for content that should always render inline;
configure drawer behavior there rather than through the navbar's `clientOptions`. [Example](examples.md#navbar).

### NavOverflow

Use it when a navigation row should move excess links into a "More" menu instead of wrapping.
[Example](examples.md#navoverflow).

### OtpInput

Use the model/name binding for the submitted code and `groups` for presentation. `options` targets the input, while
`containerOptions` targets its visual slots. [Example](examples.md#otpinput).

### Placeholder

Use skeletons while content loads. `pulse` and `glow` animations require a container; `wave` can apply directly.
[Example](examples.md#placeholder).

### Popover and Tooltip

Capture the trigger between `begin()` and `end()`. Set a popover's `content` separately; keep `html` disabled for plain
text. [Popover example](examples.md#popover), [tooltip example](examples.md#tooltip).

### Progress

Use a descriptive accessible label, and keep values inside the configured bounds. Use `ProgressBar` items for stacked
segments. [Example](examples.md#progress).

### Range

`options` targets the input, while `containerOptions` targets the plugin wrapper. Supply meaningful tick labels when the
scale needs explanation. [Example](examples.md#range).

### Spinner

Keep a descriptive status label for assistive technology, especially when nearby text does not explain the pending
operation. [Example](examples.md#spinner).

### Stepper

Provide at least one step. Mark the current step explicitly and add URLs when users may revisit earlier steps.
[Example](examples.md#stepper).

### Strength

Render it beside the password input, or set `input` to a selector. Its client-side feedback does not replace server-side
password validation. [Example](examples.md#strength).

### Tabs

When no item is active, the first visible, enabled pane is activated. Keep label and content encoding separate; content
is HTML. [Example](examples.md#tabs).

### Toast

A toast initializes its plugin but stays hidden until `show()` is called. This view shows a dismissible notification
using the same ES module URL as the registered bundle:

```php
use yii2\extensions\bootstrap6\Toast;
use yii2\extensions\bootstrap6\asset\BootstrapPluginAsset;

echo Toast::widget([
    'options' => ['id' => 'notice'],
    'title' => 'Saved',
    'body' => 'Your changes have been saved.',
]);

$bundle = BootstrapPluginAsset::register($this);
$this->registerJs($bundle->importJs(
    $this,
    'bootstrap.Toast.getOrCreateInstance(document.getElementById("notice")).show();',
));
```

For a user-supplied body, pass `Html::encode($text)`. Use `dateTime: null` to omit the time; the v5 `false` value is not
accepted. [More examples](examples.md#toast).

### ToggleButtonGroup

Choose checkbox or radio behavior to match the model attribute. With a model, the hidden input submits an empty value
when nothing is selected; `options['unselect']` changes that value. [Example](examples.md#togglebuttongroup).

### Toggler

Use it to toggle a class or attribute on target elements. It is independent of the model `attribute` concept used by
input widgets. [Example](examples.md#toggler).

## Forms

### ActiveForm

Use this package's `ActiveForm` so its fields render Bootstrap 6 markup. Given a model with `email` and `notify`
attributes, this view uses a floating email field and a switch:

```php
use yii2\extensions\bootstrap6\ActiveForm;

$form = ActiveForm::begin(['layout' => ActiveForm::LAYOUT_FLOATING, 'nativeValidation' => true]);
echo $form->field($model, 'email')->textInput(['type' => 'email']);
echo $form->field($model, 'notify')->checkbox(['switch' => true]);
ActiveForm::end();
```

`nativeValidation` opts into Bootstrap's `:user-invalid` styling; `'valid'` also styles valid controls. Server-side
validation still uses `is-invalid`, and remains necessary regardless of the browser styling.

### Layouts

Choose stacked fields for general forms, horizontal fields for aligned labels, inline fields for short toolbars, and
floating fields for controls suited to floating labels. Use the package `LAYOUT_*` constants and customize individual
fields through `fieldConfig`. Floating checkbox/radio lists fall back to stacked markup; inline fields omit the
individual error elements. See the [form recipes](examples.md#forms) before choosing that tradeoff.

### ActiveField

Use `checkbox(['switch' => true])` for a switch. Set `inputTemplate` for an input group, and override the horizontal
label/wrapper grid through `horizontalCssClasses`. List values replace the corresponding default classes. A
caller-supplied class on a select retains `form-control`; select controls do not inherit an input placeholder.
[Field recipes](examples.md#select-range-static-control-and-input-group).

## HTML helpers

Use `yii2\extensions\bootstrap6\Html` when helper output must match Bootstrap 6 controls, badges, validation feedback,
or theme classes. It extends Yii's HTML helper, including `encode()`. Its generated API documents signatures;
[helper recipes](examples.md#html-helpers) show combinations.

[Back to documentation](../README.md#documentation)
