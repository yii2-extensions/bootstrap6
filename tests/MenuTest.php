<?php

declare(strict_types=1);

namespace yii2\extensions\bootstrap6\tests;

use PHPUnit\Framework\Attributes\Group;
use yii\base\InvalidConfigException;
use yii2\extensions\bootstrap6\asset\BootstrapPluginAsset;
use yii2\extensions\bootstrap6\exception\Message;
use yii2\extensions\bootstrap6\item\MenuItem;
use yii2\extensions\bootstrap6\Menu;
use yii2\extensions\bootstrap6\tests\support\stub\StringableValue;
use yii2\extensions\bootstrap6\tests\support\TestCase;

/**
 * Unit tests for {@see Menu} panel rendering, item states, submenus, and plugin asset registration.
 */
#[Group('widget')]
final class MenuTest extends TestCase
{
    public function testRegisterPluginAssetWithoutInitialization(): void
    {
        $this->mockWebApplication();

        Menu::widget(['items' => [['label' => 'A', 'url' => '#']]]);

        self::assertArrayHasKey(
            BootstrapPluginAsset::class,
            self::webView()->assetBundles,
            'Plugin bundle must be registered for the data API.',
        );
        self::assertNull(
            self::registeredJs(self::webView(), 'w0-plugin'),
            'Data API must not need an inline init.',
        );
    }

    public function testRenderActiveItem(): void
    {
        $this->mockWebApplication();

        self::assertSameHtml(
            <<<HTML
            <div id="w0" class="menu"><a class="menu-item" href="#">Regular</a>
            <a class="menu-item active" href="#" aria-current="true">Active</a>
            <button type="button" class="menu-item active" aria-current="true">Button</button></div>
            HTML,
            Menu::widget(
                [
                    'items' => [
                        ['label' => 'Regular', 'url' => '#', 'active' => false],
                        ['label' => 'Active', 'url' => '#', 'active' => true],
                        ['label' => 'Button', 'active' => true],
                    ],
                ],
            ),
            'Active items must carry the class and `aria-current`.',
        );
    }

    public function testRenderButtonItems(): void
    {
        $this->mockWebApplication();

        self::assertSameHtml(
            <<<HTML
            <div id="w0" class="menu"><button type="button" class="menu-item">Action</button>
            <button type="submit" class="custom menu-item">Submit</button></div>
            HTML,
            Menu::widget(
                [
                    'items' => [
                        ['label' => 'Action'],
                        ['label' => 'Submit', 'linkOptions' => ['class' => 'custom', 'type' => 'submit']],
                    ],
                ],
            ),
            'Items without `url` must render buttons.',
        );
    }

    public function testRenderDisabledItems(): void
    {
        $this->mockWebApplication();

        self::assertSameHtml(
            <<<HTML
            <div id="w0" class="menu"><a class="menu-item disabled" aria-disabled="true">Link</a>
            <button type="button" class="menu-item" disabled>Button</button>
            <button type="button" class="menu-item">Enabled</button></div>
            HTML,
            Menu::widget(
                [
                    'items' => [
                        ['label' => 'Link', 'url' => '#', 'disabled' => true, 'active' => true],
                        ['label' => 'Button', 'disabled' => true],
                        ['label' => 'Enabled', 'disabled' => false],
                    ],
                ],
            ),
            'Disabled state must win over the active state.',
        );
    }

    public function testRenderDividersHeadersAndText(): void
    {
        $this->mockWebApplication();

        self::assertSameHtml(
            <<<HTML
            <div id="w0" class="menu"><h6 class="menu-header">&lt;b&gt;Header&lt;/b&gt;</h6>
            <span class="custom menu-text"><i>Text</i></span>
            <hr class="menu-divider">
            <hr class="my-2 menu-divider">
            <button type="button" class="menu-item">Not a divider</button></div>
            HTML,
            Menu::widget(
                [
                    'items' => [
                        ['header' => '<b>Header</b>'],
                        ['text' => '<i>Text</i>', 'encode' => false, 'options' => ['class' => 'custom']],
                        '-',
                        ['divider' => true, 'options' => ['class' => 'my-2']],
                        ['divider' => false, 'label' => 'Not a divider'],
                    ],
                ],
            ),
            'Special items must render their own elements.',
        );
    }

    public function testRenderItemsIsOverridable(): void
    {
        $this->mockWebApplication();

        $menu = new class (['items' => ['-']]) extends Menu {
            protected function renderItems(array $items, array $options): string
            {
                return '<nav>' . parent::renderItems($items, $options) . '</nav>';
            }
        };

        self::assertSameHtml(
            '<nav><div id="w0" class="menu"><hr class="menu-divider"></div></nav>',
            $menu->run(),
            'Subclass must be able to decorate the panel.',
        );
    }

