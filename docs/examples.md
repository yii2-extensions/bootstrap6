# Usage examples

Each snippet renders in a fresh view, so widget `id` values start at `w0`. Long inline SVG icons are shortened to
`<svg class="..." ...>...</svg>`, and asset URLs are shown as `/assets/<hash>/...`.

## Widgets

### Accordion

```php
use yii2\extensions\bootstrap6\Accordion;

echo Accordion::widget(
    [
        'items' => [
            ['label' => 'What is Yii?', 'content' => 'A high-performance PHP framework.', 'open' => true],
            'Is it free?' => 'Yes, under the BSD-3-Clause license.',
        ],
    ],
);
```

```html
<div id="w0" class="accordion">
<details class="accordion-item" name="w0" open>
<summary class="accordion-header">What is Yii?
<svg class="accordion-icon" ...>...</svg></summary>
<div class="accordion-body">A high-performance PHP framework.</div>
</details>
<details class="accordion-item" name="w0">
<summary class="accordion-header">Is it free?
<svg class="accordion-icon" ...>...</svg></summary>
<div class="accordion-body">Yes, under the BSD-3-Clause license.</div>
</details>
</div>
```

The same accordion built with value objects renders the same markup:

```php
use yii2\extensions\bootstrap6\Accordion;
use yii2\extensions\bootstrap6\item\AccordionItem;

echo Accordion::widget(
    [
        'items' => [
            AccordionItem::to('What is Yii?', 'A high-performance PHP framework.')->withOpen(true),
            AccordionItem::to('Is it free?', 'Yes, under the BSD-3-Clause license.'),
        ],
    ],
);
```

### Alert

```php
use yii2\extensions\bootstrap6\Alert;
use yii2\extensions\bootstrap6\enum\Theme;

echo Alert::widget(['body' => 'Profile saved.', 'heading' => 'Done', 'theme' => Theme::SUCCESS]);
```

```html
<div id="w0" class="alert theme-success" role="alert">
<div class="vstack">
<h4 class="alert-heading mb-2">Done</h4>
Profile saved.
</div>
<button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
```

Content between `begin()` and `end()` is not encoded:

```php
Alert::begin(['theme' => Theme::WARNING, 'closeButton' => false]);
echo 'Check your <strong>input</strong>.';
Alert::end();
```

```html
<div id="w0" class="alert theme-warning" role="alert">
Check your <strong>input</strong>.
</div>
```

### Avatar

```php
use yii2\extensions\bootstrap6\Avatar;
use yii2\extensions\bootstrap6\enum\{Size, Theme};

echo Avatar::widget(['initials' => 'WA', 'size' => Size::LG, 'theme' => Theme::PRIMARY, 'status' => 'online']);
echo Avatar::widget(['src' => '/img/user.png', 'alt' => 'Wilmer']);
```

```html
<span id="w0" class="avatar avatar-lg theme-primary">WA
<span class="avatar-status status-online" role="img" aria-label="Online"></span>
</span>
<span id="w1" class="avatar">
<img class="avatar-img" src="/img/user.png" alt="Wilmer">
</span>
```

### Badge

```php
use yii2\extensions\bootstrap6\Badge;
use yii2\extensions\bootstrap6\enum\Theme;

echo Badge::widget(['label' => 'New', 'theme' => Theme::SUCCESS, 'pill' => true]);
```

```html
<span id="w0" class="badge rounded-pill theme-success">New</span>
```

### Breadcrumbs

```php
use yii2\extensions\bootstrap6\Breadcrumbs;

echo Breadcrumbs::widget(['links' => [['label' => 'Library', 'url' => ['/library/index']], 'Data']]);
```

```html
<nav aria-label="breadcrumb"><ol id="w0" class="breadcrumb"><li class="breadcrumb-item"><a class="breadcrumb-link" href="/index.php">Home</a></li>
<li class="breadcrumb-divider"></li>
<li class="breadcrumb-item"><a class="breadcrumb-link" href="/index.php?r=library%2Findex">Library</a></li>
<li class="breadcrumb-divider"></li>
<li class="breadcrumb-item" aria-current="page"><a class="breadcrumb-link active">Data</a></li>
</ol></nav>
```

The same trail built with value objects renders the same markup:

```php
use yii2\extensions\bootstrap6\Breadcrumbs;
use yii2\extensions\bootstrap6\item\BreadcrumbLink;

echo Breadcrumbs::widget(
    ['links' => [BreadcrumbLink::to('Library', ['/library/index']), BreadcrumbLink::to('Data')]],
);
```

### Button

```php
use yii2\extensions\bootstrap6\Button;
use yii2\extensions\bootstrap6\enum\{ButtonVariant, Size, Theme};

echo Button::widget(['label' => 'Save', 'theme' => Theme::PRIMARY]);
echo Button::widget(['label' => 'Bold', 'variant' => ButtonVariant::OUTLINE, 'size' => Size::SM, 'toggle' => true]);
```

```html
<button type="button" id="w0" class="btn-solid theme-primary">Save</button>
<button type="button" id="w1" class="btn-outline btn-sm" data-bs-toggle="button" aria-pressed="false">Bold</button>
```

### ButtonGroup

```php
use yii2\extensions\bootstrap6\ButtonGroup;

echo ButtonGroup::widget(
    [
        'buttons' => [['label' => 'Left'], ['label' => 'Middle'], ['label' => 'Right']],
        'options' => ['aria-label' => 'Alignment'],
    ],
);
```

```html
<div id="w0" class="btn-group" aria-label="Alignment" role="group"><button type="button" id="w1" class="btn-solid">Left</button>
<button type="button" id="w2" class="btn-solid">Middle</button>
<button type="button" id="w3" class="btn-solid">Right</button></div>
```

### ButtonMenu

```php
use yii2\extensions\bootstrap6\ButtonMenu;
use yii2\extensions\bootstrap6\enum\Theme;

echo ButtonMenu::widget(
    [
        'label' => 'Actions',
        'theme' => Theme::PRIMARY,
        'menu' => [
            'items' => [
                ['label' => 'Edit', 'url' => ['/post/update', 'id' => 1]],
                ['label' => 'Archive'],
                '-',
                ['label' => 'Delete', 'disabled' => true],
            ],
        ],
    ],
);
```

```html
<button type="button" id="w0" class="btn-solid theme-primary" data-bs-toggle="menu" aria-expanded="false">Actions</button>
<div id="w0-menu" class="menu"><a class="menu-item" href="/index.php?r=post%2Fupdate&amp;id=1">Edit</a>
<button type="button" class="menu-item">Archive</button>
<hr class="menu-divider">
<button type="button" class="menu-item" disabled>Delete</button></div>
```

A split button renders the action button and a separate toggle inside `.btn-group`:

