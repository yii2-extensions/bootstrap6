<?php

declare(strict_types=1);

namespace yii2\extensions\bootstrap6\tests\provider;

use yii\base\Widget;
use yii2\extensions\bootstrap6\{Chips, Combobox, Datepicker, NavOverflow, OtpInput, Range, Strength};

/**
 * Data provider for {@see \yii2\extensions\bootstrap6\tests\FragmentInitializationTest} test cases.
 */
final class FragmentInitializationProvider
{
    /**
     * @return iterable<string, array{
     *     class-string<Widget>,
     *     array{name?: string, items?: array<string, string>, selectedDates?: list<string>, inline?: bool},
     *     non-empty-string,
     * }>
     */
    public static function explicitOptOutCases(): iterable
    {
        yield 'Chips' => [
            Chips::class,
            ['name' => 'tags'],
            'w0-chips',
        ];
        yield 'Combobox' => [
            Combobox::class,
            ['name' => 'choice', 'items' => ['a' => 'Alpha']],
            'w0',
        ];
        yield 'Datepicker popup' => [
            Datepicker::class,
            ['name' => 'date'],
            'w0',
        ];
        yield 'Datepicker popup with preselection' => [
            Datepicker::class,
            ['name' => 'date', 'selectedDates' => ['2026-03-01']],
            'w0',
        ];
        yield 'Datepicker inline' => [
            Datepicker::class,
            ['name' => 'date', 'inline' => true],
            'w0-datepicker',
        ];
        yield 'NavOverflow' => [
            NavOverflow::class,
            [],
            'w0',
        ];
        yield 'OtpInput' => [
            OtpInput::class,
            ['name' => 'code'],
            'w0-otp',
        ];
        yield 'Range' => [
            Range::class,
            ['name' => 'amount'],
            'w0-range',
        ];
        yield 'Strength' => [
            Strength::class,
            [],
            'w0',
        ];
    }
}
