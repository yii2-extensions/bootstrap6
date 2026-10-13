<?php

declare(strict_types=1);

namespace yii2\extensions\bootstrap6\tests;

use PHPUnit\Framework\Attributes\Group;
use yii\base\InvalidConfigException;
use yii2\extensions\bootstrap6\asset\BootstrapPluginAsset;
use yii2\extensions\bootstrap6\enum\NavStyle;
use yii2\extensions\bootstrap6\exception\Message;
use yii2\extensions\bootstrap6\item\{MenuItem, TabItem};
use yii2\extensions\bootstrap6\{Menu, Tabs};
use yii2\extensions\bootstrap6\tests\support\stub\StringableValue;
use yii2\extensions\bootstrap6\tests\support\TestCase;

/**
 * Unit tests for {@see Tabs} tab buttons, panes, activation rules, menus, and Tab plugin registration.
 */
#[Group('widget')]
final class TabsTest extends TestCase
{
    public function testRenderActivatesFirstEligiblePane(): void
    {
        $this->mockWebApplication();

        self::assertSameHtml(
            <<<HTML
            <ul id="w0" class="nav nav-tabs" role="tablist"><li class="nav-item" role="presentation"><a class="nav-link" href="/docs">Docs</a></li>
            <li class="nav-item" role="presentation"><button type="button" id="w0-tab2" class="nav-link" data-bs-toggle="tab" data-bs-target="#w0-pane2" role="tab" aria-controls="w0-pane2" aria-selected="false">Off</button></li>
            <li class="nav-item" role="presentation"><button type="button" id="w0-tab3" class="nav-link" disabled data-bs-toggle="tab" data-bs-target="#w0-pane3" role="tab" aria-controls="w0-pane3" aria-selected="false">Disabled</button></li>
            <li class="nav-item" role="presentation"><button type="button" class="nav-link" data-bs-toggle="menu" aria-expanded="false">Menu</button><div id="w1" class="menu"><button type="button" id="w0-dd4-tab0" class="menu-item" data-bs-toggle="tab" data-bs-target="#w0-dd4-pane0" role="tab" aria-controls="w0-dd4-pane0" aria-selected="false">Child</button></div></li>
            <li class="nav-item" role="presentation"><button type="button" id="w0-tab5" class="nav-link active" data-bs-toggle="tab" data-bs-target="#w0-pane5" role="tab" aria-controls="w0-pane5" aria-selected="true">First</button></li>
            <li class="nav-item" role="presentation"><button type="button" id="w0-tab6" class="nav-link" data-bs-toggle="tab" data-bs-target="#w0-pane6" role="tab" aria-controls="w0-pane6" aria-selected="false">Second</button></li>
            <li class="nav-item" role="presentation"><span class="navbar-text">Raw</span></li></ul>
            <div class="tab-content"><div id="w0-pane2" class="tab-pane" role="tabpanel" aria-labelledby="w0-tab2" tabindex="0">2</div>
            <div id="w0-pane3" class="tab-pane" role="tabpanel" aria-labelledby="w0-tab3" tabindex="0">3</div>
            <div id="w0-dd4-pane0" class="tab-pane" role="tabpanel" aria-labelledby="w0-dd4-tab0" tabindex="0">4</div>
            <div id="w0-pane5" class="tab-pane active" role="tabpanel" aria-labelledby="w0-tab5" tabindex="0">5</div>
            <div id="w0-pane6" class="tab-pane" role="tabpanel" aria-labelledby="w0-tab6" tabindex="0"></div></div>
            HTML,
            Tabs::widget(
                [
                    'items' => [
                        ['label' => 'Docs', 'url' => '/docs'],
                        ['label' => 'Hidden', 'content' => '1', 'visible' => false],
                        ['label' => 'Off', 'content' => '2', 'active' => false],
                        ['label' => 'Disabled', 'content' => '3', 'disabled' => true],
                        ['label' => 'Menu', 'items' => [['label' => 'Child', 'content' => '4']]],
                        ['label' => 'First', 'content' => '5', 'visible' => true],
                        ['label' => 'Second'],
                        '<li class="nav-item" role="presentation"><span class="navbar-text">Raw</span></li>',
                    ],
                ],
            ),
            'First visible, enabled pane without explicit state must be activated.',
        );
        self::assertArrayHasKey(
            BootstrapPluginAsset::class,
            self::webView()->assetBundles,
            'Tab plugin bundle must be registered.',
        );
        self::assertNull(
            self::registeredJs(self::webView(), 'w0-plugin'),
            'Data API must not need an inline init.',
        );
    }