    public function testRenderLabelEncoding(): void
    {
        $this->mockWebApplication();

        self::assertSameHtml(
            <<<HTML
            <div id="w0" class="menu"><a class="menu-item" href="#"><b>Raw</b></a>
            <a class="menu-item" href="#">&lt;b&gt;Encoded&lt;/b&gt;</a></div>
            HTML,
            Menu::widget(
                [
                    'encodeLabels' => false,
                    'items' => [
                        ['label' => '<b>Raw</b>', 'url' => '#'],
                        ['label' => '<b>Encoded</b>', 'url' => '#', 'encode' => true],
                    ],
                ],
            ),
            'Item `encode` must override the widget setting.',
        );
    }

    public function testRenderLinkItems(): void
    {
        $this->mockWebApplication();

        self::assertSameHtml(
            <<<HTML
            <div id="menu" class="custom menu"><a class="menu-item" href="/site/index">&lt;Home&gt;</a>
            <a class="menu-item" href="/index.php?r=site%2Fabout">About</a>
            <a class="theme-danger menu-item" href="#">Delete</a></div>
            HTML,
            Menu::widget(
                [
                    'items' => [
                        ['label' => '<Home>', 'url' => '/site/index'],
                        ['label' => 'About', 'url' => ['/site/about']],
                        ['label' => 'Delete', 'url' => '#', 'linkOptions' => ['class' => 'theme-danger']],
                    ],
                    'options' => ['class' => 'custom', 'id' => 'menu'],
                ],
            ),
            'Links must be resolved and encoded.',
        );
    }

    public function testRenderMixedItems(): void
    {
        $this->mockWebApplication();

        self::assertSameHtml(
            <<<HTML
            <div id="w0" class="menu"><a class="menu-item" href="#">&lt;A&gt;</a>
            <a class="menu-item" href="#">B</a>
            <hr class="menu-divider">
            <form class="p-3"></form>
            <button type="button" class="menu-item">C</button></div>
            HTML,
            Menu::widget(
                [
                    'items' => [
                        MenuItem::link('<A>', '#'),
                        ['label' => 'B', 'url' => '#'],
                        '-',
                        '<form class="p-3"></form>',
                        MenuItem::link('Hidden', '#')->withVisible(false),
                        ['label' => 'Hidden', 'url' => '#', 'visible' => false],
                        MenuItem::button('C'),
                    ],
                ],
            ),
            'Value objects, arrays, and strings must render in order.',
        );
    }

    public function testRenderStringableItems(): void
    {
        $this->mockWebApplication();

        self::assertSame(
            Menu::widget([
                'items' => [['encode' => false, 'label' => '<b>A</b>'], ['header' => 'H & h'], ['text' => 'T']],
                'options' => ['id' => 'm'],
            ]),
            Menu::widget([
                'items' => [
                    ['encode' => false, 'label' => new StringableValue('<b>A</b>')],
                    ['header' => new StringableValue('H & h')],
                    ['text' => new StringableValue('T')],
                ],
                'options' => ['id' => 'm'],
            ]),
            'Stringable values must render like their `string`.',
        );
    }

    public function testRenderStringAndInvisibleItems(): void
    {
        $this->mockWebApplication();

        self::assertSameHtml(
            <<<HTML
            <div id="w0" class="menu"><form class="p-3"></form>
            <a class="menu-item" href="#">Visible</a></div>
            HTML,
            Menu::widget(
                [
                    'items' => [
                        '<form class="p-3"></form>',
                        ['label' => 'Hidden', 'url' => '#', 'visible' => false],
                        ['label' => 'Visible', 'url' => '#', 'visible' => true],
                    ],
                ],
            ),
            'Strings must be raw and invisible items skipped.',
        );
    }

    public function testRenderSubmenus(): void
    {
        $this->mockWebApplication();

        self::assertSameHtml(
            <<<HTML
            <div id="w0" class="menu"><div class="submenu"><button type="button" class="menu-item">File</button>
            <div class="menu-scrollable menu"><a class="menu-item" href="#">New</a>
            <div class="nested submenu"><button type="button" class="menu-item active" aria-current="true">Recent</button>
            <div class="shadow menu"><a class="menu-item" href="#">One</a></div></div></div></div>
            <div class="submenu"><button type="button" class="menu-item" disabled>Edit</button>
            <div class="menu-scrollable menu"><a class="menu-item" href="#">Cut</a></div></div>
            <button type="button" class="menu-item">Empty</button></div>
            HTML,
            Menu::widget(
                [
                    'items' => [
                        [
                            'label' => 'File',
                            'items' => [
                                ['label' => 'New', 'url' => '#'],
                                [
                                    'label' => 'Recent',
                                    'active' => true,
                                    'options' => ['class' => 'nested'],
                                    'submenuOptions' => ['class' => 'shadow'],
                                    'items' => [['label' => 'One', 'url' => '#']],
                                ],
                            ],
                        ],
                        ['label' => 'Edit', 'disabled' => true, 'items' => [['label' => 'Cut', 'url' => '#']]],
                        ['label' => 'Empty', 'items' => []],
                    ],
                    'submenuOptions' => ['class' => 'menu-scrollable'],
                ],
            ),
            'Submenus must nest a `.menu` inside a `.submenu` wrapper.',
        );
    }

