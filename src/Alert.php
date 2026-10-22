<?php

declare(strict_types=1);

namespace yii2\extensions\bootstrap6;

use Stringable;
use Yii;
use yii\base\InvalidConfigException;
use yii\helpers\ArrayHelper;
use yii2\extensions\bootstrap6\enum\Theme;

use function is_string;
use function ob_get_clean;
use function ob_start;

/**
 * Renders a Bootstrap alert with an optional heading and a dismiss button handled by the `Alert` plugin.
 *
 * Content captured between {@see begin()} and {@see end()} is rendered before {@see $body}.
 *
 * @see https://getbootstrap.com/docs/6.0/components/alert/
 */
class Alert extends Widget
{
    /**
     * Alert content; it is not HTML-encoded.
     */
    public string|Stringable $body = '';
    /**
     * HTML attributes of the dismiss button, or `false` to omit it.
     *
     * The `tag` option sets the tag name (default `button`) and the `label` option sets the button content (default
     * empty). The defaults `class`, `data-bs-dismiss`, and `aria-label` are replaced by the given keys.
     *
     * @var array<string, mixed>|false
     */
    public array|false $closeButton = [];
    /**
     * Whether to HTML-encode {@see $heading}.
     */
    public bool $encodeHeading = true;
    /**
     * Heading rendered above the body in an `.alert-heading` element, or `null` to omit it.
     */
    public string|Stringable|null $heading = null;
    /**
     * HTML attributes of the heading tag; the `tag` option sets the tag name (default `h4`).
     *
     * @var array<string, mixed>
     */
    public array $headingOptions = ['class' => ['alert-heading', 'mb-2']];
    /**
     * Whether to remove the alert without the fade transition (`alert-instant`).
     */
    public bool $instant = false;
    /**
     * Theme color rendered as the `theme-{value}` class, or `null` for the neutral color.
     */
    public Theme|string|null $theme = null;

    /**
     * Initializes the widget, validates {@see $theme}, and starts capturing the content.
     *
     * @throws InvalidConfigException if {@see $theme} is not a {@see Theme} value.
     */
    public function init(): void
    {
        parent::init();

        Html::enumValue(Theme::class, $this->theme, 'theme');

        ob_start();
    }

    /**
     * Returns the alert markup with the captured content followed by {@see $body}, and registers the `Alert`
     * plugin.
     *
     * @throws InvalidConfigException if {@see $theme} is not a {@see Theme} value.
     *
     * @return string Rendered Alert markup.
     */
    public function run(): string
    {
        $content = ob_get_clean() . $this->body;

        $options = $this->options;

        Html::addCssClass($options, 'alert');
        Html::theme($options, $this->theme);

        if ($this->instant) {
            Html::addCssClass($options, 'alert-instant');
        }

        $options['role'] ??= 'alert';

        $this->registerPlugin('Alert');

        return Html::beginTag('div', $options) . "\n"
            . $this->renderBody($content) . "\n"
            . $this->renderCloseButton()
            . Html::endTag('div');
    }

    /**
     * Renders the alert content, wrapped with the heading in a `.vstack` element when {@see $heading} is set.
     *
     * @param string $content Captured content followed by the configured body, rendered without encoding.
     *
     * @return string Alert content with the optional heading wrapper.
     */
    protected function renderBody(string $content): string
    {
        if ($this->heading === null) {
            return $content;
        }

        $headingOptions = $this->headingOptions;
        $tag = ArrayHelper::remove($headingOptions, 'tag', 'h4');
        $heading = Html::tag(
            is_string($tag) ? $tag : 'h4',
            $this->encodeHeading ? Html::encode($this->heading) : $this->heading,
            $headingOptions,
        );

        return Html::tag(
            'div',
            "\n{$heading}\n{$content}\n",
            ['class' => 'vstack'],
        );
    }

    /**
     * Renders the dismiss button followed by a line break, or an empty `string` when {@see $closeButton} is `false`.
     *
     * @return string Dismiss button markup followed by a line break, or an empty `string` when disabled.
     */
    protected function renderCloseButton(): string
    {
        if ($this->closeButton === false) {
            return '';
        }

        $options = [
            'class' => 'btn-close ms-auto',
            'data-bs-dismiss' => 'alert',
            'aria-label' => Yii::t(Bootstrap::TRANSLATION_CATEGORY, 'Close'),
            ...$this->closeButton,
        ];

        $tag = ArrayHelper::remove($options, 'tag', 'button');
        $label = ArrayHelper::remove($options, 'label', '');

        if ($tag === 'button') {
            $options['type'] ??= 'button';
        }

        return Html::tag(
            is_string($tag) ? $tag : 'button',
            is_string($label) ? $label : '',
            $options,
        ) . "\n";
    }
}
