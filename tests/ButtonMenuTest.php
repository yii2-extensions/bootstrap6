<?php

declare(strict_types=1);

namespace yii2\extensions\bootstrap6\tests;

use PHPUnit\Framework\Attributes\{DataProviderExternal, Group};
use yii\base\InvalidConfigException;
use yii2\extensions\bootstrap6\{ButtonMenu, Menu};
use yii2\extensions\bootstrap6\enum\{ButtonVariant, Placement, Size, Theme};
use yii2\extensions\bootstrap6\tests\provider\ButtonMenuProvider;
use yii2\extensions\bootstrap6\tests\support\stub\StringableValue;
use yii2\extensions\bootstrap6\tests\support\TestCase;

/**
 * Unit tests for {@see ButtonMenu} toggle, split button, container, and Menu plugin rendering.
 *
 * {@see ButtonMenuProvider} for test case data providers.
 */
#[Group('widget')]
final class ButtonMenuTest extends TestCase
{
    private const array ITEMS = ['items' => [['label' => 'Action', 'url' => '#']]];

    public function testRenderAnchorToggle(): void
    {
        $this->mockWebApplication();

        self::assertSameHtml(
            <<<HTML
            <a id="w0" class="btn-outline" href="/index.php?r=site%2Findex" role="link" data-bs-toggle="menu" aria-expanded="false">Go</a>
            <div id="w0-menu" class="menu"><a class="menu-item" href="#">Action</a></div>
            HTML,
            ButtonMenu::widget(
                [
                    'label' => 'Go',
                    'menu' => self::ITEMS,
                    'options' => ['href' => ['/site/index'], 'role' => 'link'],
                    'tagName' => 'a',
                    'variant' => 'btn-outline',
                ],
            ),
            'Anchor toggle must resolve `href` and keep the configured role.',
        );
    }

    public function testRenderAnchorToggleDefaults(): void
    {
        $this->mockWebApplication();

        self::assertSameHtml(
            <<<HTML
            <a id="w0" class="btn-solid" href="#" data-bs-toggle="menu" aria-expanded="false" role="button">Go</a>
            <div id="w0-menu" class="menu"><a class="menu-item" href="#">Action</a></div>
            HTML,
            ButtonMenu::widget(['label' => 'Go', 'menu' => self::ITEMS, 'tagName' => 'a']),
            'Anchor toggle must default to `#` and `role="button"`.',
        );
    }

    public function testRenderButtonIsOverridable(): void
    {
        $this->mockWebApplication();

        $widget = new class (['label' => 'X', 'menu' => self::ITEMS]) extends ButtonMenu {
            protected function renderButton(string $tagName, string $content, array $options): string
            {
                return '<i>' . parent::renderButton($tagName, $content, $options) . '</i>';
            }
        };

        self::assertSameHtml(
            <<<HTML
            <i><button type="button" id="w0" class="btn-solid" data-bs-toggle="menu" aria-expanded="false">X</button></i>
            <div id="w0-menu" class="menu"><a class="menu-item" href="#">Action</a></div>
            HTML,
            $widget->run(),
            'Subclass must be able to decorate the button.',
        );
    }

    public function testRenderContainerWithoutSplit(): void
    {
        $this->mockWebApplication();

        self::assertSameHtml(
            <<<HTML
            <div class="btn-group"><button type="button" id="w0" class="btn-solid" data-bs-toggle="menu" aria-expanded="false">Label</button>
            <div id="w0-menu" class="menu"><a class="menu-item" href="#">Action</a></div></div>
            HTML,
            ButtonMenu::widget(['label' => 'Label', 'menu' => self::ITEMS, 'renderContainer' => true]),
            'Container must wrap the toggle and the menu.',
        );
    }

