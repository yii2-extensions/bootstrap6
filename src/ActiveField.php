<?php

declare(strict_types=1);

namespace yii2\extensions\bootstrap6;

use yii\helpers\ArrayHelper;
use yii2\extensions\bootstrap6\asset\BootstrapPluginAsset;

use function array_filter;
use function array_flip;
use function array_intersect_key;
use function array_map;
use function implode;
use function is_array;
use function is_bool;
use function is_string;
use function preg_split;
use function str_replace;
use function strtr;

use const ARRAY_FILTER_USE_KEY;
use const PREG_SPLIT_NO_EMPTY;

/**
 * Renders a Bootstrap 6 form field for the layouts of {@see ActiveForm}.
 *
 * Markup per layout (`{error}` is the `.invalid-feedback` element, `{hint}` the `.form-text` element):
 *
 * - default: `<div class="form-field mb-3">` + label, control, hint, and error stacked by the `.form-field` grid.
 * - horizontal: `<div class="row mb-3">` + `<label class="sm:col-2 col-form-label">` + `<div class="sm:col-10">`
 *   wrapping the control, hint, and error. Checkboxes and radios render `<div class="sm:col-10 sm:offset-2">` +
 *   `<div class="form-field">`. Grid classes come from {@see $horizontalCssClasses}.
 * - inline: the form is a `row lg:row-cols-auto g-3 align-items-center` grid and each field is `<div class="col-12">`
 *   with a `visually-hidden` label, a placeholder, and no error. Checkboxes and radios render a nested
 *   `<div class="form-field">`.
 * - floating: `<div class="form-field mb-3">` + `<div class="form-floating">` holding the label BEFORE the control,
 *   then hint and error outside the wrapper, as in the Bootstrap validation docs. The feedback is a sibling of the
 *   wrapper, not of the control, so the wrapper carries the error CSS class on the server when the attribute has
 *   errors. On the client, a `:has()` rule registered once per form displays the sibling feedback as soon as the
 *   control (or the container in {@see ActiveForm::VALIDATION_STATE_ON_CONTAINER} mode) carries the error CSS class,
 *   so any client validation layer works without jQuery.
 *   The feedback is not placed inside `.form-floating` because the absolutely positioned label spans the whole
 *   wrapper height, so the feedback would push the floated label over the control value.
 *
 * Checkboxes, radios, and switches are rendered by {@see Html} (`.check`, `.radio`, `.switch`) with a plain label; the
 * `.form-field` grid lays them out in two columns. Lists render one `.form-field` per item and the error after the list
 * container, which receives the error CSS class. Size controls with `form-control-sm` or `form-control-lg` through
 * {@see $inputOptions} or the per-call `class` option; {@see dropDownList()}, {@see listBox()}, and {@see fileInput()}
 * keep `form-control` when a per-call `class` is given.
 *
 * Besides the core placeholders, {@see $template} accepts `{beginLabel}`, `{labelTitle}`, `{endLabel}` (label parts),
 * and `{beginWrapper}`, `{endWrapper}` (tags built from {@see $wrapperOptions}).
 *
 * @see https://getbootstrap.com/docs/6.0/forms/field/
 * @see https://getbootstrap.com/docs/6.0/forms/layout/
 * @see https://getbootstrap.com/docs/6.0/forms/floating-labels/
 */
