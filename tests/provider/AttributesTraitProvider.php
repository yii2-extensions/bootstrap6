<?php

declare(strict_types=1);

namespace yii2\extensions\bootstrap6\tests\provider;

use BackedEnum;
use yii2\extensions\bootstrap6\enum\Theme;

/**
 * Data provider for {@see \yii2\extensions\bootstrap6\tests\AttributesTraitTest} test cases.
 *
 * Provides representative input/output pairs for the `class` and `style` attributes.
 */
final class AttributesTraitProvider
{
    /**
     * @return iterable<string, array{array<string, mixed>, list<BackedEnum|string|null>, string}>
     */
    public static function addClassCases(): iterable
    {
        yield 'array class' => [
            ['class' => ['a']],
            ['b'],
            '<div id="w0" class="a b"></div>',
        ];
        yield 'duplicates' => [
            ['class' => 'a'],
            ['a', 'b', 'b'],
            '<div id="w0" class="a b"></div>',
        ];
        yield 'enum' => [
            [],
            [Theme::PRIMARY, 'b'],
            '<div id="w0" class="primary b"></div>',
        ];
        yield 'no class configured' => [
            [],
            ['a', 'b'],
            '<div id="w0" class="a b"></div>',
        ];
        yield 'null entries' => [
            ['class' => 'a'],
            [null, 'b', null],
            '<div id="w0" class="a b"></div>',
        ];
        yield 'only null' => [
            [],
            [null],
            '<div id="w0"></div>',
        ];
        yield 'space-separated string class' => [
            ['class' => 'a b'],
            ['c'],
            '<div id="w0" class="a b c"></div>',
        ];
        yield 'string class' => [
            ['class' => 'a'],
            ['b'],
            '<div id="w0" class="a b"></div>',
        ];
    }

    /**
     * @return iterable<string, array{array<string, mixed>, array<string, string>|string, bool, string}>
     */
    public static function addCssStyleCases(): iterable
    {
        yield 'array appended' => [
            ['style' => 'color: red;'],
            ['margin' => '0'],
            true,
            '<div id="w0" style="color: red; margin: 0;"></div>',
        ];
        yield 'array keeps existing value' => [
            ['style' => ['color' => 'red']],
            ['color' => 'blue', 'margin' => '0'],
            false,
            '<div id="w0" style="color: red; margin: 0;"></div>',
        ];
        yield 'array replaces existing value' => [
            ['style' => 'color: red; padding: 0;'],
            ['color' => 'blue', 'margin' => '0'],
            true,
            '<div id="w0" style="color: blue; padding: 0; margin: 0;"></div>',
        ];
        yield 'array without style' => [
            [],
            ['color' => 'red'],
            true,
            '<div id="w0" style="color: red;"></div>',
        ];
        yield 'string appended' => [
            ['style' => 'color: red;'],
            'font-weight: bold;',
            false,
            '<div id="w0" style="color: red; font-weight: bold;"></div>',
        ];
        yield 'string keeps existing value' => [
            ['style' => 'color: red;'],
            'color: blue; margin: 0;',
            false,
            '<div id="w0" style="color: red; margin: 0;"></div>',
        ];
        yield 'string replaces existing value' => [
            ['style' => 'color: red; padding: 0;'],
            'color: blue;',
            true,
            '<div id="w0" style="color: blue; padding: 0;"></div>',
        ];
        yield 'string without style' => [
            [],
            'color: red',
            true,
            '<div id="w0" style="color: red"></div>',
        ];
    }
}
