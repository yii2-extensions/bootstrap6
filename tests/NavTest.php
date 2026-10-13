<?php

declare(strict_types=1);

namespace yii2\extensions\bootstrap6\tests;

use PHPUnit\Framework\Attributes\{DataProviderExternal, Group};
use Yii;
use yii\base\{Action, InvalidConfigException, Module};
use yii\web\Controller;
use yii2\extensions\bootstrap6\asset\{BootstrapAsset, BootstrapPluginAsset};
use yii2\extensions\bootstrap6\enum\{NavLayout, NavStyle};
use yii2\extensions\bootstrap6\exception\Message;
use yii2\extensions\bootstrap6\item\{MenuItem, NavItem};
use yii2\extensions\bootstrap6\{Menu, Nav};
use yii2\extensions\bootstrap6\tests\provider\NavProvider;
use yii2\extensions\bootstrap6\tests\support\stub\StringableValue;
use yii2\extensions\bootstrap6\tests\support\TestCase;

/**
 * Unit tests for {@see Nav} item rendering, active state detection, menu items, styles, and asset registration.
 *
 * {@see NavProvider} for test case data providers.
 */
#[Group('widget')]
final class NavTest extends TestCase
{
    public function testIsItemActiveRequiresStringRoute(): void
    {
        $this->mockWebApplication();

        $nav = new class (['route' => 'site/index']) extends Nav {
            /**
             * @param array<array-key, mixed> $item
             */
            public function check(array $item): bool
            {
                return $this->isItemActive(NavItem::fromArray(['label' => 'Item', ...$item]));
            }
        };

        self::assertFalse(
            $nav->check(['url' => ['id' => '1']]),
            'URL without route must be inactive.',
        );
        self::assertFalse(
            $nav->check(['url' => [5]]),
            'Non-string route must be inactive.',
        );
        self::assertTrue(
            $nav->check(['url' => ['/site/index']]),
            'Matching route must be active.',
        );
    }

    public function testRenderActivateParents(): void
    {
        $this->mockWebApplication();

        self::assertSameHtml(
            <<<HTML
            <ul id="w0" class="nav"><li class="nav-item"><a class="nav-link active" href="#" data-bs-toggle="menu" role="button" aria-expanded="false">Menu</a><div id="w1" class="menu"><div class="submenu"><button type="button" class="menu-item active" aria-current="true">Sub</button>
            <div class="menu"><a class="menu-item active" href="#" aria-current="true">Page</a></div></div></div></li></ul>
            HTML,
            Nav::widget(
                [
                    'activateParents' => true,
                    'items' => [
                        [
                            'label' => 'Menu',
                            'items' => [
                                ['label' => 'Sub', 'items' => [['label' => 'Page', 'url' => '#', 'active' => true]]],
                            ],
                        ],
                    ],
                ],
            ),
            'Active descendants must activate every ancestor.',
        );
    }

    public function testRenderActivateParentsByRoute(): void
    {
        $this->mockWebApplication();

        self::assertSameHtml(
            <<<HTML
            <ul id="w0" class="nav"><li class="nav-item"><a class="nav-link" href="#" data-bs-toggle="menu" role="button" aria-expanded="false">Hidden</a><div id="w1" class="menu"></div></li>
            <li class="nav-item"><a class="nav-link active" href="#" data-bs-toggle="menu" role="button" aria-expanded="false">Visible</a><div id="w2" class="menu"><hr class="menu-divider">
            <div class="submenu"><button type="button" class="menu-item">Sub</button>
            <div class="menu"><a class="menu-item" href="#">X</a></div></div>
            <a class="menu-item active" href="/index.php?r=site%2Findex" aria-current="true">Page</a></div></li></ul>
            HTML,
            Nav::widget(
                [
                    'activateParents' => true,
                    'items' => [
                        [
                            'label' => 'Hidden',
                            'items' => [['label' => 'Page', 'url' => ['/site/index'], 'visible' => false]],
                        ],
                        [
                            'label' => 'Visible',
                            'items' => [
                                '-',
                                ['label' => 'Sub', 'items' => [['label' => 'X', 'url' => '#']]],
                                ['label' => 'Page', 'url' => ['/site/index']],
                            ],
                        ],
                    ],
                    'route' => 'site/index',
                ],
            ),
            'Only visible active descendants must activate the parent.',
        );
    }