class ActiveField extends \yii\widgets\ActiveField
{
    /**
     * Template for checkboxes and radios enclosed by their label.
     */
    public string $checkEnclosedTemplate = "{beginLabel}\n{input}\n{labelTitle}\n{endLabel}\n{hint}\n{error}";
    /**
     * Template for checkboxes and radios in the horizontal layout.
     */
    public string $checkHorizontalTemplate = "{beginWrapper}\n<div class=\"form-field\">\n{input}\n{label}\n{hint}\n"
        . "{error}\n</div>\n{endWrapper}";
    /**
     * Default options of {@see checkbox()} and of the {@see checkboxList()} items.
     *
     * @var array<mixed>
     */
    public array $checkOptions = [];
    /**
     * Template for checkboxes and radios; the inline layout wraps it in `<div class="form-field">`.
     */
    public string $checkTemplate = "{input}\n{label}\n{hint}\n{error}";
    /**
     * Whether to render the error element; `false` by default in the inline layout.
     */
    public bool $enableError = true;
    /**
     * Whether to render the label.
     */
    public bool $enableLabel = true;
    /**
     * @var array<mixed>
     */
    public $errorOptions = ['class' => 'invalid-feedback'];
    /**
     * @var array<mixed>
     */
    public $hintOptions = ['class' => 'form-text'];
    /**
     * Grid classes of the horizontal layout, keyed by `error`, `field`, `hint`, `label`, `offset` (wrapper of fields
     * without label), and `wrapper`.
     *
     * @var array<mixed>
     */
    public array $horizontalCssClasses = [];
    /**
     * Whether {@see checkboxList()} and {@see radioList()} lay out their items horizontally.
     */
    public bool $inline = false;
    /**
     * @var array<mixed>
     */
    public $inputOptions = ['class' => 'form-control'];
    /**
     * Template wrapping the rendered control, for example an input group: `<div class="input-group">{input}</div>`.
     */
    public string|null $inputTemplate = null;
    /**
     * @var array<mixed>
     */
    public $labelOptions = ['class' => 'form-label'];
    /**
     * @var array<mixed>
     */
    public $options = ['class' => 'form-field mb-3'];
    /**
     * Default options of {@see radio()} and of the {@see radioList()} items.
     *
     * @var array<mixed>
     */
    public array $radioOptions = [];
    /**
     * HTML attributes of the `{beginWrapper}` tag; the `tag` option sets the tag name (default `div`).
     *
     * @var array<mixed>
     */
    public array $wrapperOptions = [];

    /**
     * Initializes the field with the defaults of its owning form layout.
     *
     * @param array<string, mixed> $config Configuration merged over the defaults of {@see createLayoutConfig()}.
     */
    public function __construct($config = [])
    {
        $layoutConfig = $this->createLayoutConfig($config);

        // the resolved grid classes already include the configured ones; a recursive merge would append list values
        parent::__construct(
            array_filter(
                [
                    ...ArrayHelper::merge($layoutConfig, $config),
                    ...array_intersect_key($layoutConfig, array_flip(['horizontalCssClasses'])),
                ],
                is_string(...),
                ARRAY_FILTER_USE_KEY,
            ),
        );
    }

    /**
     * Renders a checkbox, or a switch with the `switch` option.
     *
     * Besides the options of {@see Html::checkbox()}, the following options are handled: `label` (`false` hides
     * it), `labelOptions`, `template`, and `wrapperOptions`.
     *
     * @param array<mixed> $options Input attributes merged over {@see $checkOptions}.
     * @param bool $enclosedByLabel Whether to render the input inside the label ({@see $checkEnclosedTemplate}).
     *
     * @see https://getbootstrap.com/docs/6.0/forms/checkbox/
     * @see https://getbootstrap.com/docs/6.0/forms/switch/
     *
     * @return $this The current instance for method chaining.
     */
    public function checkbox($options = [], $enclosedByLabel = false): static
    {
        $this->renderBooleanInput('checkbox', ArrayHelper::merge($this->checkOptions, $options), $enclosedByLabel);

        return $this;
    }

    /**
     * Renders a list of checkboxes, see {@see Html::checkboxList()}.
     *
     * @param array<mixed> $items Data items, value => label.
     * @param array<mixed> $options List options; `inline` defaults to {@see $inline}.
     *
     * @return $this The current instance for method chaining.
     */
    public function checkboxList($items, $options = []): static
    {
        $this->renderListInput('checkbox', $items, $options);

        return $this;
    }

    /**
     * Renders a color picker with the `form-control form-control-color` classes.
     *
     * @param array<mixed> $options Input attributes.
     *
     * @see https://getbootstrap.com/docs/6.0/forms/form-control/#color
     *
     * @return $this The current instance for method chaining.
     */
    public function colorInput(array $options = []): static
    {
        Html::addCssClass($options, ['form-control', 'form-control-color']);

        return $this->input('color', $options);
    }

    /**
     * Renders a drop-down list with Bootstrap control classes.
     *
     * @param array<mixed> $items Option data items.
     * @param array<mixed> $options Tag attributes; a `class` keeps `form-control`, and the `placeholder` of
     * {@see $inputOptions} is not inherited.
     *
     * @return $this The current instance for method chaining.
     */
    public function dropDownList($items, $options = []): static
    {
        parent::dropDownList($items, self::selectOptions($options));

        return $this;
    }

