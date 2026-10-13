<?php

declare(strict_types=1);

namespace yii2\extensions\bootstrap6\tests\provider;

use BackedEnum;
use Stringable;
use yii2\extensions\bootstrap6\enum\{Placement, Theme};
use yii2\extensions\bootstrap6\exception\Message;
use yii2\extensions\bootstrap6\tests\support\stub\StringableValue;

/**
 * Data provider for {@see \yii2\extensions\bootstrap6\tests\HtmlTest} test cases.
 */
final class HtmlProvider
{
    /**
     * @return iterable<string, array{string|Stringable, string|Theme|null, array<string, mixed>, string}>
     */
    public static function badgeCases(): iterable
    {
        yield 'default' => [
            'New',
            null,
            [],
            '<span class="badge">New</span>',
        ];
        yield 'theme enum' => [
            'New',
            Theme::PRIMARY,
            [],
            '<span class="badge theme-primary">New</span>',
        ];
        yield 'theme string' => [
            'New',
            'accent',
            [],
            '<span class="badge theme-accent">New</span>',
        ];
        yield 'encoded label' => [
            '<b>4</b>',
            null,
            [],
            '<span class="badge">&lt;b&gt;4&lt;/b&gt;</span>',
        ];
        yield 'raw label' => [
            '<b>4</b>',
            null,
            ['encode' => false],
            '<span class="badge"><b>4</b></span>',
        ];
        yield 'Stringable label' => [
            new StringableValue('<b>4</b>'),
            null,
            [],
            '<span class="badge">&lt;b&gt;4&lt;/b&gt;</span>',
        ];
        yield 'raw Stringable label' => [
            new StringableValue('<b>4</b>'),
            null,
            ['encode' => false],
            '<span class="badge"><b>4</b></span>',
        ];
        yield 'options' => [
            'New',
            Theme::SUCCESS,
            ['class' => ['badge', 'badge-subtle'], 'id' => 'b'],
            '<span id="b" class="badge badge-subtle theme-success">New</span>',
        ];
    }

