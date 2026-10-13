<?php

declare(strict_types=1);

namespace yii2\extensions\bootstrap6\tests;

use PHPUnit\Framework\Attributes\Group;
use yii2\extensions\bootstrap6\asset\BootstrapPluginAsset;
use yii2\extensions\bootstrap6\tests\support\stub\StringableValue;
use yii2\extensions\bootstrap6\tests\support\TestCase;
use yii2\extensions\bootstrap6\Toggler;

/**
 * Unit tests for {@see Toggler} trigger markup, data attributes, and plugin registration.
 */
#[Group('widget')]
final class TogglerTest extends TestCase
{
    public function testClientOptionsInitializePlugin(): void
    {
        $this->mockWebApplication();

        Toggler::widget(['clientOptions' => ['value' => 'active']]);

        self::assertSame(
            <<<JS
            import("/assets/js/bootstrap.bundle.js").then((bootstrap) => { bootstrap.Toggler.getOrCreateInstance(document.getElementById("w0"), {"value":"active"}); });
            JS,
            self::registeredJs(self::webView(), 'w0-plugin'),
            'Plugin must be initialized with the options.',
        );
    }

    public function testRenderStringableContent(): void
    {
        $this->mockWebApplication();

        self::assertSame(
            Toggler::widget(['label' => 'L & l', 'options' => ['id' => 't']]),
            Toggler::widget(['label' => new StringableValue('L & l'), 'options' => ['id' => 't']]),
            'Stringable values must render like their `string`.',
        );
    }

    public function testSelfToggle(): void
    {
        $this->mockWebApplication();

        self::assertSameHtml(
            <<<HTML
            <div id="w0" class="p-3 border" data-foo="bar" data-bs-toggle="toggler" data-bs-value="true" data-bs-attribute="hidden"><b>Click</b></div>
            HTML,
            Toggler::widget(
                [
                    'attribute' => 'hidden',
                    'encodeLabel' => false,
                    'label' => '<b>Click</b>',
                    'options' => ['class' => 'p-3 border', 'data' => ['foo' => 'bar']],
                    'tagName' => 'div',
                    'value' => 'true',
                ],
            ),
            'Element must toggle its own attribute without `type`.',
        );
        self::assertSameHtml(
            <<<HTML
            <button type="submit" id="w1" data-bs-toggle="toggler" data-bs-value="fg-danger" data-bs-attribute="class">Toggle &amp; see</button>
            HTML,
            Toggler::widget(['label' => 'Toggle & see', 'options' => ['type' => 'submit'], 'value' => 'fg-danger']),
            'Class must be the default attribute and the label encoded.',
        );
    }

    public function testTarget(): void
    {
        $this->mockWebApplication();

        self::assertSameHtml(
            <<<HTML
            <button type="button" id="w0" data-bs-toggle="toggler" data-bs-target="#panel">Toggle</button>
            HTML,
            Toggler::widget(['label' => 'Toggle', 'target' => '#panel']),
            'Button must target the toggled elements.',
        );
        self::assertNull(
            self::registeredJs(self::webView(), 'w0-plugin'),
            'Data API must be used without client options.',
        );
        self::assertArrayHasKey(
            BootstrapPluginAsset::class,
            self::webView()->assetBundles,
            'Plugin bundle must be registered.',
        );
    }
}