    public function testRenderValueObjectActiveAndDisabledItems(): void
    {
        $this->mockWebApplication();

        self::assertSameHtml(
            <<<HTML
            <div id="w0" class="menu"><a class="menu-item" href="#">Regular</a>
            <a class="menu-item active" href="#" aria-current="true">Active</a>
            <button type="button" class="menu-item active" aria-current="true">Button</button></div>
            <div id="w1" class="menu"><a class="menu-item disabled" aria-disabled="true">Link</a>
            <button type="button" class="menu-item" disabled>Button</button>
            <button type="button" class="menu-item">Enabled</button></div>
            HTML,
            Menu::widget(
                [
                    'items' => [
                        MenuItem::link('Regular', '#')->withActive(false),
                        MenuItem::link('Active', '#')->withActive(true),
                        MenuItem::button('Button')->withActive(true),
                    ],
                ],
            )
            . "\n"
            . Menu::widget(
                [
                    'items' => [
                        MenuItem::link('Link', '#')->withActive(true)->withDisabled(true),
                        MenuItem::button('Button')->withDisabled(true),
                        MenuItem::button('Enabled'),
                    ],
                ],
            ),
            'States must match the array form.',
        );
    }

    public function testRenderValueObjectDividersHeadersAndText(): void
    {
        $this->mockWebApplication();

        self::assertSameHtml(
            <<<HTML
            <div id="w0" class="menu"><h6 class="menu-header">&lt;b&gt;Header&lt;/b&gt;</h6>
            <span class="custom menu-text"><i>Text</i></span>
            <hr class="menu-divider">
            <hr class="my-2 menu-divider">
            <button type="button" class="menu-item">Not a divider</button></div>
            HTML,
            Menu::widget(
                [
                    'items' => [
                        MenuItem::header('<b>Header</b>'),
                        MenuItem::text('<i>Text</i>')->withEncode(false)->withOptions(['class' => 'custom']),
                        MenuItem::divider(),
                        MenuItem::divider()->withOptions(['class' => 'my-2']),
                        MenuItem::button('Not a divider'),
                    ],
                ],
            ),
            'Special items must match the array form.',
        );
    }

    public function testRenderValueObjectLinkKinds(): void
    {
        $this->mockWebApplication();

        self::assertSameHtml(
            <<<HTML
            <div id="w0" class="menu"><a class="menu-item">No URL</a>
            <div class="submenu"><button type="button" class="menu-item">Parent</button>
            <div class="menu"><a class="menu-item" href="#">Child</a></div></div>
            <a class="menu-item" href="#"><b>Raw</b></a></div>
            HTML,
            Menu::widget(
                [
                    'items' => [
                        MenuItem::link('No URL', null),
                        MenuItem::link('Parent', '/parent')->withItems([MenuItem::link('Child', '#')]),
                        MenuItem::link('<b>Raw</b>', '#')->withEncode(false),
                    ],
                ],
            ),
            'Items must render a link without `href` and a submenu over the URL.',
        );
    }

    public function testRenderValueObjectSubmenus(): void
    {
        $this->mockWebApplication();

        self::assertSameHtml(
            <<<HTML
            <div id="w0" class="menu"><div class="submenu"><button type="button" class="menu-item">File</button>
            <div class="menu-scrollable menu"><a class="menu-item" href="#">New</a>
            <div class="nested submenu"><button type="button" class="menu-item active" aria-current="true">Recent</button>
            <div class="shadow menu"><a class="menu-item" href="#">One</a></div></div></div></div>
            <div class="submenu"><button type="button" class="menu-item" disabled>Edit</button>
            <div class="menu-scrollable menu"><a class="menu-item" href="#">Cut</a></div></div>
            <button type="button" class="menu-item">Empty</button></div>
            HTML,
            Menu::widget(
                [
                    'items' => [
                        MenuItem::submenu(
                            'File',
                            [
                                MenuItem::link('New', '#'),
                                MenuItem::submenu('Recent', [MenuItem::link('One', '#')])
                                    ->withActive(true)
                                    ->withOptions(['class' => 'nested'])
                                    ->withSubmenuOptions(['class' => 'shadow']),
                            ],
                        ),
                        MenuItem::submenu('Edit', [MenuItem::link('Cut', '#')])->withDisabled(true),
                        MenuItem::submenu('Empty', []),
                    ],
                    'submenuOptions' => ['class' => 'menu-scrollable'],
                ],
            ),
            'Submenus must match the array form.',
        );
    }

    public function testThrowInvalidConfigExceptionWhenItemLabelIsMissing(): void
    {
        $this->mockWebApplication();

        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage(
            Message::ITEM_LABEL_REQUIRED->getMessage(),
        );

        Menu::widget(['items' => [['url' => '#']]]);
    }
}