    /**
     * @return iterable<string, array{class-string<BackedEnum>, BackedEnum|string|null, bool, string|null}>
     */
    public static function enumValueCases(): iterable
    {
        yield 'enum case' => [
            Theme::class,
            Theme::PRIMARY,
            false,
            'primary',
        ];
        yield 'string value' => [
            Theme::class,
            'accent',
            false,
            'accent',
        ];
        yield 'null' => [
            Theme::class,
            null,
            false,
            null,
        ];
        yield 'responsive enum case' => [
            Placement::class,
            Placement::LEFT,
            true,
            'left',
        ];
        yield 'responsive single value' => [
            Placement::class,
            'top',
            true,
            'top',
        ];
        yield 'responsive prefixed value' => [
            Placement::class,
            'lg:right',
            true,
            'lg:right',
        ];
        yield 'responsive value list' => [
            Placement::class,
            'bottom-start md:top-end 2xl:end',
            true,
            'bottom-start md:top-end 2xl:end',
        ];
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function escapeCssIdentifierCases(): iterable
    {
        yield 'empty' => [
            '',
            '',
        ];
        yield 'widget id' => [
            'w0',
            'w0',
        ];
        yield 'inner hyphen' => [
            'login-form',
            'login-form',
        ];
        yield 'colon' => [
            'account:edit',
            'account\\:edit',
        ];
        yield 'dot and hash' => [
            'a.b#c',
            'a\\.b\\#c',
        ];
        yield 'leading digit' => [
            '0abc',
            '\\30 abc',
        ];
        yield 'digit after leading hyphen' => [
            '-0abc',
            '-\\30 abc',
        ];
        yield 'hyphen alone' => [
            '-',
            '\\-',
        ];
        yield 'double hyphen' => [
            '--a',
            '--a',
        ];
        yield 'leading underscore' => [
            '_x',
            '_x',
        ];
        yield 'space' => [
            'a b',
            'a\\ b',
        ];
        yield 'double quotes' => [
            '"quoted"',
            '\\"quoted\\"',
        ];
        yield 'null character' => [
            "a\0b",
            "a\u{FFFD}b",
        ];
        yield 'control characters' => [
            "a\x01b\x7Fc",
            'a\\1 b\\7f c',
        ];
        yield 'two-byte character' => [
            "nombre-\u{F1}",
            "nombre-\u{F1}",
        ];
        yield 'non-ASCII only' => [
            "\u{E9}",
            "\u{E9}",
        ];
        yield 'brackets' => [
            '()[]{}',
            '\\(\\)\\[\\]\\{\\}',
        ];
        yield 'digit alone' => [
            '9',
            '\\39 ',
        ];
        yield 'hyphen and digit' => [
            '-9',
            '-\\39 ',
        ];
        yield 'digit after inner hyphen' => [
            '1-0',
            '\\31 -0',
        ];
        yield 'trailing digit' => [
            'a-0',
            'a-0',
        ];
        yield 'line feed' => [
            "\n",
            '\\a ',
        ];
        yield 'hyphen and letter' => [
            '-a',
            '-a',
        ];
        yield 'slash' => [
            'a/b',
            'a\\/b',
        ];
        yield 'backslash' => [
            'a\\b',
            'a\\\\b',
        ];
        yield 'four-byte character' => [
            "\u{1F600}x",
            "\u{1F600}x",
        ];
        yield 'style end tag' => [
            '</style>',
            '\\<\\/style\\>',
        ];
        yield 'error class' => [
            'has:error',
            'has\\:error',
        ];
        yield 'last control character' => [
            "\x1f",
            '\\1f ',
        ];
        yield 'digit after non-ASCII' => [
            "\u{F1}0",
            "\u{F1}0",
        ];
        yield 'nested tab id' => [
            'w0-dd0-tab0',
            'w0-dd0-tab0',
        ];
        yield 'ASCII letter bounds' => [
            'AZaz',
            'AZaz',
        ];
        yield 'ASCII letter neighbours' => [
            '@`',
            '\\@\\`',
        ];
    }

    /**
     * @return iterable<string, array{class-string<BackedEnum>, string, bool, string}>
     */
    public static function invalidEnumValueCases(): iterable
    {
        $themes = 'accent, danger, info, inverse, primary, secondary, success, warning';
        $placements = 'bottom, bottom-end, bottom-start, end, end-end, end-start, left, left-end, left-start, right, '
            . 'right-end, right-start, start, start-end, start-start, top, top-end, top-start';

        yield 'unknown value' => [
            Theme::class,
            'light',
            false,
            Message::INVALID_VALUE->getMessage('light', 'theme', $themes),
        ];
        yield 'empty value' => [
            Theme::class,
            '',
            false,
            Message::INVALID_VALUE->getMessage('', 'theme', $themes),
        ];
        yield 'prefixed value without responsive' => [
            Placement::class,
            'md:top',
            false,
            Message::INVALID_VALUE->getMessage('md:top', 'placement', $placements),
        ];
        yield 'value list without responsive' => [
            Placement::class,
            'top md:right',
            false,
            Message::INVALID_VALUE->getMessage('top md:right', 'placement', $placements),
        ];
        yield 'unknown breakpoint' => [
            Placement::class,
            'top xs:right',
            true,
            Message::INVALID_VALUE->getMessage('top xs:right', 'placement', $placements),
        ];
        yield 'unknown prefixed value' => [
            Placement::class,
            'top md:middle',
            true,
            Message::INVALID_VALUE->getMessage('top md:middle', 'placement', $placements),
        ];
        yield 'unknown base value' => [
            Placement::class,
            'middle md:top',
            true,
            Message::INVALID_VALUE->getMessage('middle md:top', 'placement', $placements),
        ];
        yield 'extra separator' => [
            Placement::class,
            'md:top:end',
            true,
            Message::INVALID_VALUE->getMessage('md:top:end', 'placement', $placements),
        ];
        yield 'double space' => [
            Placement::class,
            'top  md:right',
            true,
            Message::INVALID_VALUE->getMessage('top  md:right', 'placement', $placements),
        ];
    }

    /**
     * @return iterable<string, array{string, array<string, mixed>, string}>
     */
    public static function staticControlCases(): iterable
    {
        yield 'text' => [
            'foo',
            [],
            '<input type="text" class="form-control-plaintext" value="foo" readonly>',
        ];
        yield 'encoded' => [
            '<html>',
            [],
            '<input type="text" class="form-control-plaintext" value="&lt;html&gt;" readonly>',
        ];
        yield 'options' => [
            'foo',
            ['class' => 'fs-5', 'id' => 'email', 'readonly' => false],
            '<input type="text" id="email" class="form-control-plaintext fs-5" value="foo" readonly>',
        ];
    }

    /**
     * @return iterable<string, array{string|Theme|null, array<string, mixed>, array<string, mixed>}>
     */
    public static function themeCases(): iterable
    {
        yield 'null' => [null, ['id' => 'x'], ['id' => 'x']];
        yield 'enum' => [Theme::DANGER, [], ['class' => 'theme-danger']];
        yield 'string' => ['inverse', ['class' => 'alert'], ['class' => 'alert theme-inverse']];
    }
}
