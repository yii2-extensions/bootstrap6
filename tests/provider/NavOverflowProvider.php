<?php

declare(strict_types=1);

namespace yii2\extensions\bootstrap6\tests\provider;

use yii2\extensions\bootstrap6\exception\Message;

/**
 * Data provider for {@see \yii2\extensions\bootstrap6\tests\NavOverflowTest} test cases.
 *
 * Provides invalid `string` values of the enum-typed properties with the expected exception messages.
 */
final class NavOverflowProvider
{
    /**
     * @return iterable<string, array{array<string, mixed>, string}>
     */
    public static function invalidEnumValueCases(): iterable
    {
        yield 'collapseBelow' => [
            ['collapseBelow' => 'xxl'],
            Message::INVALID_VALUE->getMessage('xxl', 'collapseBelow', 'lg, md, sm, xl, 2xl'),
        ];
        yield 'placement' => [
            ['placement' => 'middle'],
            Message::INVALID_VALUE->getMessage(
                'middle',
                'placement',
                'bottom, bottom-end, bottom-start, end, end-end, end-start, left, left-end, left-start, right, '
                . 'right-end, right-start, start, start-end, start-start, top, top-end, top-start',
            ),
        ];
    }
}
