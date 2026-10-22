<?php

declare(strict_types=1);

namespace yii2\extensions\bootstrap6;

use Stringable;
use yii\base\{InvalidArgumentException, InvalidConfigException};
use yii2\extensions\bootstrap6\asset\BootstrapAsset;
use yii2\extensions\bootstrap6\enum\Theme;
use yii2\extensions\bootstrap6\exception\Message;
use yii2\extensions\bootstrap6\item\ProgressBar;

use function array_map;
use function implode;
use function round;

/**
 * Renders a Bootstrap progress bar, or a stacked progress bar when {@see $bars} is set.
 *
 * The `.progress` element carries `role="progressbar"` and the `aria-value*` attributes; in stacked mode each segment
 * is a `.progress` element sized by its width inside a `.progress-stacked` container.
 *
 * @see https://getbootstrap.com/docs/6.0/components/progress/
 */
class Progress extends Widget
{
    /**
     * Whether to animate the stripes (`progress-bar-animated`) of the single bar.
     */
    public bool $animated = false;
    /**
     * Accessible name of the single bar rendered as `aria-label`, or `null` to omit it.
     */
    public string|null $ariaLabel = null;
    /**
     * HTML attributes of the single `.progress-bar` tag.
     *
     * @var array<string, mixed>
     */
    public array $barOptions = [];
    /**
     * Segments of a stacked progress bar: {@see ProgressBar} instances, or arrays converted through
     * {@see ProgressBar::fromArray()}; when not empty, the single bar properties are ignored.
     *
     * An array segment supports the keys `percent` (required), `label`, `theme`, `striped`, `animated`, `ariaLabel`,
     * and `options` (HTML attributes of the segment `.progress-bar` tag), with the meaning of the matching properties.
     *
     * @var list<array<string, mixed>|ProgressBar>
     */
    public array $bars = [];
    /**
     * Whether to HTML-encode the bar labels.
     */
    public bool $encodeLabel = true;
    /**
     * Label of the single bar.
     */
    public string|Stringable $label = '';
    /**
     * Maximum value rendered as `aria-valuemax`.
     */
    public float|int $max = 100;
    /**
     * Minimum value rendered as `aria-valuemin`.
     */
    public float|int $min = 0;
    /**
     * Current value of the single bar, between {@see $min} and {@see $max}.
     */
    public float|int $percent = 0;
    /**
     * Whether to render stripes (`progress-bar-striped`) on the single bar.
     */
    public bool $striped = false;
    /**
     * Theme color of the single bar rendered as the `theme-{value}` class, or `null` for the default color.
     */
    public Theme|string|null $theme = null;

    /**
     * Initializes the widget and validates {@see $max} and {@see $theme}.
     *
     * Segments in {@see $bars} are converted and validated when the widget runs.
     *
     * @throws InvalidConfigException if {@see $max} is not greater than {@see $min}, or {@see $theme} is not a
     * {@see Theme} value.
     */
    public function init(): void
    {
        parent::init();

        Html::enumValue(Theme::class, $this->theme, 'theme');

        if ($this->max <= $this->min) {
            throw new InvalidConfigException(
                Message::INVALID_VALUE->getMessage((string) $this->max, 'max', "a value greater than {$this->min}"),
            );
        }
    }

    /**
     * Renders a single progress bar or a stack of progress segments.
     *
     * @throws InvalidArgumentException if a value is outside the [{@see $min}, {@see $max}] range.
     * @throws InvalidConfigException if a stacked segment has no numeric `percent`, or a bar theme is invalid.
     *
     * @return string Rendered Progress markup.
     */
    public function run(): string
    {
        BootstrapAsset::register($this->getView());

        if ($this->bars === []) {
            return $this->renderProgress(
                ProgressBar::to($this->percent, $this->label)
                    ->withTheme($this->theme)
                    ->withStriped($this->striped)
                    ->withAnimated($this->animated)
                    ->withAriaLabel($this->ariaLabel)
                    ->withOptions($this->barOptions),
                $this->options,
                false,
            );
        }

        $segments = [];

        foreach ($this->normalizeBars() as $bar) {
            $segments[] = $this->renderProgress($bar, [], true);
        }

        $options = $this->options;

        Html::addCssClass($options, 'progress-stacked');

        return Html::tag(
            'div',
            "\n" . implode("\n", $segments) . "\n",
            $options,
        );
    }

    /**
     * Renders a `.progress` element with its `.progress-bar`.
     *
     * @param ProgressBar $bar Bar to render; the single bar is built from the widget properties.
     * @param array<string, mixed> $options HTML attributes of the `.progress` tag.
     * @param bool $stacked Whether the bar is a stacked segment, sized on the `.progress` element.
     *
     * @throws InvalidArgumentException if the value is outside the [{@see $min}, {@see $max}] range.
     *
     * @return string Progress element markup with its bar.
     */
    protected function renderProgress(ProgressBar $bar, array $options, bool $stacked): string
    {
        $percent = $bar->percent;

        if ($percent < $this->min || $percent > $this->max) {
            throw new InvalidArgumentException(
                Message::INVALID_VALUE->getMessage(
                    (string) $percent,
                    'percent',
                    "a value between {$this->min} and {$this->max}",
                ),
            );
        }

        $width = ['width' => round(($percent - $this->min) / ($this->max - $this->min) * 100, 4) . '%'];
        $barOptions = $bar->options;

        Html::addCssClass($options, 'progress');
        Html::addCssClass($barOptions, 'progress-bar');
        Html::theme($barOptions, $bar->theme);

        if ($bar->striped) {
            Html::addCssClass($barOptions, 'progress-bar-striped');
        }

        if ($bar->animated) {
            Html::addCssClass($barOptions, 'progress-bar-animated');
        }

        $options['role'] ??= 'progressbar';

        if ($bar->ariaLabel !== null) {
            $options['aria-label'] = $bar->ariaLabel;
        }

        $options['aria-valuenow'] = $percent;
        $options['aria-valuemin'] = $this->min;
        $options['aria-valuemax'] = $this->max;

        if ($stacked) {
            Html::addCssStyle($options, $width);
        } else {
            Html::addCssStyle($barOptions, $width);
        }

        return Html::tag(
            'div',
            "\n" . Html::tag('div', $this->encodeLabel ? Html::encode($bar->label) : $bar->label, $barOptions) . "\n",
            $options,
        );
    }

    /**
     * Converts {@see $bars} into value objects through {@see ProgressBar::fromArray()}.
     *
     * @throws InvalidConfigException if a segment has no numeric `percent` or an invalid `theme`.
     *
     * @return list<ProgressBar> Normalized progress segments in their configured order.
     */
    private function normalizeBars(): array
    {
        return array_map(
            static fn(array|ProgressBar $bar): ProgressBar => $bar instanceof ProgressBar
                ? $bar
                : ProgressBar::fromArray($bar),
            $this->bars,
        );
    }
}