    /**
     * Configures the validation error element for the field.
     *
     * @param array<mixed>|false $options Tag attributes, or `false` to omit the error element.
     *
     * @return $this The current instance for method chaining.
     */
    public function error($options = []): static
    {
        parent::error($options);

        return $this;
    }

    /**
     * Merges {@see $inputOptions} before the core method, which skips them on Yii 2.0.x when they equal the core
     * default `['class' => 'form-control']`.
     *
     * @param array<mixed> $options Tag attributes; a `class` keeps `form-control`.
     *
     * @return $this The current instance for method chaining.
     */
    public function fileInput($options = []): static
    {
        parent::fileInput(self::controlOptions([...$this->inputOptions, ...self::arrayValue($options)]));

        return $this;
    }

    /**
     * Configures the hint displayed with the field.
     *
     * @param bool|string|null $content Hint content, `null` for the model hint, or `false` to omit it.
     * @param array<mixed> $options Tag attributes.
     *
     * @return $this The current instance for method chaining.
     */
    public function hint($content, $options = []): static
    {
        parent::hint($content, $options);

        return $this;
    }

    /**
     * Sets whether {@see checkboxList()} and {@see radioList()} lay out their items horizontally.
     *
     * Call it before the list method.
     *
     * @param bool $value Whether to arrange list items horizontally.
     *
     * @return $this The current instance for method chaining.
     */
    public function inline(bool $value = true): static
    {
        $this->inline = $value;

        return $this;
    }

    /**
     * Renders an input of the specified type for the model attribute.
     *
     * @param string $type Input type.
     * @param array<mixed> $options Tag attributes merged over {@see $inputOptions}.
     *
     * @return $this The current instance for method chaining.
     */
    public function input($type, $options = []): static
    {
        parent::input($type, $options);

        return $this;
    }

    /**
     * Renders the label, or enables or disables it with a `bool` argument.
     *
     * Disabling the label in the horizontal layout adds the `offset` grid class to the wrapper.
     *
     * @param bool|string|null $label Label content (not encoded), `null` for the model label, or a `bool`.
     * @param array<mixed>|null $options Tag attributes merged over {@see $labelOptions}.
     *
     * @return $this The current instance for method chaining.
     */
    public function label($label = null, $options = []): static
    {
        $options ??= [];

        if (is_bool($label)) {
            $this->enableLabel = $label;

            if ($label === false && $this->layout() === ActiveForm::LAYOUT_HORIZONTAL) {
                Html::addCssClass($this->wrapperOptions, $this->horizontalCssClass('offset'));
            }

            return $this;
        }

        $this->enableLabel = true;

        $this->renderLabelParts($label, $options);

        parent::label($label, $options);

        return $this;
    }

    /**
     * Renders a list box with Bootstrap control classes.
     *
     * @param array<mixed> $items Option data items.
     * @param array<mixed> $options Tag attributes; a `class` keeps `form-control`, and the `placeholder` of
     * {@see $inputOptions} is not inherited.
     *
     * @return $this The current instance for method chaining.
     */
    public function listBox($items, $options = []): static
    {
        parent::listBox($items, self::selectOptions($options));

        return $this;
    }

    /**
     * Renders a password input for the model attribute.
     *
     * @param array<mixed> $options Tag attributes merged over {@see $inputOptions}.
     *
     * @return $this The current instance for method chaining.
     */
    public function passwordInput($options = []): static
    {
        parent::passwordInput($options);

        return $this;
    }

    /**
     * Renders a radio.
     *
     * Accepts the same options as {@see checkbox()} except `switch`.
     *
     * @param array<mixed> $options Input attributes merged over {@see $radioOptions}.
     * @param bool $enclosedByLabel Whether to render the input inside the label ({@see $checkEnclosedTemplate}).
     *
     * @see https://getbootstrap.com/docs/6.0/forms/radio/
     *
     * @return $this The current instance for method chaining.
     */
    public function radio($options = [], $enclosedByLabel = false): static
    {
        $this->renderBooleanInput('radio', ArrayHelper::merge($this->radioOptions, $options), $enclosedByLabel);

        return $this;
    }

    /**
     * Renders a list of radios, see {@see Html::radioList()}.
     *
     * @param array<mixed> $items Data items, value => label.
     * @param array<mixed> $options List options; `inline` defaults to {@see $inline}.
     *
     * @return $this The current instance for method chaining.
     */
    public function radioList($items, $options = []): static
    {
        $this->renderListInput('radio', $items, $options);

        return $this;
    }