    public function testRenderActiveByAbsoluteRouteWithoutController(): void
    {
        $this->mockWebApplication();

        self::assertSameHtml(
            <<<HTML
            <ul id="w0" class="nav"><li class="nav-item"><a class="nav-link active" href="/index.php?r=site%2Findex" aria-current="page">Home</a></li>
            <li class="nav-item"><a class="nav-link" href="/index.php?r=site%2Fabout">About</a></li></ul>
            HTML,
            Nav::widget(
                [
                    'items' => [
                        ['label' => 'Home', 'url' => ['/site/index']],
                        ['label' => 'About', 'url' => ['/site/about']],
                    ],
                    'route' => 'site/index',
                ],
            ),
            'Absolute routes must match without a controller.',
        );
    }

    public function testRenderActiveByRouteAndParams(): void
    {
        $this->mockWebApplication();

        $controller = new Controller('site', new Module('admin', Yii::$app));
        $controller->action = new Action('users', $controller);

        Yii::$app->controller = $controller;
        Yii::$app->getRequest()->setQueryParams(
            ['id' => '42', 'f' => '4.2', 'ids' => ['1', '2'], 'deep' => ['a' => ['x' => '1']], 'page' => 3],
        );

        self::assertSameHtml(
            <<<HTML
            <ul id="w0" class="nav"><li class="nav-item"><a class="nav-link" href="/index.php?r=admin%2Fsite%2Findex">Index</a></li>
            <li class="nav-item"><a class="nav-link active" href="/index.php?r=admin%2Fsite%2Fusers" aria-current="page">Users</a></li>
            <li class="nav-item"><a class="nav-link active" href="/index.php?r=admin%2Fsite%2Fusers&amp;id=42" aria-current="page">User 42</a></li>
            <li class="nav-item"><a class="nav-link" href="/index.php?r=admin%2Fsite%2Fusers&amp;id=7">User 7</a></li>
            <li class="nav-item"><a class="nav-link" href="/index.php?r=admin%2Fsite%2Fusers&amp;tab=1">Tab</a></li>
            <li class="nav-item"><a class="nav-link active" href="/index.php?r=admin%2Fsite%2Fusers" aria-current="page">Any id</a></li>
            <li class="nav-item"><a class="nav-link active" href="/index.php?r=admin%2Fsite%2Fusers#top" aria-current="page">Anchor</a></li>
            <li class="nav-item"><a class="nav-link" href="/index.php?r=site%2Fusers">Absolute</a></li>
            <li class="nav-item"><a class="nav-link active" href="/index.php?r=admin%2Fsite%2Fusers" aria-current="page">Absolute match</a></li>
            <li class="nav-item"><a class="nav-link" href="/index.php?r=admin%2Fsite%2Fusers">Forced off</a></li>
            <li class="nav-item"><a class="nav-link" href="/site/users">String URL</a></li>
            <li class="nav-item"><a class="nav-link active" href="/index.php?r=admin%2Fsite%2Fusers&amp;id=42" aria-current="page">Int 42</a></li>
            <li class="nav-item"><a class="nav-link" href="/index.php?r=admin%2Fsite%2Fusers&amp;id=43">Int 43</a></li>
            <li class="nav-item"><a class="nav-link active" href="/index.php?r=admin%2Fsite%2Fusers&amp;f=4.2" aria-current="page">Float 4.2</a></li>
            <li class="nav-item"><a class="nav-link" href="/index.php?r=admin%2Fsite%2Fusers&amp;f=4.3">Float 4.3</a></li>
            <li class="nav-item"><a class="nav-link active" href="/index.php?r=admin%2Fsite%2Fusers&amp;ids%5B0%5D=1&amp;ids%5B1%5D=2" aria-current="page">List 1 2</a></li>
            <li class="nav-item"><a class="nav-link" href="/index.php?r=admin%2Fsite%2Fusers&amp;ids%5B0%5D=1&amp;ids%5B1%5D=3">List 1 3</a></li>
            <li class="nav-item"><a class="nav-link" href="/index.php?r=admin%2Fsite%2Fusers&amp;ids%5B0%5D=1">List 1</a></li>
            <li class="nav-item"><a class="nav-link" href="/index.php?r=admin%2Fsite%2Fusers&amp;ids%5B0%5D=1&amp;ids%5B1%5D=2&amp;ids%5B2%5D=3">List 1 2 3</a></li>
            <li class="nav-item"><a class="nav-link" href="/index.php?r=admin%2Fsite%2Fusers&amp;ids%5Ba%5D=1&amp;ids%5Bb%5D=2">Keyed list</a></li>
            <li class="nav-item"><a class="nav-link" href="/index.php?r=admin%2Fsite%2Fusers&amp;id%5B0%5D=42">Array id</a></li>
            <li class="nav-item"><a class="nav-link" href="/index.php?r=admin%2Fsite%2Fusers&amp;ids=1">Scalar list</a></li>
            <li class="nav-item"><a class="nav-link" href="/index.php?r=admin%2Fsite%2Fusers&amp;ids=Array">Array literal</a></li>
            <li class="nav-item"><a class="nav-link active" href="/index.php?r=admin%2Fsite%2Fusers&amp;deep%5Ba%5D%5Bx%5D=1" aria-current="page">Deep match</a></li>
            <li class="nav-item"><a class="nav-link" href="/index.php?r=admin%2Fsite%2Fusers&amp;deep%5Ba%5D%5Bx%5D=2">Deep mismatch</a></li>
            <li class="nav-item"><a class="nav-link active" href="/index.php?r=admin%2Fsite%2Fusers&amp;page=3" aria-current="page">Page 3</a></li></ul>
            HTML,
            Nav::widget(
                [
                    'items' => [
                        ['label' => 'Index', 'url' => ['site/index']],
                        ['label' => 'Users', 'url' => ['site/users']],
                        ['label' => 'User 42', 'url' => ['site/users', 'id' => '42']],
                        ['label' => 'User 7', 'url' => ['site/users', 'id' => '7']],
                        ['label' => 'Tab', 'url' => ['site/users', 'tab' => 1]],
                        ['label' => 'Any id', 'url' => ['site/users', 'id' => null]],
                        ['label' => 'Anchor', 'url' => ['site/users', '#' => 'top']],
                        ['label' => 'Absolute', 'url' => ['/site/users']],
                        ['label' => 'Absolute match', 'url' => ['/admin/site/users']],
                        ['label' => 'Forced off', 'url' => ['site/users'], 'active' => false],
                        ['label' => 'String URL', 'url' => '/site/users'],
                        ['label' => 'Int 42', 'url' => ['site/users', 'id' => 42]],
                        ['label' => 'Int 43', 'url' => ['site/users', 'id' => 43]],
                        ['label' => 'Float 4.2', 'url' => ['site/users', 'f' => 4.2]],
                        ['label' => 'Float 4.3', 'url' => ['site/users', 'f' => 4.3]],
                        ['label' => 'List 1 2', 'url' => ['site/users', 'ids' => [1, 2]]],
                        ['label' => 'List 1 3', 'url' => ['site/users', 'ids' => [1, 3]]],
                        ['label' => 'List 1', 'url' => ['site/users', 'ids' => [1]]],
                        ['label' => 'List 1 2 3', 'url' => ['site/users', 'ids' => [1, 2, 3]]],
                        ['label' => 'Keyed list', 'url' => ['site/users', 'ids' => ['a' => 1, 'b' => 2]]],
                        ['label' => 'Array id', 'url' => ['site/users', 'id' => [42]]],
                        ['label' => 'Scalar list', 'url' => ['site/users', 'ids' => 1]],
                        ['label' => 'Array literal', 'url' => ['site/users', 'ids' => 'Array']],
                        ['label' => 'Deep match', 'url' => ['site/users', 'deep' => ['a' => ['x' => 1]]]],
                        ['label' => 'Deep mismatch', 'url' => ['site/users', 'deep' => ['a' => ['x' => 2]]]],
                        ['label' => 'Page 3', 'url' => ['site/users', 'page' => '3']],
                    ],
                ],
            ),
            'Route and parameters must decide the active state.',
        );
    }

