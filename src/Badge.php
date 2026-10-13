<?php

declare(strict_types=1);

namespace yii2\extensions\bootstrap6;

use Stringable;
use yii\base\InvalidConfigException;
use yii2\extensions\bootstrap6\asset\BootstrapAsset;
use yii2\extensions\bootstrap6\enum\Theme;

/**
 * Renders a Bootstrap badge for counts, labels, and status indicators.
 *
 * @see https://getbootstrap.com/docs/6.0/components/badge/
 */
class Badge extends Widget
{
    /**
     * Whether to HTML-encode {@see $label}.
     */
    public bool $encodeLabel = true;
    /**
     * Badge content.
     */
    public string|Stringable $label = '';
    /**
     * Whether to render the larger badge (`badge-lg`).
     */
    public bool $large = false;
    /**
     * Whether to render the outline variant (`badge-outline`).
     */
    public bool $outline = false;
    /**
     * Whether to render rounded pill corners (`rounded-pill`).
     */
    public bool $pill = false;
    /**
     * Whether to render the subtle variant (`badge-subtle`).
     */
    public bool $subtle = false;
    /**
     * Tag name of the badge element.
     */
    public string $tagName = 'span';
    /**
     * Theme color rendered as the `theme-{value}` class, or `null` for the neutral color.
     */
    public Theme|string|null $theme = null;

    /**
     * Initializes the widget and validates {@see $theme}.
     *
     * @throws InvalidConfigException if {@see $theme} is not a {@see Theme} value.
     */
    public function init(): void
    {
        parent::init();

        Html::enumValue(Theme::class, $this->theme, 'theme');
    }

    public function run(): string
    {
        BootstrapAsset::register($this->getView());

        $options = $this->options;
        $classes = ['badge'];

        if ($this->subtle) {
            $classes[] = 'badge-subtle';
        }

        if ($this->outline) {
            $classes[] = 'badge-outline';
        }

        if ($this->large) {
            $classes[] = 'badge-lg';
        }

        if ($this->pill) {
            $classes[] = 'rounded-pill';
        }

        Html::addCssClass($options, $classes);
        Html::theme($options, $this->theme);

        return Html::tag(
            $this->tagName,
            $this->encodeLabel ? Html::encode($this->label) : $this->label,
            $options,
        );
    }
}