    public function testRenderExplicitActiveMenuItem(): void
    {
        $this->mockWebApplication();

        self::assertSameHtml(
            <<<HTML
            <ul id="w0" class="nav nav-tabs" role="tablist"><li class="nav-item" role="presentation"><button type="button" id="w0-tab0" class="nav-link" data-bs-toggle="tab" data-bs-target="#w0-pane0" role="tab" aria-controls="w0-pane0" aria-selected="false">A</button></li>
            <li class="nav-item" role="presentation"><button type="button" class="nav-link active" data-bs-toggle="menu" aria-expanded="false">&lt;Menu&gt;</button><div id="w1" class="menu"><h6 class="menu-header">Header</h6>
            <a class="menu-item" href="/x">Link</a>
            <button type="button" id="w0-dd1-tab3" class="active menu-item" data-bs-toggle="tab" data-bs-target="#w0-dd1-pane3" role="tab" aria-controls="w0-dd1-pane3" aria-selected="true"><b>B</b></button>
            <button type="button" id="w0-dd1-tab4" class="menu-item" disabled data-bs-toggle="tab" data-bs-target="#w0-dd1-pane4" role="tab" aria-controls="w0-dd1-pane4" aria-selected="false">C</button></div></li></ul>
            <div class="tab-content"><div id="w0-pane0" class="tab-pane" role="tabpanel" aria-labelledby="w0-tab0" tabindex="0">a</div>
            <div id="w0-dd1-pane3" class="tab-pane active" role="tabpanel" aria-labelledby="w0-dd1-tab3" tabindex="0">b</div>
            <div id="w0-dd1-pane4" class="tab-pane" role="tabpanel" aria-labelledby="w0-dd1-tab4" tabindex="0">c</div></div>
            HTML,
            Tabs::widget(
                [
                    'items' => [
                        ['label' => 'A', 'content' => 'a'],
                        [
                            'label' => '<Menu>',
                            'items' => [
                                ['header' => 'Header'],
                                ['label' => 'Link', 'url' => '/x'],
                                ['label' => 'Hidden', 'content' => 'h', 'active' => true, 'visible' => false],
                                ['label' => '<b>B</b>', 'content' => 'b', 'active' => true, 'encode' => false],
                                ['label' => 'C', 'content' => 'c', 'disabled' => true],
                            ],
                        ],
                        ['label' => 'Hidden', 'active' => true, 'visible' => false],
                    ],
                ],
            ),
            'Active menu item must activate its pane and the toggle.',
        );
    }

    public function testRenderExplicitActiveTabOnly(): void
    {
        $this->mockWebApplication();

        self::assertSameHtml(
            <<<HTML
            <ul id="w0" class="nav nav-tabs" role="tablist"><li class="nav-item" role="presentation"><button type="button" id="w0-tab0" class="nav-link" data-bs-toggle="tab" data-bs-target="#w0-pane0" role="tab" aria-controls="w0-pane0" aria-selected="false">A</button></li>
            <li class="nav-item" role="presentation"><button type="button" id="w0-tab1" class="nav-link active" data-bs-toggle="tab" data-bs-target="#w0-pane1" role="tab" aria-controls="w0-pane1" aria-selected="true">B</button></li></ul>
            HTML,
            Tabs::widget(
                ['items' => [['label' => 'A'], ['label' => 'B', 'active' => true]], 'renderTabContent' => false],
            ),
            'Explicit active tab must prevent the default activation.',
        );
    }

    public function testRenderIgnoresInvisibleActiveTab(): void
    {
        $this->mockWebApplication();

        self::assertSameHtml(
            <<<HTML
            <ul id="w0" class="nav nav-tabs" role="tablist"><li class="nav-item" role="presentation"><button type="button" id="w0-tab1" class="nav-link active" data-bs-toggle="tab" data-bs-target="#w0-pane1" role="tab" aria-controls="w0-pane1" aria-selected="true">A</button></li></ul>
            HTML,
            Tabs::widget(
                [
                    'items' => [['label' => 'Hidden', 'active' => true, 'visible' => false], ['label' => 'A']],
                    'renderTabContent' => false,
                ],
            ),
            'Invisible active tab must not prevent the default activation.',
        );
    }