```php
use yii2\extensions\bootstrap6\enum\Placement;

echo ButtonMenu::widget(
    [
        'label' => 'Save',
        'split' => true,
        'placement' => Placement::BOTTOM_END,
        'menu' => ['items' => [['label' => 'Save as draft']]],
    ],
);
```

```html
<div class="btn-group"><button type="button" class="btn-solid">Save</button>
<button type="button" id="w0" class="btn-solid menu-toggle-split" data-bs-toggle="menu" data-bs-placement="bottom-end" aria-expanded="false"><span class="visually-hidden">Toggle menu</span></button>
<div id="w0-menu" class="menu"><button type="button" class="menu-item">Save as draft</button></div></div>
```

### ButtonToolbar

```php
use yii2\extensions\bootstrap6\ButtonToolbar;

echo ButtonToolbar::widget(
    [
        'buttonGroups' => [
            ['buttons' => [['label' => '1'], ['label' => '2']], 'options' => ['class' => 'me-2']],
            ['buttons' => [['label' => '3']]],
        ],
    ],
);
```

```html
<div id="w0" class="btn-toolbar" role="toolbar"><div id="w1" class="me-2 btn-group" role="group"><button type="button" id="w2" class="btn-solid">1</button>
<button type="button" id="w3" class="btn-solid">2</button></div>
<div id="w4" class="btn-group" role="group"><button type="button" id="w5" class="btn-solid">3</button></div></div>
```

### Carousel

```php
use yii2\extensions\bootstrap6\Carousel;

echo Carousel::widget(
    [
        'autoplay' => true,
        'playPause' => true,
        'items' => [
            '<img src="/img/1.jpg" class="d-block w-100" alt="First">',
            ['content' => '<img src="/img/2.jpg" class="d-block w-100" alt="Second">'],
        ],
    ],
);
```

```html
<div id="w0" class="carousel slide" data-bs-autoplay="true">
<div class="carousel-inner">
<div class="carousel-item active"><img src="/img/1.jpg" class="d-block w-100" alt="First"></div>
<div class="carousel-item"><img src="/img/2.jpg" class="d-block w-100" alt="Second"></div>
</div>
<div class="d-flex justify-content-between align-items-center">
<div>
<button type="button" class="btn-icon btn-sm carousel-control-play-pause" data-bs-target="#w0" data-bs-pause-label="Pause" data-bs-play-label="Play" aria-label="Pause">
<span class="carousel-icon-pause" aria-hidden="true"></span>
<span class="carousel-icon-play" aria-hidden="true"></span>
</button>
<button type="button" class="btn-icon btn-sm" data-bs-target="#w0" data-bs-slide="prev">
<span class="carousel-icon-prev" aria-hidden="true"></span>
<span class="visually-hidden">Previous</span>
</button>
<button type="button" class="btn-icon btn-sm" data-bs-target="#w0" data-bs-slide="next">
<span class="carousel-icon-next" aria-hidden="true"></span>
<span class="visually-hidden">Next</span>
</button>
</div>
<div class="carousel-indicators">
<button type="button" class="active" data-bs-target="#w0" data-bs-slide-to="0" aria-current="true" aria-label="Slide 1"></button>
<button type="button" data-bs-target="#w0" data-bs-slide-to="1" aria-label="Slide 2"></button>
</div>
</div>
</div>
```

The plugin is always created:

```js
import("/assets/<hash>/bootstrap.bundle.js").then((bootstrap) => { bootstrap.Carousel.getOrCreateInstance(document.getElementById("w0"), {}); });
```

The same slides as value objects render the same markup:

```php
use yii2\extensions\bootstrap6\Carousel;
use yii2\extensions\bootstrap6\item\CarouselItem;

echo Carousel::widget(
    [
        'autoplay' => true,
        'playPause' => true,
        'items' => [
            CarouselItem::to('<img src="/img/1.jpg" class="d-block w-100" alt="First">'),
            CarouselItem::to('<img src="/img/2.jpg" class="d-block w-100" alt="Second">'),
        ],
    ],
);
```

### Chips

```php
use yii2\extensions\bootstrap6\Chips;

echo Chips::widget(['name' => 'tags', 'value' => ['php', 'yii'], 'placeholder' => 'Add a tag']);
```

```html
<div id="w0-chips" class="chip-input" data-bs-chips>
<span class="chip">php</span>
<span class="chip">yii</span>
<input type="text" id="w0" class="form-ghost" placeholder="Add a tag">
<input type="hidden" id="w0-value" name="tags" value="php,yii">
</div>
```

The widget keeps the hidden input in sync with the plugin:

```js
document.getElementById("w0-chips").addEventListener("change.bs.chips", (event) => { document.getElementById("w0-value").value = event.values.join(","); });
```

### Collapse

```php
use yii2\extensions\bootstrap6\Collapse;

Collapse::begin(['toggleButton' => ['label' => 'Details']]);
echo 'Hidden content.';
Collapse::end();
```

```html
<button type="button" class="btn-solid theme-primary" data-bs-toggle="collapse" data-bs-target="#w0" aria-expanded="false" aria-controls="w0">Details</button>
<div id="w0" class="collapse">
Hidden content.
</div>
```

The fluent methods chain on the value returned by `begin()`. A toggler method enables the toggle button, and a class
added to it replaces the default `btn-solid theme-primary`:

```php
Collapse::begin()
    ->addClass('border-bottom')
    ->addCssStyle(['max-width' => '30rem'])
    ->addTogglerClass('btn-outline', 'theme-secondary');
echo 'Hidden content.';
Collapse::end();
```

```html
<button type="button" class="btn-outline theme-secondary" data-bs-toggle="collapse" data-bs-target="#w0" aria-expanded="false" aria-controls="w0">Show</button>
<div id="w0" class="border-bottom collapse" style="max-width: 30rem;">
Hidden content.
</div>
```

### Combobox

```php
use yii2\extensions\bootstrap6\Combobox;

echo Combobox::widget(
    [
        'name' => 'fruit',
        'items' => ['apple' => 'Apple', 'pear' => 'Pear'],
        'placeholder' => 'Choose a fruit',
        'value' => 'pear',
    ],
);
```

```html
<button type="button" id="w0" class="form-control combobox-toggle" data-bs-toggle="combobox" data-bs-name="fruit" data-bs-placeholder="Choose a fruit"><span class="combobox-value">Pear</span>
<svg class="combobox-caret" ...>...</svg></button>
<div class="menu">
<button type="button" class="menu-item" data-bs-value="apple">Apple</button>
<button type="button" class="menu-item selected" data-bs-value="pear" aria-selected="true">Pear</button>
</div>
```

`multiple`, `search`, and `check` add `data-bs-multiple="true"`, `data-bs-search="true"`, the `.combobox-search`
field, the `.combobox-no-results` element, and a `.menu-item-check` icon in every item.

> [!NOTE]
> Bootstrap `6.0.0-alpha.1` logs a harmless `Bootstrap doesn't allow more than one instance per element` console error
> when a combobox opens. The message comes from the upstream bundle, not from the widget.

