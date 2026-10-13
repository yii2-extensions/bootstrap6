<?php

declare(strict_types=1);

namespace yii2\extensions\bootstrap6\tests\provider;

use yii2\extensions\bootstrap6\exception\Message;

/**
 * Data provider for {@see \yii2\extensions\bootstrap6\tests\DatepickerTest} test cases.
 *
 * Provides the allowed and rejected values of the closed `selectionMode`, `placement` and `firstWeekday` options.
 */
final class DatepickerProvider
{
    /**
     * @return iterable<string, array{string, int|string, string}>
     */
    public static function invalidOptionCases(): iterable
    {
        $modes = 'single, multiple, multiple-ranged';
        $placements = 'left, center, right, auto';
        $weekdays = '0, 1, 2, 3, 4, 5, 6';

        yield 'selectionMode' => [
            'selectionMode',
            'range',
            Message::INVALID_VALUE->getMessage('range', 'selectionMode', $modes),
        ];
        yield 'placement' => [
            'placement', 'top',
            Message::INVALID_VALUE->getMessage('top', 'placement', $placements),
        ];
        yield 'firstWeekday below range' => [
            'firstWeekday',
            -1,
            Message::INVALID_VALUE->getMessage(-1, 'firstWeekday', $weekdays),
        ];
        yield 'firstWeekday above range' => [
            'firstWeekday',
            7,
            Message::INVALID_VALUE->getMessage(7, 'firstWeekday', $weekdays),
        ];
    }

    /**
     * @return iterable<string, array{string, int|string, string}>
     */
    public static function validOptionCases(): iterable
    {
        yield 'selectionMode single' => ['selectionMode', 'single', 'data-bs-selection-mode="single"'];
        yield 'selectionMode multiple' => ['selectionMode', 'multiple', 'data-bs-selection-mode="multiple"'];
        yield 'placement left' => ['placement', 'left', 'data-bs-placement="left"'];
        yield 'placement right' => ['placement', 'right', 'data-bs-placement="right"'];
        yield 'placement auto' => ['placement', 'auto', 'data-bs-placement="auto"'];
        yield 'firstWeekday 6' => ['firstWeekday', 6, 'data-bs-first-weekday="6"'];
    }
}
