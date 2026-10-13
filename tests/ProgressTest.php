<?php

declare(strict_types=1);

namespace yii2\extensions\bootstrap6\tests;

use PHPUnit\Framework\Attributes\Group;
use yii\base\{InvalidArgumentException, InvalidConfigException};
use yii2\extensions\bootstrap6\asset\BootstrapAsset;
use yii2\extensions\bootstrap6\enum\Theme;
use yii2\extensions\bootstrap6\exception\Message;
use yii2\extensions\bootstrap6\item\ProgressBar;
use yii2\extensions\bootstrap6\Progress;
use yii2\extensions\bootstrap6\tests\support\stub\StringableValue;
use yii2\extensions\bootstrap6\tests\support\TestCase;

/**
 * Unit tests for {@see Progress} single and stacked bars, ranges, and accessibility attributes.
 */
#[Group('widget')]
final class ProgressTest extends TestCase
{
    public function testBoundaryValuesAndRole(): void
    {
        $this->mockWebApplication();

        self::assertStringContainsString(
            'style="width: 100%;"',
            Progress::widget(['percent' => 100]),
            'Maximum value must be accepted.',
        );
        self::assertStringStartsWith(
            '<div id="w1" class="progress" role="meter" aria-valuenow="1" aria-valuemin="1" aria-valuemax="2">',
            Progress::widget(['max' => 2, 'min' => 1, 'options' => ['role' => 'meter'], 'percent' => 1]),
            'Minimum value and configured role must be accepted.',
        );
    }
    public function testCustomRangeComputesWidth(): void
    {
        $this->mockWebApplication();

        self::assertSameHtml(
            <<<HTML
            <div id="w0" class="progress" role="progressbar" aria-valuenow="7.5" aria-valuemin="5" aria-valuemax="35">
            <div class="progress-bar" style="width: 8.3333%;"></div>
            </div>
            HTML,
            Progress::widget(['max' => 35, 'min' => 5, 'percent' => 7.5]),
            'Width must be relative to the range.',
        );
    }

    public function testMultiple(): void
    {
        $this->mockWebApplication();

        self::assertSameHtml(
            <<<HTML
            <div id="w0" class="mb-5 progress-stacked">
            <div class="progress" role="progressbar" aria-label="Segment one" aria-valuenow="15" aria-valuemin="0" aria-valuemax="100" style="width: 15%;">
            <div class="progress-bar"></div>
            </div>
            <div class="progress" role="progressbar" aria-valuenow="30" aria-valuemin="0" aria-valuemax="100" style="width: 30%;">
            <div class="progress-bar theme-success progress-bar-striped progress-bar-animated">30%</div>
            </div>
            <div class="progress" role="progressbar" aria-valuenow="20" aria-valuemin="0" aria-valuemax="100" style="width: 20%;">
            <div class="fw-bold progress-bar theme-info">&lt;b&gt;</div>
            </div>
            </div>
            HTML,
            Progress::widget(
                [
                    'bars' => [
                        ['ariaLabel' => 'Segment one', 'percent' => 15],
                        [
                            'animated' => true,
                            'label' => '30%',
                            'percent' => 30,
                            'striped' => true,
                            'theme' => Theme::SUCCESS,
                        ],
                        ['label' => '<b>', 'options' => ['class' => 'fw-bold'], 'percent' => 20, 'theme' => 'info'],
                    ],
                    'options' => ['class' => 'mb-5'],
                ],
            ),
            'Segments must be sized on the `.progress` elements.',
        );
    }

    public function testMultipleValueObjects(): void
    {
        $this->mockWebApplication();

        self::assertSameHtml(
            <<<HTML
            <div id="w0" class="mb-5 progress-stacked">
            <div class="progress" role="progressbar" aria-label="Segment one" aria-valuenow="15" aria-valuemin="0" aria-valuemax="100" style="width: 15%;">
            <div class="progress-bar"></div>
            </div>
            <div class="progress" role="progressbar" aria-valuenow="30" aria-valuemin="0" aria-valuemax="100" style="width: 30%;">
            <div class="progress-bar theme-success progress-bar-striped progress-bar-animated">30%</div>
            </div>
            <div class="progress" role="progressbar" aria-valuenow="20" aria-valuemin="0" aria-valuemax="100" style="width: 20%;">
            <div class="fw-bold progress-bar theme-info">&lt;b&gt;</div>
            </div>
            </div>
            HTML,
            Progress::widget(
                [
                    'bars' => [
                        ProgressBar::to(15)->withAriaLabel('Segment one'),
                        ProgressBar::to(30, '30%')
                            ->withTheme(Theme::SUCCESS)
                            ->withStriped(true)
                            ->withAnimated(true),
                        ['label' => '<b>', 'options' => ['class' => 'fw-bold'], 'percent' => 20, 'theme' => 'info'],
                    ],
                    'options' => ['class' => 'mb-5'],
                ],
            ),
            'Value objects and arrays must render the same segments.',
        );
    }

