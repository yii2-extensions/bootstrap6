<?php

declare(strict_types=1);

namespace yii2\extensions\bootstrap6\tests\provider;

use yii2\extensions\bootstrap6\enum\Theme;

/**
 * Data provider for {@see \yii2\extensions\bootstrap6\tests\BadgeTest} test cases.
 *
 * Provides representative configuration/markup pairs for the badge variants, sizes, and labels.
 */
final class BadgeProvider
{
    /**
     * @return iterable<string, array{array<string, mixed>, string}>
     */
    public static function renderCases(): iterable
    {
        yield 'default' => [
            ['label' => 'New'],
            '<span id="w0" class="badge">New</span>',
        ];
        yield 'theme enum' => [
            ['label' => 'New', 'theme' => Theme::PRIMARY],
            '<span id="w0" class="badge theme-primary">New</span>',
        ];
        yield 'theme string' => [
            ['label' => 'New', 'theme' => 'accent'],
            '<span id="w0" class="badge theme-accent">New</span>',
        ];
        yield 'subtle' => [
            ['label' => 'New', 'subtle' => true, 'theme' => Theme::PRIMARY],
            '<span id="w0" class="badge badge-subtle theme-primary">New</span>',
        ];
        yield 'outline' => [
            ['label' => 'New', 'outline' => true, 'theme' => Theme::PRIMARY],
            '<span id="w0" class="badge badge-outline theme-primary">New</span>',
        ];
        yield 'large' => [
            ['label' => 'New', 'large' => true],
            '<span id="w0" class="badge badge-lg">New</span>',
        ];
        yield 'pill' => [
            ['label' => '99+', 'pill' => true],
            '<span id="w0" class="badge rounded-pill">99+</span>',
        ];
        yield 'all modifiers' => [
            ['label' => 'New', 'large' => true, 'outline' => true, 'pill' => true, 'subtle' => true],
            '<span id="w0" class="badge badge-subtle badge-outline badge-lg rounded-pill">New</span>',
        ];
        yield 'encoded label' => [
            ['label' => '<b>4</b>'],
            '<span id="w0" class="badge">&lt;b&gt;4&lt;/b&gt;</span>',
        ];
        yield 'raw label' => [
            ['encodeLabel' => false, 'label' => '<b>4</b>'],
            '<span id="w0" class="badge"><b>4</b></span>',
        ];
        yield 'tag and options' => [
            ['label' => 'New', 'options' => ['class' => 'ms-2', 'id' => 'b'], 'tagName' => 'a'],
            '<a id="b" class="ms-2 badge">New</a>',
        ];
    }
}
