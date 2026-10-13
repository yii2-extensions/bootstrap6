<?php

declare(strict_types=1);

namespace yii2\extensions\bootstrap6\tests;

use PHPUnit\Framework\Attributes\{DataProviderExternal, Group};
use yii\base\InvalidConfigException;
use yii2\extensions\bootstrap6\asset\{BootstrapAsset, BootstrapPluginAsset};
use yii2\extensions\bootstrap6\Button;
use yii2\extensions\bootstrap6\enum\{ButtonVariant, Size, Theme};
use yii2\extensions\bootstrap6\tests\provider\ButtonProvider;
use yii2\extensions\bootstrap6\tests\support\stub\StringableValue;
use yii2\extensions\bootstrap6\tests\support\TestCase;

/**
 * Unit tests for {@see Button} variants, sizes, tags, toggle state, and plugin registration.
 *
 * {@see ButtonProvider} for test case data providers.
 */
#[Group('widget')]
final class ButtonTest extends TestCase
{
    private const string IMPORT = 'import("/assets/js/bootstrap.bundle.js").then((bootstrap) => { ';

    public function testAnchorReceivesButtonRole(): void
    {
        $this->mockWebApplication();

        self::assertSame(
            '<a id="w0" class="btn-solid theme-primary" href="#" role="button">Link</a>',
            Button::widget(
                ['label' => 'Link', 'options' => ['href' => '#'], 'tagName' => 'a', 'theme' => Theme::PRIMARY],
            ),
            'Anchor must carry `role="button"` and no `type`.',
        );
        self::assertSame(
            '<a id="w1" class="btn-solid" role="link">Link</a>',
            Button::widget(['label' => 'Link', 'options' => ['role' => 'link'], 'tagName' => 'a']),
            'Configured role must be kept.',
        );
    }

    public function testClientEventsRegisterPluginWithoutInitialization(): void
    {
        $this->mockWebApplication();

        Button::widget(['clientEvents' => ['click' => 'onClick']]);

        self::assertSame(
            'document.getElementById("w0").addEventListener("click", onClick);',
            self::registeredJs(self::webView(), 'w0-events'),
            'Event listener must be registered.',
        );
        self::assertNull(
            self::registeredJs(self::webView(), 'w0-plugin'),
            '`false` client options must skip the initialization.',
        );
    }

    public function testClientOptionsInitializePlugin(): void
    {
        $this->mockWebApplication();

        Button::widget(['clientOptions' => []]);

        self::assertSame(
            self::IMPORT . 'bootstrap.Button.getOrCreateInstance(document.getElementById("w0"), {}); });',
            self::registeredJs(self::webView(), 'w0-plugin'),
            'Empty array must create the plugin instance.',
        );
    }

    public function testInputTagAndConfiguredType(): void
    {
        $this->mockWebApplication();

        self::assertSame(
            '<button type="submit" id="w0" class="btn-solid">Send</button>',
            Button::widget(['label' => 'Send', 'options' => ['type' => 'submit']]),
            'Configured `type` must be kept.',
        );
        self::assertSame(
            '<span id="w1" class="btn-solid">Span</span>',
            Button::widget(['label' => 'Span', 'tagName' => 'span']),
            'Other tags must not receive `type` nor `role`.',
        );
    }

    public function testLabelEncoding(): void
    {
        $this->mockWebApplication();

        self::assertSame(
            '<button type="button" id="w0" class="btn-solid">&lt;b&gt;Bold&lt;/b&gt;</button>',
            Button::widget(['label' => '<b>Bold</b>']),
            'Label must be encoded by default.',
        );
        self::assertSame(
            '<button type="button" id="w1" class="btn-solid"><b>Bold</b></button>',
            Button::widget(['encodeLabel' => false, 'label' => '<b>Bold</b>']),
            'Raw label must not be encoded.',
        );
    }

    public function testRenderDefault(): void
    {
        $this->mockWebApplication();

        self::assertSame(
            '<button type="button" id="w0" class="btn-solid">Button</button>',
            Button::widget(),
            'Default button must use the solid variant and translated label.',
        );
        self::assertArrayHasKey(
            BootstrapAsset::class,
            self::webView()->assetBundles,
            'CSS bundle must be registered.',
        );
        self::assertArrayNotHasKey(
            BootstrapPluginAsset::class,
            self::webView()->assetBundles,
            'Plain buttons must not load the plugin bundle.',
        );
    }

    public function testRenderStringableContent(): void
    {
        $this->mockWebApplication();

        self::assertSame(
            Button::widget(['label' => 'A & B', 'options' => ['id' => 'b']]),
            Button::widget(['label' => new StringableValue('A & B'), 'options' => ['id' => 'b']]),
            'Stringable values must render like their `string`.',
        );
    }

    /**
     * @param array<string, mixed> $config
     */
    #[DataProviderExternal(ButtonProvider::class, 'invalidEnumValueCases')]
    public function testThrowInvalidConfigExceptionForInvalidEnumValue(array $config, string $message): void
    {
        $this->mockWebApplication();

        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage(
            $message,
        );

        new Button([...$config]);
    }

    public function testToggleButton(): void
    {
        $this->mockWebApplication();

        self::assertSameHtml(
            <<<HTML
            <button type="button" id="w0" class="btn-solid theme-primary" data-bs-toggle="button" aria-pressed="false">Toggle</button>
            HTML,
            Button::widget(['label' => 'Toggle', 'theme' => 'primary', 'toggle' => true]),
            'Toggle attributes must be rendered.',
        );
        self::assertSameHtml(
            <<<HTML
            <button type="button" id="w1" class="active btn-solid" aria-pressed="true" data-bs-toggle="button">On</button>
            HTML,
            Button::widget(
                ['label' => 'On', 'options' => ['aria-pressed' => 'true', 'class' => 'active'], 'toggle' => true],
            ),
            'Configured pressed state must be kept.',
        );
        self::assertArrayHasKey(
            BootstrapPluginAsset::class,
            self::webView()->assetBundles,
            'Toggle buttons must load the plugin bundle.',
        );
        self::assertNull(
            self::registeredJs(self::webView(), 'w0-plugin'),
            'Toggle buttons rely on the data API.',
        );
    }

    public function testVariantSizeAndTheme(): void
    {
        $this->mockWebApplication();

        self::assertSame(
            '<button type="button" id="w0" class="btn-outline theme-secondary btn-lg">Large</button>',
            Button::widget(
                [
                    'label' => 'Large',
                    'size' => Size::LG,
                    'theme' => Theme::SECONDARY,
                    'variant' => ButtonVariant::OUTLINE,
                ],
            ),
            'Enum variant, theme, and size must be rendered.',
        );
        self::assertSame(
            '<button type="button" id="w1" class="btn-subtle btn-xs">Small</button>',
            Button::widget(['label' => 'Small', 'size' => 'xs', 'variant' => 'btn-subtle']),
            'String variant and size must be rendered.',
        );
        self::assertSame(
            '<button type="button" id="w2" class="btn">Base</button>',
            Button::widget(['label' => 'Base', 'variant' => null]),
            '`null` variant must use the base class.',
        );
    }
}