### Datepicker

```php
use yii2\extensions\bootstrap6\Datepicker;

echo Datepicker::widget(['name' => 'date', 'locale' => 'en-US', 'dateMin' => '2026-01-01']);
```

```html
<input type="text" id="w0" class="form-control" name="date" autocomplete="off" data-bs-toggle="datepicker" data-bs-date-min="2026-01-01" data-bs-locale="en-US">
```

In input mode the field submits the locale-formatted text shown to the user, so parse it with the same locale on the
server. Inline mode keeps the `YYYY-MM-DD` values in a hidden input:

```php
echo Datepicker::widget(
    [
        'name' => 'range',
        'inline' => true,
        'selectionMode' => 'multiple-ranged',
        'value' => '2026-10-01,2026-10-05',
    ],
);
```

```html
<div id="w0-datepicker" data-bs-toggle="datepicker" data-bs-inline="true" data-bs-selection-mode="multiple-ranged" data-bs-selected-dates='["2026-10-01","2026-10-05"]'>
<input type="hidden" id="w0" name="range" value="2026-10-01,2026-10-05">
</div>
```

### Dialog

```php
use yii2\extensions\bootstrap6\Dialog;

Dialog::begin(
    [
        'title' => 'Delete post',
        'toggleButton' => ['label' => 'Delete'],
        'footer' => '<button type="button" class="btn-solid theme-danger">Delete</button>',
    ],
);
echo '<p>This action cannot be undone.</p>';
Dialog::end();
```

```html
<button type="button" class="btn-solid theme-primary" data-bs-toggle="dialog" data-bs-target="#w0">Delete</button>
<dialog id="w0" class="dialog" aria-labelledby="w0-label">
<div class="dialog-header">
<h1 id="w0-label" class="dialog-title">Delete post</h1>
<button type="button" class="btn-close" data-bs-dismiss="dialog" aria-label="Close"></button>
</div>
<div class="dialog-body">
<p>This action cannot be undone.</p>
</div>
<div class="dialog-footer">
<button type="button" class="btn-solid theme-danger">Delete</button>
</div>
</dialog>
```

The same dialog configured with the fluent methods, which chain on the value returned by `begin()`; the `dialog` class
is still added while rendering:

```php
Dialog::begin(['title' => 'Delete post', 'toggleButton' => ['label' => 'Delete']])
    ->addClass('shadow-lg')
    ->addTogglerClass('btn-outline', 'theme-danger')
    ->addTogglerAttribute('aria-haspopup', 'dialog');
echo '<p>This action cannot be undone.</p>';
Dialog::end();
```

```html
<button type="button" class="btn-outline theme-danger" aria-haspopup="dialog" data-bs-toggle="dialog" data-bs-target="#w0">Delete</button>
<dialog id="w0" class="shadow-lg dialog" aria-labelledby="w0-label">
<div class="dialog-header">
<h1 id="w0-label" class="dialog-title">Delete post</h1>
<button type="button" class="btn-close" data-bs-dismiss="dialog" aria-label="Close"></button>
</div>
<div class="dialog-body">
<p>This action cannot be undone.</p>
</div>
</dialog>
```

Sizes, responsive fullscreen, and backdrop behavior:

```php
use yii2\extensions\bootstrap6\enum\{Breakpoint, DialogSize};

Dialog::begin(
    [
        'title' => 'Terms of service',
        'toggleButton' => ['label' => 'Read the terms'],
        'size' => DialogSize::LG,
        'fullscreenBelow' => Breakpoint::MD,
        'backdrop' => 'static',
        'scrollable' => true,
    ],
);
echo '<p>A static backdrop keeps the dialog open on outside clicks; close it with the button.</p>';
echo '<p>The body scrolls inside the dialog when the content is taller than the viewport.</p>';
Dialog::end();
```

```html
<button type="button" class="btn-solid theme-primary" data-bs-toggle="dialog" data-bs-target="#w0">Read the terms</button>
<dialog id="w0" class="dialog dialog-lg md-down:dialog-fullscreen dialog-scrollable" aria-labelledby="w0-label" data-bs-backdrop="static">
<div class="dialog-header">
<h1 id="w0-label" class="dialog-title">Terms of service</h1>
<button type="button" class="btn-close" data-bs-dismiss="dialog" aria-label="Close"></button>
</div>
<div class="dialog-body">
<p>A static backdrop keeps the dialog open on outside clicks; close it with the button.</p>
<p>The body scrolls inside the dialog when the content is taller than the viewport.</p>
</div>
</dialog>
```

### Drawer

```php
use yii2\extensions\bootstrap6\Drawer;
use yii2\extensions\bootstrap6\enum\DrawerPlacement;

Drawer::begin(
    ['title' => 'Filters', 'placement' => DrawerPlacement::END, 'toggleButton' => ['label' => 'Show filters']],
);
echo 'Filter form';
Drawer::end();
```

```html
<button type="button" class="btn-solid theme-primary" data-bs-toggle="drawer" data-bs-target="#w0" aria-controls="w0">Show filters</button>
<dialog id="w0" class="drawer drawer-end" tabindex="-1" aria-labelledby="w0-label">
<div class="drawer-header">
<h5 id="w0-label" class="drawer-title">Filters</h5>
<button type="button" class="btn-close" data-bs-dismiss="drawer" aria-label="Close"></button>
</div>
<div class="drawer-body">
Filter form
</div>
</dialog>
```

`'responsive' => Breakpoint::LG` renders `class="lg:drawer drawer-start"`: the content is a drawer below `lg` and
inline from `lg` up.

### LinkPager

```php
use yii\data\Pagination;
use yii2\extensions\bootstrap6\LinkPager;
use yii2\extensions\bootstrap6\enum\{Size, Theme};

echo LinkPager::widget(
    [
        'pagination' => new Pagination(['totalCount' => 30, 'pageSize' => 10, 'page' => 1, 'route' => 'post/index']),
        'size' => Size::SM,
        'theme' => Theme::PRIMARY,
    ],
);
```

```html
<nav id="w0"><ul class="pagination pagination-sm theme-primary"><li class="page-item prev"><a class="page-link" href="/index.php?r=post%2Findex&amp;page=1&amp;per-page=10" data-page="0" aria-label="Previous"><span aria-hidden="true">&laquo;</span></a></li>
<li class="page-item"><a class="page-link" href="/index.php?r=post%2Findex&amp;page=1&amp;per-page=10" data-page="0">1</a></li>
<li class="page-item active"><a class="page-link" href="/index.php?r=post%2Findex&amp;page=2&amp;per-page=10" data-page="1" aria-current="page">2</a></li>
<li class="page-item"><a class="page-link" href="/index.php?r=post%2Findex&amp;page=3&amp;per-page=10" data-page="2">3</a></li>
<li class="page-item next"><a class="page-link" href="/index.php?r=post%2Findex&amp;page=3&amp;per-page=10" data-page="2" aria-label="Next"><span aria-hidden="true">&raquo;</span></a></li></ul></nav>
```

