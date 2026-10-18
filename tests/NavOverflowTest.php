<?php

declare(strict_types=1);

namespace yii2\extensions\bootstrap6\tests;

use PHPUnit\Framework\Attributes\{DataProviderExternal, Group};
use yii\base\InvalidConfigException;
use yii2\extensions\bootstrap6\asset\BootstrapPluginAsset;
use yii2\extensions\bootstrap6\enum\{Breakpoint, NavStyle, Placement};
use yii2\extensions\bootstrap6\{Nav, NavOverflow};
use yii2\extensions\bootstrap6\tests\provider\NavOverflowProvider;
use yii2\extensions\bootstrap6\tests\support\TestCase;

/**
 * Unit tests for {@see NavOverflow} wrapper markup, data attributes, and NavOverflow plugin initialization.
 *
 * {@see NavOverflowProvider} for test case data providers.
 */
#[Group('widget')]
final class NavOverflowTest extends TestCase
{
    public function testRenderDataAttributes(): void
    {
        $this->mockWebApplication();

        self::assertSameHtml(
            <<<HTML
            <div id="w0" class="nav-overflow" data-bs-toggle="nav-overflow" data-bs-collapse-below="md" data-bs-more-text="See all" data-bs-menu-placement="bottom-start"><ul id="w1" class="nav"></ul></div>
            <div id="w2" class="nav-overflow" data-bs-toggle="nav-overflow" data-bs-collapse-below="768" data-bs-more-text="false" data-bs-menu-placement="top md:bottom-end"><ul id="w3" class="nav"></ul></div>
            <div id="w4" class="nav-overflow" data-bs-toggle="nav-overflow" data-bs-collapse-below="lg"><ul id="w5" class="nav"></ul></div>
            HTML,
            NavOverflow::widget(
                ['collapseBelow' => Breakpoint::MD, 'moreText' => 'See all', 'placement' => Placement::BOTTOM_START],
            )
            . "\n"
            . NavOverflow::widget(
                ['collapseBelow' => 768, 'moreText' => false, 'placement' => 'top md:bottom-end'],
            )
            . "\n"
            . NavOverflow::widget(
                ['collapseBelow' => 'lg'],
            ),
            'Typed options must render as data attributes.',
        );
    }

    public function testRenderDefaults(): void
    {
        $this->mockWebApplication();

        self::assertSameHtml(
            <<<HTML
            <div id="w0" class="custom nav-overflow" data-bs-toggle="nav-overflow"><ul id="w1" class="nav nav-pills"><li class="nav-item"><a class="nav-link active" href="#" aria-current="page">Home</a></li>
            <li class="nav-overflow-keep nav-item"><a class="nav-link" href="#">Help</a></li></ul></div>
            HTML,
            NavOverflow::widget(
                [
                    'nav' => [
                        'items' => [
                            ['label' => 'Home', 'active' => true],
                            ['label' => 'Help', 'options' => ['class' => 'nav-overflow-keep']],
                        ],
                        'style' => NavStyle::PILLS,
                    ],
                    'options' => ['class' => 'custom'],
                ],
            ),
            'Nav must be wrapped for the data API.',
        );
        self::assertArrayHasKey(
            BootstrapPluginAsset::class,
            self::webView()->assetBundles,
            'Plugin bundle must be registered.',
        );
        self::assertSame(
            'import("/assets/js/bootstrap.bundle.js").then((bootstrap) => { bootstrap.NavOverflow.getOrCreateInstance(document.getElementById("w0"), {}); });',
            self::registeredJs(self::webView(), 'w0-plugin'),
            'Default initialization must also support fragments inserted after page load.',
        );
    }

    public function testRenderInitializesPluginWithClientOptions(): void
    {
        $this->mockWebApplication();

        self::assertSameHtml(
            <<<HTML
            <div id="w0" class="nav-overflow"><ul id="w1" class="nav"></ul></div>
            HTML,
            NavOverflow::widget(['clientOptions' => ['threshold' => 2]]),
            'Toggle attribute must be omitted for the JavaScript initialization.',
        );
        self::assertSame(
            <<<JS
            import("/assets/js/bootstrap.bundle.js").then((bootstrap) => { bootstrap.NavOverflow.getOrCreateInstance(document.getElementById("w0"), {"threshold":2}); });
            JS,
            self::registeredJs(self::webView(), 'w0-plugin'),
            'Plugin must target the wrapper.',
        );
    }

    public function testRenderKeepsDataApiWhenClientOptionsAreDisabled(): void
    {
        $this->mockWebApplication();

        self::assertSameHtml(
            <<<HTML
            <div id="w0" class="nav-overflow" data-bs-toggle="nav-overflow"><ul id="w1" class="nav"></ul></div>
            HTML,
            NavOverflow::widget(['clientOptions' => false]),
            'Toggle attribute must be kept.',
        );
        self::assertNull(
            self::registeredJs(self::webView(), 'w0-plugin'),
            'No inline init must be registered.',
        );
    }

    public function testRenderWithNavClass(): void
    {
        $this->mockWebApplication();

        $nav = new class (['id' => 'n']) extends Nav {
            public function run(): string
            {
                return '<nav class="nav"></nav>';
            }
        };

        self::assertSameHtml(
            <<<HTML
            <div id="w0" class="nav-overflow" data-bs-toggle="nav-overflow"><nav class="nav"></nav></div>
            HTML,
            NavOverflow::widget(['navClass' => $nav::class]),
            'Configured nav class must render the nav.',
        );
    }

    /**
     * @param array<string, mixed> $config
     */
    #[DataProviderExternal(NavOverflowProvider::class, 'invalidEnumValueCases')]
    public function testThrowInvalidConfigExceptionForInvalidEnumValue(array $config, string $message): void
    {
        $this->mockWebApplication();

        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage(
            $message,
        );

        new NavOverflow([...$config]);
    }
}
