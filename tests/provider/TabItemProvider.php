<?php

declare(strict_types=1);

namespace yii2\extensions\bootstrap6\tests\provider;

use Closure;
use yii2\extensions\bootstrap6\item\{MenuItem, TabItem};
use yii2\extensions\bootstrap6\tests\support\stub\StringableValue;

/**
 * Data provider for {@see \yii2\extensions\bootstrap6\tests\item\TabItemTest} test cases.
 *
 * Provides array configurations with their equivalent value objects, `with*()` mutations, and invalid configurations.
 */
final class TabItemProvider
{
    /**
     * @return iterable<string, array{array<array-key, mixed>, TabItem}>
     */
    public static function fromArrayCases(): iterable
    {
        $label = new StringableValue('Stringable');
        $content = new StringableValue('Content');

        yield 'label only' => [
            ['label' => 'A'],
            TabItem::to('A'),
        ];
        yield 'Stringable values' => [
            ['content' => $content, 'label' => $label],
            TabItem::to($label, $content),
        ];
        yield 'content' => [
            ['content' => 'c', 'label' => 'A'],
            TabItem::to('A', 'c'),
        ];
        yield 'url' => [
            ['label' => 'A', 'url' => '/a'],
            TabItem::link('A', '/a'),
        ];
        yield 'url `null`' => [
            ['label' => 'A', 'url' => null],
            TabItem::to('A'),
        ];
        yield 'route' => [
            ['label' => 'A', 'url' => ['site/index']],
            TabItem::link('A', ['site/index']),
        ];
        yield 'every key' => [
            [
                'active' => true,
                'content' => 'c',
                'disabled' => true,
                'encode' => false,
                'headerOptions' => ['class' => 'header'],
                'items' => ['-'],
                'label' => 'A',
                'linkOptions' => ['class' => 'link'],
                'options' => ['class' => 'pane'],
                'url' => '/a',
                'visible' => false,
            ],
            TabItem::to('A', 'c')
                ->withActive(true)
                ->withDisabled(true)
                ->withVisible(false)
                ->withEncode(false)
                ->withHeaderOptions(['class' => 'header'])
                ->withLinkOptions(['class' => 'link'])
                ->withOptions(['class' => 'pane'])
                ->withUrl('/a')
                ->withItems(['-']),
        ];
        yield 'menu items' => [
            [
                'items' => [
                    'x' => ['content' => 't', 'label' => 'T'],
                    2 => ['label' => 'L', 'url' => '/l'],
                    3 => ['header' => 'H'],
                    4 => '-',
                    5 => TabItem::to('V'),
                    6 => MenuItem::divider(),
                    7 => ['label' => 'N', 'url' => null],
                    8 => ['items' => [['label' => 'D2']], 'label' => 'D'],
                ],
                'label' => 'A',
            ],
            TabItem::menu(
                'A',
                [
                    'x' => TabItem::to('T', 't'),
                    2 => MenuItem::link('L', '/l'),
                    3 => MenuItem::header('H'),
                    4 => '-',
                    5 => TabItem::to('V'),
                    6 => MenuItem::divider(),
                    7 => MenuItem::link('N', null),
                    8 => TabItem::menu('D', [TabItem::to('D2')]),
                ],
            ),
        ];
        yield 'active `true`' => [
            ['active' => true, 'label' => 'A'],
            TabItem::to('A')->withActive(true),
        ];
        yield 'active `false`' => [
            ['active' => false, 'label' => 'A'],
            TabItem::to('A')->withActive(false),
        ];
        yield 'active `null`' => [
            ['active' => null, 'label' => 'A'],
            TabItem::to('A')->withActive(false),
        ];
        yield 'active non-`bool`' => [
            ['active' => 1, 'label' => 'A'],
            TabItem::to('A')->withActive(false),
        ];
        yield 'disabled `false`' => [
            ['disabled' => false, 'label' => 'A'],
            TabItem::to('A'),
        ];
        yield 'disabled non-`bool`' => [
            ['disabled' => 1, 'label' => 'A'],
            TabItem::to('A'),
        ];
        yield 'visible `true`' => [
            ['label' => 'A', 'visible' => true],
            TabItem::to('A'),
        ];
        yield 'visible `null`' => [
            ['label' => 'A', 'visible' => null],
            TabItem::to('A'),
        ];
        yield 'visible non-`bool`' => [
            ['label' => 'A', 'visible' => 0],
            TabItem::to('A'),
        ];
        yield 'encode `true`' => [
            ['encode' => true, 'label' => 'A'],
            TabItem::to('A')->withEncode(true),
        ];
        yield 'encode `null`' => [
            ['encode' => null, 'label' => 'A'],
            TabItem::to('A'),
        ];
        yield 'encode non-`bool`' => [
            ['encode' => 0, 'label' => 'A'],
            TabItem::to('A')->withEncode(true),
        ];
    }

    /**
     * @return iterable<string, array{array<array-key, mixed>}>
     */
    public static function invalidLabelCases(): iterable
    {
        yield 'empty' => [
            [],
        ];
        yield 'content without label' => [
            ['content' => 'a'],
        ];
        yield 'invisible without label' => [
            ['content' => 'a', 'visible' => false],
        ];
        yield 'label `int`' => [
            ['label' => 5],
        ];
        yield 'menu item without label' => [
            ['items' => [['content' => 'a']], 'label' => 'M'],
        ];
        yield 'nested tab item without label' => [
            ['items' => [['items' => [['content' => 'a']], 'label' => 'T']], 'label' => 'M'],
        ];
    }

    /**
     * @return iterable<string, array{Closure(TabItem): TabItem, string, mixed}>
     */
    public static function withCases(): iterable
    {
        yield 'active' => [
            static fn(TabItem $item): TabItem => $item->withActive(null),
            'active',
            null,
        ];
        yield 'content' => [
            static fn(TabItem $item): TabItem => $item->withContent(null),
            'content',
            null,
        ];
        yield 'disabled' => [
            static fn(TabItem $item): TabItem => $item->withDisabled(false),
            'disabled',
            false,
        ];
        yield 'encode' => [
            static fn(TabItem $item): TabItem => $item->withEncode(true),
            'encode',
            true,
        ];
        yield 'headerOptions' => [
            static fn(TabItem $item): TabItem => $item->withHeaderOptions(['id' => 'h']),
            'headerOptions',
            ['id' => 'h'],
        ];
        yield 'items' => [
            static fn(TabItem $item): TabItem => $item->withItems(['-']),
            'items',
            ['-'],
        ];
        yield 'label' => [
            static fn(TabItem $item): TabItem => $item->withLabel('New'),
            'label',
            'New',
        ];
        yield 'linkOptions' => [
            static fn(TabItem $item): TabItem => $item->withLinkOptions(['id' => 'l']),
            'linkOptions',
            ['id' => 'l'],
        ];
        yield 'options' => [
            static fn(TabItem $item): TabItem => $item->withOptions(['id' => 'o']),
            'options',
            ['id' => 'o'],
        ];
        yield 'url' => [
            static fn(TabItem $item): TabItem => $item->withUrl(null),
            'url',
            null,
        ];
        yield 'visible' => [
            static fn(TabItem $item): TabItem => $item->withVisible(true),
            'visible',
            true,
        ];
    }
}