    public function testRender(): void
    {
        $this->mockWebApplication();

        self::assertSameHtml(
            <<<HTML
            <div id="w0" class="progress" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">
            <div class="progress-bar" style="width: 0%;"></div>
            </div>
            HTML,
            Progress::widget(),
            'Default bar must be empty.',
        );
        self::assertArrayHasKey(
            BootstrapAsset::class,
            self::webView()->assetBundles,
            'CSS bundle must be registered.',
        );
    }

    public function testRenderProgressIsExtensionPoint(): void
    {
        $this->mockWebApplication();

        $progress = new class (['percent' => 50]) extends Progress {
            protected function renderProgress(ProgressBar $bar, array $options, bool $stacked): string
            {
                return '[' . parent::renderProgress($bar, $options, $stacked) . ']';
            }
        };

        self::assertStringStartsWith(
            '[<div id="w0" class="progress"',
            $progress->run(),
            'Overridden helper must decorate the parent output.',
        );
    }

    public function testRenderStringableContent(): void
    {
        $this->mockWebApplication();

        self::assertSame(
            Progress::widget(['label' => 'L & l', 'options' => ['id' => 'p'], 'percent' => 50]),
            Progress::widget(['label' => new StringableValue('L & l'), 'options' => ['id' => 'p'], 'percent' => 50]),
            'Stringable values must render like their `string`.',
        );
        self::assertSame(
            Progress::widget(['bars' => [['label' => 'A & a', 'percent' => 20]], 'options' => ['id' => 'p']]),
            Progress::widget(
                [
                    'bars' => [['label' => new StringableValue('A & a'), 'percent' => 20]],
                    'options' => ['id' => 'p'],
                ],
            ),
            'Stringable segment label must render like its `string`.',
        );
    }

    public function testRussianLocaleRendering(): void
    {
        $this->mockWebApplication(['language' => 'ru-RU', 'sourceLanguage' => 'en-US']);

        self::assertStringContainsString(
            'aria-valuenow="25.5"',
            Progress::widget(['percent' => 25.5]),
            'Decimal separator must not depend on the locale.',
        );
    }

    public function testSimpleRender(): void
    {
        $this->mockWebApplication();

        self::assertSameHtml(
            <<<HTML
            <div id="w0" class="progress" style="height: 20px;" role="progressbar" aria-label="Upload" aria-valuenow="25" aria-valuemin="0" aria-valuemax="100">
            <div class="fw-bold progress-bar theme-warning progress-bar-striped progress-bar-animated" style="width: 25%;"><b>25%</b></div>
            </div>
            HTML,
            Progress::widget(
                [
                    'animated' => true,
                    'ariaLabel' => 'Upload',
                    'barOptions' => ['class' => 'fw-bold'],
                    'encodeLabel' => false,
                    'label' => '<b>25%</b>',
                    'options' => ['style' => 'height: 20px;'],
                    'percent' => 25,
                    'striped' => true,
                    'theme' => Theme::WARNING,
                ],
            ),
            'Single bar must carry the configured modifiers.',
        );
    }

    public function testThrowInvalidArgumentExceptionWhenPercentIsAboveMax(): void
    {
        $this->mockWebApplication();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            Message::INVALID_VALUE->getMessage('100.5', 'percent', 'a value between 0 and 100'),
        );

        Progress::widget(['percent' => 100.5]);
    }

    public function testThrowInvalidArgumentExceptionWhenPercentIsBelowMin(): void
    {
        $this->mockWebApplication();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            Message::INVALID_VALUE->getMessage('9', 'percent', 'a value between 10 and 20'),
        );

        Progress::widget(['bars' => [['percent' => 9]], 'max' => 20, 'min' => 10]);
    }

    public function testThrowInvalidConfigExceptionForInvalidTheme(): void
    {
        $this->mockWebApplication();

        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage(
            Message::INVALID_VALUE->getMessage(
                'light',
                'theme',
                'accent, danger, info, inverse, primary, secondary, success, warning',
            ),
        );

        new Progress(['theme' => 'light']);
    }

    public function testThrowInvalidConfigExceptionWhenMaxIsNotGreaterThanMin(): void
    {
        $this->mockWebApplication();

        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage(
            Message::INVALID_VALUE->getMessage('5', 'max', 'a value greater than 5'),
        );

        Progress::widget(['max' => 5, 'min' => 5]);
    }

    public function testThrowInvalidConfigExceptionWhenSegmentHasNoPercent(): void
    {
        $this->mockWebApplication();

        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage(
            Message::OPTION_REQUIRED->getMessage('percent'),
        );

        Progress::widget(['bars' => [['label' => 'x']]]);
    }
}