    public function testRenderInactiveMenuItemsBeforeActiveTab(): void
    {
        $this->mockWebApplication();

        self::assertSameHtml(
            <<<HTML
            <ul id="w0" class="nav nav-tabs" role="tablist"><li class="nav-item" role="presentation"><button type="button" class="nav-link" data-bs-toggle="menu" aria-expanded="false">Menu</button><div id="w1" class="menu"><button type="button" id="w0-dd0-tab0" class="menu-item" data-bs-toggle="tab" data-bs-target="#w0-dd0-pane0" role="tab" aria-controls="w0-dd0-pane0" aria-selected="false">X</button>
            <button type="button" id="w0-dd0-tab1" class="menu-item" data-bs-toggle="tab" data-bs-target="#w0-dd0-pane1" role="tab" aria-controls="w0-dd0-pane1" aria-selected="false">Y</button></div></li>
            <li class="nav-item" role="presentation"><button type="button" id="w0-tab1" class="nav-link active" data-bs-toggle="tab" data-bs-target="#w0-pane1" role="tab" aria-controls="w0-pane1" aria-selected="true">Z</button></li></ul>
            HTML,
            Tabs::widget(
                [
                    'items' => [
                        [
                            'label' => 'Menu',
                            'items' => [['label' => 'X', 'active' => false], ['label' => 'Y']],
                        ],
                        ['label' => 'Z', 'active' => true],
                    ],
                    'renderTabContent' => false,
                ],
            ),
            'Toggle must stay inactive without active menu items.',
        );
    }

    public function testRenderMixedItems(): void
    {
        $this->mockWebApplication();

        self::assertSameHtml(
            <<<HTML
            <ul id="w0" class="nav nav-tabs" role="tablist"><li class="nav-item" role="presentation"><button type="button" id="w0-tab0" class="nav-link" data-bs-toggle="tab" data-bs-target="#w0-pane0" role="tab" aria-controls="w0-pane0" aria-selected="false">A</button></li>
            <li class="nav-item" role="presentation"><button type="button" id="w0-tab1" class="nav-link" data-bs-toggle="tab" data-bs-target="#w0-pane1" role="tab" aria-controls="w0-pane1" aria-selected="false">B</button></li>
            <li class="nav-item" role="presentation"><span class="navbar-text">Raw</span></li>
            <li class="nav-item" role="presentation"><button type="button" class="nav-link" data-bs-toggle="menu" aria-expanded="false">M</button><div id="w1" class="menu"><button type="button" id="w0-dd3-tab0" class="menu-item" data-bs-toggle="tab" data-bs-target="#w0-dd3-pane0" role="tab" aria-controls="w0-dd3-pane0" aria-selected="false">C</button>
            <a class="menu-item active" href="/l" aria-current="true">L</a>
            <hr class="menu-divider"></div></li></ul>
            <div class="tab-content"><div id="w0-pane0" class="tab-pane" role="tabpanel" aria-labelledby="w0-tab0" tabindex="0">a</div>
            <div id="w0-pane1" class="tab-pane" role="tabpanel" aria-labelledby="w0-tab1" tabindex="0">b</div>
            <div id="w0-dd3-pane0" class="tab-pane" role="tabpanel" aria-labelledby="w0-dd3-tab0" tabindex="0">c</div></div>
            HTML,
            Tabs::widget(
                [
                    'items' => [
                        TabItem::to('A', 'a'),
                        ['content' => 'b', 'label' => 'B'],
                        '<li class="nav-item" role="presentation"><span class="navbar-text">Raw</span></li>',
                        TabItem::menu(
                            'M',
                            [TabItem::to('C', 'c'), MenuItem::link('L', '/l')->withActive(true), '-'],
                        ),
                    ],
                ],
            ),
            'Active menu link must prevent the default activation.',
        );
    }

