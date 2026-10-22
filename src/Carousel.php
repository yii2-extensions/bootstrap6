<?php

declare(strict_types=1);

namespace yii2\extensions\bootstrap6;

use Stringable;
use Yii;
use yii\base\InvalidConfigException;
use yii2\extensions\bootstrap6\enum\CarouselEnds;
use yii2\extensions\bootstrap6\exception\Message;
use yii2\extensions\bootstrap6\item\CarouselItem;

use function count;
use function implode;
use function is_array;
use function strtr;

/**
 * Renders a Bootstrap carousel with optional previous/next controls, play/pause control, and indicators.
 *
 * The plugin is always initialized so that the indicators follow the visible slide.
 *
 * @see https://getbootstrap.com/docs/6.0/components/carousel/
 */
class Carousel extends Widget
{
    /**
     * Whether to cycle the slides automatically on page load (`data-bs-autoplay="true"`).
     */
    public bool $autoplay = false;
    /**
     * Visually hidden labels of the previous and next controls, as `[previous, next]`, or `false` to omit the controls.
     *
     * Missing labels default to the translated `Previous` and `Next`.
     *
     * @var array<int, string>|false
     */
    public array|false $controls = [];
    /**
     * HTML attributes of the element wrapping the controls and the indicators.
     *
     * @var array<string, mixed>
     */
    public array $controlsOptions = [];
    /**
     * Whether to fade between slides instead of sliding (`.carousel-fade`).
     */
    public bool $crossfade = false;
    /**
     * Behavior at the first and last slides (`data-bs-ends`), or `null` for the default `loop`.
     */
    public CarouselEnds|string|null $ends = null;
    /**
     * Accessible label of each indicator; `{number}` is replaced with the slide number starting at `1`.
     */
    public string|Stringable $indicatorLabel = 'Slide {number}';
    /**
     * Delay in milliseconds between slides when cycling (`data-bs-interval`), or `null` for the default.
     */
    public int|null $interval = null;
    /**
     * Carousel slides: {@see CarouselItem} instances, arrays converted through {@see CarouselItem::fromArray()}, or the
     * slide content (not encoded).
     *
     * An array item accepts the following options:
     *
     * - `content`: `string|Stringable`, required, slide content (not encoded).
     * - `options`: `array`, HTML attributes of the `.carousel-item` element.
     *
     * The first slide is the active one.
     *
     * @var array<array-key, array<string, mixed>|CarouselItem|string|Stringable>
     */
    public array $items = [];
    /**
     * Whether the carousel reacts to keyboard events (`data-bs-keyboard`), or `null` for the default.
     */
    public bool|null $keyboard = null;
    /**
     * Whether to overlay the controls on the slides (`.carousel-overlay`).
     */
    public bool $overlay = false;
    /**
     * Pause behavior on hover (`data-bs-pause`): `hover`, `false` to never pause, or `null` for the default.
     */
    public string|false|null $pause = null;
    /**
     * Whether to render the play/pause control (`.carousel-control-play-pause`).
     */
    public bool $playPause = false;
    /**
     * Whether to render the slide indicators.
     */
    public bool $showIndicators = true;

    /**
     * Initializes the widget and validates {@see $ends}.
     *
     * @throws InvalidConfigException if {@see $ends} is not a {@see CarouselEnds} value.
     */
    public function init(): void
    {
        parent::init();

        Html::enumValue(CarouselEnds::class, $this->ends, 'ends');
    }

    /**
     * Renders the carousel slides and enabled controls, and registers the plugin.
     *
     * @throws InvalidConfigException if {@see $items} is empty, an item lacks its content, or {@see $ends} is
     * invalid.
     *
     * @return string Rendered Carousel markup.
     */
    public function run(): string
    {
        if ($this->items === []) {
            throw new InvalidConfigException(
                Message::INVALID_ITEMS->getMessage(),
            );
        }

        $items = $this->renderItems();
        $controls = $this->renderControls();

        $this->registerPlugin('Carousel', true);

        return Html::tag(
            'div',
            "\n{$items}\n" . ($controls === '' ? '' : "$controls\n"),
            $this->carouselOptions(),
        );
    }