    public function testRenderActiveDisabledByActivateItems(): void
    {
        $this->mockWebApplication();

        self::assertSameHtml(
            <<<HTML
            <ul id="w0" class="nav"><li class="nav-item"><a class="nav-link" href="#">Item</a></li></ul>
            HTML,
            Nav::widget(['activateItems' => false, 'items' => [['label' => 'Item', 'active' => true]]]),
            'Explicit active state must be ignored.',
        );
    }

    public function testRenderItems(): void
    {
        $this->mockWebApplication();

        self::assertSameHtml(
            <<<HTML
            <ul id="main" class="custom nav"><li class="nav-item"><a class="nav-link active" href="#" aria-current="page">&lt;Home&gt;</a></li>
            <li class="extra nav-item"><a class="link nav-link" href="/about"><b>About</b></a></li>
            <li class="nav-item"><a class="nav-link disabled" aria-disabled="true">Disabled</a></li>
            <li class="nav-item"><a class="nav-link" href="#">Enabled</a></li>
            <li class="nav-item"><span class="navbar-text">Raw</span></li>
            <li class="nav-item"><a class="nav-link" href="#">Visible</a></li></ul>
            HTML,
            Nav::widget(
                [
                    'items' => [
                        ['label' => '<Home>', 'active' => true],
                        [
                            'encode' => false,
                            'label' => '<b>About</b>',
                            'linkOptions' => ['class' => 'link'],
                            'options' => ['class' => 'extra'],
                            'url' => '/about',
                        ],
                        ['label' => 'Disabled', 'disabled' => true, 'active' => true],
                        ['label' => 'Enabled', 'disabled' => false],
                        '<li class="nav-item"><span class="navbar-text">Raw</span></li>',
                        ['label' => 'Hidden', 'visible' => false],
                        ['label' => 'Visible', 'visible' => true],
                    ],
                    'options' => ['class' => 'custom', 'id' => 'main'],
                ],
            ),
            'Items must render links, states, and raw strings.',
        );
    }

