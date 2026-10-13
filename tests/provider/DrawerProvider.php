<?php

declare(strict_types=1);

namespace yii2\extensions\bootstrap6\tests\provider;

use yii2\extensions\bootstrap6\exception\Message;

/**
 * Data provider for {@see \yii2\extensions\bootstrap6\tests\DrawerTest} test cases.
 *
 * Provides invalid `string` values of the enum-typed properties with the expected exception messages.
 */
final class DrawerProvider
{
    /**
     * @return iterable<string, array{array<string, mixed>, string}>
     */
    public static function invalidEnumValueCases(): iterable
    {
        yield 'placement' => [
            ['placement' => 'start'],
            Message::INVALID_VALUE->getMessage(
                'start',
                'placement',
                'drawer-bottom, drawer-end, drawer-start, drawer-top',
            ),
        ];
        yield 'responsive' => [
            ['responsive' => 'xxl'],
            Message::INVALID_VALUE->getMessage('xxl', 'responsive', 'lg, md, sm, xl, 2xl'),
        ];
    }
}
