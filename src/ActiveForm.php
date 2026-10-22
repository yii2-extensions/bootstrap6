<?php

declare(strict_types=1);

namespace yii2\extensions\bootstrap6;

use yii\base\InvalidConfigException;
use yii2\extensions\bootstrap6\asset\BootstrapAsset;
use yii2\extensions\bootstrap6\exception\Message;

use function get_debug_type;
use function implode;
use function in_array;
use function is_string;

/**
 * Renders a Bootstrap 6 form with default, horizontal, inline, or floating-label field layouts.
 *
 * The layout selects the default {@see ActiveField} configuration; override it per form through {@see $fieldConfig}.
 * The inline layout adds `row lg:row-cols-auto g-3 align-items-center` to the form, and {@see $nativeValidation}
 * opts the form in to the Bootstrap `:user-invalid` styling with the `data-bs-validate` attribute.
 *
 * @see https://getbootstrap.com/docs/6.0/forms/layout/
 * @see https://getbootstrap.com/docs/6.0/forms/validation/
 */
class ActiveForm extends \yii\widgets\ActiveForm
{
    /**
     * Stacked labels, controls, hints, and feedback.
     */
    public const string LAYOUT_DEFAULT = 'default';
    /**
     * Floating labels rendered inside a `.form-floating` wrapper.
     */
    public const string LAYOUT_FLOATING = 'floating';
    /**
     * Grid rows with the label and the control side by side.
     */
    public const string LAYOUT_HORIZONTAL = 'horizontal';
    /**
     * Auto-sized columns with visually hidden labels and no error feedback.
     */
    public const string LAYOUT_INLINE = 'inline';
    /**
     * Supported layouts.
     *
     * @var list<string>
     */
    protected const array LAYOUTS = [
        self::LAYOUT_DEFAULT,
        self::LAYOUT_HORIZONTAL,
        self::LAYOUT_INLINE,
        self::LAYOUT_FLOATING,
    ];

    /**
     * CSS class added to the control when the attribute has validation errors.
     *
     * @var string
     */
    public $errorCssClass = 'is-invalid';
    /**
     * CSS class of the error summary container.
     *
     * @var string
     */
    public $errorSummaryCssClass = 'alert theme-danger';
    /**
     * Field class used by {@see field()}; it must extend {@see ActiveField}.
     *
     * @var string
     */
    public $fieldClass = ActiveField::class;
    /**
     * Form layout, one of the `LAYOUT_*` constants.
     */
    public string $layout = self::LAYOUT_DEFAULT;
    /**
     * Client-side validation styling: `true` renders `data-bs-validate`, `'valid'` renders `data-bs-validate="valid"`
     * (success styling too), `false` removes the attribute, and `null` keeps {@see $options} unchanged.
     */
    public bool|string|null $nativeValidation = null;
    /**
     * CSS class added to the control when the attribute passes validation.
     *
     * @var string
     */
    public $successCssClass = 'is-valid';
    /**
     * Element that receives the validation CSS classes; Bootstrap styles the control itself.
     *
     * @var string
     */
    public $validationStateOn = self::VALIDATION_STATE_ON_INPUT;

    /**
     * Creates a Bootstrap field for the given model attribute.
     *
     * @param \yii\base\Model $model Model holding the attribute.
     * @param string $attribute Attribute name or expression.
     * @param array<mixed> $options Field configuration merged over {@see $fieldConfig}.
     *
     * @throws InvalidConfigException if the configured field class does not extend {@see ActiveField}.
     *
     * @return ActiveField Field instance configured for the model attribute.
     */
    public function field($model, $attribute, $options = []): ActiveField
    {
        $field = parent::field($model, $attribute, $options);

        if (!$field instanceof ActiveField) {
            throw new InvalidConfigException(
                Message::INVALID_VALUE->getMessage(get_debug_type($field), 'fieldClass', ActiveField::class),
            );
        }

        return $field;
    }

    /**
     * Validates the form configuration, applies its layout, and registers the Bootstrap stylesheet.
     *
     * @throws InvalidConfigException if {@see $layout} or {@see $nativeValidation} is invalid.
     */
    public function init(): void
    {
        if (!in_array($this->layout, static::LAYOUTS, true)) {
            throw new InvalidConfigException(
                Message::INVALID_VALUE->getMessage($this->layout, 'layout', implode(', ', static::LAYOUTS)),
            );
        }

        if (is_string($this->nativeValidation) && $this->nativeValidation !== 'valid') {
            throw new InvalidConfigException(
                Message::INVALID_VALUE->getMessage($this->nativeValidation, 'nativeValidation', 'true, false, valid'),
            );
        }

        if ($this->layout === self::LAYOUT_INLINE) {
            Html::addCssClass($this->options, ['row', 'lg:row-cols-auto', 'g-3', 'align-items-center']);
        }

        if ($this->nativeValidation !== null) {
            $this->options['data-bs-validate'] = $this->nativeValidation;
        }

        parent::init();

        BootstrapAsset::register($this->getView());
    }
}