    /**
     * Renders a range input wrapped in the `.form-range` element and registers the plugin bundle that initializes it.
     *
     * The `rangeOptions` option holds the HTML attributes of the wrapper, for example `data-bs-bubble` or a theme
     * class.
     *
     * @param array<mixed> $options Input attributes, for example `min`, `max`, `step`, or `list`.
     *
     * @return $this The current instance for method chaining.
     *
     * @see https://getbootstrap.com/docs/6.0/forms/range/
     */
    public function rangeInput(array $options = []): static
    {
        $rangeOptions = self::arrayValue(ArrayHelper::remove($options, 'rangeOptions'));

        Html::addCssClass($options, 'form-range-input');

        $this->input('range', $options);

        $this->parts['{input}'] = Html::tag(
            'div',
            self::stringValue($this->parts['{input}'] ?? null) ?? '',
            self::prependClass($rangeOptions, 'form-range'),
        );

        BootstrapPluginAsset::register($this->form->getView());

        return $this;
    }

    /**
     * Renders the field with its layout-specific label, wrapper, and validation feedback.
     *
     * @param (callable(): mixed)|string|null $content Field content, or `null` to render the parts with
     * {@see $template}.
     *
     * @return string the rendered field content.
     */
    public function render($content = null): string
    {
        if ($content === null) {
            $this->renderWrapperParts();

            if ($this->enableLabel === false) {
                $this->parts['{beginLabel}'] = '';
                $this->parts['{endLabel}'] = '';
                $this->parts['{label}'] = '';
                $this->parts['{labelTitle}'] = '';
            } elseif (!isset($this->parts['{beginLabel}'])) {
                $this->renderLabelParts();
            }

            if ($this->enableError === false) {
                $this->parts['{error}'] = '';
            } elseif ($this->layout() === ActiveForm::LAYOUT_FLOATING) {
                $this->registerFloatingFeedbackStyle();
            }

            $inputTemplate = $this->inputTemplate;

            if ($inputTemplate !== null) {
                if (!isset($this->parts['{input}'])) {
                    $this->textInput();
                }

                $this->parts['{input}'] = strtr(
                    $inputTemplate,
                    ['{input}' => self::stringValue($this->parts['{input}'] ?? null) ?? ''],
                );
            }
        }

        return parent::render($content);
    }

    /**
     * Renders a read-only plain-text control with the attribute value.
     *
     * @param array<mixed> $options Tag attributes; the `value` option replaces the attribute value.
     *
     * @see https://getbootstrap.com/docs/6.0/forms/form-control/#readonly-plain-text
     *
     * @return $this The current instance for method chaining.
     */
    public function staticControl(array $options = []): static
    {
        $options['id'] ??= $this->getInputId();

        $this->adjustLabelFor($options);

        $this->parts['{input}'] = Html::activeStaticControl($this->model, $this->attribute, $options);

        return $this;
    }

    /**
     * Renders a multiline text input for the model attribute.
     *
     * @param array<mixed> $options Tag attributes merged over {@see $inputOptions}.
     *
     * @return $this The current instance for method chaining.
     */
    public function textarea($options = []): static
    {
        parent::textarea($options);

        return $this;
    }

    /**
     * Renders a text input for the model attribute.
     *
     * @param array<mixed> $options Tag attributes merged over {@see $inputOptions}.
     *
     * @return $this The current instance for method chaining.
     */
    public function textInput($options = []): static
    {
        parent::textInput($options);

        return $this;
    }

    /**
     * Renders an input widget for the model attribute.
     *
     * @param string $class Widget class name.
     * @param array<mixed> $config Widget configuration.
     *
     * @return $this The current instance for method chaining.
     */
    public function widget($class, $config = []): static
    {
        parent::widget($class, $config);

        return $this;
    }

