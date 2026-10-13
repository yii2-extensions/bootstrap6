<?php

declare(strict_types=1);

namespace yii2\extensions\bootstrap6\tests\provider;

use Closure;
use yii2\extensions\bootstrap6\item\{MenuItem, NavItem};
use yii2\extensions\bootstrap6\tests\support\stub\StringableValue;

/**
 * Data provider for {@see \yii2\extensions\bootstrap6\tests\item\NavItemTest} test cases.
 *
 * Provides array configurations with their equivalent value objects, `with*()` mutations, and invalid configurations.
 */
final class NavItemProvider
{
    /**
     * @return iterable<string, array{array<array-key, mixed>, NavItem}>
     */
    public static function fromArrayCases(): iterable
    {
        $label = new StringableValue('Stringable');

        yield 'label only' => [
            ['label' => 'A'],
            NavItem::to('A'),
        ];
        yield 'Stringable label' => [
            ['label' => $label],
            NavItem::to($label),
        ];
        yield 'url' => [
            ['label' => 'A', 'url' => '/a'],
            NavItem::to('A', '/a'),
        ];
        yield 'url `null`' => [
            ['label' => 'A', 'url' => null],
            NavItem::to('A', null),
        ];
        yield 'route' => [
            ['label' => 'A', 'url' => ['site/index']],
            NavItem::to('A', ['site/index']),
        ];
        yield 'every key' => [
            [
                'active' => true,
                'disabled' => true,
                'encode' => false,
                'items' => [['label' => 'B', 'url' => '#']],
                'label' => 'A',
                'linkOptions' => ['class' => 'link'],
                'menuOptions' => ['class' => 'menu'],
                'options' => ['class' => 'item'],
                'url' => '/a',
                'visible' => false,
            ],
            NavItem::menu('A', [MenuItem::link('B', '#')])
                ->withActive(true)
                ->withDisabled(true)
                ->withVisible(false)
                ->withEncode(false)
                ->withOptions(['class' => 'item'])
                ->withLinkOptions(['class' => 'link'])
                ->withMenuOptions(['class' => 'menu'])
                ->withUrl('/a'),
        ];
        yield 'menu HTML' => [
            ['items' => '<div class="menu"></div>', 'label' => 'A'],
            NavItem::menu('A', '<div class="menu"></div>'),
        ];
        yield 'menu items' => [
            [
                'items' => [
                    'x' => ['label' => 'B'],
                    3 => '-',
                    4 => MenuItem::header('H'),
                    5 => ['items' => [['label' => 'D', 'url' => '#']], 'label' => 'C'],
                ],
                'label' => 'A',
            ],
            NavItem::menu(
                'A',
                [
                    'x' => MenuItem::button('B'),
                    3 => '-',
                    4 => MenuItem::header('H'),
                    5 => MenuItem::submenu('C', [MenuItem::link('D', '#')]),
                ],
            ),
        ];
        yield 'empty items' => [
            ['items' => [], 'label' => 'A'],
            NavItem::to('A'),
        ];
        yield 'active `false`' => [
            ['active' => false, 'label' => 'A'],
            NavItem::to('A')->withActive(false),
        ];
        yield 'active `null`' => [
            ['active' => null, 'label' => 'A'],
            NavItem::to('A'),
        ];
        yield 'active non-`bool`' => [
            ['active' => 1, 'label' => 'A'],
            NavItem::to('A')->withActive(false),
        ];
        yield 'disabled `false`' => [
            ['disabled' => false, 'label' => 'A'],
            NavItem::to('A'),
        ];
        yield 'disabled non-`bool`' => [
            ['disabled' => 1, 'label' => 'A'],
            NavItem::to('A'),
        ];
        yield 'visible `true`' => [
            ['label' => 'A', 'visible' => true],
            NavItem::to('A'),
        ];
        yield 'visible `null`' => [
            ['label' => 'A', 'visible' => null],
            NavItem::to('A'),
        ];
        yield 'visible non-`bool`' => [
            ['label' => 'A', 'visible' => 0],
            NavItem::to('A'),
        ];
        yield 'encode `true`' => [
            ['encode' => true, 'label' => 'A'],
            NavItem::to('A')->withEncode(true),
        ];
        yield 'encode `null`' => [
            ['encode' => null, 'label' => 'A'],
            NavItem::to('A'),
        ];
        yield 'encode non-`bool`' => [
            ['encode' => 0, 'label' => 'A'],
            NavItem::to('A')->withEncode(true),
        ];
    }

    /**
     * @return iterable<string, array{array<array-key, mixed>}>
     */
    public static function invalidLabelCases(): iterable
    {
        yield 'empty' => [[]];
        yield 'url without label' => [['url' => '#']];
        yield 'invisible without label' => [['url' => '#', 'visible' => false]];
        yield 'label `int`' => [['label' => 5]];
        yield 'menu item without label' => [['items' => [['url' => '#']], 'label' => 'A']];
    }

    /**
     * @return iterable<string, array{Closure(NavItem): NavItem, string, mixed}>
     */
    public static function withCases(): iterable
    {
        yield 'active' => [
            static fn(NavItem $item): NavItem => $item->withActive(null),
            'active',
            null,
        ];
        yield 'disabled' => [
            static fn(NavItem $item): NavItem => $item->withDisabled(false),
            'disabled',
            false,
        ];
        yield 'encode' => [
            static fn(NavItem $item): NavItem => $item->withEncode(true),
            'encode',
            true,
        ];
        yield 'items' => [
            static fn(NavItem $item): NavItem => $item->withItems('<div></div>'),
            'items',
            '<div></div>',
        ];
        yield 'label' => [
            static fn(NavItem $item): NavItem => $item->withLabel('New'),
            'label',
            'New',
        ];
        yield 'linkOptions' => [
            static fn(NavItem $item): NavItem => $item->withLinkOptions(['id' => 'l']),
            'linkOptions',
            ['id' => 'l'],
        ];
        yield 'menuOptions' => [
            static fn(NavItem $item): NavItem => $item->withMenuOptions(['id' => 'm']),
            'menuOptions',
            ['id' => 'm'],
        ];
        yield 'options' => [
            static fn(NavItem $item): NavItem => $item->withOptions(['id' => 'o']),
            'options',
            ['id' => 'o'],
        ];
        yield 'url' => [
            static fn(NavItem $item): NavItem => $item->withUrl(null),
            'url',
            null,
        ];
        yield 'visible' => [
            static fn(NavItem $item): NavItem => $item->withVisible(true),
            'visible',
            true,
        ];
    }
}
