<?php

declare(strict_types=1);

namespace yii2\extensions\bootstrap6\tests\provider;

use yii2\extensions\bootstrap6\exception\Message;

/**
 * Data provider for {@see \yii2\extensions\bootstrap6\tests\NavTest} test cases.
 *
 * Provides invalid `string` values of the enum-typed properties with the expected exception messages.
 */
final class NavProvider
{
    /**
     * @return iterable<string, array{array<string, mixed>, string}>
     */
    public static function invalidEnumValueCases(): iterable
    {
        yield 'layout' => [
            ['layout' => 'nav-column'],
            Message::INVALID_VALUE->getMessage('nav-column', 'layout', 'nav-fill, nav-justified, flex-column'),
        ];
        yield 'style' => [
            ['style' => 'nav-links'],
            Message::INVALID_VALUE->getMessage(
                'nav-links',
                'style',
                'nav-pills, nav-pills-vertical, nav-tabs, nav-underline',
            ),
        ];
    }
}