Use it in every grid and list view through the DI container: add the definition to `container.definitions` in
`config/web.php`, or set it at runtime:

```php
use yii\data\ArrayDataProvider;
use yii\widgets\ListView;

// config/web.php: 'container' => ['definitions' => [\yii\widgets\LinkPager::class => LinkPager::class]]
Yii::$container->set(\yii\widgets\LinkPager::class, LinkPager::class);

echo ListView::widget(
    [
        'dataProvider' => new ArrayDataProvider(
            ['allModels' => range(1, 30), 'pagination' => ['pageSize' => 10, 'route' => 'post/index']],
        ),
        'itemView' => static fn(int $number): string => "Post $number",
        'layout' => '{pager}',
    ],
);
```

### Menu

`Menu` renders only the panel; place it after your own toggle:

```php
use yii2\extensions\bootstrap6\{Html, Menu};

echo Html::button('Options', ['class' => 'btn-solid', 'data-bs-toggle' => 'menu', 'aria-expanded' => 'false']);
echo Menu::widget(
    [
        'items' => [
            ['header' => 'Account'],
            ['label' => 'Profile', 'url' => ['/user/profile'], 'active' => true],
            ['label' => 'More', 'items' => [['label' => 'Settings', 'url' => '#']]],
            '-',
            ['text' => 'Signed in as admin'],
        ],
    ],
);
```

```html
<button type="button" class="btn-solid" data-bs-toggle="menu" aria-expanded="false">Options</button>
<div id="w0" class="menu"><h6 class="menu-header">Account</h6>
<a class="menu-item active" href="/index.php?r=user%2Fprofile" aria-current="true">Profile</a>
<div class="submenu"><button type="button" class="menu-item">More</button>
<div class="menu"><a class="menu-item" href="#">Settings</a></div></div>
<hr class="menu-divider">
<span class="menu-text">Signed in as admin</span></div>
```

The same panel built with value objects renders the same markup:

```php
use yii2\extensions\bootstrap6\item\MenuItem;
use yii2\extensions\bootstrap6\Menu;

echo Menu::widget(
    [
        'items' => [
            MenuItem::header('Account'),
            MenuItem::link('Profile', ['/user/profile'])->withActive(true),
            MenuItem::submenu('More', [MenuItem::link('Settings', '#')]),
            MenuItem::divider(),
            MenuItem::text('Signed in as admin'),
        ],
    ],
);
```

The Menu plugin binds to the toggle, so `clientOptions` and `clientEvents` set on a standalone `Menu` target the panel
and have no effect on the toggle. Put the options on the toggle as `data-bs-*` attributes, or use `ButtonMenu`.

### Nav

```php
use yii2\extensions\bootstrap6\Nav;
use yii2\extensions\bootstrap6\enum\NavStyle;

echo Nav::widget(
    [
        'style' => NavStyle::PILLS,
        'items' => [
            ['label' => 'Home', 'url' => ['/site/index'], 'active' => true],
            ['label' => 'Docs', 'items' => [['label' => 'Guide', 'url' => '#'], ['label' => 'API', 'url' => '#']]],
            ['label' => 'Disabled', 'disabled' => true],
        ],
    ],
);
```

```html
<ul id="w0" class="nav nav-pills"><li class="nav-item"><a class="nav-link active" href="/index.php?r=site%2Findex" aria-current="page">Home</a></li>
<li class="nav-item"><a class="nav-link" href="#" data-bs-toggle="menu" role="button" aria-expanded="false">Docs</a><div id="w1" class="menu"><a class="menu-item" href="#">Guide</a>
<a class="menu-item" href="#">API</a></div></li>
<li class="nav-item"><a class="nav-link disabled" aria-disabled="true">Disabled</a></li></ul>
```

The same nav built with value objects renders the same markup; leave `active` as `null` to detect it from the route:

```php
use yii2\extensions\bootstrap6\enum\NavStyle;
use yii2\extensions\bootstrap6\item\{MenuItem, NavItem};
use yii2\extensions\bootstrap6\Nav;

echo Nav::widget(
    [
        'style' => NavStyle::PILLS,
        'items' => [
            NavItem::to('Home', ['/site/index'])->withActive(true),
            NavItem::menu('Docs', [MenuItem::link('Guide', '#'), MenuItem::link('API', '#')]),
            NavItem::to('Disabled')->withDisabled(true),
        ],
    ],
);
```

### NavBar

```php
use yii2\extensions\bootstrap6\{Nav, NavBar};

NavBar::begin(['brandLabel' => 'My App', 'brandUrl' => '/', 'options' => ['class' => 'bg-1']]);
echo Nav::widget(
    [
        'options' => ['class' => 'navbar-nav'],
        'items' => [['label' => 'Home', 'url' => '/', 'active' => true], ['label' => 'About', 'url' => '/about']],
    ],
);
NavBar::end();
```

```html
<nav id="w0" class="bg-1 navbar lg:navbar-expand">
<div class="container-fluid">
<a class="navbar-brand" href="/">My App</a>
<button type="button" class="btn-icon navbar-toggler" data-bs-toggle="drawer" data-bs-target="#w0-drawer" aria-controls="w0-drawer" aria-expanded="false" aria-label="Toggle navigation"><span class="navbar-toggler-icon" aria-hidden="true"></span></button>
<dialog id="w0-drawer" class="drawer drawer-end" tabindex="-1" aria-labelledby="w0-drawer-label">
<div class="drawer-header">
<h5 id="w0-drawer-label" class="drawer-title">Menu</h5>
<button type="button" class="btn-close" data-bs-dismiss="drawer" aria-label="Close"></button>
</div>
<div class="drawer-body">
<ul id="w1" class="navbar-nav nav"><li class="nav-item"><a class="nav-link active" href="/" aria-current="page">Home</a></li>
<li class="nav-item"><a class="nav-link" href="/about">About</a></li></ul></div>
</dialog>
</div>
</nav>
```

`'drawerOptions' => false` renders the content inline without toggler and drawer, and `'expand' => null` renders
`navbar-expand` for every width.

### NavOverflow

```php
use yii2\extensions\bootstrap6\NavOverflow;
use yii2\extensions\bootstrap6\enum\NavStyle;

echo NavOverflow::widget(
    [
        'moreText' => 'More',
        'nav' => [
            'style' => NavStyle::UNDERLINE,
            'items' => [['label' => 'Overview', 'url' => '#', 'active' => true], ['label' => 'Reports', 'url' => '#']],
        ],
    ],
);
```

