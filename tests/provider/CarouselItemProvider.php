<?php

declare(strict_types=1);

namespace yii2\extensions\bootstrap6\tests\provider;

use Closure;
use yii2\extensions\bootstrap6\item\CarouselItem;
use yii2\extensions\bootstrap6\tests\support\stub\StringableValue;

/**
 * Data provider for {@see \yii2\extensions\bootstrap6\tests\item\CarouselItemTest} test cases.
 *
 * Provides array configurations with their equivalent value objects, `with*()` mutations, and invalid configurations.
 */
final class CarouselItemProvider
{
    /**
     * @return iterable<string, array{array<array-key, mixed>, CarouselItem}>
     */
    public static function fromArrayCases(): iterable
    {
        $content = new StringableValue('Content');

        yield 'content only' => [
            ['content' => 'A'],
            CarouselItem::to('A'),
        ];
        yield 'Stringable content' => [
            ['content' => $content],
            CarouselItem::to($content),
        ];
        yield 'every key' => [
            ['content' => 'A', 'options' => ['class' => 'slide']],
            CarouselItem::to('A')->withOptions(['class' => 'slide']),
        ];
        yield 'options non-`array`' => [
            ['content' => 'A', 'options' => 'x'],
            CarouselItem::to('A'),
        ];
    }

    /**
     * @return iterable<string, array{array<array-key, mixed>}>
     */
    public static function invalidContentCases(): iterable
    {
        yield 'empty' => [[]];
        yield 'options without content' => [['options' => []]];
        yield 'content `null`' => [['content' => null]];
        yield 'content `int`' => [['content' => 5]];
    }

    /**
     * @return iterable<string, array{Closure(CarouselItem): CarouselItem, string, mixed}>
     */
    public static function withCases(): iterable
    {
        yield 'content' => [
            static fn(CarouselItem $item): CarouselItem => $item->withContent('New'),
            'content',
            'New',
        ];
        yield 'options' => [
            static fn(CarouselItem $item): CarouselItem => $item->withOptions(['id' => 'o']),
            'options',
            ['id' => 'o'],
        ];
    }
}
