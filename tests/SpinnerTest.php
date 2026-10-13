<?php

declare(strict_types=1);

namespace yii2\extensions\bootstrap6\tests;

use PHPUnit\Framework\Attributes\Group;
use yii\base\InvalidConfigException;
use yii2\extensions\bootstrap6\asset\BootstrapAsset;
use yii2\extensions\bootstrap6\enum\Theme;
use yii2\extensions\bootstrap6\exception\Message;
use yii2\extensions\bootstrap6\Spinner;
use yii2\extensions\bootstrap6\tests\support\stub\StringableValue;
use yii2\extensions\bootstrap6\tests\support\TestCase;

/**
 * Unit tests for {@see Spinner} types, sizes, colors, and status label.
 */
#[Group('widget')]
final class SpinnerTest extends TestCase
{
    public function testEmptyLabelOmitsStatusText(): void
    {
        $this->mockWebApplication();

        self::assertSame(
            '<div id="w0" class="spinner-border" role="status"></div>',
            Spinner::widget(['label' => '']),
            'Empty label must omit the hidden text.',
        );
    }

    public function testGrowSmallThemedSpinner(): void
    {
        $this->mockWebApplication();

        self::assertSameHtml(
            <<<HTML
            <div id="w0" class="spinner-grow spinner-sm fg-accent" role="status">
            <span class="visually-hidden">Loading...</span>
            </div>
            HTML,
            Spinner::widget(['small' => true, 'theme' => Theme::ACCENT, 'type' => Spinner::TYPE_GROW]),
            'Type, size, and color classes must be rendered.',
        );
    }

    public function testLabelEncodingAndOptions(): void
    {
        $this->mockWebApplication();

        self::assertSameHtml(
            <<<HTML
            <span id="s" class="me-2 spinner-border fg-primary" role="presentation">
            <span class="sr visually-hidden">&lt;Saving&gt;</span>
            </span>
            HTML,
            Spinner::widget(
                [
                    'label' => '<Saving>',
                    'labelOptions' => ['class' => 'sr'],
                    'options' => ['class' => 'me-2', 'id' => 's', 'role' => 'presentation'],
                    'tagName' => 'span',
                    'theme' => 'primary',
                ],
            ),
            'Label must be encoded and configured attributes kept.',
        );
        self::assertStringContainsString(
            '<span class="visually-hidden"><b>Wait</b></span>',
            Spinner::widget(['encodeLabel' => false, 'label' => '<b>Wait</b>']),
            'Raw label must not be encoded.',
        );
    }

    public function testRenderDefaultBorderSpinner(): void
    {
        $this->mockWebApplication();

        self::assertSameHtml(
            <<<HTML
            <div id="w0" class="spinner-border" role="status">
            <span class="visually-hidden">Loading...</span>
            </div>
            HTML,
            Spinner::widget(),
            'Default spinner must carry a hidden status label.',
        );
        self::assertArrayHasKey(
            BootstrapAsset::class,
            self::webView()->assetBundles,
            'CSS bundle must be registered.',
        );
    }

    public function testRenderLabelIsExtensionPoint(): void
    {
        $this->mockWebApplication();

        $spinner = new class extends Spinner {
            protected function renderLabel(): string
            {
                return '[' . parent::renderLabel() . ']';
            }
        };

        self::assertSameHtml(
            <<<HTML
            <div id="w0" class="spinner-border" role="status">[
            <span class="visually-hidden">Loading...</span>
            ]</div>
            HTML,
            $spinner->run(),
            'Overridden helper must decorate the parent output.',
        );
    }

    public function testRenderStringableContent(): void
    {
        $this->mockWebApplication();

        self::assertSame(
            Spinner::widget(['label' => 'Wait & see', 'options' => ['id' => 's']]),
            Spinner::widget(['label' => new StringableValue('Wait & see'), 'options' => ['id' => 's']]),
            'Stringable values must render like their `string`.',
        );
        self::assertSame(
            Spinner::widget(['label' => '', 'options' => ['id' => 's']]),
            Spinner::widget(['label' => new StringableValue(''), 'options' => ['id' => 's']]),
            'Empty Stringable label must omit the label.',
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

        new Spinner(['theme' => 'light']);
    }

    public function testThrowInvalidConfigExceptionWhenTypeIsInvalid(): void
    {
        $this->mockWebApplication();

        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage(
            Message::INVALID_VALUE->getMessage('dots', 'type', 'border, grow'),
        );

        Spinner::widget(['type' => 'dots']);
    }
}
