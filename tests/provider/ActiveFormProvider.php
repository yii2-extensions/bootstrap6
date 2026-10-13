<?php

declare(strict_types=1);

namespace yii2\extensions\bootstrap6\tests\provider;

use yii2\extensions\bootstrap6\exception\Message;

/**
 * Data provider for {@see \yii2\extensions\bootstrap6\tests\ActiveFormTest} test cases.
 */
final class ActiveFormProvider
{
    /**
     * @return iterable<string, array{array<string, mixed>, string}>
     */
    public static function invalidConfigCases(): iterable
    {
        yield 'layout' => [
            ['layout' => 'vertical'],
            Message::INVALID_VALUE->getMessage('vertical', 'layout', 'default, horizontal, inline, floating'),
        ];
        yield 'native validation' => [
            ['nativeValidation' => 'invalid'],
            Message::INVALID_VALUE->getMessage('invalid', 'nativeValidation', 'true, false, valid'),
        ];
    }

    /**
     * @return iterable<string, array{bool|string|null, string}>
     */
    public static function nativeValidationCases(): iterable
    {
        yield 'null' => [null, '<form id="w0" action="/some-action" method="post"></form>'];
        yield 'false' => [false, '<form id="w0" action="/some-action" method="post"></form>'];
        yield 'true' => [true, '<form id="w0" action="/some-action" method="post" data-bs-validate></form>'];
        yield 'valid' => ['valid', '<form id="w0" action="/some-action" method="post" data-bs-validate="valid"></form>'];
    }
}
