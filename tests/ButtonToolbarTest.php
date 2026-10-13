<?php

declare(strict_types=1);

namespace yii2\extensions\bootstrap6\tests;

use PHPUnit\Framework\Attributes\Group;
use yii2\extensions\bootstrap6\asset\BootstrapAsset;
use yii2\extensions\bootstrap6\{ButtonGroup, ButtonToolbar};
use yii2\extensions\bootstrap6\tests\support\TestCase;

/**
 * Unit tests for {@see ButtonToolbar} group rendering and additional content.
 */
#[Group('widget')]
final class ButtonToolbarTest extends TestCase
{
    public function testAdditionalContent(): void
    {
        $this->mockWebApplication();

        $addHtml = <<<HTML
        <div class="input-group">
        <div class="input-group-text" id="btnGroupAddon">@</div>
        <input type="text" class="form-control" aria-label="Input group example" aria-describedby="btnGroupAddon">
        </div>
        HTML;

        self::assertSameHtml(
            <<<HTML
            <div id="w0" class="btn-toolbar" aria-label="Toolbar with button groups" role="toolbar"><div id="w1" class="me-2 btn-group" aria-label="First group" role="group"><button type="button" id="w2" class="btn-outline">1</button>
            <button type="button" id="w3" class="btn-outline">2</button></div>
            $addHtml</div>
            HTML,
            ButtonToolbar::widget(
                [
                    'buttonGroups' => [
                        [
                            'buttons' => [
                                ['label' => '1', 'variant' => 'btn-outline'],
                                ['label' => '2', 'variant' => 'btn-outline'],
                            ],
                            'options' => ['aria-label' => 'First group', 'class' => 'me-2'],
                        ],
                        ['options' => ['aria-label' => 'Skipped group']],
                        $addHtml,
                    ],
                    'options' => ['aria-label' => 'Toolbar with button groups'],
                ],
            ),
            'Groups without buttons must be skipped and strings rendered verbatim.',
        );
        self::assertArrayHasKey(
            BootstrapAsset::class,
            self::webView()->assetBundles,
            'CSS bundle must be registered.',
        );
    }

    public function testContainerOptions(): void
    {
        $this->mockWebApplication();

        self::assertSameHtml(
            <<<HTML
            <div id="w3" class="btn-toolbar" role="menubar"><div id="w0" class="btn-group" aria-label="First group" role="group"><button type="button" id="w1" class="btn-solid">1</button>
            <button type="button" id="w2" class="btn-solid">2</button></div>
            <div id="w4" class="btn-group" aria-label="Second group" role="group"><button type="button" id="w5" class="btn-solid">3</button></div></div>
            HTML,
            ButtonToolbar::widget(
                [
                    'buttonGroups' => [
                        ButtonGroup::widget(
                            [
                                'buttons' => [['label' => '1'], ['label' => '2']],
                                'options' => ['aria-label' => 'First group'],
                            ],
                        ),
                        ['buttons' => [['label' => '3']], 'options' => ['aria-label' => 'Second group']],
                    ],
                    'options' => ['role' => 'menubar'],
                ],
            ),
            'Rendered and configured groups must be joined by line breaks.',
        );
    }

    public function testRenderButtonGroupsIsExtensionPoint(): void
    {
        $this->mockWebApplication();

        $toolbar = new class (['buttonGroups' => ['<b>G</b>']]) extends ButtonToolbar {
            protected function renderButtonGroups(): string
            {
                return '[' . parent::renderButtonGroups() . ']';
            }
        };

        self::assertSame(
            '<div id="w0" class="btn-toolbar" role="toolbar">[<b>G</b>]</div>',
            $toolbar->run(),
            'Overridden helper must decorate the parent output.',
        );
        self::assertArrayHasKey(
            BootstrapAsset::class,
            self::webView()->assetBundles,
            'CSS bundle must be registered without configured groups.',
        );
    }
}
