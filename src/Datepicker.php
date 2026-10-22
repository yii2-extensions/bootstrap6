<?php

declare(strict_types=1);

namespace yii2\extensions\bootstrap6;

use yii\base\{InvalidConfigException, Model};
use yii\helpers\Json;
use yii\web\View;
use yii2\extensions\bootstrap6\asset\BootstrapPluginAsset;
use yii2\extensions\bootstrap6\exception\Message;

use function array_key_exists;
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
     * @var list<string> Preselected dates in `YYYY-MM-DD` format, taking precedence over the initial input value.
     */
    public array $selectedDates = [];
    /**
     * Selection mode (`single`, `multiple`, `multiple-ranged`), or `null` for the plugin default (`single`).
     */
    public string|null $selectionMode = null;

    /**
     * Initializes the widget and validates the closed option sets.
     *
     * @throws InvalidConfigException if neither a model attribute nor a name is set, or {@see $selectionMode},
     * {@see $placement}, or {@see $firstWeekday} is invalid.
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

    /**
     * Returns the text input bound to the popup calendar, or the inline calendar when {@see $inline} is `true`,
     * and registers the `Datepicker` plugin.
     *
     * @return string Rendered Datepicker markup.
     */
    public function run(): string
    {
        if ($this->inline) {
            return $this->renderInline();
        }

        $options = $this->options;

        Html::addCssClass($options, 'form-control');

        $options['autocomplete'] ??= 'off';

        $this->options = [...$options, ...$this->dataAttributes($this->selectedDates)];

        $this->registerPlugin('Datepicker', $this->selectedDates !== []);
        $this->registerDataApiSelection();

        return $this->renderInputHtml('text');
    }

    /**
     * Returns the `data-bs-*` attributes of the plugin element.
     *
     * @param list<string> $selectedDates Preselected dates.
     *
     * @return array<string, mixed> Plugin data attributes, including preselected dates when present.
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
     * Returns the plugin initialization, followed by a `setSelectedDates()` call for a popup datepicker with
     * preselected dates.
     *
     * The `selectedDates` client option takes precedence over {@see $selectedDates}.
     *
     * @param string $name Plugin class exported by the bundle.
     * @param string $element JavaScript expression selecting the plugin element.
     * @param string $options JSON-encoded constructor options.
     *
     * @return string Plugin initialization JavaScript with popup preselection when applicable.
     */
    protected function pluginInitialization(string $name, string $element, string $options): string
    {
        if (
            $name !== 'Datepicker'
            || $this->inline
            || ($this->selectedDates === [] && !array_key_exists('selectedDates', (array) $this->clientOptions))
        ) {
            return parent::pluginInitialization($name, $element, $options);
        }

        $selectedDates = Json::htmlEncode($this->selectedDates);

        return <<<JS
        const options = {$options};
        const instance = bootstrap.{$name}.getOrCreateInstance({$element}, options);
        instance.setSelectedDates(options.selectedDates ?? {$selectedDates});
        JS;
    }

    /**
     * Renders the inline calendar container with the hidden input that receives the selected dates.
     *
     * @return string Inline calendar markup with its hidden input.
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
        $calendarId = "{$containerId}-calendar";
        $containerOptions = [
            ...$this->containerOptions,
            'id' => $containerId,
            ...$this->dataAttributes($selectedDates),
            'data-bs-position-element' => '#' . Html::escapeCssIdentifier($calendarId),
        ];
        $input = $this->renderInputHtml('hidden');
        $calendar = Html::tag('div', '', ['id' => $calendarId]);

        $this->options['id'] = $containerId;

        $this->registerPlugin('Datepicker', true);

        $this->options = $options;

        return Html::tag(
            'div',
            "\n{$input}\n{$calendar}\n",
            $containerOptions,
        );
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
     * Applies explicit popup preselection once the data API creates an instance, without creating one.
     */
    private function registerDataApiSelection(): void
    {
        if ($this->clientOptions !== false || $this->selectedDates === []) {
            return;
        }

        $view = $this->getView();
        $bundle = BootstrapPluginAsset::register($view);
        $id = Json::htmlEncode($this->inputId());
        $selectedDates = Json::htmlEncode($this->selectedDates);

        $code = <<<JS
        const input = document.getElementById({$id});
        const sync = () => {
            const instance = bootstrap.Datepicker.getInstance(input);
            if (instance) {
                instance.setSelectedDates({$selectedDates});
                input.removeEventListener('show.bs.datepicker', sync);
                document.removeEventListener('DOMContentLoaded', sync);
            }
        };
        input.addEventListener('show.bs.datepicker', sync);
        document.addEventListener('DOMContentLoaded', sync, {once: true});
        sync();
        JS;

        $view->registerJs($bundle->importJs($view, $code), View::POS_END, "{$this->inputId()}-selection");
    }
}
