<?php

declare(strict_types=1);

namespace yii2\extensions\bootstrap6;

use Stringable;
use yii\base\{InvalidConfigException, Model};
use yii\helpers\ArrayHelper;
use yii2\extensions\bootstrap6\asset\BootstrapAsset;
use yii2\extensions\bootstrap6\enum\{ButtonVariant, Size, Theme};
use yii2\extensions\bootstrap6\exception\Message;

use function implode;
use function in_array;
use function is_iterable;
use function is_scalar;

/**
 * Renders a group of checkbox or radio toggle buttons for a model attribute or a named input.
 *
 * Each item renders as `<label class="btn-check ..."><input type="checkbox" ...>Label</label>`; the input is nested in
 * the label, so no `id`/`for` pair is needed. With a model, a hidden input submits the empty value when nothing is
 * selected (configurable through the `unselect` key of {@see $options}); it is disabled together with the inputs when
 * {@see $inputOptions} carries `disabled`.
 *
 * @see https://getbootstrap.com/docs/6.0/components/button-group/#toggle-buttons
 */
class ToggleButtonGroup extends InputWidget
{
    /**
     * Checkbox toggle buttons; the input name receives the `[]` suffix.
     */
    public const string TYPE_CHECKBOX = 'checkbox';
    /**
     * Radio toggle buttons.
     */
    public const string TYPE_RADIO = 'radio';
    /**
     * Supported values of {@see $type}.
     */
    public const array TYPES = [self::TYPE_CHECKBOX, self::TYPE_RADIO];
    /**
     * Whether to HTML-encode the item labels.
     */
    public bool $encodeLabels = true;
    /**
     * HTML attributes of every input tag, for example `['disabled' => true]`.
     *
     * @var array<string, mixed>
     */
    public array $inputOptions = [];
    /**
     * Toggle buttons as value => label pairs.
     *
     * @var array<array-key, string>
     */
    public array $items = [];
    /**
     * HTML attributes of every label (button) tag.
     *
     * @var array<string, mixed>
     */
    public array $labelOptions = [];
    /**
     * Size modifier rendered as the `btn-group-{value}` class (`xs`, `sm`, or `lg`), or `null` for the default.
     */
    public Size|string|null $size = null;
    /**
     * Theme color of the buttons rendered as the `theme-{value}` class, or `null` for the neutral color.
     */
    public Theme|string|null $theme = null;
    /**
     * Input type, either {@see TYPE_CHECKBOX} or {@see TYPE_RADIO}.
     */
    public string $type = self::TYPE_CHECKBOX;
    /**
     * Button style class of the labels.
     */
    public ButtonVariant|string $variant = ButtonVariant::OUTLINE;

    /**
     * @throws InvalidConfigException if the widget has neither a model attribute nor a name, or {@see $size},
     * {@see $theme}, {@see $type}, or {@see $variant} is not supported.
     */
    public function init(): void
    {
        parent::init();

        Html::enumValue(Size::class, $this->size, 'size');
        Html::enumValue(Theme::class, $this->theme, 'theme');
        Html::enumValue(ButtonVariant::class, $this->variant, 'variant');

        if (!in_array($this->type, self::TYPES, true)) {
            throw new InvalidConfigException(
                Message::INVALID_VALUE->getMessage($this->type, 'type', implode(', ', self::TYPES)),
            );
        }
    }

    public function run(): string
    {
        BootstrapAsset::register($this->getView());

        $options = $this->options;

        if ($this->model instanceof Model && $this->attribute !== null) {
            $name = Html::getInputName($this->model, $this->attribute);
            $selection = Html::getAttributeValue($this->model, $this->attribute);
            $unselect = ArrayHelper::remove($options, 'unselect', '');
        } else {
            $name = $this->name ?? '';
            $selection = $this->value;
            $unselect = ArrayHelper::remove($options, 'unselect');
        }

        Html::addCssClass($options, 'btn-group');

        $size = Html::enumValue(Size::class, $this->size, 'size');

        if ($size !== null) {
            Html::addCssClass($options, "btn-group-{$size}");
        }

        $options['role'] ??= 'group';
        $inputName = $this->type === self::TYPE_CHECKBOX ? "{$name}[]" : $name;
        $items = [];

        foreach ($this->items as $value => $label) {
            $value = (string) $value;
            $items[] = $this->renderItem($label, $inputName, self::isSelected($value, $selection), $value);
        }

        $hiddenOptions = [
            'disabled' => $this->inputOptions['disabled'] ?? null,
            'form' => $this->inputOptions['form'] ?? null,
        ];

        $hidden = is_scalar($unselect) ? Html::hiddenInput($name, (string) $unselect, $hiddenOptions) : '';

        return $hidden
            . Html::tag(
                'div',
                implode("\n", $items),
                $options
            );
    }

    /**
     * Renders a toggle button.
     *
     * @param string $label Item label, HTML-encoded when {@see $encodeLabels} is `true`.
     * @param string $name Input name.
     * @param bool $checked Whether the input is checked.
     * @param string $value Input value.
     *
     * @return string Generated HTML.
     */
    protected function renderItem(string $label, string $name, bool $checked, string $value): string
    {
        $inputOptions = $this->inputOptions;
        $inputOptions['checked'] = $checked;
        $inputOptions['autocomplete'] ??= 'off';

        $labelOptions = $this->labelOptions;

        Html::addCssClass(
            $labelOptions,
            ['btn-check', Html::enumValue(ButtonVariant::class, $this->variant, 'variant')],
        );
        Html::theme($labelOptions, $this->theme);

        $input = Html::input($this->type, $name, $value, $inputOptions);

        return Html::tag(
            'label',
            $input . ($this->encodeLabels ? Html::encode($label) : $label),
            $labelOptions,
        );
    }

    /**
     * Returns whether the item value is part of the selection.
     *
     * @param string $value Item value.
     * @param mixed $selection Selected value, or iterable of selected values.
     */
    private static function isSelected(string $value, mixed $selection): bool
    {
        foreach (is_iterable($selection) ? $selection : [$selection] as $selected) {
            if ((is_scalar($selected) || $selected instanceof Stringable) && (string) $selected === $value) {
                return true;
            }
        }

        return false;
    }
}
