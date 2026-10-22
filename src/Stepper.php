<?php

declare(strict_types=1);

namespace yii2\extensions\bootstrap6;

use yii\base\InvalidConfigException;
use yii\helpers\Url;
use yii2\extensions\bootstrap6\asset\BootstrapAsset;
use yii2\extensions\bootstrap6\enum\Breakpoint;
use yii2\extensions\bootstrap6\exception\Message;
use yii2\extensions\bootstrap6\item\StepItem;

use function array_filter;
use function array_map;
use function implode;
use function is_bool;

/**
 * Renders a Bootstrap stepper as an ordered list of steps, or as a `<div>` of links when a step has a `url`.
 *
 * A responsive {@see $horizontal} breakpoint wraps the stepper in the `.contains-inline` container that the container
 * queries require.
 *
 * @see https://getbootstrap.com/docs/6.0/components/stepper/
 */
class Stepper extends Widget
{
    /**
     * Whether to HTML-encode the item labels and descriptions; an item `encode` key overrides it.
     */
    public bool $encodeLabels = true;
    /**
     * Horizontal layout: `true` for every width, a breakpoint (for example {@see Breakpoint::MD}) for that width and
     * up, or `false` for the vertical layout.
     */
    public Breakpoint|bool|string $horizontal = false;
    /**
     * Steps in display order: {@see StepItem} instances, or arrays converted through {@see StepItem::fromArray()}.
     *
     * An array item accepts the following options:
     *
     * - `label`: `string|Stringable`, required.
     * - `description`: `string|Stringable`, secondary text rendered under the label.
     * - `active`: `bool`, whether the step is completed or current.
     * - `theme`: `Theme|string`, theme color rendered as the `theme-{value}` class.
     * - `url`: `array|string`, processed by {@see \yii\helpers\Url::to()}; any step with a URL renders links.
     * - `encode`: `bool`, whether to HTML-encode the label and description.
     * - `options`: `array`, HTML attributes of the `.stepper-item` element.
     *
     * @var list<array<string, mixed>|StepItem>
     */
    public array $items = [];
    /**
     * Steps normalized by {@see init()}.
     *
     * @var list<StepItem>
     */
    private array $steps = [];

    /**
     * Initializes the widget, normalizes {@see $items}, and validates {@see $horizontal}.
     *
     * @throws InvalidConfigException if {@see $items} is empty, an item has no `label` or an invalid `theme`, or
     * {@see $horizontal} is not a breakpoint.
     */
    public function init(): void
    {
        parent::init();

        if ($this->items === []) {
            throw new InvalidConfigException(
                Message::INVALID_ITEMS->getMessage(),
            );
        }

        $this->steps = $this->normalizeItems();

        if (!is_bool($this->horizontal)) {
            Html::enumValue(Breakpoint::class, $this->horizontal, 'horizontal');
        }
    }

    /**
     * Returns the stepper markup, wrapped in a `.contains-inline` container when {@see $horizontal} is a
     * breakpoint.
     *
     * @throws InvalidConfigException if {@see $horizontal} is a `string` that is not a {@see Breakpoint} value.
     *
     * @return string Rendered Stepper markup.
     */
    public function run(): string
    {
        BootstrapAsset::register($this->getView());

        $options = $this->options;
        $links = array_filter($this->steps, static fn(StepItem $item): bool => $item->url !== null) !== [];
        $breakpoint = is_bool($this->horizontal)
            ? null
            : Html::enumValue(Breakpoint::class, $this->horizontal, 'horizontal');

        Html::addCssClass($options, 'stepper');

        if ($this->horizontal === true) {
            Html::addCssClass($options, 'stepper-horizontal');
        }

        if ($breakpoint !== null) {
            Html::addCssClass($options, "{$breakpoint}:stepper-horizontal");
        }

        $items = array_map(
            fn(StepItem $item): string => $this->renderItem($item, $links),
            $this->steps,
        );
        $html = Html::tag($links ? 'div' : 'ol', "\n" . implode("\n", $items) . "\n", $options);

        return $breakpoint !== null
            ? Html::tag(
                'div',
                "\n{$html}\n",
                ['class' => 'contains-inline']
            )
            : $html;
    }

    /**
     * Renders a step.
     *
     * @param StepItem $item Step content, state, and attributes to render.
     * @param bool $links Whether the stepper renders links, so steps without `url` use a `<div>` instead of `<li>`.
     *
     * @return string Rendered item markup.
     */
    protected function renderItem(StepItem $item, bool $links): string
    {
        $options = $item->options;
        $encode = $item->encode ?? $this->encodeLabels;
        $content = $encode ? Html::encode($item->label) : $item->label;

        Html::addCssClass($options, 'stepper-item');

        if ($item->description !== null) {
            Html::addCssClass($options, 'align-items-start');

            $content = "\n" . Html::tag(
                'div',
                "\n" . Html::tag('div', $content, ['class' => 'fw-semibold'])
                . "\n" . Html::tag(
                    'small',
                    $encode ? Html::encode($item->description) : $item->description,
                    ['class' => 'fg-3'],
                )
                . "\n",
                ['class' => 'pt-1'],
            ) . "\n";
        }

        if ($item->active) {
            Html::addCssClass($options, 'active');
        }

        Html::theme($options, $item->theme);

        if ($item->url !== null) {
            return Html::a($content, Url::to($item->url), $options);
        }

        return Html::tag(
            $links ? 'div' : 'li',
            $content,
            $options,
        );
    }

    /**
     * Converts {@see $items} into value objects through {@see StepItem::fromArray()}.
     *
     * @throws InvalidConfigException if an item has no `label` or an invalid `theme`.
     *
     * @return list<StepItem> Normalized steps in their configured order.
     */
    private function normalizeItems(): array
    {
        return array_map(
            static fn(array|StepItem $item): StepItem => $item instanceof StepItem ? $item : StepItem::fromArray($item),
            $this->items,
        );
    }
}
