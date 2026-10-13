<?php

declare(strict_types=1);

namespace yii2\extensions\bootstrap6;

use yii\base\{InvalidConfigException, Model};
use yii2\extensions\bootstrap6\exception\Message;

use function explode;
use function implode;
use function in_array;
use function is_string;

/**
 * Renders a Bootstrap datepicker as a text input, or as an inline calendar bound to a hidden input.
 *
 * Every option is emitted as the `data-bs-*` attribute read by the plugin. In inline mode the calendar container
 * carries the attributes and receives the `id` `{id}-datepicker`, and the selected dates default to the
 * comma-separated value of the hidden input.
 *
 * @see https://getbootstrap.com/docs/6.0/forms/datepicker/
 */
class Datepicker extends InputWidget
{
    /**
     * Allowed values of {@see $placement}.
     *
     * @var list<string>
     */
    protected const array PLACEMENTS = ['left', 'center', 'right', 'auto'];
    /**
     * Allowed values of {@see $selectionMode}.
     *
     * @var list<string>
     */
    protected const array SELECTION_MODES = ['single', 'multiple', 'multiple-ranged'];

    /**
     * @var array<string, mixed> HTML attributes of the inline calendar container.
     */
    public array $containerOptions = [];
    /**
     * @var array<string, mixed>|null `Intl.DateTimeFormat` options used to display the dates, or `null` for the
     * locale default.
     */
    public array|null $dateFormat = null;
    /**
     * Latest selectable date in `YYYY-MM-DD` format, or `null` for no limit.
     */
    public string|null $dateMax = null;
    /**
     * Earliest selectable date in `YYYY-MM-DD` format, or `null` for no limit.
     */
    public string|null $dateMin = null;
    /**
     * Number of months displayed side by side, or `null` for the plugin default (`1`).
     */
    public int|null $displayMonthsCount = null;
    /**
     * First day of the week from `0` (Sunday) to `6` (Saturday), or `null` for the plugin default (`1`).
     */
    public int|null $firstWeekday = null;
    /**
     * Whether to render the calendar inline instead of a popup bound to a text input.
     */
    public bool $inline = false;
    /**
     * Locale used to format the dates, for example `de-DE`, or `null` for the browser locale.
     */
    public string|null $locale = null;
    /**
     * Popup position relative to the input (`left`, `center`, `right`, `auto`), or `null` for the plugin default.
     */
    public string|null $placement = null;
    /**
     * @var list<string> Preselected dates in `YYYY-MM-DD` format.
     */
    public array $selectedDates = [];
    /**
     * Selection mode (`single`, `multiple`, `multiple-ranged`), or `null` for the plugin default (`single`).
     */
    public string|null $selectionMode = null;

    /**
     * Initializes the widget and validates the closed option sets.
     *
     * @throws InvalidConfigException if {@see $selectionMode}, {@see $placement} or {@see $firstWeekday} is invalid.
     */
    public function init(): void
    {
        parent::init();

        if ($this->selectionMode !== null && !in_array($this->selectionMode, static::SELECTION_MODES, true)) {
            throw new InvalidConfigException(
                Message::INVALID_VALUE->getMessage(
                    $this->selectionMode,
                    'selectionMode',
                    implode(', ', static::SELECTION_MODES),
                ),
            );
        }

        if ($this->placement !== null && !in_array($this->placement, static::PLACEMENTS, true)) {
            throw new InvalidConfigException(
                Message::INVALID_VALUE->getMessage($this->placement, 'placement', implode(', ', static::PLACEMENTS)),
            );
        }

        if ($this->firstWeekday !== null && ($this->firstWeekday < 0 || $this->firstWeekday > 6)) {
            throw new InvalidConfigException(
                Message::INVALID_VALUE->getMessage($this->firstWeekday, 'firstWeekday', '0, 1, 2, 3, 4, 5, 6'),
            );
        }
    }

    public function run(): string
    {
        if ($this->inline) {
            return $this->renderInline();
        }

        $options = $this->options;

        Html::addCssClass($options, 'form-control');

        $options['autocomplete'] ??= 'off';

        $this->options = [...$options, ...$this->dataAttributes($this->selectedDates)];

        $this->registerPlugin('Datepicker');

        return $this->renderInputHtml('text');
    }

    /**
     * Returns the `data-bs-*` attributes of the plugin element.
     *
     * @param list<string> $selectedDates Preselected dates.
     *
     * @return array<string, mixed>
     */
    protected function dataAttributes(array $selectedDates): array
    {
        return [
            'data-bs-toggle' => 'datepicker',
            'data-bs-inline' => $this->inline ? 'true' : null,
            'data-bs-selection-mode' => $this->selectionMode,
            'data-bs-date-format' => $this->dateFormat,
            'data-bs-date-min' => $this->dateMin,
            'data-bs-date-max' => $this->dateMax,
            'data-bs-locale' => $this->locale,
            'data-bs-first-weekday' => $this->firstWeekday,
            'data-bs-display-months-count' => $this->displayMonthsCount,
            'data-bs-placement' => $this->placement,
            'data-bs-selected-dates' => $selectedDates === [] ? null : $selectedDates,
        ];
    }

    /**
     * Renders the inline calendar container with the hidden input that receives the selected dates.
     */
    protected function renderInline(): string
    {
        $options = $this->options;
        $value = $this->model instanceof Model && is_string($this->attribute)
            ? Html::getAttributeValue($this->model, $this->attribute)
            : $this->value;
        $selectedDates = $this->selectedDates;

        if ($selectedDates === [] && is_string($value) && $value !== '') {
            $selectedDates = explode(',', $value);
        }

        $id = $this->inputId();
        $containerId = is_string($this->containerOptions['id'] ?? null)
            ? $this->containerOptions['id']
            : "{$id}-datepicker";
        $containerOptions = [
            ...$this->containerOptions,
            'id' => $containerId,
            ...$this->dataAttributes($selectedDates),
        ];
        $input = $this->renderInputHtml('hidden');

        $this->options['id'] = $containerId;

        $this->registerPlugin('Datepicker');

        $this->options = $options;

        return Html::tag(
            'div',
            "\n{$input}\n",
            $containerOptions,
        );
    }

    /**
     * Returns the input `id` assigned by {@see init()}.
     */
    private function inputId(): string
    {
        /** @var string */
        return $this->options['id'];
    }
}
