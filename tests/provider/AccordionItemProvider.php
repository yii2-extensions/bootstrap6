<?php

declare(strict_types=1);

namespace yii2\extensions\bootstrap6\tests\provider;

use Closure;
use yii2\extensions\bootstrap6\exception\Message;
use yii2\extensions\bootstrap6\item\AccordionItem;
use yii2\extensions\bootstrap6\tests\support\stub\StringableValue;

/**
 * Data provider for {@see \yii2\extensions\bootstrap6\tests\item\AccordionItemTest} test cases.
 */
final class AccordionItemProvider
{
    /**
     * @return iterable<string, array{array<array-key, mixed>, AccordionItem}>
     */
    public static function fromArrayCases(): iterable
    {
        $label = new StringableValue('Label');
        $content = new StringableValue('Content');

        yield 'label and content' => [
            ['content' => 'B', 'label' => 'A'],
            AccordionItem::to('A', 'B'),
        ];
        yield 'Stringable label and content' => [
            ['content' => $content, 'label' => $label],
            AccordionItem::to($label, $content),
        ];
        yield 'every key' => [
            [
                'bodyOptions' => ['class' => 'body'],
                'content' => 'B',
                'encode' => false,
                'headerOptions' => ['class' => 'header'],
                'label' => 'A',
                'open' => true,
                'options' => ['class' => 'item'],
                'visible' => false,
            ],
            AccordionItem::to('A', 'B')
                ->withOpen(true)
                ->withVisible(false)
                ->withEncode(false)
                ->withOptions(['class' => 'item'])
                ->withHeaderOptions(['class' => 'header'])
                ->withBodyOptions(['class' => 'body']),
        ];
        yield 'open `false`' => [
            ['content' => 'B', 'label' => 'A', 'open' => false],
            AccordionItem::to('A', 'B'),
        ];
        yield 'open non-`bool`' => [
            ['content' => 'B', 'label' => 'A', 'open' => 'yes'],
            AccordionItem::to('A', 'B'),
        ];
        yield 'visible `true`' => [
            ['content' => 'B', 'label' => 'A', 'visible' => true],
            AccordionItem::to('A', 'B'),
        ];
        yield 'visible `null`' => [
            ['content' => 'B', 'label' => 'A', 'visible' => null],
            AccordionItem::to('A', 'B'),
        ];
        yield 'visible non-`bool`' => [
            ['content' => 'B', 'label' => 'A', 'visible' => 0],
            AccordionItem::to('A', 'B'),
        ];
        yield 'encode `true`' => [
            ['content' => 'B', 'encode' => true, 'label' => 'A'],
            AccordionItem::to('A', 'B')->withEncode(true),
        ];
        yield 'encode `null`' => [
            ['content' => 'B', 'encode' => null, 'label' => 'A'],
            AccordionItem::to('A', 'B'),
        ];
        yield 'encode non-`bool`' => [
            ['content' => 'B', 'encode' => 0, 'label' => 'A'],
            AccordionItem::to('A', 'B')->withEncode(true),
        ];
        yield 'attribute keys non-`array`' => [
            ['bodyOptions' => 'b', 'content' => 'B', 'headerOptions' => 'h', 'label' => 'A', 'options' => 'o'],
            AccordionItem::to('A', 'B'),
        ];
    }

    /**
     * @return iterable<string, array{array<array-key, mixed>, string}>
     */
    public static function invalidCases(): iterable
    {
        $label = Message::ITEM_LABEL_REQUIRED->getMessage();
        $content = Message::ITEM_CONTENT_REQUIRED->getMessage();

        yield 'empty' => [[], $label];
        yield 'content without label' => [['content' => 'B'], $label];
        yield 'label `int`' => [['content' => 'B', 'label' => 5], $label];
        yield 'label without content' => [['label' => 'A'], $content];
        yield 'content `null`' => [['content' => null, 'label' => 'A'], $content];
        yield 'content `array`' => [['content' => ['B'], 'label' => 'A'], $content];
        yield 'invisible without content' => [['label' => 'A', 'visible' => false], $content];
    }

    /**
     * @return iterable<string, array{Closure(AccordionItem): AccordionItem, string, mixed}>
     */
    public static function withCases(): iterable
    {
        yield 'bodyOptions' => [
            static fn(AccordionItem $item): AccordionItem => $item->withBodyOptions(['id' => 'b']),
            'bodyOptions',
            ['id' => 'b'],
        ];
        yield 'content' => [
            static fn(AccordionItem $item): AccordionItem => $item->withContent('New'),
            'content',
            'New',
        ];
        yield 'encode' => [
            static fn(AccordionItem $item): AccordionItem => $item->withEncode(null),
            'encode',
            null,
        ];
        yield 'headerOptions' => [
            static fn(AccordionItem $item): AccordionItem => $item->withHeaderOptions(['id' => 'h']),
            'headerOptions',
            ['id' => 'h'],
        ];
        yield 'label' => [
            static fn(AccordionItem $item): AccordionItem => $item->withLabel('New'),
            'label',
            'New',
        ];
        yield 'open' => [
            static fn(AccordionItem $item): AccordionItem => $item->withOpen(false),
            'open',
            false,
        ];
        yield 'options' => [
            static fn(AccordionItem $item): AccordionItem => $item->withOptions(['id' => 'o']),
            'options',
            ['id' => 'o'],
        ];
        yield 'visible' => [
            static fn(AccordionItem $item): AccordionItem => $item->withVisible(true),
            'visible',
            true,
        ];
    }
}
