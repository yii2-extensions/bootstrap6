<?php

declare(strict_types=1);

namespace yii2\extensions\bootstrap6\tests\provider;

use Closure;
use yii2\extensions\bootstrap6\enum\MenuItemType;
use yii2\extensions\bootstrap6\item\MenuItem;
use yii2\extensions\bootstrap6\tests\support\stub\StringableValue;

/**
 * Data provider for {@see \yii2\extensions\bootstrap6\tests\item\MenuItemTest} test cases.
 *
 * Provides array configurations with their equivalent value objects, `with*()` mutations, and invalid configurations.
 */
final class MenuItemProvider
{
    /**
     * @return iterable<string, array{array<array-key, mixed>, MenuItem}>
     */
    public static function fromArrayCases(): iterable
    {
        $label = new StringableValue('Stringable');

        yield 'link' => [
            ['label' => 'A', 'url' => '/a'],
            MenuItem::link('A', '/a'),
        ];
        yield 'link with `null` url' => [
            ['label' => 'A', 'url' => null],
            MenuItem::link('A', null),
        ];
        yield 'link with route' => [
            ['label' => 'A', 'url' => ['site/index']],
            MenuItem::link('A', ['site/index']),
        ];
        yield 'link with empty items' => [
            ['items' => [], 'label' => 'A', 'url' => '#'],
            MenuItem::link('A', '#'),
        ];
        yield 'button' => [
            ['label' => 'A'],
            MenuItem::button('A'),
        ];
        yield 'button with empty items' => [
            ['items' => [], 'label' => 'A'],
            MenuItem::button('A'),
        ];
        yield 'button with Stringable label' => [
            ['label' => $label],
            MenuItem::button($label),
        ];
        yield 'submenu' => [
            ['items' => [['label' => 'B', 'url' => '#'], '-', '<hr>', MenuItem::divider()], 'label' => 'A'],
            MenuItem::submenu('A', [MenuItem::link('B', '#'), '-', '<hr>', MenuItem::divider()]),
        ];
        yield 'submenu keeps keys' => [
            ['items' => ['x' => ['label' => 'B'], 3 => '-'], 'label' => 'A'],
            MenuItem::submenu('A', ['x' => MenuItem::button('B'), 3 => '-']),
        ];
        yield 'submenu nested recursively' => [
            ['items' => [['items' => [['label' => 'C', 'url' => '#']], 'label' => 'B']], 'label' => 'A'],
            MenuItem::submenu('A', [MenuItem::submenu('B', [MenuItem::link('C', '#')])]),
        ];
        yield 'submenu with url' => [
            ['items' => ['-'], 'label' => 'A', 'url' => '/a'],
            MenuItem::submenu('A', ['-'])->withUrl('/a'),
        ];
        yield 'submenu with every key' => [
            [
                'active' => true,
                'disabled' => true,
                'encode' => false,
                'items' => ['-'],
                'label' => 'A',
                'linkOptions' => ['class' => 'link'],
                'options' => ['class' => 'wrapper'],
                'submenuOptions' => ['class' => 'nested'],
                'visible' => false,
            ],
            MenuItem::submenu('A', ['-'])
                ->withActive(true)
                ->withDisabled(true)
                ->withVisible(false)
                ->withEncode(false)
                ->withOptions(['class' => 'wrapper'])
                ->withLinkOptions(['class' => 'link'])
                ->withSubmenuOptions(['class' => 'nested']),
        ];
        yield 'header' => [
            ['header' => 'H'],
            MenuItem::header('H'),
        ];
        yield 'header with every key' => [
            ['encode' => false, 'header' => 'H', 'options' => ['class' => 'h'], 'visible' => false],
            MenuItem::header('H')->withVisible(false)->withEncode(false)->withOptions(['class' => 'h']),
        ];
        yield 'header before text' => [
            ['header' => 'H', 'text' => 'T'],
            MenuItem::header('H'),
        ];
        yield 'header `null`' => [
            ['header' => null, 'label' => 'L'],
            MenuItem::button('L'),
        ];
        yield 'text' => [
            ['text' => 'T'],
            MenuItem::text('T'),
        ];
        yield 'text before label' => [
            ['label' => 'L', 'text' => 'T'],
            MenuItem::text('T'),
        ];
        yield 'text with every key' => [
            ['encode' => true, 'options' => ['class' => 't'], 'text' => 'T', 'visible' => false],
            MenuItem::text('T')->withVisible(false)->withEncode(true)->withOptions(['class' => 't']),
        ];
        yield 'text `null`' => [
            ['label' => 'L', 'text' => null],
            MenuItem::button('L'),
        ];
        yield 'divider' => [
            ['divider' => true],
            MenuItem::divider(),
        ];
        yield 'divider with options' => [
            ['divider' => true, 'options' => ['class' => 'my-2']],
            MenuItem::divider()->withOptions(['class' => 'my-2']),
        ];
        yield 'divider before header' => [
            ['divider' => true, 'header' => 'H', 'label' => 'L'],
            MenuItem::divider(),
        ];
        yield 'divider `false`' => [
            ['divider' => false, 'label' => 'L'],
            MenuItem::button('L'),
        ];
        yield 'divider non-`bool`' => [
            ['divider' => 1, 'label' => 'L'],
            MenuItem::button('L'),
        ];
        yield 'active `false`' => [
            ['active' => false, 'label' => 'L'],
            MenuItem::button('L')->withActive(false),
        ];
        yield 'active `null`' => [
            ['active' => null, 'label' => 'L'],
            MenuItem::button('L'),
        ];
        yield 'active non-`bool`' => [
            ['active' => 1, 'label' => 'L'],
            MenuItem::button('L')->withActive(false),
        ];
        yield 'disabled `false`' => [
            ['disabled' => false, 'label' => 'L'],
            MenuItem::button('L'),
        ];
        yield 'disabled non-`bool`' => [
            ['disabled' => 1, 'label' => 'L'],
            MenuItem::button('L'),
        ];
        yield 'visible `true`' => [
            ['label' => 'L', 'visible' => true],
            MenuItem::button('L'),
        ];
        yield 'visible `null`' => [
            ['label' => 'L', 'visible' => null],
            MenuItem::button('L'),
        ];
        yield 'visible non-`bool`' => [
            ['label' => 'L', 'visible' => 0],
            MenuItem::button('L'),
        ];
        yield 'encode `true`' => [
            ['encode' => true, 'label' => 'L'],
            MenuItem::button('L')->withEncode(true),
        ];
        yield 'encode `null`' => [
            ['encode' => null, 'label' => 'L'],
            MenuItem::button('L'),
        ];
        yield 'encode non-`bool`' => [
            ['encode' => 0, 'label' => 'L'],
            MenuItem::button('L')->withEncode(true),
        ];
    }

