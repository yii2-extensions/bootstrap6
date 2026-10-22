<?php

declare(strict_types=1);

namespace yii2\extensions\bootstrap6;

use Stringable;
use Yii;
use yii\base\InvalidConfigException;
use yii2\extensions\bootstrap6\asset\BootstrapAsset;
use yii2\extensions\bootstrap6\item\AccordionItem;

use function implode;
use function is_array;

/**
 * Renders a Bootstrap accordion built on native `<details>` and `<summary>` elements.
 *
 * @see https://getbootstrap.com/docs/6.0/components/accordion/
 */
class Accordion extends Widget
{
    /**
     * Whether opening an item closes the others, through a shared `name` attribute on every `<details>` element.
     */
    public bool $autoCloseItems = true;
    /**
     * Whether to HTML-encode the item labels; the item `encode` option takes precedence.
     */
    public bool $encodeLabels = true;
    /**
     * Whether to remove borders and rounded corners (`.accordion-flush`).
     */
    public bool $flush = false;
    /**
     * Whether to separate the items (`.accordion-gap`).
     */
    public bool $gap = false;
    /**
     * Markup rendered after each item label (not encoded).
     *
     * `null` renders the {@see IconSet::$accordionChevron} default, an empty string omits the icon, and a
     * {@see \UIAwesome\Html\Svg\Svg} instance receives the `accordion-icon` class automatically.
     */
    public string|Stringable|null $icon = null;
    /**
     * Accordion items: {@see AccordionItem} instances, arrays converted through {@see AccordionItem::fromArray()}, or
     * `label => content` pairs.
     *
     * An array item accepts the following options:
     *
     * - `label`: `string|Stringable`, required, content of the `<summary>` element.
     * - `content`: `string|Stringable`, required, content of the `.accordion-body` element (not encoded).
     * - `open`: `bool`, whether the item is initially expanded.
     * - `encode`: `bool`, whether to HTML-encode the label.
     * - `options`: `array`, HTML attributes of the `<details>` element.
     * - `headerOptions`: `array`, HTML attributes of the `<summary>` element.
     * - `bodyOptions`: `array`, HTML attributes of the `.accordion-body` element.
     * - `visible`: `bool`, whether to render the item (default `true`).
     *
     * @var array<array-key, AccordionItem|array<string, mixed>|string|Stringable>
     */
    public array $items = [];
    /**
     * Whether to render the compact variant (`.accordion-sm`).
     */
    public bool $small = false;

    /**
     * Icon markup resolved once per {@see run()} and rendered by {@see renderItem()}.
     */
    private string $iconMarkup = '';

    /**
     * Renders the accordion with its visible items and registers its stylesheet.
     *
     * @throws InvalidConfigException if an item lacks its `label` or `content` option.
     *
     * @return string the rendered accordion markup.
     */
    public function run(): string
    {
        BootstrapAsset::register($this->getView());

        $icon = $this->icon ?? Yii::createObject(IconSet::class)->accordionChevron;
        $this->iconMarkup = IconSet::render($icon, 'accordion-icon');

        $options = $this->options;

        Html::addCssClass($options, ['widget' => 'accordion']);

        if ($this->flush) {
            Html::addCssClass($options, ['flush' => 'accordion-flush']);
        }

        if ($this->small) {
            Html::addCssClass($options, ['size' => 'accordion-sm']);
        }

        if ($this->gap) {
            Html::addCssClass($options, ['gap' => 'accordion-gap']);
        }

        return Html::tag(
            'div',
            "\n" . $this->renderItems() . "\n",
            $options,
        );
    }

    /**
     * Renders a single `<details>` item.
     *
     * @param AccordionItem $item Accordion item to render.
     *
     * @return string the rendered `<details>` item.
     */
    protected function renderItem(AccordionItem $item): string
    {
        $options = $item->options;
        $headerOptions = $item->headerOptions;
        $bodyOptions = $item->bodyOptions;

        Html::addCssClass($options, ['widget' => 'accordion-item']);
        Html::addCssClass($headerOptions, ['widget' => 'accordion-header']);
        Html::addCssClass($bodyOptions, ['widget' => 'accordion-body']);

        if ($this->autoCloseItems) {
            $options['name'] ??= $this->elementId();
        }

        if ($item->open) {
            $options['open'] = true;
        }

        $encode = $item->encode ?? $this->encodeLabels;
        $summary = ($encode
            ? Html::encode($item->label) : $item->label) . ($this->iconMarkup === ''
            ? '' : "\n{$this->iconMarkup}");

        return Html::tag(
            'details',
            "\n"
            . Html::tag('summary', $summary, $headerOptions)
            . "\n"
            . Html::tag('div', $item->content, $bodyOptions) . "\n",
            $options,
        );
    }

    /**
     * Renders the visible items separated by line breaks.
     *
     * @throws InvalidConfigException if an item lacks its `label` or `content` option.
     *
     * @return string the rendered accordion items.
     */
    protected function renderItems(): string
    {
        $items = [];

        foreach ($this->normalizeItems() as $item) {
            if ($item->visible) {
                $items[] = $this->renderItem($item);
            }
        }

        return implode("\n", $items);
    }

    /**
     * Returns the `id` of the accordion element.
     *
     * @return string the `id` of the accordion element.
     */
    private function elementId(): string
    {
        /** @var string */
        return $this->options['id'];
    }

    /**
     * Converts {@see $items} into value objects: arrays through {@see AccordionItem::fromArray()}, and
     * `label => content` pairs into an item with that label and content.
     *
     * @throws InvalidConfigException if an item lacks its `label` or `content` option.
     *
     * @return list<AccordionItem> the normalized accordion items.
     */
    private function normalizeItems(): array
    {
        $items = [];

        foreach ($this->items as $key => $item) {
            $items[] = match (true) {
                $item instanceof AccordionItem => $item,
                is_array($item) => AccordionItem::fromArray($item),
                default => AccordionItem::fromArray(['label' => $key, 'content' => $item]),
            };
        }

        return $items;
    }
}
