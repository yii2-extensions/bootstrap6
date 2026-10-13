<?php

declare(strict_types=1);

namespace yii2\extensions\bootstrap6\tests\provider;

/**
 * Data provider for {@see \yii2\extensions\bootstrap6\tests\HtmlListTest} test cases.
 *
 * Provides representative input/output pairs for the container, encoding, and item options of the list helpers.
 */
final class HtmlListProvider
{
    /**
     * @return iterable<string, array{string, array<mixed>|string|null, array<mixed>, array<mixed>, string}>
     */
    public static function checkboxListCases(): iterable
    {
        yield 'empty' => [
            'test',
            null,
            [],
            [],
            '<div class="form-group"></div>',
        ];
        yield 'selection' => [
            'test',
            ['value2'],
            ['value1' => 'text1', 'value2' => 'text2'],
            [],
            <<<HTML
            <div class="form-group"><div class="form-field"><input type="checkbox" id="i0" class="check" name="test[]" value="value1">
            <label for="i0">text1</label></div>
            <div class="form-field"><input type="checkbox" id="i1" class="check" name="test[]" value="value2" checked>
            <label for="i1">text2</label></div></div>
            HTML,
        ];
        yield 'encoded label' => [
            'test',
            'value',
            ['value' => 'label&'],
            [],
            <<<HTML
            <div class="form-group"><div class="form-field"><input type="checkbox" id="i0" class="check" name="test[]" value="value" checked>
            <label for="i0">label&amp;</label></div></div>
            HTML,
        ];
        yield 'raw label' => [
            'test',
            'value',
            ['value' => 'label&'],
            ['encode' => false],
            <<<HTML
            <div class="form-group"><div class="form-field"><input type="checkbox" id="i0" class="check" name="test[]" value="value" checked>
            <label for="i0">label&</label></div></div>
            HTML,
        ];
        yield 'non-string labels' => [
            'test',
            null,
            [1 => 5, 2 => ['x']],
            [],
            <<<HTML
            <div class="form-group"><div class="form-field"><input type="checkbox" id="i0" class="check" name="test[]" value="1">
            <label for="i0">5</label></div>
            <div class="form-field"><input type="checkbox" id="i1" class="check" name="test[]" value="2">
            <label for="i1"></label></div></div>
            HTML,
        ];
        yield 'inline' => [
            'test',
            null,
            ['a' => 'A'],
            ['class' => 'mb-3', 'inline' => true],
            <<<HTML
            <div class="mb-3 d-flex flex-wrap gap-5"><div class="form-field"><input type="checkbox" id="i0" class="check" name="test[]" value="a">
            <label for="i0">A</label></div></div>
            HTML,
        ];
        yield 'inline not `true`' => [
            'test',
            null,
            ['a' => 'A'],
            ['inline' => 1],
            <<<HTML
            <div class="form-group"><div class="form-field"><input type="checkbox" id="i0" class="check" name="test[]" value="a">
            <label for="i0">A</label></div></div>
            HTML,
        ];
        yield 'item options' => [
            'test',
            null,
            ['a' => 'A'],
            [
                'itemOptions' => [
                    'class' => 'check-lg',
                    'disabled' => true,
                    'labelOptions' => ['class' => 'fw-medium'],
                    'wrapperOptions' => ['class' => 'form-field-card', 'data-id' => 'a'],
                ],
            ],
            <<<HTML
            <div class="form-group"><div class="form-field form-field-card" data-id="a"><input type="checkbox" id="i0" class="check check-lg" name="test[]" value="a" disabled>
            <label class="fw-medium" for="i0">A</label></div></div>
            HTML,
        ];
        yield 'item options override label and value' => [
            'test',
            null,
            ['a' => 'A'],
            ['itemOptions' => ['label' => 'Fixed', 'value' => 'v']],
            <<<HTML
            <div class="form-group"><div class="form-field"><input type="checkbox" id="i0" class="check" name="test[]" value="v">
            <label for="i0">Fixed</label></div></div>
            HTML,
        ];
        yield 'invalid item options' => [
            'test',
            null,
            ['a' => 'A'],
            ['itemOptions' => 'invalid'],
            <<<HTML
            <div class="form-group"><div class="form-field"><input type="checkbox" id="i0" class="check" name="test[]" value="a">
            <label for="i0">A</label></div></div>
            HTML,
        ];
        yield 'invalid wrapper options' => [
            'test',
            null,
            ['a' => 'A'],
            ['itemOptions' => ['wrapperOptions' => 'invalid']],
            <<<HTML
            <div class="form-group"><div class="form-field"><input type="checkbox" id="i0" class="check" name="test[]" value="a">
            <label for="i0">A</label></div></div>
            HTML,
        ];
        yield 'switch items' => [
            'test',
            null,
            ['a' => 'A'],
            ['itemOptions' => ['switch' => true]],
            <<<HTML
            <div class="form-group"><div class="form-field"><div class="switch"><input type="checkbox" id="i0" name="test[]" value="a" role="switch" switch></div>
            <label for="i0">A</label></div></div>
            HTML,
        ];
        yield 'separator and tag' => [
            'test',
            null,
            ['a' => 'A', 'b' => 'B'],
            ['separator' => '', 'tag' => 'fieldset'],
            <<<HTML
            <fieldset class="form-group"><div class="form-field"><input type="checkbox" id="i0" class="check" name="test[]" value="a">
            <label for="i0">A</label></div><div class="form-field"><input type="checkbox" id="i1" class="check" name="test[]" value="b">
            <label for="i1">B</label></div></fieldset>
            HTML,
        ];
        yield 'no tag' => [
            'test',
            null,
            ['a' => 'A'],
            ['tag' => false],
            <<<HTML
            <div class="form-field"><input type="checkbox" id="i0" class="check" name="test[]" value="a">
            <label for="i0">A</label></div>
            HTML,
        ];
        yield 'unselect' => [
            'test',
            null,
            ['a' => 'A'],
            ['disabled' => true, 'unselect' => '0'],
            <<<HTML
            <input type="hidden" name="test" value="0" disabled><div class="form-group"><div class="form-field"><input type="checkbox" id="i0" class="check" name="test[]" value="a">
            <label for="i0">A</label></div></div>
            HTML,
        ];
    }