    public function testRenderDefaults(): void
    {
        $this->mockWebApplication();

        self::assertSameHtml(
            <<<HTML
            <button type="button" id="w0" class="btn-solid" data-bs-toggle="menu" aria-expanded="false">Button</button>
            <div id="w0-menu" class="menu"><a class="menu-item" href="#">Action</a></div>
            HTML,
            ButtonMenu::widget(['menu' => self::ITEMS]),
            'Toggle must precede the menu without a container.',
        );
        self::assertNull(self::registeredJs(self::webView(), 'w0-plugin'), 'Data API must not need an inline init.');
    }

    public function testRenderInitializesPluginOnToggle(): void
    {
        $this->mockWebApplication();

        ButtonMenu::widget(
            [
                'clientEvents' => ['shown.bs.menu' => 'onShown'],
                'clientOptions' => ['offset' => [0, 8]],
                'menu' => self::ITEMS,
            ],
        );

        self::assertSame(
            <<<JS
            import("/assets/js/bootstrap.bundle.js").then((bootstrap) => { bootstrap.Menu.getOrCreateInstance(document.getElementById("w0"), {"offset":[0,8]}); });
            JS,
            self::registeredJs(self::webView(), 'w0-plugin'),
            'Plugin must target the toggle.',
        );
        self::assertSame(
            'document.getElementById("w0").addEventListener("shown.bs.menu", onShown);',
            self::registeredJs(self::webView(), 'w0-events'),
            'Events must be bound to the toggle.',
        );
    }

    public function testRenderLabelEncoding(): void
    {
        $this->mockWebApplication();

        self::assertSameHtml(
            <<<HTML
            <button type="button" id="w0" class="btn-solid" data-bs-toggle="menu" aria-expanded="false">&lt;b&gt;A&lt;/b&gt;</button>
            <div id="w0-menu" class="menu"></div>
            <button type="button" id="w1" class="btn-solid" data-bs-toggle="menu" aria-expanded="false"><b>B</b></button>
            <div id="w1-menu" class="menu"></div>
            HTML,
            ButtonMenu::widget(['label' => '<b>A</b>'])
            . "\n"
            . ButtonMenu::widget(['encodeLabel' => false, 'label' => '<b>B</b>']),
            'Label must be encoded unless disabled.',
        );
    }

    public function testRenderMenuConfiguration(): void
    {
        $this->mockWebApplication();

        $menu = new class extends Menu {};

        self::assertSameHtml(
            <<<HTML
            <button type="button" id="toggle" class="btn-solid" data-bs-toggle="menu" aria-expanded="false">Label</button>
            <div id="panel" class="menu-translucent menu"><button type="button" class="menu-item">Action</button></div>
            HTML,
            ButtonMenu::widget(
                [
                    'label' => 'Label',
                    'menu' => [
                        'items' => [['label' => 'Action']],
                        'options' => ['class' => 'menu-translucent', 'id' => 'panel'],
                    ],
                    'menuClass' => $menu::class,
                    'options' => ['id' => 'toggle'],
                ],
            ),
            'Menu configuration must be passed to the menu class.',
        );
    }

    public function testRenderPlacementAndAutoClose(): void
    {
        $this->mockWebApplication();

        self::assertSameHtml(
            <<<HTML
            <button type="button" id="w0" class="btn-solid" data-bs-toggle="menu" data-bs-placement="top-end" data-bs-auto-close="false" aria-expanded="false">A</button>
            <div id="w0-menu" class="menu"></div>
            <button type="button" id="w1" class="btn-solid" data-bs-toggle="menu" data-bs-placement="bottom md:end" data-bs-auto-close="true" aria-expanded="false">B</button>
            <div id="w1-menu" class="menu"></div>
            <button type="button" id="w2" class="btn-solid" data-bs-toggle="menu" data-bs-auto-close="outside" aria-expanded="false">C</button>
            <div id="w2-menu" class="menu"></div>
            HTML,
            ButtonMenu::widget(['autoClose' => false, 'label' => 'A', 'placement' => Placement::TOP_END])
            . "\n"
            . ButtonMenu::widget(['autoClose' => true, 'label' => 'B', 'placement' => 'bottom md:end'])
            . "\n"
            . ButtonMenu::widget(['autoClose' => 'outside', 'label' => 'C']),
            'Placement and auto close must render as data attributes.',
        );
    }

