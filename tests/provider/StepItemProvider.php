<?php

declare(strict_types=1);

namespace yii2\extensions\bootstrap6\tests\provider;

use Closure;
use yii2\extensions\bootstrap6\enum\Theme;
use yii2\extensions\bootstrap6\exception\Message;
use yii2\extensions\bootstrap6\item\StepItem;
use yii2\extensions\bootstrap6\tests\support\stub\StringableValue;

/**
 * Data provider for {@see \yii2\extensions\bootstrap6\tests\item\StepItemTest} test cases.
 *
 * Provides array configurations with their equivalent value objects, `with*()` mutations, and invalid configurations.
 */
final class StepItemProvider
{
    /**
     * @return iterable<string, array{array<array-key, mixed>, StepItem}>
     */
    public static function fromArrayCases(): iterable
    {
        $label = new StringableValue('Label');
        $description = new StringableValue('Description');

        yield 'label only' => [
            ['label' => 'A'],
            StepItem::to('A'),
        ];
        yield 'Stringable label and description' => [
            ['description' => $description, 'label' => $label],
            StepItem::to($label, $description),
        ];
        yield 'every key' => [
            [
                'active' => true,
                'description' => 'D',
                'encode' => false,
                'label' => 'A',
                'options' => ['class' => 'step'],
                'theme' => Theme::SUCCESS,
                'url' => '/a',
            ],
            StepItem::to('A', 'D')
                ->withActive(true)
                ->withTheme(Theme::SUCCESS)
                ->withUrl('/a')
                ->withEncode(false)
                ->withOptions(['class' => 'step']),
        ];
        yield 'description non-`string`' => [
            ['description' => 5, 'label' => 'A'],
            StepItem::to('A'),
        ];
        yield 'active `false`' => [
            ['active' => false, 'label' => 'A'],
            StepItem::to('A'),
        ];
        yield 'active non-`bool`' => [
            ['active' => 'yes', 'label' => 'A'],
            StepItem::to('A'),
        ];
        yield 'theme `string`' => [
            ['label' => 'A', 'theme' => 'danger'],
            StepItem::to('A')->withTheme('danger'),
        ];
        yield 'theme non-`string`' => [
            ['label' => 'A', 'theme' => 5],
            StepItem::to('A'),
        ];
        yield 'route' => [
            ['label' => 'A', 'url' => ['site/index']],
            StepItem::to('A')->withUrl(['site/index']),
        ];
        yield 'url non-`string`' => [
            ['label' => 'A', 'url' => 5],
            StepItem::to('A'),
        ];
        yield 'encode `true`' => [
            ['encode' => true, 'label' => 'A'],
            StepItem::to('A')->withEncode(true),
        ];
        yield 'encode `null`' => [
            ['encode' => null, 'label' => 'A'],
            StepItem::to('A'),
        ];
        yield 'encode non-`bool`' => [
            ['encode' => 'no', 'label' => 'A'],
            StepItem::to('A'),
        ];
        yield 'options non-`array`' => [
            ['label' => 'A', 'options' => 'x'],
            StepItem::to('A'),
        ];
    }

    /**
     * @return iterable<string, array{Closure(): StepItem, string}>
     */
    public static function invalidCases(): iterable
    {
        $label = Message::ITEM_LABEL_REQUIRED->getMessage();
        $themes = 'accent, danger, info, inverse, primary, secondary, success, warning';
        $theme = Message::INVALID_VALUE->getMessage('light', 'theme', $themes);

        yield 'empty' => [
            static fn(): StepItem => StepItem::fromArray([]),
            $label,
        ];
        yield 'url without label' => [
            static fn(): StepItem => StepItem::fromArray(['url' => '#']),
            $label,
        ];
        yield 'label `int`' => [
            static fn(): StepItem => StepItem::fromArray(['label' => 5]),
            $label,
        ];
        yield 'theme in array' => [
            static fn(): StepItem => StepItem::fromArray(['label' => 'A', 'theme' => 'light']),
            $theme,
        ];
        yield 'theme in copy' => [
            static fn(): StepItem => StepItem::to('A')->withTheme('light'),
            $theme,
        ];
    }

    /**
     * @return iterable<string, array{Closure(StepItem): StepItem, string, mixed}>
     */
    public static function withCases(): iterable
    {
        yield 'active' => [
            static fn(StepItem $item): StepItem => $item->withActive(false),
            'active',
            false,
        ];
        yield 'description' => [
            static fn(StepItem $item): StepItem => $item->withDescription(null),
            'description',
            null,
        ];
        yield 'encode' => [
            static fn(StepItem $item): StepItem => $item->withEncode(null),
            'encode',
            null,
        ];
        yield 'label' => [
            static fn(StepItem $item): StepItem => $item->withLabel('New'),
            'label',
            'New',
        ];
        yield 'options' => [
            static fn(StepItem $item): StepItem => $item->withOptions(['id' => 'o']),
            'options',
            ['id' => 'o'],
        ];
        yield 'theme' => [
            static fn(StepItem $item): StepItem => $item->withTheme(Theme::DANGER),
            'theme',
            Theme::DANGER,
        ];
        yield 'url' => [
            static fn(StepItem $item): StepItem => $item->withUrl(null),
            'url',
            null,
        ];
    }
}