    public function testRenderNavTypeAndOptions(): void
    {
        $this->mockWebApplication();

        self::assertSameHtml(
            <<<HTML
            <ul id="w0" class="nav nav-pills nav-pills-vertical" role="tablist" aria-orientation="vertical"></ul>
            <div class="tab-content"></div>
            <ul id="w1" class="custom nav nav-underline" role="list"></ul>
            <div class="tab-content"></div>
            <ul id="w2" class="nav nav-pills nav-pills-vertical" aria-orientation="horizontal" role="tablist"></ul>
            <div class="tab-content"></div>
            HTML,
            Tabs::widget(['navType' => NavStyle::PILLS_VERTICAL])
            . "\n"
            . Tabs::widget(['navType' => 'nav-underline', 'options' => ['class' => 'custom', 'role' => 'list']])
            . "\n"
            . Tabs::widget(['navType' => 'nav-pills-vertical', 'options' => ['aria-orientation' => 'horizontal']]),
            'Style classes and tab list attributes must be rendered.',
        );
    }

    public function testRenderPaneAndHeaderOptions(): void
    {
        $this->mockWebApplication();

        self::assertSameHtml(
            <<<HTML
            <ul id="w0" class="nav nav-tabs" role="tablist"><li class="all one nav-item" role="presentation"><button type="button" id="first" class="tab nav-link active" data-bs-toggle="tab" data-bs-target="#w0-pane0" role="tab" aria-controls="w0-pane0" aria-selected="true">&lt;b&gt;One&lt;/b&gt;</button></li>
            <li class="all nav-item" role="presentation"><button type="button" id="w0-tab1" class="tab nav-link" data-bs-toggle="tab" data-bs-target="#second" role="tab" aria-controls="second" aria-selected="false"><b>Two</b></button></li></ul>
            <div class="extra tab-content"><section id="w0-pane0" class="pane tab-pane active" role="tabpanel" aria-labelledby="first" tabindex="0">One content</section>
            <section id="second" class="pane tab-pane" role="tabpanel" aria-labelledby="w0-tab1" tabindex="0">Two content</section></div>
            HTML,
            Tabs::widget(
                [
                    'headerOptions' => ['class' => 'all'],
                    'itemOptions' => ['class' => 'pane', 'tag' => 'section'],
                    'items' => [
                        [
                            'content' => 'One content',
                            'headerOptions' => ['class' => 'all one'],
                            'label' => '<b>One</b>',
                            'linkOptions' => ['id' => 'first'],
                        ],
                        [
                            'content' => 'Two content',
                            'encode' => false,
                            'label' => '<b>Two</b>',
                            'options' => ['id' => 'second'],
                        ],
                    ],
                    'linkOptions' => ['class' => 'tab'],
                    'tabContentOptions' => ['class' => 'extra'],
                ],
            ),
            'Configured attributes must be merged into headers, buttons, and panes.',
        );
    }

    public function testRenderPanesIsOverridable(): void
    {
        $this->mockWebApplication();

        $tabs = new class (['items' => [['label' => 'A', 'content' => 'a']]]) extends Tabs {
            protected function renderPanes(array $panes): string
            {
                return '<!--' . count($panes) . '-->';
            }
        };

        self::assertSameHtml(
            <<<HTML
            <ul id="w0" class="nav nav-tabs" role="tablist"><li class="nav-item" role="presentation"><button type="button" id="w0-tab0" class="nav-link active" data-bs-toggle="tab" data-bs-target="#w0-pane0" role="tab" aria-controls="w0-pane0" aria-selected="true">A</button></li></ul><!--1-->
            HTML,
            $tabs->run(),
            'Subclass must be able to render the panes.',
        );
    }

    public function testRenderRegistersClientEvents(): void
    {
        $this->mockWebApplication();

        Tabs::widget(['clientEvents' => ['shown.bs.tab' => 'onShown'], 'items' => [['label' => 'A']]]);

        self::assertSame(
            'document.getElementById("w0").addEventListener("shown.bs.tab", onShown);',
            self::registeredJs(self::webView(), 'w0-events'),
            'Bubbling tab events must be bound to the nav.',
        );
    }

    public function testRenderStringableItems(): void
    {
        $this->mockWebApplication();

        self::assertSame(
            Tabs::widget([
                'items' => [
                    ['content' => 'C', 'encode' => false, 'label' => '<b>A</b>'],
                    ['content' => 'D', 'label' => 'B & b'],
                ],
                'options' => ['id' => 't'],
            ]),
            Tabs::widget([
                'items' => [
                    [
                        'content' => new StringableValue('C'),
                        'encode' => false,
                        'label' => new StringableValue('<b>A</b>'),
                    ],
                    ['content' => 'D', 'label' => new StringableValue('B & b')],
                ],
                'options' => ['id' => 't'],
            ]),
            'Stringable values must render like their `string`.',
        );
    }