    /**
     * Returns the layout-specific default configuration.
     *
     * Defaults are taken from the current property values, so subclasses can redefine them.
     *
     * @param array<mixed> $instanceConfig Configuration passed to the constructor.
     *
     * @return array<string, mixed> Configuration merged under the constructor configuration.
     */
    protected function createLayoutConfig(array $instanceConfig): array
    {
        return match (self::layoutOf($instanceConfig['form'] ?? null)) {
            ActiveForm::LAYOUT_FLOATING => [
                'inputOptions' => [...$this->inputOptions, 'placeholder' => true],
                'labelOptions' => [],
                'template' => "{beginWrapper}\n{label}\n{input}\n{endWrapper}\n{hint}\n{error}",
                'wrapperOptions' => ['class' => 'form-floating'],
            ],
            ActiveForm::LAYOUT_HORIZONTAL => $this->createHorizontalLayoutConfig($instanceConfig),
            ActiveForm::LAYOUT_INLINE => [
                'checkTemplate' => "<div class=\"form-field\">\n{input}\n{label}\n{hint}\n{error}\n</div>",
                'enableError' => false,
                'inputOptions' => [...$this->inputOptions, 'placeholder' => true],
                'labelOptions' => ['class' => 'visually-hidden'],
                'options' => ['class' => 'col-12'],
            ],
            default => [],
        };
    }

    /**
     * Returns a grid class of {@see $horizontalCssClasses}, or an empty array when it is unset or empty.
     *
     * @param string $key Grid class key, such as `label`, `wrapper`, or `offset`.
     *
     * @return array<mixed>|string Configured grid classes, or an empty array when unavailable.
     */
    protected function horizontalCssClass(string $key): array|string
    {
        return self::cssClass($this->horizontalCssClasses[$key] ?? null);
    }

    /**
     * Returns the layout of the owning form, or {@see ActiveForm::LAYOUT_DEFAULT} for a non-Bootstrap form.
     *
     * @return string Owning form layout, or the default layout for a non-Bootstrap form.
     */
    protected function layout(): string
    {
        return self::layoutOf($this->form);
    }

    /**
     * Registers, once per form, the style rule that displays the feedback following a `.form-floating` wrapper when
     * its control or its container carries the error CSS class. Whitespace-separated classes in
     * {@see ActiveForm::$errorCssClass} are matched together; no rule is registered when it holds no class.
     *
     * The form `id` and each class are escaped through {@see Html::escapeCssIdentifier()}; the registration key keeps
     * the raw `id`.
     *
     * @see https://developer.mozilla.org/en-US/docs/Web/CSS/:has
     */
    protected function registerFloatingFeedbackStyle(): void
    {
        $id = self::stringValue($this->form->options['id'] ?? null) ?? $this->form->getId();
        $classes = preg_split('/\s+/', $this->form->errorCssClass, flags: PREG_SPLIT_NO_EMPTY);

        if ($classes === false || $classes === []) {
            return;
        }

        $selectorId = Html::escapeCssIdentifier($id ?? '');
        $errorCssClass = implode('.', array_map(Html::escapeCssIdentifier(...), $classes));

        $css = <<<CSS
            #{$selectorId} :is(.form-floating:has(.{$errorCssClass}), .{$errorCssClass} > .form-floating)
            ~ :is(.invalid-feedback, .invalid-tooltip) { display: block; }
            CSS;

        $this->form->getView()->registerCss(str_replace("\n", ' ', $css), key: "{$id}-floating-feedback");
    }

    /**
     * Renders a checkbox or radio with the layout-specific template and label.
     *
     * @param string $type Input type, either `checkbox` or `radio`.
     * @param array<mixed> $options Input attributes and the special options of {@see checkbox()}.
     * @param bool $enclosedByLabel Whether to render the input inside the label.
     */
    protected function renderBooleanInput(string $type, array $options, bool $enclosedByLabel): void
    {
        $layout = $this->layout();

        $template = ArrayHelper::remove($options, 'template');

        $this->labelOptions = ArrayHelper::merge(
            $this->labelOptions,
            self::arrayValue(ArrayHelper::remove($options, 'labelOptions')),
        );
        $this->wrapperOptions = ArrayHelper::merge(
            $this->wrapperOptions,
            self::arrayValue(ArrayHelper::remove($options, 'wrapperOptions')),
        );
        $this->template = is_string($template) ? $template : match (true) {
            $layout === ActiveForm::LAYOUT_HORIZONTAL => $this->checkHorizontalTemplate,
            $enclosedByLabel => $this->checkEnclosedTemplate,
            default => $this->checkTemplate,
        };

        if ($layout === ActiveForm::LAYOUT_HORIZONTAL) {
            Html::removeCssClass($this->labelOptions, $this->horizontalCssClass('label'));
            Html::addCssClass($this->wrapperOptions, $this->horizontalCssClass('offset'));
        }

        if ($layout === ActiveForm::LAYOUT_INLINE) {
            Html::removeCssClass($this->labelOptions, 'visually-hidden');
        }

        Html::removeCssClass($this->labelOptions, 'form-label');

        if ($this->form->validationStateOn === ActiveForm::VALIDATION_STATE_ON_INPUT) {
            $this->addErrorClassIfNeeded($options);
        }

        $this->addAriaAttributes($options);

        $options = self::arrayValue($options);

        $this->adjustLabelFor($options);

        $label = ArrayHelper::remove($options, 'label');

        if ($label === false) {
            $this->enableLabel = false;
        } elseif (is_string($label)) {
            $this->label($label);
        }

        $options['label'] = null;

        $this->parts['{input}'] = $type === 'radio'
            ? Html::activeRadio($this->model, $this->attribute, $options)
            : Html::activeCheckbox($this->model, $this->attribute, $options);
    }

