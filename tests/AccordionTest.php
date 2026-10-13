<?php

declare(strict_types=1);

namespace yii2\extensions\bootstrap6\tests;

use PHPUnit\Framework\Attributes\Group;
use UIAwesome\Html\Svg\{Path, Svg};
use Yii;
use yii\base\InvalidConfigException;
use yii2\extensions\bootstrap6\{Accordion, IconSet};
use yii2\extensions\bootstrap6\asset\{BootstrapAsset, BootstrapPluginAsset};
use yii2\extensions\bootstrap6\exception\Message;
use yii2\extensions\bootstrap6\item\AccordionItem;
use yii2\extensions\bootstrap6\tests\support\stub\StringableValue;
use yii2\extensions\bootstrap6\tests\support\TestCase;

/**
 * Unit tests for {@see Accordion} markup built on `<details>` and `<summary>` elements.
 */
#[Group('widget')]
final class AccordionTest extends TestCase
{
    public function testAccordionModifiers(): void
    {
        $this->mockWebApplication();

        self::assertSameHtml(
            <<<HTML
            <div id="w0" class="theme-primary accordion accordion-flush accordion-sm accordion-gap">
            <details class="accordion-item">
            <summary class="accordion-header">Item</summary>
            <div class="accordion-body">Body</div>
            </details>
            </div>
            HTML,
            Accordion::widget(
                [
                    'autoCloseItems' => false,
                    'flush' => true,
                    'gap' => true,
                    'icon' => '',
                    'items' => [['content' => 'Body', 'label' => 'Item']],
                    'options' => ['class' => 'theme-primary'],
                    'small' => true,
                ],
            ),
            'Every modifier must be rendered without `name` and icon.',
        );
    }

    public function testItemOptions(): void
    {
        $this->mockWebApplication();

        self::assertSameHtml(
            <<<HTML
            <div id="w0" class="accordion">
            <details class="my-item accordion-item" name="group" open>
            <summary class="my-header accordion-header"><b>Raw</b>
            <svg class="accordion-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
            <path d="m2 5 6 6 6-6">
            </svg></summary>
            <div class="my-body accordion-body">Body</div>
            </details>
            <details class="accordion-item" name="w0">
            <summary class="accordion-header">&lt;b&gt;Encoded&lt;/b&gt;
            <svg class="accordion-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
            <path d="m2 5 6 6 6-6">
            </svg></summary>
            <div class="accordion-body">Other</div>
            </details>
            <details class="accordion-item" name="w0">
            <summary class="accordion-header">Shown
            <svg class="accordion-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
            <path d="m2 5 6 6 6-6">
            </svg></summary>
            <div class="accordion-body">Shown</div>
            </details>
            </div>
            HTML,
            Accordion::widget(
                [
                    'encodeLabels' => false,
                    'items' => [
                        [
                            'bodyOptions' => ['class' => 'my-body'],
                            'content' => 'Body',
                            'headerOptions' => ['class' => 'my-header'],
                            'label' => '<b>Raw</b>',
                            'open' => true,
                            'options' => ['class' => 'my-item', 'name' => 'group'],
                        ],
                        ['content' => 'Other', 'encode' => true, 'label' => '<b>Encoded</b>', 'open' => 'yes'],
                        ['content' => 'Hidden', 'label' => 'Hidden', 'visible' => false],
                        ['content' => 'Shown', 'label' => 'Shown', 'visible' => true, 'options' => 'invalid'],
                    ],
                ],
            ),
            'Item options must be applied and hidden items skipped.',
        );
    }

    public function testProtectedMethodsAreExtensionPoints(): void
    {
        $this->mockWebApplication();

        $accordion = new class (['icon' => '', 'items' => ['A' => 'a']]) extends Accordion {
            protected function renderItem(AccordionItem $item): string
            {
                return '<!-- item -->' . parent::renderItem($item);
            }

            protected function renderItems(): string
            {
                return '<!-- items -->' . parent::renderItems();
            }
        };

        self::assertSameHtml(
            <<<HTML
            <div id="w0" class="accordion">
            <!-- items --><!-- item --><details class="accordion-item" name="w0">
            <summary class="accordion-header">A</summary>
            <div class="accordion-body">a</div>
            </details>
            </div>
            HTML,
            $accordion->run(),
            'Overridden renderers must be used.',
        );
    }

    public function testRender(): void
    {
        $this->mockWebApplication();

        self::assertSameHtml(
            <<<HTML
            <div id="w0" class="accordion">
            <details class="accordion-item" name="w0" open>
            <summary class="accordion-header">Item #1
            <svg class="accordion-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
            <path d="m2 5 6 6 6-6">
            </svg></summary>
            <div class="accordion-body"><strong>First</strong></div>
            </details>
            <details class="accordion-item" name="w0">
            <summary class="accordion-header">Item &amp; #2
            <svg class="accordion-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
            <path d="m2 5 6 6 6-6">
            </svg></summary>
            <div class="accordion-body">Second</div>
            </details>
            </div>
            HTML,
            Accordion::widget(
                [
                    'items' => [
                        ['content' => '<strong>First</strong>', 'label' => 'Item #1', 'open' => true],
                        'Item & #2' => 'Second',
                    ],
                ],
            ),
            'Items must share the accordion `id` as `name`.',
        );
        self::assertArrayHasKey(
            BootstrapAsset::class,
            self::webView()->assetBundles,
            'Stylesheet bundle must be registered.',
        );
        self::assertArrayNotHasKey(
            BootstrapPluginAsset::class,
            self::webView()->assetBundles,
            'No JavaScript must be registered.',
        );
    }

