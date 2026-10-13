<?php

declare(strict_types=1);

namespace yii2\extensions\bootstrap6\tests;

use PHPUnit\Framework\Attributes\Group;
use yii\base\InvalidConfigException;
use yii2\extensions\bootstrap6\asset\BootstrapAsset;
use yii2\extensions\bootstrap6\{Button, ButtonGroup};
use yii2\extensions\bootstrap6\enum\{Size, Theme};
use yii2\extensions\bootstrap6\exception\Message;
use yii2\extensions\bootstrap6\tests\support\TestCase;

/**
 * Unit tests for {@see ButtonGroup} buttons, visibility, label encoding, and layout modifiers.
 */
#[Group('widget')]
final class ButtonGroupTest extends TestCase
{
    public function testContainerOptions(): void
    {
        $this->mockWebApplication();

        $html = ButtonGroup::widget(
            [
                'buttons' => [
                    ['label' => 'button-A', 'theme' => Theme::PRIMARY],
                    ['label' => 'button-B', 'visible' => true],
                    ['label' => 'button-C', 'visible' => false],
                    Button::widget(['label' => 'button-D']),
                ],
                'options' => ['aria-label' => 'Basic example'],
            ],
        );

        self::assertSameHtml(
            <<<HTML
            <div id="w1" class="btn-group" aria-label="Basic example" role="group"><button type="button" id="w2" class="btn-solid theme-primary">button-A</button>
            <button type="button" id="w3" class="btn-solid">button-B</button>
            <button type="button" id="w0" class="btn-solid">button-D</button></div>
            HTML,
            $html,
            'Hidden buttons must be skipped and strings rendered verbatim.',
        );
        self::assertArrayHasKey(
            BootstrapAsset::class,
            self::webView()->assetBundles,
            'CSS bundle must be registered.',
        );
    }

    public function testEncodeLabels(): void
    {
        $this->mockWebApplication();

        self::assertSameHtml(
            <<<HTML
            <div id="w0" class="btn-group" role="group"><button type="button" id="w1" class="btn-solid"><b>A</b></button>
            <button type="button" id="w2" class="btn-solid">&lt;b&gt;B&lt;/b&gt;</button></div>
            HTML,
            ButtonGroup::widget(
                [
                    'buttons' => [['label' => '<b>A</b>'], ['encodeLabel' => true, 'label' => '<b>B</b>']],
                    'encodeLabels' => false,
                ],
            ),
            'Group setting must apply unless the button overrides it.',
        );
    }

    public function testRenderButtonsIsExtensionPoint(): void
    {
        $this->mockWebApplication();

        $group = new class (['buttons' => ['<b>A</b>']]) extends ButtonGroup {
            protected function renderButtons(): string
            {
                return '[' . parent::renderButtons() . ']';
            }
        };

        self::assertSame(
            '<div id="w0" class="btn-group" role="group">[<b>A</b>]</div>',
            $group->run(),
            'Overridden helper must decorate the parent output.',
        );
        self::assertArrayHasKey(
            BootstrapAsset::class,
            self::webView()->assetBundles,
            'CSS bundle must be registered without configured buttons.',
        );
    }

    public function testThrowInvalidConfigExceptionForInvalidSize(): void
    {
        $this->mockWebApplication();

        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage(
            Message::INVALID_VALUE->getMessage('md', 'size', 'lg, sm, xl, xs'),
        );

        new ButtonGroup(['size' => 'md']);
    }

    public function testVerticalDividerSizeAndRole(): void
    {
        $this->mockWebApplication();

        self::assertSame(
            '<div id="w0" class="btn-group-vertical btn-group-divider btn-group-lg" role="toolbar"></div>',
            ButtonGroup::widget(
                ['divider' => true, 'options' => ['role' => 'toolbar'], 'size' => Size::LG, 'vertical' => true],
            ),
            'Vertical, divider, and size classes must be rendered.',
        );
        self::assertSame(
            '<div id="w1" class="btn-group btn-group-xs" role="group"></div>',
            ButtonGroup::widget(['size' => 'xs']),
            'String size must be rendered.',
        );
    }
}
