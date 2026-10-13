<?php

declare(strict_types=1);

namespace yii2\extensions\bootstrap6\tests;

use PHPUnit\Framework\Attributes\Group;
use yii\base\InvalidConfigException;
use yii2\extensions\bootstrap6\Alert;
use yii2\extensions\bootstrap6\asset\BootstrapPluginAsset;
use yii2\extensions\bootstrap6\enum\Theme;
use yii2\extensions\bootstrap6\exception\Message;
use yii2\extensions\bootstrap6\tests\support\stub\StringableValue;
use yii2\extensions\bootstrap6\tests\support\TestCase;

use function ob_get_clean;
use function ob_start;

/**
 * Unit tests for {@see Alert} markup, dismiss button, heading, and plugin registration.
 */
#[Group('widget')]
final class AlertTest extends TestCase
{
    public function testBeginEndRendersContentBeforeBody(): void
    {
        $this->mockWebApplication();

        ob_start();
        Alert::begin(['body' => ' Body']);
        echo 'Content';
        Alert::end();
        $echoed = ob_get_clean();

        self::assertSameHtml(
            <<<HTML
            <div id="w0" class="alert" role="alert">
            Content Body
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            HTML,
            (string) $echoed,
            'Captured content must precede the body.',
        );
    }

    public function testClientOptionsInitializePlugin(): void
    {
        $this->mockWebApplication();

        Alert::widget(['body' => 'Message', 'clientOptions' => ['x' => 1]]);

        self::assertSame(
            <<<JS
            import("/assets/js/bootstrap.bundle.js").then((bootstrap) => { bootstrap.Alert.getOrCreateInstance(document.getElementById("w0"), {"x":1}); });
            JS,
            self::registeredJs(self::webView(), 'w0-plugin'),
            'Alert plugin must be initialized with the options.',
        );
    }

    public function testCloseButtonCustomTagAndLabel(): void
    {
        $this->mockWebApplication();

        self::assertSameHtml(
            <<<HTML
            <div id="w0" class="alert theme-warning" role="alert">
            Low Blow
            <a class="btn-solid theme-warning" data-bs-dismiss="alert" aria-label="Close" style="margin-inline-start: auto;">Dismiss</a>
            </div>
            HTML,
            Alert::widget(
                [
                    'body' => 'Low Blow',
                    'closeButton' => [
                        'class' => 'btn-solid theme-warning',
                        'label' => 'Dismiss',
                        'style' => ['margin-inline-start' => 'auto'],
                        'tag' => 'a',
                    ],
                    'theme' => Theme::WARNING,
                ],
            ),
            'Custom tag must not receive `type` and the label must be rendered.',
        );
    }

    public function testCloseButtonFalseOmitsButton(): void
    {
        $this->mockWebApplication();

        self::assertSameHtml(
            <<<HTML
            <div id="w0" class="alert" role="alert">
            Message
            </div>
            HTML,
            Alert::widget(['body' => 'Message', 'closeButton' => false]),
            'No dismiss button must be rendered.',
        );
    }

    public function testCloseButtonInvalidTagAndLabelUseDefaults(): void
    {
        $this->mockWebApplication();

        self::assertStringContainsString(
            '<button class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>',
            Alert::widget(
                ['body' => 'Message', 'closeButton' => ['class' => 'btn-close', 'label' => 1, 'tag' => null]],
            ),
            'Non-string tag and label must fall back to `button` and empty content.',
        );
    }

    public function testCloseButtonKeepsConfiguredType(): void
    {
        $this->mockWebApplication();

        self::assertStringContainsString(
            '<button type="submit" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>',
            Alert::widget(['body' => 'Message', 'closeButton' => ['type' => 'submit']]),
            'Configured `type` must be kept.',
        );
    }

    public function testHeadingIsEncodedAndWrappedWithBody(): void
    {
        $this->mockWebApplication();

        self::assertSameHtml(
            <<<HTML
            <div id="w0" class="alert theme-success" role="alert">
            <div class="vstack">
            <h4 class="alert-heading mb-2">Well &amp; done!</h4>
            <p>Body</p>
            </div>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            HTML,
            Alert::widget(['body' => '<p>Body</p>', 'heading' => 'Well & done!', 'theme' => 'success']),
            'Heading must be encoded and stacked above the body.',
        );
    }