    public function testRenderValueObjectActivatesFirstEligiblePane(): void
    {
        $this->mockWebApplication();

        self::assertSameHtml(
            <<<HTML
            <ul id="w0" class="nav nav-tabs" role="tablist"><li class="nav-item" role="presentation"><a class="nav-link" href="/docs">Docs</a></li>
            <li class="nav-item" role="presentation"><button type="button" id="w0-tab2" class="nav-link" data-bs-toggle="tab" data-bs-target="#w0-pane2" role="tab" aria-controls="w0-pane2" aria-selected="false">Off</button></li>
            <li class="nav-item" role="presentation"><button type="button" id="w0-tab3" class="nav-link" disabled data-bs-toggle="tab" data-bs-target="#w0-pane3" role="tab" aria-controls="w0-pane3" aria-selected="false">Disabled</button></li>
            <li class="nav-item" role="presentation"><button type="button" class="nav-link" data-bs-toggle="menu" aria-expanded="false">Menu</button><div id="w1" class="menu"><button type="button" id="w0-dd4-tab0" class="menu-item" data-bs-toggle="tab" data-bs-target="#w0-dd4-pane0" role="tab" aria-controls="w0-dd4-pane0" aria-selected="false">Child</button></div></li>
            <li class="nav-item" role="presentation"><button type="button" id="w0-tab5" class="nav-link active" data-bs-toggle="tab" data-bs-target="#w0-pane5" role="tab" aria-controls="w0-pane5" aria-selected="true">First</button></li>
            <li class="nav-item" role="presentation"><button type="button" id="w0-tab6" class="nav-link" data-bs-toggle="tab" data-bs-target="#w0-pane6" role="tab" aria-controls="w0-pane6" aria-selected="false">Second</button></li>
            <li class="nav-item" role="presentation"><span class="navbar-text">Raw</span></li></ul>
            <div class="tab-content"><div id="w0-pane2" class="tab-pane" role="tabpanel" aria-labelledby="w0-tab2" tabindex="0">2</div>
            <div id="w0-pane3" class="tab-pane" role="tabpanel" aria-labelledby="w0-tab3" tabindex="0">3</div>
            <div id="w0-dd4-pane0" class="tab-pane" role="tabpanel" aria-labelledby="w0-dd4-tab0" tabindex="0">4</div>
            <div id="w0-pane5" class="tab-pane active" role="tabpanel" aria-labelledby="w0-tab5" tabindex="0">5</div>
            <div id="w0-pane6" class="tab-pane" role="tabpanel" aria-labelledby="w0-tab6" tabindex="0"></div></div>
            HTML,
            Tabs::widget(
                [
                    'items' => [
                        TabItem::link('Docs', '/docs'),
                        TabItem::to('Hidden', '1')->withVisible(false),
                        TabItem::to('Off', '2')->withActive(false),
                        TabItem::to('Disabled', '3')->withDisabled(true),
                        TabItem::menu('Menu', [TabItem::to('Child', '4')]),
                        TabItem::to('First', '5'),
                        TabItem::to('Second'),
                        '<li class="nav-item" role="presentation"><span class="navbar-text">Raw</span></li>',
                    ],
                ],
            ),
            'Value objects must activate like the array form.',
        );
    }