    public function testRenderSplit(): void
    {
        $this->mockWebApplication();

        self::assertSameHtml(
            <<<HTML
            <section class="shadow btn-group"><a class="btn-solid theme-secondary btn-sm action" href="/index.php?r=site%2Findex" role="button">Split</a>
            <button type="button" id="w0" class="btn-solid theme-secondary btn-sm menu-toggle-split" data-bs-reference="parent" data-bs-toggle="menu" aria-expanded="false"><svg class="bi"></svg><span class="visually-hidden">Toggle menu</span></button>
            <div id="w0-menu" class="menu"><a class="menu-item" href="#">Action</a></div></section>
            HTML,
            ButtonMenu::widget(
                [
                    'buttonOptions' => ['class' => 'action', 'href' => ['/site/index']],
                    'containerOptions' => ['class' => 'shadow', 'tag' => 'section'],
                    'label' => 'Split',
                    'menu' => self::ITEMS,
                    'options' => ['data-bs-reference' => 'parent'],
                    'size' => Size::SM,
                    'split' => true,
                    'splitIcon' => '<svg class="bi"></svg>',
                    'tagName' => 'a',
                    'theme' => Theme::SECONDARY,
                ],
            ),
            'Split mode must render the action button, the toggle, and the menu inside a group.',
        );
    }

    public function testRenderSplitDefaults(): void
    {
        $this->mockWebApplication();

        self::assertSameHtml(
            <<<HTML
            <div class="btn-group"><button type="submit" class="btn-subtle theme-danger btn-lg">Save</button>
            <button type="button" id="w0" class="btn-subtle theme-danger btn-lg menu-toggle-split" data-bs-toggle="menu" aria-expanded="false"><span class="visually-hidden">Toggle menu</span></button>
            <div id="w0-menu" class="menu"><a class="menu-item" href="#">Action</a></div></div>
            HTML,
            ButtonMenu::widget(
                [
                    'buttonOptions' => ['type' => 'submit'],
                    'label' => 'Save',
                    'menu' => self::ITEMS,
                    'size' => 'lg',
                    'split' => true,
                    'theme' => 'danger',
                    'variant' => ButtonVariant::SUBTLE,
                ],
            ),
            'Split mode must keep the configured action button type.',
        );
    }

    public function testRenderStringableContent(): void
    {
        $this->mockWebApplication();

        self::assertSame(
            ButtonMenu::widget(['label' => 'A & B', 'options' => ['id' => 'm']]),
            ButtonMenu::widget(['label' => new StringableValue('A & B'), 'options' => ['id' => 'm']]),
            'Stringable values must render like their `string`.',
        );
        self::assertSame(
            ButtonMenu::widget(['encodeLabel' => false, 'label' => '<b>B</b>', 'options' => ['id' => 'm']]),
            ButtonMenu::widget(
                [
                    'encodeLabel' => false,
                    'label' => new StringableValue('<b>B</b>'),
                    'options' => ['id' => 'm'],
                ],
            ),
            'Raw Stringable label must render like its `string`.',
        );
    }

    public function testRenderWithoutVariant(): void
    {
        $this->mockWebApplication();

        self::assertSameHtml(
            <<<HTML
            <button type="button" id="w0" data-bs-toggle="menu" aria-expanded="false">Plain</button>
            <div id="w0-menu" class="menu"></div>
            HTML,
            ButtonMenu::widget(['label' => 'Plain', 'variant' => null]),
            'No style class must render no `class` attribute.',
        );
    }

    /**
     * @param array<string, mixed> $config
     */
    #[DataProviderExternal(ButtonMenuProvider::class, 'invalidEnumValueCases')]
    public function testThrowInvalidConfigExceptionForInvalidEnumValue(array $config, string $message): void
    {
        $this->mockWebApplication();

        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage(
            $message,
        );

        new ButtonMenu([...$config]);
    }
}
