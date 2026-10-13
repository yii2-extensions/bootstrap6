<?php

declare(strict_types=1);

namespace yii2\extensions\bootstrap6\tests;

use PHPUnit\Framework\Attributes\Group;
use yii\base\InvalidConfigException;
use yii2\extensions\bootstrap6\asset\{BootstrapAsset, BootstrapPluginAsset};
use yii2\extensions\bootstrap6\enum\{Breakpoint, Theme};
use yii2\extensions\bootstrap6\exception\Message;
use yii2\extensions\bootstrap6\item\StepItem;
use yii2\extensions\bootstrap6\Stepper;
use yii2\extensions\bootstrap6\tests\support\stub\StringableValue;
use yii2\extensions\bootstrap6\tests\support\TestCase;

/**
 * Unit tests for {@see Stepper} list and link rendering, item states, layouts, and validation.
 */
#[Group('widget')]
final class StepperTest extends TestCase
{
    public function testRegisterCssAssetOnly(): void
    {
        $this->mockWebApplication();

        Stepper::widget(['items' => [['label' => 'A']]]);

        self::assertArrayHasKey(
            BootstrapAsset::class,
            self::webView()->assetBundles,
            'CSS bundle must be registered.',
        );
        self::assertArrayNotHasKey(
            BootstrapPluginAsset::class,
            self::webView()->assetBundles,
            'Plugin bundle must not be registered.',
        );
    }

    public function testRenderBasic(): void
    {
        $this->mockWebApplication();

        self::assertSameHtml(
            <<<HTML
            <ol id="w0" class="stepper">
            <li class="stepper-item active">Create account</li>
            <li class="stepper-item active">Confirm email</li>
            <li class="stepper-item">Update &amp; profile</li>
            <li class="custom stepper-item" data-step="4">Finish</li>
            </ol>
            HTML,
            Stepper::widget(
                [
                    'items' => [
                        ['label' => 'Create account', 'active' => true],
                        ['label' => 'Confirm email', 'active' => true],
                        ['label' => 'Update & profile', 'active' => false],
                        ['label' => 'Finish', 'active' => 'yes', 'options' => ['class' => 'custom', 'data-step' => 4]],
                    ],
                ],
            ),
            'Active steps must carry the class.',
        );
    }

    public function testRenderDescriptions(): void
    {
        $this->mockWebApplication();

        self::assertSameHtml(
            <<<HTML
            <ol id="w0" class="stepper">
            <li class="stepper-item align-items-start active">
            <div class="pt-1">
            <div class="fw-semibold">Create &amp; go</div>
            <small class="fg-3">Create an &lt;account&gt;.</small>
            </div>
            </li>
            <li class="stepper-item align-items-start">
            <div class="pt-1">
            <div class="fw-semibold"><b>Finish</b></div>
            <small class="fg-3"><i>Welcome</i></small>
            </div>
            </li>
            </ol>
            HTML,
            Stepper::widget(
                [
                    'items' => [
                        ['label' => 'Create & go', 'description' => 'Create an <account>.', 'active' => true],
                        ['label' => '<b>Finish</b>', 'description' => '<i>Welcome</i>', 'encode' => false],
                    ],
                ],
            ),
            'Descriptions must use the complex step layout.',
        );
    }

    public function testRenderHorizontal(): void
    {
        $this->mockWebApplication();

        self::assertSameHtml(
            <<<HTML
            <ol id="w0" class="stepper stepper-horizontal">
            <li class="stepper-item active theme-accent">A</li>
            <li class="stepper-item theme-danger">B</li>
            </ol>
            HTML,
            Stepper::widget(
                [
                    'horizontal' => true,
                    'items' => [
                        ['label' => 'A', 'active' => true, 'theme' => Theme::ACCENT],
                        ['label' => 'B', 'theme' => 'danger'],
                    ],
                ],
            ),
            'Horizontal stepper must carry the modifier and item themes.',
        );
    }

    public function testRenderItemIsOverridable(): void
    {
        $this->mockWebApplication();

        $stepper = new class (['items' => [['label' => 'A']]]) extends Stepper {
            protected function renderItem(StepItem $item, bool $links): string
            {
                return '<step>' . parent::renderItem($item, $links) . '</step>';
            }
        };

        self::assertSameHtml(
            "<ol id=\"w0\" class=\"stepper\">\n<step><li class=\"stepper-item\">A</li></step>\n</ol>",
            $stepper->run(),
            'Subclass must be able to decorate the steps.',
        );
    }

    public function testRenderLinks(): void
    {
        $this->mockWebApplication();

        self::assertSameHtml(
            <<<HTML
            <div id="w0" class="stepper">
            <a class="stepper-item active" href="/account">Create account</a>
            <a class="stepper-item" href="/index.php?r=site%2Femail">Confirm email</a>
            <div class="stepper-item theme-secondary">Finish</div>
            </div>
            HTML,
            Stepper::widget(
                [
                    'items' => [
                        ['label' => 'Create account', 'url' => '/account', 'active' => true],
                        ['label' => 'Confirm email', 'url' => ['/site/email']],
                        ['label' => 'Finish', 'theme' => 'secondary', 'url' => 5],
                    ],
                ],
            ),
            'Steps with a URL must render links inside a `div`.',
        );
    }

