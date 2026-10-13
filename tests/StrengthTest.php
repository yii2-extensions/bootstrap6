<?php

declare(strict_types=1);

namespace yii2\extensions\bootstrap6\tests;

use PHPUnit\Framework\Attributes\Group;
use yii\base\InvalidConfigException;
use yii2\extensions\bootstrap6\asset\BootstrapPluginAsset;
use yii2\extensions\bootstrap6\exception\Message;
use yii2\extensions\bootstrap6\Strength;
use yii2\extensions\bootstrap6\tests\support\TestCase;

/**
 * Unit tests for {@see Strength} meter rendering, data attributes, messages, and plugin registration.
 */
#[Group('widget')]
final class StrengthTest extends TestCase
{
    private const string IMPORT = 'import("/assets/js/bootstrap.bundle.js").then((bootstrap) => { ';

    public function testMessagesAreMergedIntoClientOptions(): void
    {
        $this->mockWebApplication();

        Strength::widget(
            [
                'messages' => ['weak' => 'Too weak', 'fair' => 'Fair', 'good' => 'Good', 'strong' => 'Excellent!'],
                'clientOptions' => ['thresholds' => [3, 5, 7], 'messages' => ['weak' => 'Override']],
            ],
        );

        $import = self::IMPORT;

        self::assertSame(
            <<<JS
            {$import}bootstrap.Strength.getOrCreateInstance(document.getElementById("w0"), {"messages":{"weak":"Override"},"thresholds":[3,5,7]}); });
            JS,
            self::registeredJs(self::webView(), 'w0-plugin'),
            'Explicit client options must win over the messages.',
        );
    }

    public function testMessagesAreSkippedWhenClientOptionsAreDisabled(): void
    {
        $this->mockWebApplication();

        Strength::widget(['messages' => ['weak' => 'Too weak'], 'clientOptions' => false]);

        self::assertNull(
            self::registeredJs(self::webView(), 'w0-plugin'),
            'Disabled options must skip the init.',
        );
    }

    public function testRegisterMessages(): void
    {
        $this->mockWebApplication();

        Strength::widget(
            ['messages' => ['weak' => 'Too weak'], 'clientEvents' => ['strengthChange.bs.strength' => 'f']],
        );

        $import = self::IMPORT;

        self::assertSame(
            <<<JS
            {$import}bootstrap.Strength.getOrCreateInstance(document.getElementById("w0"), {"messages":{"weak":"Too weak"}}); });
            JS,
            self::registeredJs(self::webView(), 'w0-plugin'),
            'Messages must be passed to the constructor.',
        );
        self::assertSame(
            'document.getElementById("w0").addEventListener("strengthChange.bs.strength", f);',
            self::registeredJs(self::webView(), 'w0-events'),
            'Events must be attached to the meter.',
        );
    }

    public function testRegisterPluginAssetWithoutInitialization(): void
    {
        $this->mockWebApplication();

        Strength::widget();

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

    public function testRenderBar(): void
    {
        $this->mockWebApplication();

        self::assertSameHtml(
            <<<HTML
            <div id="w0" class="strength-bar" data-bs-strength></div>
            HTML,
            Strength::widget(['segments' => false]),
            'Bar variant must render a single element.',
        );
    }

    public function testRenderSegments(): void
    {
        $this->mockWebApplication();

        self::assertSameHtml(
            <<<HTML
            <div id="w0" class="strength" data-bs-strength>
            <div class="strength-segment"></div>
            <div class="strength-segment"></div>
            <div class="strength-segment"></div>
            <div class="strength-segment"></div>
            </div>
            HTML,
            Strength::widget(),
            'Default meter must render four segments.',
        );
    }

    public function testRenderWithInputMinLengthAndText(): void
    {
        $this->mockWebApplication();

        self::assertSameHtml(
            <<<HTML
            <div id="meter" class="mt-3 strength-bar" data-bs-strength data-bs-input="#user-password" data-bs-min-length="10"></div>
            <span class="strength-text"></span>
            HTML,
            Strength::widget(
                [
                    'input' => '#user-password',
                    'minLength' => 10,
                    'segments' => false,
                    'showText' => true,
                    'options' => ['id' => 'meter', 'class' => 'mt-3'],
                ],
            ),
            'Input selector and minimum length must be emitted with the text element.',
        );
    }

    public function testThrowInvalidConfigExceptionForInvalidMessageLevel(): void
    {
        $this->mockWebApplication();

        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage(
            Message::INVALID_VALUE->getMessage('poor', 'messages', 'weak, fair, good, strong'),
        );

        Strength::widget(['messages' => ['weak' => 'Weak', 'poor' => 'Poor']]);
    }
}
