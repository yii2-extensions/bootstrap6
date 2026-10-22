<?php

declare(strict_types=1);

namespace yii2\extensions\bootstrap6;

use yii\base\InvalidConfigException;
use yii2\extensions\bootstrap6\enum\{Breakpoint, Placement};

use function is_int;

/**
 * Renders a {@see Nav} inside a `.nav-overflow` wrapper that moves the items that do not fit into a "More" menu.
 *
 * The plugin initializes explicitly so navigation inserted after page load also works. When {@see $clientOptions}
 * is a non-empty array, the data toggle is omitted so the data API cannot create the instance before those options
 * are applied.
 *
 * @see https://getbootstrap.com/docs/6.0/components/nav-overflow/
 */
class NavOverflow extends Widget
{
    /**
     * Wrapper width below which every item moves into the menu: a breakpoint name or a pixel value; `null` keeps the
     * plugin default.
     */
    public Breakpoint|int|string|null $collapseBelow = null;
    /**
     * Text of the overflow toggle, `false` for an icon-only toggle, or `null` to keep the plugin default.
     */
    public string|false|null $moreText = null;
    /**
     * Configuration of the nav widget.
     *
     * @var array<string, mixed>
     */
    public array $nav = [];
    /**
     * Class of the nav widget.
     *
     * @var class-string<Nav>
     */
    public string $navClass = Nav::class;
    /**
     * Placement of the overflow menu, or `null` to keep the plugin default (`bottom-end`).
     */
    public Placement|string|null $placement = null;

    /**
     * Initializes the widget and validates {@see $collapseBelow} and {@see $placement}.
     *
     * @throws InvalidConfigException if {@see $collapseBelow} is a `string` that is not a {@see Breakpoint} value, or
     * {@see $placement} is not a {@see Placement} value, optionally breakpoint-prefixed.
     */
    public function init(): void
    {
        parent::init();

        if (!is_int($this->collapseBelow)) {
            Html::enumValue(Breakpoint::class, $this->collapseBelow, 'collapseBelow');
        }

        Html::enumValue(Placement::class, $this->placement, 'placement', true);
    }

    /**
     * Renders the navigation wrapper and registers the `NavOverflow` plugin.
     *
     * @throws InvalidConfigException if a nav item lacks a required label, or the overflow or nested nav
     * configuration is invalid.
     *
     * @return string Rendered NavOverflow markup.
     */
    public function run(): string
    {
        $options = $this->options;

        Html::addCssClass($options, ['widget' => 'nav-overflow']);

        if ($this->clientOptions === false || $this->clientOptions === []) {
            $options['data-bs-toggle'] = 'nav-overflow';
        }

        $collapseBelow = is_int($this->collapseBelow)
            ? $this->collapseBelow
            : Html::enumValue(Breakpoint::class, $this->collapseBelow, 'collapseBelow');

        if ($collapseBelow !== null) {
            $options['data-bs-collapse-below'] = $collapseBelow;
        }

        if ($this->moreText !== null) {
            $options['data-bs-more-text'] = $this->moreText === false ? 'false' : $this->moreText;
        }

        $placement = Html::enumValue(Placement::class, $this->placement, 'placement', true);

        if ($placement !== null) {
            $options['data-bs-menu-placement'] = $placement;
        }

        $nav = $this->nav;
        $nav['view'] = $this->getView();

        $html = Html::tag('div', $this->navClass::widget($nav), $options);

        $this->registerPlugin('NavOverflow', true);

        return $html;
    }
}
