<?php

declare(strict_types=1);

namespace yii2\extensions\bootstrap6;

use Stringable;
use UIAwesome\Html\Svg\{Path, Svg};
use UIAwesome\Html\Svg\Values\{StrokeLineCap, StrokeLineJoin};
use yii\base\BaseObject;

/**
 * Provides the default SVG icons rendered by the widgets and lets applications replace them through the DI container.
 *
 * Widgets resolve the set with `Yii::createObject(IconSet::class)`, so a `container.definitions` entry for this class
 * replaces any icon globally. Every property holds its bundled default until the configuration overrides it.
 *
 * @see https://developer.mozilla.org/en-US/docs/Web/SVG/Element/svg
 */
final class IconSet extends BaseObject
{
    /**
     * Chevron rendered after each {@see Accordion} item label; receives the `accordion-icon` class.
     */
    public string|Stringable $accordionChevron;
    /**
     * Caret rendered inside the {@see Combobox} toggle; receives the `combobox-caret` class.
     */
    public string|Stringable $comboboxCaret;
    /**
     * Check mark rendered inside each {@see Combobox} item; receives the `menu-item-check` class.
     */
    public string|Stringable $menuItemCheck;

    /**
     * Builds the bundled defaults before the configuration replaces any of them.
     *
     * @param array<string, mixed> $config Name-value pairs that override the default icons.
     */
    public function __construct(array $config = [])
    {
        $this->accordionChevron = Svg::tag()
            ->xmlns('http://www.w3.org/2000/svg')
            ->viewBox('0 0 16 16')
            ->fill('none')
            ->stroke('currentColor')
            ->strokeLineCap(StrokeLineCap::ROUND)
            ->strokeLineJoin(StrokeLineJoin::ROUND)
            ->html(Path::tag()->d('m2 5 6 6 6-6'));
        $this->comboboxCaret = Svg::tag()
            ->width(10)
            ->height(16)
            ->viewBox('0 0 10 16')
            ->fill('none')
            ->xmlns('http://www.w3.org/2000/svg')
            ->html(
                Path::tag()
                    ->d(
                        'M0.46967 5.46967C0.762563 5.17678 1.23744 5.17678 1.53033 5.46967L5 8.93934L8.46967 '
                        . '5.46967C8.76256 5.17678 9.23744 5.17678 9.53033 5.46967C9.82322 5.76256 9.82322 6.23744 '
                        . '9.53033 6.53033L5.53033 10.5303C5.23744 10.8232 4.76256 10.8232 4.46967 10.5303L0.46967 '
                        . '6.53033C0.176777 6.23744 0.176777 5.76256 0.46967 5.46967Z',
                    )
                    ->fill('currentcolor'),
            );
        $this->menuItemCheck = Svg::tag()
            ->xmlns('http://www.w3.org/2000/svg')
            ->width(16)
            ->height(16)
            ->fill('none')
            ->stroke('currentColor')
            ->strokeLineCap(StrokeLineCap::ROUND)
            ->strokeLineJoin(StrokeLineJoin::ROUND)
            ->strokeWidth(2)
            ->viewBox('0 0 16 16')
            ->html(Path::tag()->d('m2 7 4 5 8-8'));

        parent::__construct($config);
    }

    /**
     * Returns the markup of an icon with its structural CSS class.
     *
     * A {@see Svg} instance receives `$class` appended to its own classes (html-svg skips a class it already carries).
     * Any other value is cast to `string` verbatim, so plain markup and other `Stringable` objects must already carry
     * the class.
     *
     * @param string|Stringable $icon Icon to render.
     * @param string $class Structural CSS class the Bootstrap stylesheet targets, for example `accordion-icon`.
     *
     * @return string Icon markup with the requested class applied to SVG instances.
     */
    public static function render(string|Stringable $icon, string $class): string
    {
        if ($icon instanceof Svg) {
            return $icon->class($class)->render();
        }

        return (string) $icon;
    }
}
