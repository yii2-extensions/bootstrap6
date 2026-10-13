<?php

declare(strict_types=1);

namespace yii2\extensions\bootstrap6\tests\provider;

use yii2\extensions\bootstrap6\exception\Message;

/**
 * Data provider for {@see \yii2\extensions\bootstrap6\tests\DialogTest} test cases.
 *
 * Provides invalid `string` values of the enum-typed properties with the expected exception messages.
 */
final class DialogProvider
{
    /**
     * @return iterable<string, array{array<string, mixed>, string}>
     */
    public static function invalidEnumValueCases(): iterable
    {
        yield 'fullscreenBelow' => [
            ['fullscreenBelow' => 'xxl'],
            Message::INVALID_VALUE->getMessage('xxl', 'fullscreenBelow', 'lg, md, sm, xl, 2xl'),
        ];
        yield 'size' => [
            ['size' => 'dialog-md'],
            Message::INVALID_VALUE->getMessage(
                'dialog-md',
                'size',
                'dialog-fullscreen, dialog-lg, dialog-sm, dialog-xl',
            ),
        ];
    }
}