    public function testRenderMenuItems(): void
    {
        $this->mockWebApplication();

        self::assertSameHtml(
            <<<HTML
            <ul id="w0" class="nav nav-tabs"><li class="nav-item"><a class="nav-link" href="#" data-bs-toggle="menu" role="button" aria-expanded="false">More</a><div id="panel" class="menu-translucent menu"><a class="menu-item active" href="#" aria-current="true">&lt;A&gt;</a>
            <hr class="menu-divider">
            <a class="menu-item" href="#">B</a></div></li>
            <li class="nav-item"><a class="nav-link" href="#" data-bs-toggle="menu" role="button" aria-expanded="false">Raw</a><div class="menu">x</div></li>
            <li class="nav-item"><a class="nav-link" href="#">Empty</a></li></ul>
            HTML,
            Nav::widget(
                [
                    'items' => [
                        [
                            'label' => 'More',
                            'menuOptions' => ['class' => 'menu-translucent', 'id' => 'panel'],
                            'items' => [
                                ['label' => '<A>', 'url' => '#', 'active' => true],
                                '-',
                                ['label' => 'Hidden', 'url' => '#', 'active' => true, 'visible' => false],
                                ['label' => 'B', 'url' => '#'],
                            ],
                        ],
                        ['label' => 'Raw', 'items' => '<div class="menu">x</div>'],
                        ['label' => 'Empty', 'items' => []],
                    ],
                    'style' => 'nav-tabs',
                ],
            ),
            'Menu items must render a toggle followed by the panel.',
        );
        self::assertArrayHasKey(
            BootstrapPluginAsset::class,
            self::webView()->assetBundles,
            'Menu toggles need the plugin bundle.',
        );
    }

