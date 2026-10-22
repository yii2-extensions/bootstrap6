<?php

declare(strict_types=1);

namespace yii2\extensions\bootstrap6;

use yii\base\Model;
use yii\helpers\Json;
use yii\web\View;

use function array_map;
use function explode;
use function implode;
use function is_array;
use function is_scalar;
use function is_string;
use function trim;

/**
 * Renders a Bootstrap chip input: a `.chip-input` container with the initial chips, a `.form-ghost` text input and a
 * hidden input that submits the chip values.
 *
 * {@see $options} applies to the ghost input, so labels target the visible field; the hidden input copies its
 * `disabled` and `form` attributes, so a disabled field submits nothing. The container receives the `id`
 * `{id}-chips`, carries the plugin `data-bs-*` attributes and the client events. The chips come from the model
 * attribute or {@see $value} (an array, or a string joined by {@see $separator}) and are submitted joined by the same
 * separator, kept in sync by a `change.bs.chips` listener.
 *
 * @see https://getbootstrap.com/docs/6.0/forms/chips/
 */
class Chips extends InputWidget
{
    /**
     * Whether duplicate chip values are allowed, or `null` for the plugin default (`false`).
     */
    public bool|null $allowDuplicates = null;
    /**
     * @var array<string, mixed> HTML attributes of the `.chip-input` container.
     */
    public array $containerOptions = [];
    /**
     * Whether to create a chip from the typed text when the input loses focus, or `null` for the plugin default
     * (`true`).
     */
    public bool|null $createOnBlur = null;
    /**
     * Whether the plugin adds dismiss buttons to the chips, or `null` for the plugin default (`true`).
     */
    public bool|null $dismissible = null;
    /**
     * Whether to HTML-encode the chip labels.
     */
    public bool $encodeLabels = true;
    /**
     * Maximum number of chips, or `null` for no limit.
     */
    public int|null $maxChips = null;
    /**
     * Placeholder of the ghost input, or `null` for none.
     */
    public string|null $placeholder = null;
    /**
     * Character that creates a chip when typed, `''` to disable it, or `null` for the plugin default (`,`).
     *
     * A non-empty separator also joins the submitted values; otherwise they are joined by `,`.
     */
    public string|null $separator = null;

    /**
     * Returns the `.chip-input` container with the initial chips, the ghost input, and the hidden input, and
     * registers the `Chips` plugin with the listener that updates the hidden input.
     *
     * @return string Rendered Chips markup.
     */
    public function run(): string
    {
        $options = $this->options;

        $id = $this->inputId();

        $delimiter = $this->separator !== null && $this->separator !== '' ? $this->separator : ',';

        $chips = $this->chipValues($delimiter);
        $containerId = is_string($this->containerOptions['id'] ?? null) ? $this->containerOptions['id'] : "{$id}-chips";

        $containerOptions = [...$this->containerOptions, 'id' => $containerId];

        Html::addCssClass($containerOptions, 'chip-input');

        $containerOptions['data-bs-chips'] = true;
        $containerOptions['data-bs-separator'] = $this->separator;
        $containerOptions['data-bs-allow-duplicates'] = self::booleanAttribute($this->allowDuplicates);
        $containerOptions['data-bs-max-chips'] = $this->maxChips;
        $containerOptions['data-bs-dismissible'] = self::booleanAttribute($this->dismissible);
        $containerOptions['data-bs-create-on-blur'] = self::booleanAttribute($this->createOnBlur);

        Html::addCssClass($options, 'form-ghost');

        $options['placeholder'] ??= $this->placeholder;

        $lines = array_map(
            fn(string $chip): string => Html::tag(
                'span',
                $this->encodeLabels ? Html::encode($chip) : $chip,
                ['class' => 'chip'],
            ),
            $chips,
        );

        $lines[] = Html::input('text', null, null, $options);

        $lines[] = $this->renderHiddenInput("{$id}-value", implode($delimiter, $chips));

        $original = $this->options;
        $this->options['id'] = $containerId;

        $this->registerPlugin('Chips', true);

        $this->options = $original;

        $encodedContainerId = Json::htmlEncode($containerId);
        $encodedValueId = Json::htmlEncode("{$id}-value");
        $encodedDelimiter = Json::htmlEncode($delimiter);

        $this->getView()->registerJs(
            <<<JS
            document.getElementById({$encodedContainerId}).addEventListener("change.bs.chips", (event) => {
                document.getElementById({$encodedValueId}).value = event.values.join({$encodedDelimiter});
            });
            JS,
            View::POS_END,
            "{$containerId}-sync",
        );

        return Html::tag(
            'div',
            "\n" . implode("\n", $lines) . "\n",
            $containerOptions,
        );
    }

    /**
     * Renders the hidden input that submits the chip values.
     *
     * The hidden input copies the `disabled` and `form` attributes of the ghost input, so a disabled field submits
     * nothing.
     *
     * @param string $id `id` of the hidden input.
     * @param string $value Chip values joined by the separator.
     *
     * @return string Hidden input markup that submits the chip values.
     */
    protected function renderHiddenInput(string $id, string $value): string
    {
        $hiddenOptions = [
            'id' => $id,
            'disabled' => $this->options['disabled'] ?? null,
            'form' => $this->options['form'] ?? null,
        ];

        if ($this->model instanceof Model && is_string($this->attribute)) {
            return Html::activeHiddenInput($this->model, $this->attribute, [...$hiddenOptions, 'value' => $value]);
        }

        return Html::hiddenInput((string) $this->name, $value, $hiddenOptions);
    }

    /**
     * Returns the `data-bs-*` value of a boolean option, or `null` when the option is not set.
     *
     * @param bool|null $value Boolean option, or `null` when it is unset.
     *
     * @return string|null `true` or `false` as a `string`, or `null` for an unset option.
     */
    private static function booleanAttribute(bool|null $value): string|null
    {
        return $value === null ? null : ($value ? 'true' : 'false');
    }

    /**
     * Returns the initial chip values from the model attribute or {@see $value}.
     *
     * @param non-empty-string $delimiter Separator of a string value.
     *
     * @return list<string> Non-empty, trimmed chip values in their original order.
     */
    private function chipValues(string $delimiter): array
    {
        $value = $this->model instanceof Model && is_string($this->attribute)
            ? Html::getAttributeValue($this->model, $this->attribute)
            : $this->value;

        return self::splitValue($value, $delimiter);
    }

    /**
     * Returns the input `id` assigned by {@see init()}.
     *
     * @return string Input element identifier.
     */
    private function inputId(): string
    {
        /** @var string */
        return $this->options['id'];
    }

    /**
     * Splits a chip value given as an array or as a string joined by `$delimiter`.
     *
     * @param mixed $value Chip values as an array or a delimiter-separated scalar; unsupported values are ignored.
     * @param non-empty-string $delimiter Separator of the joined string.
     *
     * @return list<string> Non-empty, trimmed chip values with list indexes.
     */
    private static function splitValue(mixed $value, string $delimiter): array
    {
        $values = is_array($value) ? $value : explode($delimiter, is_scalar($value) ? (string) $value : '');
        $chips = [];

        foreach ($values as $chip) {
            $chip = is_scalar($chip) ? trim((string) $chip) : '';

            if ($chip !== '') {
                $chips[] = $chip;
            }
        }

        return $chips;
    }
}
