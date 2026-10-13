<?php

declare(strict_types=1);

namespace yii2\extensions\bootstrap6\tests\provider;

use yii2\extensions\bootstrap6\exception\Message;

/**
 * Data provider for {@see \yii2\extensions\bootstrap6\tests\ToggleButtonGroupTest} test cases.
 *
 * Provides invalid `string` values of the enum-typed properties with the expected exception messages.
 */
final class ToggleButtonGroupProvider
{
    /**
     * @return iterable<string, array{array<string, mixed>, string}>
     */
    public static function invalidEnumValueCases(): iterable
    {
        $themes = 'accent, danger, info, inverse, primary, secondary, success, warning';
        $variants = 'btn-icon, btn-link, btn-outline, btn-solid, btn-styled, btn-subtle, btn-text';

        yield 'size' => [
            ['size' => 'md'],
            Message::INVALID_VALUE->getMessage('md', 'size', 'lg, sm, xl, xs'),
        ];
        yield 'theme' => [
            ['theme' => 'light'],
            Message::INVALID_VALUE->getMessage('light', 'theme', $themes),
        ];
        yield 'variant' => [
            ['variant' => 'btn-primary'],
            Message::INVALID_VALUE->getMessage('btn-primary', 'variant', $variants),
        ];
    }
}