    public function testRenderValueObjectExplicitActiveMenuItem(): void
    {
        $this->mockWebApplication();

        self::assertSameHtml(
            <<<HTML
            <ul id="w0" class="nav nav-tabs" role="tablist"><li class="nav-item" role="presentation"><button type="button" id="w0-tab0" class="nav-link" data-bs-toggle="tab" data-bs-target="#w0-pane0" role="tab" aria-controls="w0-pane0" aria-selected="false">A</button></li>
            <li class="nav-item" role="presentation"><button type="button" class="nav-link active" data-bs-toggle="menu" aria-expanded="false">&lt;Menu&gt;</button><div id="w1" class="menu"><h6 class="menu-header">Header</h6>
            <a class="menu-item" href="/x">Link</a>
            <button type="button" id="w0-dd1-tab3" class="active menu-item" data-bs-toggle="tab" data-bs-target="#w0-dd1-pane3" role="tab" aria-controls="w0-dd1-pane3" aria-selected="true"><b>B</b></button>
            <button type="button" id="w0-dd1-tab4" class="menu-item" disabled data-bs-toggle="tab" data-bs-target="#w0-dd1-pane4" role="tab" aria-controls="w0-dd1-pane4" aria-selected="false">C</button></div></li></ul>
            <div class="tab-content"><div id="w0-pane0" class="tab-pane" role="tabpanel" aria-labelledby="w0-tab0" tabindex="0">a</div>
            <div id="w0-dd1-pane3" class="tab-pane active" role="tabpanel" aria-labelledby="w0-dd1-tab3" tabindex="0">b</div>
            <div id="w0-dd1-pane4" class="tab-pane" role="tabpanel" aria-labelledby="w0-dd1-tab4" tabindex="0">c</div></div>
            HTML,
            Tabs::widget(
                [
                    'items' => [
                        TabItem::to('A', 'a'),
                        TabItem::menu(
                            '<Menu>',
                            [
                                MenuItem::header('Header'),
                                MenuItem::link('Link', '/x'),
                                TabItem::to('Hidden', 'h')->withActive(true)->withVisible(false),
                                TabItem::to('<b>B</b>', 'b')->withActive(true)->withEncode(false),
                                TabItem::to('C', 'c')->withDisabled(true),
                            ],
                        ),
                        TabItem::to('Hidden')->withActive(true)->withVisible(false),
                    ],
                ],
            ),
            'Menu tabs must render like the array form.',
        );
    }

    public function testRenderWithMenuClassAndLabelEncoding(): void
    {
        $this->mockWebApplication();

        $menu = new class (['id' => 'm']) extends Menu {};

        self::assertSameHtml(
            <<<HTML
            <ul id="w0" class="nav nav-tabs" role="tablist"><li class="nav-item" role="presentation"><button type="button" class="nav-link" data-bs-toggle="menu" aria-expanded="false"><i>M</i></button><div id="w1" class="menu"><a class="menu-item" href="#"><u>L</u></a></div></li></ul>
            HTML,
            Tabs::widget(
                [
                    'encodeLabels' => false,
                    'items' => [['label' => '<i>M</i>', 'items' => [['label' => '<u>L</u>', 'url' => '#']]]],
                    'menuClass' => $menu::class,
                    'renderTabContent' => false,
                ],
            ),
            'Menu must use the configured class and encoding.',
        );
    }

    public function testRenderWithoutTabContent(): void
    {
        $this->mockWebApplication();

        self::assertSameHtml(
            <<<HTML
            <ul id="w0" class="nav nav-tabs" role="tablist"><li class="nav-item" role="presentation"><button type="button" id="w0-tab0" class="nav-link active" data-bs-toggle="tab" data-bs-target="#w0-pane0" role="tab" aria-controls="w0-pane0" aria-selected="true">A</button></li></ul>
            HTML,
            Tabs::widget(['items' => [['label' => 'A', 'content' => 'a']], 'renderTabContent' => false]),
            'Panes must not be rendered.',
        );
    }

    public function testThrowInvalidConfigExceptionForInvalidNavType(): void
    {
        $this->mockWebApplication();

        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage(
            Message::INVALID_VALUE->getMessage(
                'nav-links',
                'navType',
                'nav-pills, nav-pills-vertical, nav-tabs, nav-underline',
            ),
        );

        new Tabs(['navType' => 'nav-links']);
    }

    public function testThrowInvalidConfigExceptionWhenItemLabelIsMissing(): void
    {
        $this->mockWebApplication();

        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage(
            Message::ITEM_LABEL_REQUIRED->getMessage(),
        );

        Tabs::widget(['items' => [['content' => 'a']]]);
    }

    public function testThrowInvalidConfigExceptionWhenMenuItemLabelIsMissing(): void
    {
        $this->mockWebApplication();

        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage(
            Message::ITEM_LABEL_REQUIRED->getMessage(),
        );

        Tabs::widget(['items' => [['label' => 'M', 'items' => [['content' => 'a']]]]]);
    }
}