```html
<div id="w0" class="nav-overflow" data-bs-toggle="nav-overflow" data-bs-more-text="More"><ul id="w1" class="nav nav-underline"><li class="nav-item"><a class="nav-link active" href="#" aria-current="page">Overview</a></li>
<li class="nav-item"><a class="nav-link" href="#">Reports</a></li></ul></div>
```

### OtpInput

```php
use yii2\extensions\bootstrap6\OtpInput;

echo OtpInput::widget(['name' => 'code', 'length' => 6, 'groups' => [3, 3]]);
```

```html
<div id="w0-otp" class="otp" data-bs-otp data-bs-groups='[3,3]'>
<input type="text" id="w0" class="otp-input" name="code" maxlength="6">
</div>
```

### Placeholder

```php
use yii2\extensions\bootstrap6\Placeholder;
use yii2\extensions\bootstrap6\enum\Size;

echo Placeholder::widget(['width' => 6, 'animation' => 'wave']);
echo Placeholder::widget(['width' => 8, 'animation' => 'pulse', 'container' => true, 'size' => Size::LG]);
```

```html
<span id="w0" class="placeholder col-6 placeholder-wave"></span>
<p class="placeholder-pulse"><span id="w1" class="placeholder col-8 placeholder-lg"></span></p>
```

### Popover

```php
use yii2\extensions\bootstrap6\Popover;
use yii2\extensions\bootstrap6\enum\Placement;

Popover::begin(['title' => 'Popover title', 'content' => 'And here is some content.', 'placement' => Placement::RIGHT]);
echo '<button type="button" class="btn-solid theme-danger">Click me</button>';
Popover::end();
```

```html
<span id="w0" data-bs-toggle="popover" data-bs-title="Popover title" data-bs-placement="right" data-bs-content="And here is some content."><button type="button" class="btn-solid theme-danger">Click me</button></span>
```

### Progress

```php
use yii2\extensions\bootstrap6\Progress;
use yii2\extensions\bootstrap6\enum\Theme;

echo Progress::widget(
    ['percent' => 60, 'label' => '60%', 'theme' => Theme::SUCCESS, 'striped' => true, 'ariaLabel' => 'Upload'],
);
```

```html
<div id="w0" class="progress" role="progressbar" aria-label="Upload" aria-valuenow="60" aria-valuemin="0" aria-valuemax="100">
<div class="progress-bar theme-success progress-bar-striped" style="width: 60%;">60%</div>
</div>
```

Stacked segments size the `.progress` elements:

```php
echo Progress::widget(
    [
        'bars' => [
            ['percent' => 30, 'theme' => Theme::PRIMARY, 'ariaLabel' => 'Segment one'],
            ['percent' => 20, 'theme' => Theme::WARNING, 'ariaLabel' => 'Segment two'],
        ],
    ],
);
```

```html
<div id="w0" class="progress-stacked">
<div class="progress" role="progressbar" aria-label="Segment one" aria-valuenow="30" aria-valuemin="0" aria-valuemax="100" style="width: 30%;">
<div class="progress-bar theme-primary"></div>
</div>
<div class="progress" role="progressbar" aria-label="Segment two" aria-valuenow="20" aria-valuemin="0" aria-valuemax="100" style="width: 20%;">
<div class="progress-bar theme-warning"></div>
</div>
</div>
```

The same segments as value objects render the same markup:

```php
use yii2\extensions\bootstrap6\item\ProgressBar;

echo Progress::widget(
    [
        'bars' => [
            ProgressBar::to(30)->withTheme(Theme::PRIMARY)->withAriaLabel('Segment one'),
            ProgressBar::to(20)->withTheme(Theme::WARNING)->withAriaLabel('Segment two'),
        ],
    ],
);
```

### Range

```php
use yii2\extensions\bootstrap6\Range;

echo Range::widget(
    [
        'name' => 'volume',
        'value' => 40,
        'min' => 0,
        'max' => 100,
        'step' => 10,
        'bubble' => true,
        'ticks' => [0 => 'Mute', 50 => null, 100 => 'Max'],
    ],
);
```

```html
<div id="w0-range" class="form-range" data-bs-bubble>
<input type="range" id="w0" class="form-range-input" name="volume" value="40" min="0" max="100" step="10" list="w0-ticks">
</div>
<datalist id="w0-ticks">
<option value="0" label="Mute"></option>
<option value="50"></option>
<option value="100" label="Max"></option>
</datalist>
```

### Spinner

```php
use yii2\extensions\bootstrap6\Spinner;
use yii2\extensions\bootstrap6\enum\Theme;

echo Spinner::widget(['theme' => Theme::PRIMARY]);
echo Spinner::widget(['type' => Spinner::TYPE_GROW, 'small' => true, 'label' => '']);
```

```html
<div id="w0" class="spinner-border fg-primary" role="status">
<span class="visually-hidden">Loading...</span>
</div>
<div id="w1" class="spinner-grow spinner-sm" role="status"></div>
```

### Stepper

```php
use yii2\extensions\bootstrap6\Stepper;
use yii2\extensions\bootstrap6\enum\Breakpoint;

echo Stepper::widget(
    [
        'items' => [
            ['label' => 'Cart', 'active' => true],
            ['label' => 'Shipping', 'active' => true],
            ['label' => 'Payment'],
        ],
    ],
);
```

```html
<ol id="w0" class="stepper">
<li class="stepper-item active">Cart</li>
<li class="stepper-item active">Shipping</li>
<li class="stepper-item">Payment</li>
</ol>
```

A responsive horizontal stepper with a description and a link:

```php
echo Stepper::widget(
    [
        'horizontal' => Breakpoint::MD,
        'items' => [
            ['label' => 'Account', 'description' => 'Create your login', 'active' => true],
            ['label' => 'Profile', 'url' => ['/profile']],
        ],
    ],
);
```

```html
<div class="contains-inline">
<div id="w0" class="stepper md:stepper-horizontal">
<div class="stepper-item align-items-start active">
<div class="pt-1">
<div class="fw-semibold">Account</div>
<small class="fg-3">Create your login</small>
</div>
</div>
<a class="stepper-item" href="/index.php?r=profile">Profile</a>
</div>
</div>
```

The same stepper built with value objects renders the same markup:

```php
use yii2\extensions\bootstrap6\item\StepItem;

echo Stepper::widget(
    [
        'horizontal' => Breakpoint::MD,
        'items' => [
            StepItem::to('Account', 'Create your login')->withActive(true),
            StepItem::to('Profile')->withUrl(['/profile']),
        ],
    ],
);
```

### Strength

```php
use yii2\extensions\bootstrap6\{Html, Strength};

echo Html::passwordInput('password', null, ['class' => 'form-control']);
echo Strength::widget(['showText' => true, 'messages' => ['weak' => 'Too weak', 'strong' => 'Great']]);
```

```html
<input type="password" class="form-control" name="password">
<div id="w0" class="strength" data-bs-strength>
<div class="strength-segment"></div>
<div class="strength-segment"></div>
<div class="strength-segment"></div>
<div class="strength-segment"></div>
</div>
<span class="strength-text"></span>
```

