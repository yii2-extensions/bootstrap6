<?php

declare(strict_types=1);

namespace yii2\extensions\bootstrap6\tests\provider;

use yii2\extensions\bootstrap6\enum\Size;
use yii2\extensions\bootstrap6\exception\Message;

/**
 * Data provider for {@see \yii2\extensions\bootstrap6\tests\AvatarTest} test cases.
 */
final class AvatarProvider
{
    /**
     * @return iterable<string, array{array<string, mixed>, string}>
     */
    public static function invalidEnumValueCases(): iterable
    {
        $themes = 'accent, danger, info, inverse, primary, secondary, success, warning';

        yield 'size' => [
            ['size' => 'md'],
            Message::INVALID_VALUE->getMessage('md', 'size', 'lg, sm, xl, xs'),
        ];
        yield 'theme' => [
            ['theme' => 'light'],
            Message::INVALID_VALUE->getMessage('light', 'theme', $themes),
        ];
    }
    /**
     * @return iterable<string, array{Size|string, string}>
     */
    public static function sizeCases(): iterable
    {
        yield 'XS enum' => [Size::XS, 'avatar-xs'];
        yield 'SM enum' => [Size::SM, 'avatar-sm'];
        yield 'LG string' => ['lg', 'avatar-lg'];
        yield 'XL string' => ['xl', 'avatar-xl'];
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function statusCases(): iterable
    {
        yield 'online' => ['online', 'Online'];
        yield 'offline' => ['offline', 'Offline'];
        yield 'busy' => ['busy', 'Busy'];
        yield 'away' => ['away', 'Away'];
    }
}