    /**
     * @return iterable<string, array{array<array-key, mixed>}>
     */
    public static function invalidLabelCases(): iterable
    {
        yield 'empty' => [[]];
        yield 'link without label' => [['url' => '#']];
        yield 'invisible without label' => [['url' => '#', 'visible' => false]];
        yield 'label `null`' => [['label' => null]];
        yield 'label `int`' => [['label' => 5]];
        yield 'nested without label' => [['items' => [['url' => '#']], 'label' => 'A']];
    }

    /**
     * @return iterable<string, array{Closure(MenuItem): MenuItem, string, mixed}>
     */
    public static function withCases(): iterable
    {
        yield 'active' => [
            static fn(MenuItem $item): MenuItem => $item->withActive(null),
            'active',
            null,
        ];
        yield 'disabled' => [
            static fn(MenuItem $item): MenuItem => $item->withDisabled(false),
            'disabled',
            false,
        ];
        yield 'encode' => [
            static fn(MenuItem $item): MenuItem => $item->withEncode(true),
            'encode',
            true,
        ];
        yield 'items' => [
            static fn(MenuItem $item): MenuItem => $item->withItems(['-']),
            'items',
            ['-'],
        ];
        yield 'label' => [
            static fn(MenuItem $item): MenuItem => $item->withLabel('New'),
            'label',
            'New',
        ];
        yield 'linkOptions' => [
            static fn(MenuItem $item): MenuItem => $item->withLinkOptions(['id' => 'l']),
            'linkOptions',
            ['id' => 'l'],
        ];
        yield 'options' => [
            static fn(MenuItem $item): MenuItem => $item->withOptions(['id' => 'o']),
            'options',
            ['id' => 'o'],
        ];
        yield 'submenuOptions' => [
            static fn(MenuItem $item): MenuItem => $item->withSubmenuOptions(['id' => 's']),
            'submenuOptions',
            ['id' => 's'],
        ];
        yield 'type' => [
            static fn(MenuItem $item): MenuItem => $item->withType(MenuItemType::HEADER),
            'type',
            MenuItemType::HEADER,
        ];
        yield 'url' => [
            static fn(MenuItem $item): MenuItem => $item->withUrl(['site/index']),
            'url',
            ['site/index'],
        ];
        yield 'visible' => [
            static fn(MenuItem $item): MenuItem => $item->withVisible(true),
            'visible',
            true,
        ];
    }
}