    /**
     * @return iterable<string, array{string, array<mixed>|string|null, array<mixed>, array<mixed>, string}>
     */
    public static function radioListCases(): iterable
    {
        yield 'empty' => [
            'test',
            null,
            [],
            [],
            '<div class="form-group"></div>',
        ];
        yield 'selection' => [
            'test',
            ['value2'],
            ['value1' => 'text1', 'value2' => 'text2'],
            [],
            <<<HTML
            <div class="form-group"><div class="form-field"><input type="radio" id="i0" class="radio" name="test" value="value1">
            <label for="i0">text1</label></div>
            <div class="form-field"><input type="radio" id="i1" class="radio" name="test" value="value2" checked>
            <label for="i1">text2</label></div></div>
            HTML,
        ];
        yield 'encoded label' => [
            'test',
            null,
            ['value' => 'label&'],
            [],
            <<<HTML
            <div class="form-group"><div class="form-field"><input type="radio" id="i0" class="radio" name="test" value="value">
            <label for="i0">label&amp;</label></div></div>
            HTML,
        ];
        yield 'raw label' => [
            'test',
            null,
            ['value' => 'label&'],
            ['encode' => false],
            <<<HTML
            <div class="form-group"><div class="form-field"><input type="radio" id="i0" class="radio" name="test" value="value">
            <label for="i0">label&</label></div></div>
            HTML,
        ];
        yield 'inline' => [
            'test',
            null,
            ['a' => 'A'],
            ['inline' => true],
            <<<HTML
            <div class="d-flex flex-wrap gap-5"><div class="form-field"><input type="radio" id="i0" class="radio" name="test" value="a">
            <label for="i0">A</label></div></div>
            HTML,
        ];
        yield 'switch ignored' => [
            'test',
            null,
            ['a' => 'A'],
            ['itemOptions' => ['class' => 'theme-success', 'switch' => true]],
            <<<HTML
            <div class="form-group"><div class="form-field"><input type="radio" id="i0" class="radio theme-success" name="test" value="a">
            <label for="i0">A</label></div></div>
            HTML,
        ];
        yield 'unselect' => [
            'test',
            null,
            ['a' => 'A'],
            ['unselect' => ''],
            <<<HTML
            <input type="hidden" name="test" value=""><div class="form-group"><div class="form-field"><input type="radio" id="i0" class="radio" name="test" value="a">
            <label for="i0">A</label></div></div>
            HTML,
        ];
    }
}