    /**
     * Returns the HTML attributes of the `.carousel` element.
     *
     * @throws InvalidConfigException if {@see $ends} is not a {@see CarouselEnds} value.
     *
     * @return array<mixed> Carousel attributes with its classes and plugin data options.
     */
    protected function carouselOptions(): array
    {
        $options = $this->options;
        $data = is_array($options['data'] ?? null) ? $options['data'] : [];

        Html::addCssClass($options, ['widget' => $this->crossfade ? 'carousel carousel-fade' : 'carousel slide']);

        if ($this->overlay) {
            Html::addCssClass($options, ['overlay' => 'carousel-overlay']);
        }

        if ($this->autoplay) {
            $data['bs-autoplay'] = 'true';
        }

        $ends = Html::enumValue(CarouselEnds::class, $this->ends, 'ends');

        if ($ends !== null) {
            $data['bs-ends'] = $ends;
        }

        if ($this->interval !== null) {
            $data['bs-interval'] = $this->interval;
        }

        if ($this->pause !== null) {
            $data['bs-pause'] = $this->pause === false ? 'false' : $this->pause;
        }

        if ($this->keyboard !== null) {
            $data['bs-keyboard'] = $this->keyboard ? 'true' : 'false';
        }

        $options['data'] = $data;

        return $options;
    }

    /**
     * Renders the previous/next and play/pause controls with the indicators, or an empty string when all are disabled.
     *
     * @return string Enabled control markup, or an empty `string` when all controls are disabled.
     */
    protected function renderControls(): string
    {
        $buttons = [];

        if ($this->playPause) {
            $buttons[] = $this->renderPlayPause();
        }

        if ($this->controls !== false) {
            $buttons[] = $this->renderControl(
                'prev',
                $this->controls[0] ?? Yii::t(Bootstrap::TRANSLATION_CATEGORY, 'Previous'),
            );
            $buttons[] = $this->renderControl(
                'next',
                $this->controls[1] ?? Yii::t(Bootstrap::TRANSLATION_CATEGORY, 'Next'),
            );
        }

        $parts = [];

        if ($buttons !== []) {
            $parts[] = Html::tag('div', "\n" . implode("\n", $buttons) . "\n");
        }

        if ($this->showIndicators) {
            $parts[] = $this->renderIndicators();
        }

        if ($parts === []) {
            return '';
        }

        $options = $this->controlsOptions;

        $class = $this->overlay ? 'carousel-overlay-controls' : 'd-flex justify-content-between align-items-center';

        Html::addCssClass($options, ['widget' => $class]);

        return Html::tag(
            'div',
            "\n" . implode("\n", $parts) . "\n",
            $options,
        );
    }

    /**
     * Renders the `.carousel-indicators` element.
     *
     * @return string Indicator container markup with one button per slide.
     */
    protected function renderIndicators(): string
    {
        $indicators = [];

        for ($index = 0, $count = count($this->items); $index < $count; $index++) {
            $options = [
                'type' => 'button',
                'data' => [
                    'bs-target' => '#' . $this->elementId(),
                    'bs-slide-to' => $index,
                ],
            ];

            if ($index === 0) {
                $options['class'] = 'active';
                $options['aria-current'] = 'true';
            }

            $options['aria-label'] = strtr((string) $this->indicatorLabel, ['{number}' => $index + 1]);

            $indicators[] = Html::tag('button', '', $options);
        }

        return Html::tag(
            'div',
            "\n" . implode("\n", $indicators) . "\n",
            ['class' => 'carousel-indicators'],
        );
    }