    /**
     * Renders the `{beginLabel}`, `{labelTitle}`, and `{endLabel}` parts.
     *
     * @param string|null $label Label content, or `null` for the `label` option or the encoded model label.
     * @param array<mixed> $options Tag attributes merged over {@see $labelOptions}.
     */
    protected function renderLabelParts(string|null $label = null, array $options = []): void
    {
        $options = [...$this->labelOptions, ...$options];

        $optionLabel = ArrayHelper::remove($options, 'label');

        $label ??= is_string($optionLabel)
            ? $optionLabel
            : Html::encode($this->model->getAttributeLabel(Html::getAttributeName($this->attribute)));
        $options['for'] ??= $this->getInputId();

        $this->parts['{beginLabel}'] = Html::beginTag('label', $options);
        $this->parts['{endLabel}'] = Html::endTag('label');
        $this->parts['{labelTitle}'] ??= $label;
    }

    /**
     * Renders a checkbox or radio list with the list label pointing to no control.
     *
     * In the floating layout the list cannot float, so it uses the stacked template and a `.form-label` label.
     *
     * @param string $type Input type, either `checkbox` or `radio`.
     * @param array<mixed> $items Data items, value => label.
     * @param array<mixed> $options List options.
     */
    protected function renderListInput(string $type, array $items, array $options): void
    {
        if ($this->form->validationStateOn === ActiveForm::VALIDATION_STATE_ON_INPUT) {
            $this->addErrorClassIfNeeded($options);
        }

        if ($type === 'radio') {
            $this->addRoleAttributes($options, 'radiogroup');
        }

        if ($this->layout() === ActiveForm::LAYOUT_FLOATING) {
            Html::addCssClass($this->labelOptions, 'form-label');

            $this->template = "{label}\n{input}\n{hint}\n{error}";
        }

        $this->addAriaAttributes($options);

        $options = self::arrayValue($options);

        $this->adjustLabelFor($options);

        $options['inline'] ??= $this->inline;
        $options['itemOptions'] = ArrayHelper::merge(
            $type === 'radio' ? $this->radioOptions : $this->checkOptions,
            self::arrayValue($options['itemOptions'] ?? null),
        );

        $this->labelOptions['for'] = null;

        $this->parts['{input}'] = $type === 'radio'
            ? Html::activeRadioList($this->model, $this->attribute, $items, $options)
            : Html::activeCheckboxList($this->model, $this->attribute, $items, $options);
    }

    /**
     * Builds the `{beginWrapper}` and `{endWrapper}` parts unless they are already set.
     *
     * In the floating layout the wrapper receives the error CSS class, so the sibling feedback is displayed.
     */
    protected function renderWrapperParts(): void
    {
        if (isset($this->parts['{beginWrapper}'])) {
            return;
        }

        $options = $this->wrapperOptions;

        $tag = self::stringValue(ArrayHelper::remove($options, 'tag')) ?? 'div';

        if ($this->layout() === ActiveForm::LAYOUT_FLOATING) {
            $this->addErrorClassIfNeeded($options);
        }

        $this->parts['{beginWrapper}'] = Html::beginTag($tag, self::arrayValue($options));
        $this->parts['{endWrapper}'] = Html::endTag($tag);
    }