    public function testHeadingOptionsAndRawHeading(): void
    {
        $this->mockWebApplication();

        self::assertStringContainsString(
            "<div class=\"vstack\">\n<h5 class=\"fs-lg\"><b>Title</b></h5>\nBody\n</div>",
            Alert::widget(
                [
                    'body' => 'Body',
                    'encodeHeading' => false,
                    'heading' => '<b>Title</b>',
                    'headingOptions' => ['class' => 'fs-lg', 'tag' => 'h5'],
                ],
            ),
            'Heading tag, attributes, and raw content must be rendered.',
        );
        self::assertStringContainsString(
            '<h4>Title</h4>',
            Alert::widget(['body' => 'Body', 'heading' => 'Title', 'headingOptions' => ['tag' => null]]),
            'Non-string heading tag must fall back to `h4`.',
        );
    }

    public function testInstantAddsClass(): void
    {
        $this->mockWebApplication();

        self::assertStringStartsWith(
            '<div id="w0" class="alert theme-danger alert-instant" role="alert">',
            Alert::widget(['body' => 'Message', 'instant' => true, 'theme' => Theme::DANGER]),
            'Instant alerts must carry `alert-instant`.',
        );
    }

    public function testOptionsAreMerged(): void
    {
        $this->mockWebApplication();

        self::assertStringStartsWith(
            '<div id="custom" class="d-flex alert" role="status">',
            Alert::widget(
                ['body' => 'Message', 'options' => ['class' => 'd-flex', 'id' => 'custom', 'role' => 'status']],
            ),
            'Configured attributes must be kept.',
        );
    }

    public function testRenderHelpersAreExtensionPoints(): void
    {
        $this->mockWebApplication();

        $alert = new class (['body' => 'Body', 'heading' => 'Title']) extends Alert {
            protected function renderBody(string $content): string
            {
                return '[' . parent::renderBody($content) . ']';
            }

            protected function renderCloseButton(): string
            {
                return '{' . parent::renderCloseButton() . '}';
            }
        };

        $html = $alert->run();

        self::assertStringContainsString(
            '[<div class="vstack">',
            $html,
            'Overridden helpers must decorate the parent output.',
        );
        self::assertStringContainsString(
            <<<HTML
            {<button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
            }
            HTML,
            $html,
            'Close button helper must be overridable.',
        );
    }

    public function testRenderRegistersPluginAssetWithoutInitialization(): void
    {
        $this->mockWebApplication();

        self::assertSameHtml(
            <<<HTML
            <div id="w0" class="alert" role="alert">
            &lt;b&gt; stays raw: <b>Message</b>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            HTML,
            Alert::widget(['body' => '&lt;b&gt; stays raw: <b>Message</b>']),
            'Body must not be encoded.',
        );
        self::assertArrayHasKey(
            BootstrapPluginAsset::class,
            self::webView()->assetBundles,
            'Plugin bundle must be registered for the data API.',
        );
        self::assertNull(
            self::registeredJs(self::webView(), 'w0-plugin'),
            'Empty options must not initialize the plugin.',
        );
    }

    public function testRenderStringableContent(): void
    {
        $this->mockWebApplication();

        self::assertSame(
            Alert::widget(['body' => '<p>B</p>', 'heading' => 'H & h', 'options' => ['id' => 'a']]),
            Alert::widget([
                'body' => new StringableValue('<p>B</p>'),
                'heading' => new StringableValue('H & h'),
                'options' => ['id' => 'a'],
            ]),
            'Stringable values must render like their `string`.',
        );
    }

    public function testThrowInvalidConfigExceptionForInvalidTheme(): void
    {
        $themes = 'accent, danger, info, inverse, primary, secondary, success, warning';

        $this->mockWebApplication();

        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage(
            Message::INVALID_VALUE->getMessage('light', 'theme', $themes),
        );

        new Alert(['theme' => 'light']);
    }
}