    public function testRenderLinksFromStepItemUrl(): void
    {
        $this->mockWebApplication();

        self::assertSameHtml(
            <<<HTML
            <div id="w0" class="stepper">
            <div class="stepper-item active">A</div>
            <div class="stepper-item">B</div>
            <a class="stepper-item" href="/c">C</a>
            </div>
            HTML,
            Stepper::widget(
                [
                    'items' => [
                        StepItem::to('A')->withActive(true),
                        ['label' => 'B'],
                        StepItem::to('C')->withUrl('/c'),
                    ],
                ],
            ),
            'One step URL must switch every step to the link layout.',
        );
    }

    public function testRenderResponsiveHorizontal(): void
    {
        $this->mockWebApplication();

        self::assertSameHtml(
            <<<HTML
            <div class="contains-inline">
            <ol id="w0" class="stepper sm:stepper-horizontal">
            <li class="stepper-item">A</li>
            </ol>
            </div>
            HTML,
            Stepper::widget(['horizontal' => Breakpoint::SM, 'items' => [['label' => 'A', 'theme' => 5]]]),
            'Breakpoint must prefix the modifier inside a query container.',
        );
    }

    public function testRenderResponsiveHorizontalString(): void
    {
        $this->mockWebApplication();

        self::assertStringContainsString(
            '<ol id="w0" class="stepper 2xl:stepper-horizontal">',
            Stepper::widget(['horizontal' => '2xl', 'items' => [['label' => 'A']]]),
            'Breakpoint string must be accepted.',
        );
    }

    public function testRenderStringableItems(): void
    {
        $this->mockWebApplication();

        self::assertSame(
            Stepper::widget(['items' => [['label' => 'A & a'], ['label' => 'B']], 'options' => ['id' => 's']]),
            Stepper::widget(
                [
                    'items' => [['label' => new StringableValue('A & a')], ['label' => 'B']],
                    'options' => ['id' => 's'],
                ],
            ),
            'Stringable values must render like their `string`.',
        );
    }

    public function testRenderValueObjects(): void
    {
        $this->mockWebApplication();

        self::assertSame(
            Stepper::widget(
                [
                    'horizontal' => true,
                    'items' => [
                        ['label' => 'Create & go', 'description' => 'Create an <account>.', 'active' => true],
                        ['label' => '<b>Finish</b>', 'description' => '<i>Welcome</i>', 'encode' => false],
                        ['label' => 'B', 'options' => ['class' => 'custom'], 'theme' => 'danger'],
                    ],
                    'options' => ['id' => 's'],
                ],
            ),
            Stepper::widget(
                [
                    'horizontal' => true,
                    'items' => [
                        StepItem::to('Create & go', new StringableValue('Create an <account>.'))->withActive(true),
                        StepItem::to('<b>Finish</b>', '<i>Welcome</i>')->withEncode(false),
                        StepItem::to('B')->withTheme(Theme::DANGER)->withOptions(['class' => 'custom']),
                    ],
                    'options' => ['id' => 's'],
                ],
            ),
            'Value objects must render like their array form.',
        );
    }

    public function testRenderWithoutEncoding(): void
    {
        $this->mockWebApplication();

        self::assertSameHtml(
            <<<HTML
            <ol id="w0" class="stepper">
            <li class="stepper-item"><b>A</b></li>
            <li class="stepper-item">&lt;i&gt;B&lt;/i&gt;</li>
            </ol>
            HTML,
            Stepper::widget(
                [
                    'encodeLabels' => false,
                    'items' => [['label' => '<b>A</b>', 'encode' => 'no'], ['label' => '<i>B</i>', 'encode' => true]],
                ],
            ),
            'Item `encode` must override the widget default.',
        );
    }

    public function testThrowInvalidConfigExceptionForInvalidBreakpoint(): void
    {
        $this->mockWebApplication();

        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage(
            Message::INVALID_VALUE->getMessage('xxl', 'horizontal', 'lg, md, sm, xl, 2xl'),
        );

        new Stepper(['horizontal' => 'xxl', 'items' => [['label' => 'A']]]);
    }

    public function testThrowInvalidConfigExceptionWhenItemLabelIsMissing(): void
    {
        $this->mockWebApplication();

        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage(
            Message::ITEM_LABEL_REQUIRED->getMessage(),
        );

        Stepper::widget(['items' => [['label' => 'A'], ['label' => 5]]]);
    }

    public function testThrowInvalidConfigExceptionWhenItemsAreEmpty(): void
    {
        $this->mockWebApplication();

        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage(
            Message::INVALID_ITEMS->getMessage(),
        );

        Stepper::widget();
    }
}
