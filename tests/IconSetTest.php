<?php

declare(strict_types=1);

namespace yii2\extensions\bootstrap6\tests;

use PHPUnit\Framework\Attributes\Group;
use Stringable;
use UIAwesome\Html\Svg\{Path, Svg};
use yii2\extensions\bootstrap6\IconSet;
use yii2\extensions\bootstrap6\tests\support\TestCase;

/**
 * Unit tests for {@see IconSet} default icons, configured overrides, and structural class handling.
 */
#[Group('widget')]
final class IconSetTest extends TestCase
{
    public function testConfiguredIconsReplaceDefaults(): void
    {
        $icons = new IconSet(
            [
                'accordionChevron' => '<i>chevron</i>',
                'comboboxCaret' => '<i>caret</i>',
                'menuItemCheck' => '<i>check</i>',
            ],
        );

        self::assertSame(
            '<i>chevron</i>',
            $icons->accordionChevron,
            'Configured chevron must be kept.',
        );
        self::assertSame(
            '<i>caret</i>',
            $icons->comboboxCaret,
            'Configured caret must be kept.',
        );
        self::assertSame(
            '<i>check</i>',
            $icons->menuItemCheck,
            'Configured check mark must be kept.',
        );
    }

    public function testRenderAccordionChevronDefault(): void
    {
        $icon = (new IconSet())->accordionChevron;

        self::assertInstanceOf(
            Svg::class,
            $icon,
            'Default must be an html-svg element.',
        );
        self::assertSameHtml(
            <<<HTML
            <svg class="accordion-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
            <path d="m2 5 6 6 6-6">
            </svg>
            HTML,
            IconSet::render($icon, 'accordion-icon'),
            'Chevron attributes and path data must match the Bootstrap icon.',
        );
        self::assertStringNotContainsString('class=', (string) $icon, 'Default must not carry the structural class.');
    }

    public function testRenderAppendsClassToSvg(): void
    {
        $icon = Svg::tag()->class('my-icon')->html(Path::tag()->d('m0 0'));

        self::assertSameHtml(
            <<<HTML
            <svg class="my-icon accordion-icon">
            <path d="m0 0">
            </svg>
            HTML,
            IconSet::render($icon, 'accordion-icon'),
            'Structural class must follow the own classes.',
        );
        self::assertSameHtml(
            <<<HTML
            <svg class="accordion-icon">
            <path d="m0 0">
            </svg>
            HTML,
            IconSet::render(Svg::tag()->class('accordion-icon')->html(Path::tag()->d('m0 0')), 'accordion-icon'),
            'Structural class already present must not be duplicated.',
        );
    }

    public function testRenderCastsStringableVerbatim(): void
    {
        $icon = new class implements Stringable {
            public function __toString(): string
            {
                return '<i class="bi bi-chevron-down"></i>';
            }
        };

        self::assertSame(
            '<i class="bi bi-chevron-down"></i>',
            IconSet::render($icon, 'accordion-icon'),
            'Non-Svg `Stringable` must not receive the class.',
        );
    }

    public function testRenderComboboxCaretDefault(): void
    {
        $icon = (new IconSet())->comboboxCaret;

        self::assertInstanceOf(
            Svg::class,
            $icon,
            'Default must be an html-svg element.',
        );
        self::assertSameHtml(
            <<<HTML
            <svg class="combobox-caret" width="10" height="16" viewBox="0 0 10 16" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M0.46967 5.46967C0.762563 5.17678 1.23744 5.17678 1.53033 5.46967L5 8.93934L8.46967 5.46967C8.76256 5.17678 9.23744 5.17678 9.53033 5.46967C9.82322 5.76256 9.82322 6.23744 9.53033 6.53033L5.53033 10.5303C5.23744 10.8232 4.76256 10.8232 4.46967 10.5303L0.46967 6.53033C0.176777 6.23744 0.176777 5.76256 0.46967 5.46967Z" fill="currentcolor">
            </svg>
            HTML,
            IconSet::render($icon, 'combobox-caret'),
            'Caret attributes and path data must match the Bootstrap icon.',
        );
        self::assertStringNotContainsString(
            'class=',
            (string) $icon,
            'Default must not carry the structural class.',
        );
    }

    public function testRenderMenuItemCheckDefault(): void
    {
        $icon = (new IconSet())->menuItemCheck;

        self::assertInstanceOf(
            Svg::class,
            $icon,
            'Default must be an html-svg element.',
        );
        self::assertSameHtml(
            <<<HTML
            <svg class="menu-item-check" width="16" height="16" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewBox="0 0 16 16">
            <path d="m2 7 4 5 8-8">
            </svg>
            HTML,
            IconSet::render($icon, 'menu-item-check'),
            'Check mark attributes and path data must match the Bootstrap icon.',
        );
        self::assertStringNotContainsString(
            'class=',
            (string) $icon,
            'Default must not carry the structural class.',
        );
    }

    public function testRenderReturnsStringVerbatim(): void
    {
        self::assertSame(
            '<i class="bi bi-chevron-down"></i>',
            IconSet::render('<i class="bi bi-chevron-down"></i>', 'accordion-icon'),
            'Plain markup must not receive the class.',
        );
        self::assertSame(
            '',
            IconSet::render('', 'accordion-icon'),
            'Empty string must stay empty.',
        );
    }
}