`messages` are passed to the plugin constructor:

```js
import("/assets/<hash>/bootstrap.bundle.js").then((bootstrap) => { bootstrap.Strength.getOrCreateInstance(document.getElementById("w0"), {"messages":{"weak":"Too weak","strong":"Great"}}); });
```

### Tabs

```php
use yii2\extensions\bootstrap6\Tabs;

echo Tabs::widget(
    [
        'items' => [
            ['label' => 'Home', 'content' => 'Home pane.'],
            ['label' => 'Profile', 'content' => 'Profile pane.'],
            ['label' => 'Disabled', 'disabled' => true],
        ],
    ],
);
```

```html
<ul id="w0" class="nav nav-tabs" role="tablist"><li class="nav-item" role="presentation"><button type="button" id="w0-tab0" class="nav-link active" data-bs-toggle="tab" data-bs-target="#w0-pane0" role="tab" aria-controls="w0-pane0" aria-selected="true">Home</button></li>
<li class="nav-item" role="presentation"><button type="button" id="w0-tab1" class="nav-link" data-bs-toggle="tab" data-bs-target="#w0-pane1" role="tab" aria-controls="w0-pane1" aria-selected="false">Profile</button></li>
<li class="nav-item" role="presentation"><button type="button" id="w0-tab2" class="nav-link" disabled data-bs-toggle="tab" data-bs-target="#w0-pane2" role="tab" aria-controls="w0-pane2" aria-selected="false">Disabled</button></li></ul>
<div class="tab-content"><div id="w0-pane0" class="tab-pane active" role="tabpanel" aria-labelledby="w0-tab0" tabindex="0">Home pane.</div>
<div id="w0-pane1" class="tab-pane" role="tabpanel" aria-labelledby="w0-tab1" tabindex="0">Profile pane.</div>
<div id="w0-pane2" class="tab-pane" role="tabpanel" aria-labelledby="w0-tab2" tabindex="0"></div></div>
```

The same tabs built with value objects render the same markup:

```php
use yii2\extensions\bootstrap6\item\TabItem;
use yii2\extensions\bootstrap6\Tabs;

echo Tabs::widget(
    [
        'items' => [
            TabItem::to('Home', 'Home pane.'),
            TabItem::to('Profile', 'Profile pane.'),
            TabItem::to('Disabled')->withDisabled(true),
        ],
    ],
);
```

### Toast

```php
use yii2\extensions\bootstrap6\Toast;

echo Toast::widget(['title' => 'Bootstrap', 'body' => 'Hello, world! This is a toast message.']);
```

```html
<div id="w0" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
<div class="toast-header">
<strong class="me-auto">Bootstrap</strong>
<button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
</div>
<div class="toast-body">
Hello, world! This is a toast message.
</div>
</div>
```

The widget creates the plugin instance but does not show the toast. Show it from the view with the bundle helper:

```php
use yii2\extensions\bootstrap6\asset\BootstrapPluginAsset;

$bundle = BootstrapPluginAsset::register($this);

$this->registerJs(
    $bundle->importJs($this, 'bootstrap.Toast.getOrCreateInstance(document.getElementById("w0")).show();'),
);
```

Set `dateTime` to render a relative time such as `<small>2 minutes ago</small>` in the header.

### ToggleButtonGroup

```php
use yii2\extensions\bootstrap6\ToggleButtonGroup;
use yii2\extensions\bootstrap6\enum\Theme;

echo ToggleButtonGroup::widget(
    [
        'name' => 'style',
        'type' => ToggleButtonGroup::TYPE_RADIO,
        'items' => ['b' => 'Bold', 'i' => 'Italic'],
        'value' => 'b',
        'theme' => Theme::PRIMARY,
    ],
);
```

```html
<div id="w0" class="btn-group" role="group"><label class="btn-check btn-outline theme-primary"><input type="radio" name="style" value="b" checked autocomplete="off">Bold</label>
<label class="btn-check btn-outline theme-primary"><input type="radio" name="style" value="i" autocomplete="off">Italic</label></div>
```

### Toggler

```php
use yii2\extensions\bootstrap6\Toggler;

echo Toggler::widget(['label' => 'Toggle highlight', 'value' => 'theme-primary']);
echo Toggler::widget(['label' => 'Show details', 'target' => '#details']);
```

```html
<button type="button" id="w0" data-bs-toggle="toggler" data-bs-value="theme-primary" data-bs-attribute="class">Toggle highlight</button>
<button type="button" id="w1" data-bs-toggle="toggler" data-bs-target="#details">Show details</button>
```

With `target`, the matching elements carry their own `data-bs-value` and `data-bs-attribute` attributes.

### Tooltip

```php
use yii2\extensions\bootstrap6\Tooltip;
use yii2\extensions\bootstrap6\enum\Placement;

Tooltip::begin(['title' => 'Copied!', 'placement' => Placement::TOP]);
echo 'Hover me';
Tooltip::end();
```

```html
<span id="w0" data-bs-toggle="tooltip" data-bs-title="Copied!" data-bs-placement="top">Hover me</span>
```

## Forms

The examples use this model, validated with an invalid `email` so the forms show the error state; the CSRF input
rendered after `<form>` is omitted from the output.

```php
use yii\base\Model;
use yii2\extensions\bootstrap6\Html;

final class SignupForm extends Model
{
    public $email;
    public $password;
    public $remember = true;
    public $notify;
    public $topics = ['php'];
    public $plan = 'pro';
    public $country;
    public $volume = 50;
    public $username = 'admin';
    public $amount;
    public $tags;
    public $fruit;
    public $birthday;

    public function attributeHints(): array
    {
        return ['password' => 'At least 8 characters.'];
    }

    public function rules(): array
    {
        return [[['email', 'password'], 'required'], ['email', 'email']];
    }
}

$model = new SignupForm(['email' => 'not-an-email']);
$model->validate(['email']);

echo Html::errorSummary($model, ['class' => 'alert theme-danger']);
```

```html
<div class="alert theme-danger"><p>Please fix the following errors:</p><ul><li>Email is not a valid email address.</li></ul></div>
```

### Default layout

```php
use yii2\extensions\bootstrap6\ActiveForm;

$form = ActiveForm::begin(['action' => '/signup']);
echo $form->field($model, 'email')->textInput(['type' => 'email']);
echo $form->field($model, 'password')->passwordInput();
echo $form->field($model, 'remember')->checkbox();
ActiveForm::end();
```