    /**
     * Returns the value when it is an array, or an empty array otherwise.
     *
     * @param mixed $value Value to inspect.
     *
     * @return array<mixed> The array value, or an empty array for other types.
     */
    private static function arrayValue(mixed $value): array
    {
        return is_array($value) ? $value : [];
    }

    /**
     * Adds `form-control` before the per-call `class` option, when one is given.
     *
     * @param array<mixed> $options Tag attributes.
     *
     * @return array<mixed> Input attributes with the Bootstrap base class preserved.
     */
    private static function controlOptions(array $options): array
    {
        return isset($options['class']) ? self::prependClass($options, 'form-control') : $options;
    }

    /**
     * Returns the horizontal layout configuration with the grid classes applied.
     *
     * @param array<mixed> $instanceConfig Configuration passed to the constructor.
     *
     * @return array<string, mixed> Horizontal layout defaults with the resolved grid classes.
     */
    private function createHorizontalLayoutConfig(array $instanceConfig): array
    {
        $this->horizontalCssClasses = [
            'field' => ['row', 'mb-3'],
            'label' => ['sm:col-2', 'col-form-label'],
            'offset' => ['sm:col-10', 'sm:offset-2'],
            'wrapper' => 'sm:col-10',
            ...$this->horizontalCssClasses,
            ...self::arrayValue($instanceConfig['horizontalCssClasses'] ?? null),
        ];

        $errorOptions = $this->errorOptions;
        $hintOptions = $this->hintOptions;
        $labelOptions = [];
        $options = [];
        $wrapperOptions = $this->wrapperOptions;

        Html::addCssClass($errorOptions, $this->horizontalCssClass('error'));
        Html::addCssClass($hintOptions, $this->horizontalCssClass('hint'));
        Html::addCssClass($labelOptions, $this->horizontalCssClass('label'));
        Html::addCssClass($options, $this->horizontalCssClass('field'));
        Html::addCssClass($wrapperOptions, $this->horizontalCssClass('wrapper'));

        return [
            'errorOptions' => $errorOptions,
            'hintOptions' => $hintOptions,
            'horizontalCssClasses' => $this->horizontalCssClasses,
            'labelOptions' => $labelOptions,
            'options' => $options,
            'template' => "{label}\n{beginWrapper}\n{input}\n{hint}\n{error}\n{endWrapper}",
            'wrapperOptions' => $wrapperOptions,
        ];
    }

    /**
     * Returns the value as a CSS class, or an empty array when it is empty or not a `string` or `array`.
     *
     * @param mixed $value Configured CSS class value to inspect.
     *
     * @return array<mixed>|string The value as a CSS class, or an empty array when it is empty or not a `string` or
     * `array`.
     */
    private static function cssClass(mixed $value): array|string
    {
        return is_array($value) || is_string($value) && $value !== '' ? $value : [];
    }

    /**
     * Returns the layout of the form, or {@see ActiveForm::LAYOUT_DEFAULT} for a non-Bootstrap form.
     *
     * @param mixed $form Form instance whose layout is requested.
     *
     * @return string Bootstrap form layout, or the default layout for other values.
     */
    private static function layoutOf(mixed $form): string
    {
        return $form instanceof ActiveForm ? $form->layout : ActiveForm::LAYOUT_DEFAULT;
    }

    /**
     * Returns the options with a base class placed before the configured classes.
     *
     * @param array<mixed> $options Tag attributes.
     * @param string $class Base class, for example `form-control`.
     *
     * @return array<mixed> The options with the base class prepended.
     */
    private static function prependClass(array $options, string $class): array
    {
        $configured = self::cssClass($options['class'] ?? null);

        $options['class'] = $class;

        Html::addCssClass($options, $configured);

        return $options;
    }

    /**
     * Returns the control options of a `<select>`, which does not inherit the `placeholder` of {@see $inputOptions}.
     *
     * @param array<mixed> $options Tag attributes.
     *
     * @return array<mixed> The control options for the `<select>` element.
     */
    private static function selectOptions(array $options): array
    {
        return ['placeholder' => null, ...self::controlOptions($options)];
    }

    /**
     * Returns the value when it is a `string`, or `null` otherwise.
     *
     * @param mixed $value The value to check.
     *
     * @return string|null The string value or `null` if it is not a string.
     */
    private static function stringValue(mixed $value): string|null
    {
        return is_string($value) ? $value : null;
    }
}