    public function testRenderMenuItemsRegisterClientEvents(): void
    {
        $this->mockWebApplication();

        Nav::widget(
            [
                'clientEvents' => ['shown.bs.menu' => 'onShown'],
                'items' => [['label' => 'M', 'items' => '<div class="menu"></div>']],
            ],
        );

        self::assertSame(
            'document.getElementById("w0").addEventListener("shown.bs.menu", onShown);',
            self::registeredJs(self::webView(), 'w0-events'),
            'Bubbling menu events must be bound to the nav.',
        );
    }

    public function testRenderMenuLabelsFollowEncodeLabels(): void
    {
        $this->mockWebApplication();

        self::assertSameHtml(
            <<<HTML
            <ul id="w0" class="nav"><li class="nav-item"><a class="nav-link" href="#" data-bs-toggle="menu" role="button" aria-expanded="false"><i>M</i></a><div id="w1" class="menu"><a class="menu-item" href="#"><b>A</b></a></div></li></ul>
            HTML,
            Nav::widget(
                [
                    'encodeLabels' => false,
                    'items' => [['label' => '<i>M</i>', 'items' => [['label' => '<b>A</b>', 'url' => '#']]]],
                ],
            ),
            'Menu must inherit the encoding setting.',
        );
    }

    public function testRenderProtectedMethodsAreOverridable(): void
    {
        $this->mockWebApplication();

        $items = [['label' => 'Forced'], ['label' => 'Menu', 'items' => [['label' => 'A', 'url' => '#']]]];

        $nav = new class (['items' => $items]) extends Nav {
            protected function isChildActive(array $items, bool &$active): array
            {
                $items = parent::isChildActive($items, $active);
                $active = $items !== [];

                return $items;
            }

            protected function isItemActive(MenuItem|NavItem $item): bool
            {
                return $item->label === 'Forced' || parent::isItemActive($item);
            }

            protected function renderItem(NavItem|string $item): string
            {
                return '<!--i-->' . parent::renderItem($item);
            }

            protected function renderMenu(array $items, array $options): string
            {
                return parent::renderMenu($items, $options) . '<!--m-->';
            }
        };

        self::assertSameHtml(
            <<<HTML
            <ul id="w0" class="nav"><!--i--><li class="nav-item"><a class="nav-link active" href="#" aria-current="page">Forced</a></li>
            <!--i--><li class="nav-item"><a class="nav-link active" href="#" data-bs-toggle="menu" role="button" aria-expanded="false">Menu</a><div id="w1" class="menu"><a class="menu-item" href="#">A</a></div><!--m--></li></ul>
            HTML,
            $nav->run(),
            'Subclass overrides must take effect.',
        );
    }

    public function testRenderStringableItems(): void
    {
        $this->mockWebApplication();

        self::assertSame(
            Nav::widget(['items' => [['label' => 'A & B', 'url' => '#']], 'options' => ['id' => 'n']]),
            Nav::widget(
                [
                    'items' => [['label' => new StringableValue('A & B'), 'url' => '#']],
                    'options' => ['id' => 'n'],
                ],
            ),
            'Stringable values must render like their `string`.',
        );
    }

    public function testRenderStyleAndLayout(): void
    {
        $this->mockWebApplication();

        self::assertSameHtml(
            <<<HTML
            <ul id="w0" class="nav nav-pills nav-pills-vertical nav-fill"></ul>
            <ul id="w1" class="nav nav-underline flex-column"></ul>
            HTML,
            Nav::widget(['layout' => NavLayout::FILL, 'style' => NavStyle::PILLS_VERTICAL])
            . "\n"
            . Nav::widget(['layout' => 'flex-column', 'style' => NavStyle::UNDERLINE]),
            'Style and layout classes must follow the base class.',
        );
    }