```html
<form id="w0" action="/signup" method="post">
<div class="form-field mb-3 field-signupform-email required">
<label class="form-label" for="signupform-email">Email</label>
<input type="email" id="signupform-email" class="form-control is-invalid" name="SignupForm[email]" value="not-an-email" aria-required="true" aria-invalid="true">

<div class="invalid-feedback">Email is not a valid email address.</div>
</div>
<div class="form-field mb-3 field-signupform-password required">
<label class="form-label" for="signupform-password">Password</label>
<input type="password" id="signupform-password" class="form-control" name="SignupForm[password]" aria-required="true">
<div class="form-text">At least 8 characters.</div>
<div class="invalid-feedback"></div>
</div>
<div class="form-field mb-3 field-signupform-remember">
<input type="hidden" name="SignupForm[remember]" value="0"><input type="checkbox" id="signupform-remember" class="check" name="SignupForm[remember]" value="1" checked>
<label for="signupform-remember">Remember</label>

<div class="invalid-feedback"></div>
</div>
</form>
```

### Horizontal layout

```php
$form = ActiveForm::begin(['action' => '/signup', 'layout' => ActiveForm::LAYOUT_HORIZONTAL]);
echo $form->field($model, 'email');
echo $form->field($model, 'remember')->checkbox();
ActiveForm::end();
```

With an error on `email`:

```html
<div class="row mb-3 field-signupform-email required">
<label class="sm:col-2 col-form-label" for="signupform-email">Email</label>
<div class="sm:col-10">
<input type="text" id="signupform-email" class="form-control is-invalid" name="SignupForm[email]" value="not-an-email" aria-required="true" aria-invalid="true">

<div class="invalid-feedback">Email is not a valid email address.</div>
</div>
</div>
<div class="row mb-3 field-signupform-remember">
<div class="sm:col-10 sm:offset-2">
<div class="form-field">
<input type="hidden" name="SignupForm[remember]" value="0"><input type="checkbox" id="signupform-remember" class="check" name="SignupForm[remember]" value="1" checked>
<label for="signupform-remember">Remember</label>

<div class="invalid-feedback"></div>
</div>
</div>
</div>
```

Change the grid through `fieldConfig`:

```php
$form = ActiveForm::begin(
    [
        'action' => '/signup',
        'layout' => ActiveForm::LAYOUT_HORIZONTAL,
        'fieldConfig' => [
            'horizontalCssClasses' => [
                'label' => 'md:col-3 col-form-label',
                'offset' => 'md:col-9 md:offset-3',
                'wrapper' => 'md:col-9',
            ],
        ],
    ],
);
echo $form->field($model, 'username');
ActiveForm::end();
```

```html
<div class="row mb-3 field-signupform-username">
<label class="md:col-3 col-form-label" for="signupform-username">Username</label>
<div class="md:col-9">
<input type="text" id="signupform-username" class="form-control" name="SignupForm[username]" value="admin">

<div class="invalid-feedback"></div>
</div>
</div>
```

### Floating layout

The label comes before the control inside `.form-floating`; the hint and the error follow the wrapper. The wrapper
receives `is-invalid` on the server so the sibling feedback is displayed. On the client, a `:has()` rule registered
once per form displays the feedback when the control carries `is-invalid`, so client validation needs no jQuery:

```php
$form = ActiveForm::begin(['action' => '/signup', 'layout' => ActiveForm::LAYOUT_FLOATING]);
echo $form->field($model, 'email');
ActiveForm::end();
```

```html
<div class="form-field mb-3 field-signupform-email required">
<div class="form-floating is-invalid">
<label for="signupform-email">Email</label>
<input type="text" id="signupform-email" class="form-control is-invalid" name="SignupForm[email]" value="not-an-email" placeholder="Email" aria-required="true" aria-invalid="true">
</div>

<div class="invalid-feedback">Email is not a valid email address.</div>
</div>
```

### Inline layout

```php
$form = ActiveForm::begin(['action' => '/signup', 'layout' => ActiveForm::LAYOUT_INLINE]);
echo $form->field($model, 'email');
echo $form->field($model, 'remember')->checkbox();
ActiveForm::end();
```

```html
<form id="w0" class="row lg:row-cols-auto g-3 align-items-center" action="/signup" method="post">
<div class="col-12 field-signupform-email required">
<label class="visually-hidden" for="signupform-email">Email</label>
<input type="text" id="signupform-email" class="form-control is-invalid" name="SignupForm[email]" value="not-an-email" placeholder="Email" aria-required="true" aria-invalid="true">


</div>
<div class="col-12 field-signupform-remember">
<div class="form-field">
<input type="hidden" name="SignupForm[remember]" value="0"><input type="checkbox" id="signupform-remember" class="check" name="SignupForm[remember]" value="1" checked>
<label for="signupform-remember">Remember</label>


</div>
</div>
</form>
```

### Switch

```php
echo $form->field($model, 'notify')->checkbox(['switch' => true, 'switchOptions' => ['class' => 'theme-success']]);
```

```html
<div class="form-field mb-3 field-signupform-notify">
<input type="hidden" name="SignupForm[notify]" value="0"><div class="switch theme-success"><input type="checkbox" id="signupform-notify" name="SignupForm[notify]" value="1" role="switch" switch></div>
<label for="signupform-notify">Notify</label>

<div class="invalid-feedback"></div>
</div>
```

### Checkbox and radio lists

```php
echo $form->field($model, 'topics')->inline()->checkboxList(['php' => 'PHP', 'js' => 'JavaScript']);
echo $form->field($model, 'plan')->radioList(['free' => 'Free', 'pro' => 'Pro']);
```

```html
<div class="form-field mb-3 field-signupform-topics">
<label class="form-label">Topics</label>
<input type="hidden" name="SignupForm[topics]" value=""><div id="signupform-topics" class="d-flex flex-wrap gap-5"><div class="form-field"><input type="checkbox" id="i0" class="check" name="SignupForm[topics][]" value="php" checked>
<label for="i0">PHP</label></div>
<div class="form-field"><input type="checkbox" id="i1" class="check" name="SignupForm[topics][]" value="js">
<label for="i1">JavaScript</label></div></div>

<div class="invalid-feedback"></div>
</div>
<div class="form-field mb-3 field-signupform-plan">
<label class="form-label">Plan</label>
<input type="hidden" name="SignupForm[plan]" value=""><div id="signupform-plan" class="form-group" role="radiogroup"><div class="form-field"><input type="radio" id="i2" class="radio" name="SignupForm[plan]" value="free">
<label for="i2">Free</label></div>
<div class="form-field"><input type="radio" id="i3" class="radio" name="SignupForm[plan]" value="pro" checked>
<label for="i3">Pro</label></div></div>

<div class="invalid-feedback"></div>
</div>
```

### Select, range, static control, and input group

```php
echo $form->field($model, 'country')->dropDownList(['es' => 'Spain', 've' => 'Venezuela'], ['prompt' => 'Select...']);
echo $form->field($model, 'volume')->rangeInput(
    ['min' => 0, 'max' => 100, 'rangeOptions' => ['data-bs-bubble' => 'true']],
);
echo $form->field($model, 'username')->staticControl();
echo $form->field(
    $model,
    'amount',
    ['inputTemplate' => '<div class="input-group"><span class="input-group-text">$</span>{input}</div>'],
);
```