    public function testRenderContainerIcon(): void
    {
        $this->mockWebApplication();

        Yii::$container->set(
            IconSet::class,
            ['accordionChevron' => Svg::tag()->class('my-icon')->html(Path::tag()->d('m0 0'))],
        );

        self::assertSameHtml(
            <<<HTML
            <div id="w0" class="accordion">
            <details class="accordion-item" name="w0">
            <summary class="accordion-header">Item
            <svg class="my-icon accordion-icon">
            <path d="m0 0">
            </svg></summary>
            <div class="accordion-body">Body</div>
            </details>
            </div>
            HTML,
            Accordion::widget(['items' => ['Item' => 'Body']]),
            'Container icon must replace the default and receive the structural class.',
        );
    }

    public function testRenderIconString(): void
    {
        $this->mockWebApplication();

        self::assertSameHtml(
            <<<HTML
            <div id="w0" class="accordion">
            <details class="accordion-item" name="w0">
            <summary class="accordion-header">Item
            <i class="bi bi-chevron-down"></i></summary>
            <div class="accordion-body">Body</div>
            </details>
            </div>
            HTML,
            Accordion::widget(['icon' => '<i class="bi bi-chevron-down"></i>', 'items' => ['Item' => 'Body']]),
            'Markup must be rendered verbatim, without the structural class.',
        );
    }

    public function testRenderIconSvg(): void
    {
        $this->mockWebApplication();

        Yii::$container->set(IconSet::class, ['accordionChevron' => '<i>container</i>']);

        self::assertSameHtml(
            <<<HTML
            <div id="w0" class="accordion">
            <details class="accordion-item" name="w0">
            <summary class="accordion-header">Item
            <svg class="my-icon accordion-icon">
            <path d="m0 0">
            </svg></summary>
            <div class="accordion-body">Body</div>
            </details>
            </div>
            HTML,
            Accordion::widget(
                [
                    'icon' => Svg::tag()->class('my-icon')->html(Path::tag()->d('m0 0')),
                    'items' => ['Item' => 'Body'],
                ],
            ),
            'Instance icon must take precedence and receive the structural class.',
        );
    }

    public function testRenderMixedItems(): void
    {
        $this->mockWebApplication();

        self::assertSameHtml(
            <<<HTML
            <div id="w0" class="accordion">
            <details class="accordion-item" name="w0" open>
            <summary class="accordion-header">A</summary>
            <div class="accordion-body">a</div>
            </details>
            <details class="accordion-item" name="w0">
            <summary class="accordion-header">B</summary>
            <div class="accordion-body">b</div>
            </details>
            <details class="accordion-item" name="w0">
            <summary class="accordion-header">C</summary>
            <div class="accordion-body">c</div>
            </details>
            </div>
            HTML,
            Accordion::widget(
                [
                    'icon' => '',
                    'items' => [
                        AccordionItem::to('A', 'a')->withOpen(true),
                        ['content' => 'b', 'label' => 'B'],
                        'C' => 'c',
                        AccordionItem::to('D', 'd')->withVisible(false),
                    ],
                ],
            ),
            'Value objects, arrays, and pairs must share one list.',
        );
    }

    public function testRenderStringableItems(): void
    {
        $this->mockWebApplication();

        self::assertSame(
            Accordion::widget(['items' => [['content' => '<p>C</p>', 'label' => 'A & a']], 'options' => ['id' => 'a']]),
            Accordion::widget([
                'items' => [['content' => new StringableValue('<p>C</p>'), 'label' => new StringableValue('A & a')]],
                'options' => ['id' => 'a'],
            ]),
            'Stringable values must render like their `string`.',
        );
    }

    public function testRenderValueObjects(): void
    {
        $this->mockWebApplication();

        self::assertSame(
            Accordion::widget(
                [
                    'encodeLabels' => false,
                    'items' => [
                        [
                            'bodyOptions' => ['class' => 'my-body'],
                            'content' => 'Body',
                            'headerOptions' => ['class' => 'my-header'],
                            'label' => '<b>Raw</b>',
                            'open' => true,
                            'options' => ['class' => 'my-item', 'name' => 'group'],
                        ],
                        ['content' => 'Other', 'encode' => true, 'label' => '<b>Encoded</b>'],
                        ['content' => 'Hidden', 'label' => 'Hidden', 'visible' => false],
                    ],
                    'options' => ['id' => 'a'],
                ],
            ),
            Accordion::widget(
                [
                    'encodeLabels' => false,
                    'items' => [
                        AccordionItem::to('<b>Raw</b>', 'Body')
                            ->withOpen(true)
                            ->withOptions(['class' => 'my-item', 'name' => 'group'])
                            ->withHeaderOptions(['class' => 'my-header'])
                            ->withBodyOptions(['class' => 'my-body']),
                        AccordionItem::to('<b>Encoded</b>', 'Other')->withEncode(true),
                        AccordionItem::to('Hidden', 'Hidden')->withVisible(false),
                    ],
                    'options' => ['id' => 'a'],
                ],
            ),
            'Value objects must render like their array form.',
        );
    }

    public function testThrowInvalidConfigExceptionWhenContentIsMissing(): void
    {
        $this->mockWebApplication();

        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage(
            Message::ITEM_CONTENT_REQUIRED->getMessage(),
        );

        Accordion::widget(['items' => [['label' => 'Item']]]);
    }

    public function testThrowInvalidConfigExceptionWhenLabelIsMissing(): void
    {
        $this->mockWebApplication();

        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage(
            Message::ITEM_LABEL_REQUIRED->getMessage(),
        );

        Accordion::widget(['items' => ['content']]);
    }
}
