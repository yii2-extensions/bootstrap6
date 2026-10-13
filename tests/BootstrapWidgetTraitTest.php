<?php

declare(strict_types=1);

namespace yii2\extensions\bootstrap6\tests;

use PHPUnit\Framework\Attributes\{DataProviderExternal, Group};
use yii\base\InvalidArgumentException;
use yii\web\JsExpression;
use yii2\extensions\bootstrap6\asset\BootstrapPluginAsset;
use yii2\extensions\bootstrap6\BootstrapWidgetTrait;
use yii2\extensions\bootstrap6\enum\{Placement, Size};
use yii2\extensions\bootstrap6\exception\Message;
use yii2\extensions\bootstrap6\tests\provider\BootstrapWidgetTraitProvider;
use yii2\extensions\bootstrap6\tests\support\stub\{StringableValue, StubUnitEnum, StubWidget};
use yii2\extensions\bootstrap6\tests\support\TestCase;

/**
 * Unit tests for {@see BootstrapWidgetTrait} plugin initialization and client event registration.
 *
 * {@see BootstrapWidgetTraitProvider} for test case data providers.
 */
#[Group('widget')]
final class BootstrapWidgetTraitTest extends TestCase
{
    private const string IMPORT = 'import("/assets/js/bootstrap.bundle.js").then((bootstrap) => { ';

    public function testAlwaysInitializesPluginWithEmptyOptions(): void
    {
        $this->mockWebApplication();

        StubWidget::widget(['always' => true]);

        self::assertSame(
            self::IMPORT . 'bootstrap.Toast.getOrCreateInstance(document.getElementById("w0"), {}); });',
            self::js('w0-plugin'),
            'Empty options must render as an empty object.',
        );
    }

    #[DataProviderExternal(BootstrapWidgetTraitProvider::class, 'pluginCases')]
    public function testBundlePluginIsAccepted(string $plugin): void
    {
        $this->mockWebApplication();

        StubWidget::widget(['always' => true, 'plugin' => $plugin]);

        self::assertSame(
            self::IMPORT . "bootstrap.{$plugin}.getOrCreateInstance(document.getElementById(\"w0\"), {}); });",
            self::js('w0-plugin'),
            'Exported plugin must be initialized.',
        );
    }

    public function testClientEventsRegisterListeners(): void
    {
        $this->mockWebApplication();

        StubWidget::widget(
            [
                'clientEvents' => [
                    'shown.bs.toast' => 'function (event) { console.log(event); }',
                    'hidden.bs.toast' => new JsExpression('onHidden'),
                ],
            ],
        );

        self::assertSame(
            <<<JS
            document.getElementById("w0").addEventListener("shown.bs.toast", function (event) { console.log(event); });
            document.getElementById("w0").addEventListener("hidden.bs.toast", onHidden);
            JS,
            self::js('w0-events'),
            'Handlers must be emitted as raw JavaScript.',
        );
        self::assertNull(
            self::js('w0-plugin'),
            'Events alone must not initialize the plugin.',
        );
    }

    public function testClientEventsRegistrationIsOverridable(): void
    {
        $this->mockWebApplication();

        StubWidget::widget(['clientEvents' => ['shown.bs.toast' => 'onShown'], 'events' => false]);

        self::assertNull(
            self::js('w0-events'),
            'Subclass override must replace the registration.',
        );
    }