```html
<div class="form-field mb-3 field-signupform-country">
<label class="form-label" for="signupform-country">Country</label>
<select id="signupform-country" class="form-control" name="SignupForm[country]">
<option value="">Select...</option>
<option value="es">Spain</option>
<option value="ve">Venezuela</option>
</select>

<div class="invalid-feedback"></div>
</div>
<div class="form-field mb-3 field-signupform-volume">
<label class="form-label" for="signupform-volume">Volume</label>
<div class="form-range" data-bs-bubble="true"><input type="range" id="signupform-volume" class="form-range-input" name="SignupForm[volume]" value="50" min="0" max="100"></div>

<div class="invalid-feedback"></div>
</div>
<div class="form-field mb-3 field-signupform-username">
<label class="form-label" for="signupform-username">Username</label>
<input type="text" id="signupform-username" class="form-control-plaintext" value="admin" readonly>

<div class="invalid-feedback"></div>
</div>
<div class="form-field mb-3 field-signupform-amount">
<label class="form-label" for="signupform-amount">Amount</label>
<div class="input-group"><span class="input-group-text">$</span><input type="text" id="signupform-amount" class="form-control" name="SignupForm[amount]"></div>

<div class="invalid-feedback"></div>
</div>
```

Bootstrap 6 uses `.form-control` for `<select>` elements too; `rangeInput()` registers the plugin bundle that
initializes the range.

### Client-side validation styling and error summary

```php
$form = ActiveForm::begin(['action' => '/signup', 'nativeValidation' => true, 'options' => ['novalidate' => true]]);
echo $form->errorSummary($model);
echo $form->field($model, 'email');
echo Html::submitButton('Sign up', ['class' => 'btn-solid theme-primary']);
ActiveForm::end();
```

```html
<form id="w0" action="/signup" method="post" novalidate data-bs-validate>
<div class="alert theme-danger"><p>Please fix the following errors:</p><ul><li>Email is not a valid email address.</li></ul></div>
<div class="form-field mb-3 field-signupform-email required">
<label class="form-label" for="signupform-email">Email</label>
<input type="text" id="signupform-email" class="form-control is-invalid" name="SignupForm[email]" value="not-an-email" aria-required="true" aria-invalid="true">

<div class="invalid-feedback">Email is not a valid email address.</div>
</div>
<button type="submit" class="btn-solid theme-primary">Sign up</button>
</form>
```

### Widgets as fields

Input widgets work with `ActiveField::widget()` (the `tags`, `fruit`, and `birthday` attributes of `SignupForm`):

```php
use yii2\extensions\bootstrap6\{Chips, Combobox, Datepicker};

echo $form->field($model, 'tags')->widget(Chips::class, ['placeholder' => 'Add a tag']);
echo $form->field($model, 'fruit')->widget(Combobox::class, ['items' => ['apple' => 'Apple', 'pear' => 'Pear']]);
echo $form->field($model, 'birthday')->widget(Datepicker::class, ['inline' => true]);
```

## HTML helpers

```php
use yii2\extensions\bootstrap6\Html;
use yii2\extensions\bootstrap6\enum\Theme;

echo Html::badge('New', Theme::PRIMARY);
echo Html::closeButton(['data-bs-dismiss' => 'alert']);
echo Html::staticControl('email@example.com', ['id' => 'staticEmail']);
echo Html::checkbox('notify', true, ['switch' => true, 'label' => 'Email me']);
echo Html::radioList(
    'plan',
    'pro',
    ['free' => 'Free', 'pro' => 'Pro'],
    ['itemOptions' => ['wrapperOptions' => ['class' => 'form-field-card']]],
);

$options = ['class' => 'card'];
Html::theme($options, Theme::ACCENT);
echo Html::tag('div', 'Card', $options);
```

```html
<span class="badge theme-primary">New</span>
<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
<input type="text" id="staticEmail" class="form-control-plaintext" value="email@example.com" readonly>
<div class="switch"><input type="checkbox" id="i0" name="notify" value="1" checked role="switch" switch></div>
<label for="i0">Email me</label>
<div class="form-group"><div class="form-field form-field-card"><input type="radio" id="i1" class="radio" name="plan" value="free">
<label for="i1">Free</label></div>
<div class="form-field form-field-card"><input type="radio" id="i2" class="radio" name="plan" value="pro" checked>
<label for="i2">Pro</label></div></div>
<div class="card theme-accent">Card</div>
```

## JavaScript

### Plugin options and client events

```php
use yii2\extensions\bootstrap6\Toast;

echo Toast::widget(
    [
        'body' => 'Saved.',
        'clientOptions' => ['autohide' => false],
        'clientEvents' => ['hidden.bs.toast' => 'function (event) { console.log(event.type); }'],
    ],
);
```

The widget registers, at the end of the body:

```js
import("/assets/<hash>/bootstrap.bundle.js").then((bootstrap) => { bootstrap.Toast.getOrCreateInstance(document.getElementById("w0"), {"autohide":false}); });
document.getElementById("w0").addEventListener("hidden.bs.toast", function (event) { console.log(event.type); });
```

### Accessing plugin instances with `import()`

The bundle is an ES module without globals. Import the same URL the page loaded, so the browser reuses the evaluated
module; `BootstrapPluginAsset::importJs()` builds the call from PHP:

```php
use yii2\extensions\bootstrap6\Dialog;
use yii2\extensions\bootstrap6\asset\BootstrapPluginAsset;

Dialog::begin(['title' => 'Saved', 'options' => ['id' => 'confirm']]);
echo '<p>Opened from PHP through <code>import()</code>.</p>';
Dialog::end();

$bundle = BootstrapPluginAsset::register($this);

$this->registerJs(
    $bundle->importJs($this, 'bootstrap.Dialog.getOrCreateInstance(document.getElementById("confirm")).show();'),
);
```

In your own module scripts, read the URL with `$bundle->moduleUrl($this)` and import it:

```js
const { Dialog } = await import(moduleUrl);

Dialog.getOrCreateInstance(document.getElementById("confirm")).show();
```

### Accessing plugin instances through `window.bootstrap`

Enable `exposeGlobal` (see the [installation guide](installation.md#the-javascript-bundle-is-an-es-module)) for
scripts written against the Bootstrap 5 global. The assignment happens when the module import resolves, so wait for
the `load` event:

```js
window.addEventListener("load", () => {
  bootstrap.Toast.getOrCreateInstance(document.getElementById("w0")).show();
});
```

## Next steps

- 📚 [Installation Guide](installation.md)
- ⚙️ [Configuration Reference](configuration.md)
- 🔄 [Migration Guide](migration.md)
- 🧪 [Testing Guide](testing.md)
