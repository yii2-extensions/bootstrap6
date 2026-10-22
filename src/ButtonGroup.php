<?php

declare(strict_types=1);

namespace yii2\extensions\bootstrap6;

use yii\base\InvalidConfigException;
use yii\helpers\ArrayHelper;
use yii2\extensions\bootstrap6\asset\BootstrapAsset;
use yii2\extensions\bootstrap6\enum\Size;

use function implode;
use function is_array;

/**
 * Renders a Bootstrap button group from HTML strings and {@see Button} configurations.
 *
 * @see https://getbootstrap.com/docs/6.0/components/button-group/
 */
class ButtonGroup extends Widget
{
    /**
     * Buttons of the group, each an HTML `string` or a {@see Button} configuration array.
     *
     * Configuration arrays support the extra key `visible` (`bool`, default `true`); `encodeLabel` defaults to
     * {@see $encodeLabels}.
     *
     * @var array<array<string, mixed>|string>
     */
    public array $buttons = [];
    /**
     * Whether to render dividers between the buttons (`btn-group-divider`).
     */
    public bool $divider = false;
    /**
     * Whether to HTML-encode the labels of configured buttons.
     */
    public bool $encodeLabels = true;
    /**
     * Size modifier rendered as the `btn-group-{value}` class (`xs`, `sm`, or `lg`), or `null` for the default.
     */
    public Size|string|null $size = null;
    /**
     * Whether to stack the buttons vertically (`btn-group-vertical` instead of `btn-group`).
     */
    public bool $vertical = false;

    /**
     * Initializes the widget and validates {@see $size}.
     *
     * @throws InvalidConfigException if {@see $size} is not a {@see Size} value.
     */
    public function init(): void
    {
        parent::init();

        Html::enumValue(Size::class, $this->size, 'size');
    }

    /**
     * Returns the button group with the visible buttons.
     *
     * @throws InvalidConfigException if {@see $size} or a configured button size, theme, or variant is invalid.
     *
     * @return string Rendered ButtonGroup markup.
     */
    public function run(): string
    {
        BootstrapAsset::register($this->getView());

        $options = $this->options;
        $classes = [$this->vertical ? 'btn-group-vertical' : 'btn-group'];

        if ($this->divider) {
            $classes[] = 'btn-group-divider';
        }

        $size = Html::enumValue(Size::class, $this->size, 'size');

        if ($size !== null) {
            $classes[] = "btn-group-{$size}";
        }

        Html::addCssClass($options, $classes);

        $options['role'] ??= 'group';

        return Html::tag(
            'div',
            $this->renderButtons(),
            $options,
        );
    }

    /**
     * Renders the visible buttons separated by line breaks.
     *
     * @throws InvalidConfigException if a configured button has an invalid size, theme, or variant.
     *
     * @return string Visible button markup separated by line breaks.
     */
    protected function renderButtons(): string
    {
        $buttons = [];

        foreach ($this->buttons as $button) {
            if (is_array($button)) {
                if (ArrayHelper::remove($button, 'visible', true) === false) {
                    continue;
                }

                $button['encodeLabel'] ??= $this->encodeLabels;
                $button['view'] = $this->getView();
                $button = Button::widget($button);
            }

            $buttons[] = $button;
        }

        return implode("\n", $buttons);
    }
}