    public function testClientOptionsAreJsonEncoded(): void
    {
        $import = self::IMPORT;

        $this->mockWebApplication();

        StubWidget::widget(['clientOptions' => ['delay' => 1000, 'title' => '<b>"x"</b>']]);

        self::assertSame(
            <<<JS
            {$import}bootstrap.Toast.getOrCreateInstance(document.getElementById("w0"), {"delay":1000,"title":"\u003Cb\u003E\u0022x\u0022\u003C\/b\u003E"}); });
            JS,
            self::js('w0-plugin'),
            'Options must be HTML-safe JSON.',
        );
    }

    public function testClientOptionsEncodeBackedEnumsAsValues(): void
    {
        $import = self::IMPORT;

        $this->mockWebApplication();

        StubWidget::widget(
            ['clientOptions' => ['placement' => Placement::TOP_END, 'nested' => ['size' => Size::LG, 'n' => 1]]],
        );

        self::assertSame(
            <<<JS
            {$import}bootstrap.Toast.getOrCreateInstance(document.getElementById("w0"), {"placement":"top-end","nested":{"size":"lg","n":1}}); });
            JS,
            self::js('w0-plugin'),
            'Enums must be encoded as their values at any depth.',
        );
    }

    public function testClientOptionsFalseSkipsInitialization(): void
    {
        $this->mockWebApplication();

        StubWidget::widget(['always' => true, 'clientOptions' => false]);

        self::assertNull(
            self::js('w0-plugin'),
            '`false` must skip the initialization even when forced.',
        );
        self::assertArrayHasKey(
            BootstrapPluginAsset::class,
            self::webView()->assetBundles,
            'Plugin bundle must still be registered for the data API.',
        );
    }

    public function testClientOptionsNormalizeEnumAndStringableLeaves(): void
    {
        $import = self::IMPORT;

        $this->mockWebApplication();

        StubWidget::widget(
            [
                'clientOptions' => [
                    'backed' => Size::LG,
                    'pure' => StubUnitEnum::Flush,
                    'text' => new StringableValue('<t>'),
                    'nested' => ['callback' => new JsExpression('onShow'), 'object' => (object) ['a' => 1]],
                ],
            ],
        );

        self::assertSame(
            <<<JS
            {$import}bootstrap.Toast.getOrCreateInstance(document.getElementById("w0"), {"backed":"lg","pure":"Flush","text":"\u003Ct\u003E","nested":{"callback":onShow,"object":{"a":1}}}); });
            JS,
            self::js('w0-plugin'),
            'Enums and Stringable leaves must be scalars; JsExpression and objects must be kept.',
        );
    }

    public function testElementIdIsJsonEncoded(): void
    {
        $import = self::IMPORT;

        $this->mockWebApplication();

        StubWidget::widget(
            [
                'clientEvents' => ['shown.bs.toast' => 'f'],
                'clientOptions' => ['delay' => 1],
                'options' => ['id' => 'a"b</script>'],
            ],
        );

        self::assertSame(
            <<<JS
            {$import}bootstrap.Toast.getOrCreateInstance(document.getElementById("a\u0022b\u003C\/script\u003E"), {"delay":1}); });
            JS,
            self::js('a"b</script>-plugin'),
            'Plugin script must encode the `id`.',
        );
        self::assertSame(
            'document.getElementById("a\u0022b\u003C\/script\u003E").addEventListener("shown.bs.toast", f);',
            self::js('a"b</script>-events'),
            'Event script must encode the `id`.',
        );
    }

    public function testEmptyClientOptionsSkipInitialization(): void
    {
        $this->mockWebApplication();

        StubWidget::widget();

        self::assertSame(
            [],
            self::webView()->js,
            'No inline script must be registered.',
        );
        self::assertArrayHasKey(
            BootstrapPluginAsset::class,
            self::webView()->assetBundles,
            'Plugin bundle must be registered for the data API.',
        );
    }

    public function testModuleUrlFollowsCdnDelivery(): void
    {
        $this->mockWebApplication(
            ['components' => ['assetManager' => ['bundles' => [BootstrapPluginAsset::class => ['cdn' => true]]]]],
        );

        StubWidget::widget(['clientOptions' => ['delay' => 1]]);

        self::assertSame(
            <<<JS
            import("https://cdn.jsdelivr.net/npm/bootstrap@6.0.0-alpha.1/dist/js/bootstrap.bundle.js").then((bootstrap) => { bootstrap.Toast.getOrCreateInstance(document.getElementById("w0"), {"delay":1}); });
            JS,
            self::js('w0-plugin'),
            'Import must use the CDN module URL.',
        );
    }

    public function testThrowInvalidArgumentExceptionForUnknownPlugin(): void
    {
        $this->mockWebApplication();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            Message::UNKNOWN_PLUGIN->getMessage('Modal'),
        );

        StubWidget::widget(['plugin' => 'Modal']);
    }

    private static function js(string $key): string|null
    {
        return self::registeredJs(self::webView(), $key);
    }
}
