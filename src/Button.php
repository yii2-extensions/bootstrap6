<?php

declare(strict_types=1);

namespace yii2\extensions\bootstrap6;

use Stringable;
use Yii;
use yii\base\InvalidConfigException;
use yii2\extensions\bootstrap6\asset\BootstrapAsset;
use yii2\extensions\bootstrap6\enum\{ButtonVariant, Size, Theme};

/**
 * Renders a Bootstrap button, optionally as a toggle button handled by the `Button` plugin.
 *
 * The plugin asset is registered only for toggle buttons, configured {@see $clientOptions}, or {@see $clientEvents}.
 *
 * @see https://getbootstrap.com/docs/6.0/components/button/
 */
class Button extends Widget
{
    /**
     * Options passed to the `Button` plugin, or `false` to skip the JavaScript initialization.
     *
     * An empty array creates the plugin instance on page load.
     *
     * @var array<string, mixed>|false
     */
    public array|false $clientOptions = false;
    /**
     * Whether to HTML-encode {@see $label}.
     */
    public bool $encodeLabel = true;
    /**
     * Button content, or `null` for the translated `Button`.
     */
    public string|Stringable|null $label = null;
    /**
     * Size modifier rendered as the `btn-{value}` class (`xs`, `sm`, or `lg`), or `null` for the default size.
     */
    public Size|string|null $size = null;
    /**
     * Tag name of the button element; `a` tags receive `role="button"`.
     */
    public string $tagName = 'button';
    /**
     * Theme color rendered as the `theme-{value}` class, or `null` for the neutral color.
     */
    public Theme|string|null $theme = null;
    /**
     * Whether to render a toggle button (`data-bs-toggle="button"` and `aria-pressed="false"` unless configured).
     */
    public bool $toggle = false;
    /**
     * Button style class, for example {@see ButtonVariant::OUTLINE}, or `null` for the base `btn` class.
     */
    public ButtonVariant|string|null $variant = ButtonVariant::SOLID;

    /**
     * Initializes the widget and validates {@see $size}, {@see $theme}, and {@see $variant}.
     *
     * @throws InvalidConfigException if {@see $size}, {@see $theme}, or {@see $variant} is not a value of its enum.
     */
    public function init(): void
    {
        parent::init();

        Html::enumValue(Size::class, $this->size, 'size');
        Html::enumValue(Theme::class, $this->theme, 'theme');
        Html::enumValue(ButtonVariant::class, $this->variant, 'variant');
    }

    /**
     * Returns the button markup, registering the `Button` plugin only when it is needed.
     *
     * @throws InvalidConfigException if {@see $size}, {@see $theme}, or {@see $variant} is not a value of its enum.
     *
     * @return string Rendered Button markup.
     */
    public function run(): string
    {
        $options = $this->options;
        $size = Html::enumValue(Size::class, $this->size, 'size');

        Html::addCssClass($options, Html::enumValue(ButtonVariant::class, $this->variant, 'variant') ?? 'btn');
        Html::theme($options, $this->theme);

        if ($size !== null) {
            Html::addCssClass($options, "btn-{$size}");
        }

        if ($this->tagName === 'button') {
            $options['type'] ??= 'button';
        }

        if ($this->tagName === 'a') {
            $options['role'] ??= 'button';
        }

        if ($this->toggle) {
            $options['data-bs-toggle'] = 'button';
            $options['aria-pressed'] ??= 'false';
        }

        if ($this->toggle || $this->clientOptions !== false || $this->clientEvents !== []) {
            $this->registerPlugin('Button', true);
        } else {
            BootstrapAsset::register($this->getView());
        }

        $label = $this->label ?? Yii::t(Bootstrap::TRANSLATION_CATEGORY, 'Button');

        return Html::tag(
            $this->tagName,
            $this->encodeLabel ? Html::encode($label) : $label,
            $options,
        );
    }
}
