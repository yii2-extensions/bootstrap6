<?php

declare(strict_types=1);

namespace yii2\extensions\bootstrap6;

use function implode;
use function is_string;

/**
 * Renders a Bootstrap range: a `.form-range-input` range input inside a `.form-range` wrapper, with optional tick
 * marks from a `<datalist>`.
 *
 * {@see $options} applies to the input. The wrapper receives the `id` `{id}-range`, carries the plugin `data-bs-*`
 * attributes and the client events; `changed.bs.range` fires on the input and bubbles to the wrapper.
 *
 * @see https://getbootstrap.com/docs/6.0/forms/range/
 */
class Range extends InputWidget
{
    /**
     * Whether to show the value bubble above the thumb.
     */
    public bool $bubble = false;
    /**
     * @var array<string, mixed> HTML attributes of the `.form-range` wrapper.
     */
    public array $containerOptions = [];
    /**
     * Maximum value, or `null` for the browser default (`100`).
     */
    public float|int|null $max = null;
    /**
     * Minimum value, or `null` for the browser default (`0`).
     */
    public float|int|null $min = null;
    /**
     * Step between values (a number or `any`), or `null` for the browser default (`1`).
     */
    public float|int|string|null $step = null;
    /**
     * @var array<int|string, string|null> Tick marks as value => label; a `null` or empty label renders an unlabeled
     * tick.
     */
    public array $ticks = [];

    /**
     * Returns the `.form-range` wrapper with the range input, followed by the tick `<datalist>` when
     * {@see $ticks} is set, and registers the `Range` plugin on the wrapper.
     *
     * @return string Rendered Range markup.
     */
    public function run(): string
    {
        $original = $this->options;

        $id = $this->inputId();
        $containerId = is_string($this->containerOptions['id'] ?? null) ? $this->containerOptions['id'] : "{$id}-range";
        $containerOptions = [...$this->containerOptions, 'id' => $containerId];

        Html::addCssClass($containerOptions, 'form-range');

        $containerOptions['data-bs-bubble'] = $this->bubble;

        Html::addCssClass($this->options, 'form-range-input');

        foreach (['min' => $this->min, 'max' => $this->max, 'step' => $this->step] as $name => $value) {
            if ($value !== null) {
                $this->options[$name] = $value;
            }
        }

        if ($this->ticks !== []) {
            $this->options['list'] = "{$id}-ticks";
        }

        $html = Html::tag('div', "\n" . $this->renderInputHtml('range') . "\n", $containerOptions);

        if ($this->ticks !== []) {
            $html .= "\n" . $this->renderTicks("{$id}-ticks");
        }

        $this->options['id'] = $containerId;

        $this->registerPlugin('Range', true);

        $this->options = $original;

        return $html;
    }

    /**
     * Renders the `<datalist>` with the tick marks.
     *
     * @param string $id `id` of the datalist.
     *
     * @return string Datalist markup with the configured ticks.
     */
    protected function renderTicks(string $id): string
    {
        $options = [];

        foreach ($this->ticks as $value => $label) {
            $options[] = Html::tag(
                'option',
                '',
                ['value' => $value, 'label' => $label === '' ? null : $label],
            );
        }

        return Html::tag(
            'datalist',
            "\n" . implode("\n", $options) . "\n",
            ['id' => $id],
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
}
