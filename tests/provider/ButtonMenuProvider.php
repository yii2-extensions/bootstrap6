<?php

declare(strict_types=1);

namespace yii2\extensions\bootstrap6\tests\provider;

use yii2\extensions\bootstrap6\exception\Message;

/**
 * Data provider for {@see \yii2\extensions\bootstrap6\tests\ButtonMenuTest} test cases.
 *
 * Provides invalid `string` values of the enum-typed properties with the expected exception messages.
 */
final class ButtonMenuProvider
{
    /**
     * @return iterable<string, array{array<string, mixed>, string}>
     */
    public static function invalidEnumValueCases(): iterable
    {
        $placements = 'bottom, bottom-end, bottom-start, end, end-end, end-start, left, left-end, left-start, right, '
            . 'right-end, right-start, start, start-end, start-start, top, top-end, top-start';

        yield 'placement' => [
            ['placement' => 'middle'],
            Message::INVALID_VALUE->getMessage('middle', 'placement', $placements),
        ];
        yield 'size' => [
            ['size' => 'md'],
            Message::INVALID_VALUE->getMessage('md', 'size', 'lg, sm, xl, xs'),
        ];
        yield 'theme' => [
            ['theme' => 'light'],
            Message::INVALID_VALUE->getMessage(
                'light',
                'theme',
                'accent, danger, info, inverse, primary, secondary, success, warning',
            ),
        ];
        yield 'variant' => [
            ['variant' => 'btn-primary'],
            Message::INVALID_VALUE->getMessage(
                'btn-primary',
                'variant',
                'btn-icon, btn-link, btn-outline, btn-solid, btn-styled, btn-subtle, btn-text',
            ),
        ];
    }
}