    public function testRenderValueObjectActivateParentsByRoute(): void
    {
        $this->mockWebApplication();

        $items = [
            NavItem::menu('Menu', [MenuItem::submenu('Sub', [MenuItem::link('Page', ['/site/index'])])]),
        ];

        self::assertSameHtml(
            <<<HTML
            <ul id="w0" class="nav"><li class="nav-item"><a class="nav-link" href="#" data-bs-toggle="menu" role="button" aria-expanded="false">Menu</a><div id="w1" class="menu"><div class="submenu"><button type="button" class="menu-item">Sub</button>
            <div class="menu"><a class="menu-item active" href="/index.php?r=site%2Findex" aria-current="true">Page</a></div></div></div></li></ul>
            <ul id="w2" class="nav"><li class="nav-item"><a class="nav-link active" href="#" data-bs-toggle="menu" role="button" aria-expanded="false">Menu</a><div id="w3" class="menu"><div class="submenu"><button type="button" class="menu-item active" aria-current="true">Sub</button>
            <div class="menu"><a class="menu-item active" href="/index.php?r=site%2Findex" aria-current="true">Page</a></div></div></div></li></ul>
            HTML,
            Nav::widget(['items' => $items, 'route' => 'site/index'])
            . "\n"
            . Nav::widget(['activateParents' => true, 'items' => $items, 'route' => 'site/index']),
            'Nested routes must activate the item, and the ancestors only with `activateParents`.',
        );
    }

    public function testRenderValueObjectActiveByRoute(): void
    {
        $this->mockWebApplication();

        self::assertSameHtml(
            <<<HTML
            <ul id="w0" class="nav"><li class="nav-item"><a class="nav-link active" href="/index.php?r=site%2Findex" aria-current="page">Detected</a></li>
            <li class="nav-item"><a class="nav-link" href="/index.php?r=site%2Findex">Forced off</a></li>
            <li class="nav-item"><a class="nav-link active" href="/site/about" aria-current="page">Forced on</a></li>
            <li class="nav-item"><a class="nav-link" href="/index.php?r=site%2Fabout">Other</a></li>
            <li class="nav-item"><a class="nav-link active" href="/index.php?r=site%2Findex" aria-current="page">Array</a></li></ul>
            HTML,
            Nav::widget(
                [
                    'items' => [
                        NavItem::to('Detected', ['/site/index']),
                        NavItem::to('Forced off', ['/site/index'])->withActive(false),
                        NavItem::to('Forced on', '/site/about')->withActive(true),
                        NavItem::to('Other', ['/site/about']),
                        ['label' => 'Array', 'url' => ['/site/index']],
                    ],
                    'route' => 'site/index',
                ],
            ),
            'A `null` active state must be detected and an explicit one kept.',
        );
    }

    public function testRenderValueObjectItems(): void
    {
        $this->mockWebApplication();

        self::assertSameHtml(
            <<<HTML
            <ul id="main" class="custom nav"><li class="nav-item"><a class="nav-link active" href="#" aria-current="page">&lt;Home&gt;</a></li>
            <li class="extra nav-item"><a class="link nav-link" href="/about"><b>About</b></a></li>
            <li class="nav-item"><a class="nav-link disabled" aria-disabled="true">Disabled</a></li>
            <li class="nav-item"><a class="nav-link" href="#">Enabled</a></li>
            <li class="nav-item"><span class="navbar-text">Raw</span></li>
            <li class="nav-item"><a class="nav-link" href="#">Visible</a></li>
            <li class="nav-item"><a class="nav-link">No URL</a></li></ul>
            HTML,
            Nav::widget(
                [
                    'items' => [
                        NavItem::to('<Home>')->withActive(true),
                        NavItem::to('<b>About</b>', '/about')
                            ->withEncode(false)
                            ->withOptions(['class' => 'extra'])
                            ->withLinkOptions(['class' => 'link']),
                        NavItem::to('Disabled')->withActive(true)->withDisabled(true),
                        ['label' => 'Enabled', 'disabled' => false],
                        '<li class="nav-item"><span class="navbar-text">Raw</span></li>',
                        NavItem::to('Hidden')->withVisible(false),
                        NavItem::to('Visible'),
                        NavItem::to('No URL', null),
                    ],
                    'options' => ['class' => 'custom', 'id' => 'main'],
                ],
            ),
            'Value objects must render like the array form.',
        );
    }

