<?php

declare(strict_types=1);

namespace yii2\extensions\bootstrap6\tests\provider;

/**
 * Data provider for {@see \yii2\extensions\bootstrap6\tests\OtpInputTest} test cases.
 *
 * Provides every allowed value of the `type` option.
 */
final class OtpInputProvider
{
    /**
     * @return iterable<string, array{string}>
     */
    public static function typeCases(): iterable
    {
        yield 'numeric' => ['numeric'];
        yield 'alphanumeric' => ['alphanumeric'];
        yield 'alpha' => ['alpha'];
    }
}