    /**
     * Renders a single `.carousel-item` element.
     *
     * @param CarouselItem $item Slide content and attributes to render.
     * @param int $index Zero-based position of the item.
     *
     * @return string Rendered item markup.
     */
    protected function renderItem(CarouselItem $item, int $index): string
    {
        $options = $item->options;

        Html::addCssClass($options, ['widget' => 'carousel-item']);

        if ($index === 0) {
            Html::addCssClass($options, ['active' => 'active']);
        }

        return Html::tag(
            'div',
            $item->content,
            $options,
        );
    }

    /**
     * Renders the `.carousel-inner` element with every slide.
     *
     * @throws InvalidConfigException if an item lacks its `content` option.
     *
     * @return string Carousel inner container markup with every slide.
     */
    protected function renderItems(): string
    {
        $items = [];

        foreach ($this->normalizeItems() as $index => $item) {
            $items[] = $this->renderItem($item, $index);
        }

        return Html::tag(
            'div',
            "\n" . implode("\n", $items) . "\n",
            ['class' => 'carousel-inner'],
        );
    }

    /**
     * Returns the classes of the control buttons.
     *
     * @return list<string> CSS classes applied to carousel controls.
     */
    private function buttonClass(): array
    {
        return $this->overlay
            ? ['btn-icon', 'btn-sm', 'btn-subtle', 'theme-secondary']
            : ['btn-icon', 'btn-sm'];
    }

    /**
     * Returns the `id` of the carousel element.
     *
     * @return string Widget element identifier.
     */
    private function elementId(): string
    {
        /** @var string */
        return $this->options['id'];
    }

    /**
     * Converts {@see $items} into a list of value objects: arrays through {@see CarouselItem::fromArray()}, and slide
     * contents into a slide with that content.
     *
     * @throws InvalidConfigException if an item lacks its `content` option.
     *
     * @return list<CarouselItem> Normalized slides in rendering order.
     */
    private function normalizeItems(): array
    {
        $items = [];

        foreach ($this->items as $item) {
            $items[] = match (true) {
                $item instanceof CarouselItem => $item,
                is_array($item) => CarouselItem::fromArray($item),
                default => CarouselItem::to($item),
            };
        }

        return $items;
    }

    /**
     * Renders a previous or next control.
     *
     * @param string $direction Either `prev` or `next`.
     * @param string $label Visually hidden label (HTML-encoded).
     *
     * @return string Previous or next control markup.
     */
    private function renderControl(string $direction, string $label): string
    {
        return Html::button(
            "\n" . Html::tag('span', '', ['class' => "carousel-icon-{$direction}", 'aria-hidden' => 'true'])
            . "\n" . Html::tag('span', Html::encode($label), ['class' => 'visually-hidden']) . "\n",
            [
                'class' => $this->buttonClass(),
                'data' => [
                    'bs-target' => '#' . $this->elementId(),
                    'bs-slide' => $direction,
                ],
            ],
        );
    }

    /**
     * Renders the play/pause control with translated accessible labels.
     *
     * @return string Play/pause button markup with accessible labels.
     */
    private function renderPlayPause(): string
    {
        $pause = Yii::t(Bootstrap::TRANSLATION_CATEGORY, 'Pause');
        $play = Yii::t(Bootstrap::TRANSLATION_CATEGORY, 'Play');

        return Html::button(
            "\n" . Html::tag('span', '', ['class' => 'carousel-icon-pause', 'aria-hidden' => 'true'])
            . "\n" . Html::tag('span', '', ['class' => 'carousel-icon-play', 'aria-hidden' => 'true']) . "\n",
            [
                'class' => [...$this->buttonClass(), 'carousel-control-play-pause'],
                'data' => [
                    'bs-target' => '#' . $this->elementId(),
                    'bs-pause-label' => $pause,
                    'bs-play-label' => $play,
                ],
                'aria-label' => $this->autoplay ? $pause : $play,
            ],
        );
    }
}