    public function testRenderValueObjectMenuItems(): void
    {
        $this->mockWebApplication();

        self::assertSameHtml(
            <<<HTML
            <ul id="w0" class="nav nav-tabs"><li class="nav-item"><a class="nav-link" href="#" data-bs-toggle="menu" role="button" aria-expanded="false">More</a><div id="panel" class="menu-translucent menu"><a class="menu-item active" href="#" aria-current="true">&lt;A&gt;</a>
            <hr class="menu-divider">
            <a class="menu-item" href="#">B</a></div></li>
            <li class="nav-item"><a class="nav-link" href="#" data-bs-toggle="menu" role="button" aria-expanded="false">Raw</a><div class="menu">x</div></li>
            <li class="nav-item"><a class="nav-link" href="#">Empty</a></li></ul>
            HTML,
            Nav::widget(
                [
                    'items' => [
                        NavItem::menu(
                            'More',
                            [
                                MenuItem::link('<A>', '#')->withActive(true),
                                '-',
                                MenuItem::link('Hidden', '#')->withActive(true)->withVisible(false),
                                MenuItem::link('B', '#'),
                            ],
                        )
                            ->withMenuOptions(['class' => 'menu-translucent', 'id' => 'panel']),
                        NavItem::menu('Raw', '<div class="menu">x</div>'),
                        NavItem::menu('Empty', []),
                    ],
                    'style' => 'nav-tabs',
                ],
            ),
            'Menu items must render like the array form.',
        );
    }

    public function testRenderWithoutMenuRegistersStylesOnly(): void
    {
        $this->mockWebApplication();

        Nav::widget(['items' => [['label' => 'A']]]);

        self::assertArrayHasKey(
            BootstrapAsset::class,
            self::webView()->assetBundles,
            'CSS bundle must be registered.',
        );
        self::assertArrayNotHasKey(
            BootstrapPluginAsset::class,
            self::webView()->assetBundles,
            'Plugin bundle must not be registered.',
        );
    }

    public function testStyleClasses(): void
    {
        self::assertSame(
            [],
            Nav::styleClasses(null),
            'No style means no class.',
        );
        self::assertSame(
            ['nav-tabs'],
            Nav::styleClasses('nav-tabs'),
            'String style must be kept.',
        );
        self::assertSame(
            ['nav-pills', 'nav-pills-vertical'],
            Nav::styleClasses('nav-pills-vertical'),
            'Vertical pills need the pills class.',
        );
    }

    /**
     * @param array<string, mixed> $config
     */
    #[DataProviderExternal(NavProvider::class, 'invalidEnumValueCases')]
    public function testThrowInvalidConfigExceptionForInvalidEnumValue(array $config, string $message): void
    {
        $this->mockWebApplication();

        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage(
            $message,
        );

        new Nav([...$config]);
    }

    public function testThrowInvalidConfigExceptionWhenItemLabelIsMissing(): void
    {
        $this->mockWebApplication();

        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage(
            Message::ITEM_LABEL_REQUIRED->getMessage(),
        );

        Nav::widget(['items' => [['url' => '#']]]);
    }

    public function testUseMenuClass(): void
    {
        $this->mockWebApplication();

        $menu = new class extends Menu {
            public function run(): string
            {
                return '<div class="custom-menu"></div>';
            }
        };

        self::assertSameHtml(
            <<<HTML
            <ul id="w1" class="nav"><li class="nav-item"><a class="nav-link" href="#" data-bs-toggle="menu" role="button" aria-expanded="false">M</a><div class="custom-menu"></div></li></ul>
            HTML,
            Nav::widget(['items' => [['label' => 'M', 'items' => [['label' => 'A']]]], 'menuClass' => $menu::class]),
            'Configured menu class must render the panel.',
        );
    }
}
