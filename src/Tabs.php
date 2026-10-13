<?php

declare(strict_types=1);

namespace yii2\extensions\bootstrap6;

use yii\base\InvalidConfigException;
use yii\helpers\ArrayHelper;
use yii2\extensions\bootstrap6\enum\NavStyle;
use yii2\extensions\bootstrap6\item\{MenuItem, TabItem};

use function implode;
use function in_array;
use function is_array;
use function is_string;

/**
 * Renders a nav of tab buttons and the matching `.tab-pane` panels for the Bootstrap Tab plugin.
 *
 * When no item is active, the first visible, enabled pane is activated. Tab plugin events bubble up to the nav
 * element, so {@see $clientEvents} such as `shown.bs.tab` can be attached to it.
 *
 * @see https://getbootstrap.com/docs/6.0/components/tab/
 */
class Tabs extends Widget
{
    /**
     * Whether to HTML-encode the item labels; the item `encode` option takes precedence.
     */
    public bool $encodeLabels = true;
    /**
     * HTML attributes of every `.nav-item` element, merged with the item `headerOptions`.
     *
     * @var array<array-key, mixed>
     */
    public array $headerOptions = [];
    /**
     * HTML attributes of every `.tab-pane` element, merged with the item `options`; the `tag` option sets the tag name
     * (default `div`).
     *
     * @var array<array-key, mixed>
     */
    public array $itemOptions = [];
    /**
     * Tab items: {@see TabItem} instances, arrays converted through {@see TabItem::fromArray()}, or strings rendered
     * as is.
     *
     * An array item accepts the following options:
     *
     * - `label`: `string|Stringable`, required.
     * - `content`: `string|Stringable`, HTML content of the pane.
     * - `active`: `bool`, whether the tab and its pane, or the link tab, are active.
     * - `disabled`: `bool`, disables the tab button, or the link tab, which loses its `href`.
     * - `visible`: `bool`, whether to render the tab and its pane. Defaults to `true`.
     * - `encode`: `bool`, whether to HTML-encode the label.
     * - `headerOptions`: `array`, HTML attributes of the `.nav-item` element.
     * - `linkOptions`: `array`, HTML attributes of the tab button; its `id` defaults to `{id}-tab{n}`.
     * - `options`: `array`, HTML attributes of the pane; its `id` defaults to `{id}-pane{n}`.
     * - `url`: `array|string`, renders a plain link that takes the `active` and `disabled` state instead of a tab,
     *   processed by {@see \yii\helpers\Url::to()}.
     * - `items`: `array`, tab items rendered inside a {@see Menu}, with the same options; items with a `url` or without
     *   a `label` are {@see Menu::$items}. A nested item with its own `items` renders as a `.submenu` (its
     *   `headerOptions` apply to the `.submenu` wrapper), and an active nested tab activates the outer toggle only.
     *   The Bootstrap docs advise against menus in tabbed interfaces for accessibility reasons.
     *
     * @var array<array-key, array<array-key, mixed>|string|TabItem>
     */
    public array $items = [];
    /**
     * HTML attributes of every tab button, merged with the item `linkOptions`.
     *
     * @var array<array-key, mixed>
     */
    public array $linkOptions = [];
    /**
     * Class of the menu widget.
     *
     * @var class-string<Menu>
     */
    public string $menuClass = Menu::class;
    /**
     * Nav style, such as {@see NavStyle::PILLS}; {@see NavStyle::PILLS_VERTICAL} adds `aria-orientation="vertical"`.
     */
    public NavStyle|string $navType = NavStyle::TABS;
    /**
     * Whether to render the `.tab-content` container; set it to `false` to render the panes yourself.
     */
    public bool $renderTabContent = true;
    /**
     * HTML attributes of the `.tab-content` container.
     *
     * @var array<array-key, mixed>
     */
    public array $tabContentOptions = [];
    /**
     * Rendered tab panes.
     *
     * @var list<string>
     */
    protected array $panes = [];

    /**
     * Initializes the widget and validates {@see $navType}.
     *
     * @throws InvalidConfigException if {@see $navType} is not a {@see NavStyle} value.
     */
    public function init(): void
    {
        parent::init();

        Html::enumValue(NavStyle::class, $this->navType, 'navType');
    }

    /**
     * @throws InvalidConfigException if an item has no `label`.
     */
    public function run(): string
    {
        $options = $this->options;

        $styles = Nav::styleClasses($this->navType);

        Html::addCssClass($options, ['widget' => 'nav']);
        Html::addCssClass($options, $styles);

        $options['role'] ??= 'tablist';

        if (in_array(NavStyle::PILLS_VERTICAL->value, $styles, true)) {
            $options['aria-orientation'] ??= 'vertical';
        }

        $items = $this->normalizeItems();

        if ($this->hasActiveTab($items) === false) {
            $items = $this->activateFirstTab($items);
        }

        $headers = [];

        foreach ($items as $n => $item) {
            if (is_string($item)) {
                $headers[] = $item;
            } elseif ($item->visible) {
                $headers[] = $this->renderHeader($item, "-tab{$n}", "-pane{$n}", "-dd{$n}");
            }
        }

        $this->registerPlugin('Tab');

        return Html::tag(
            'ul',
            implode("\n", $headers),
            $options
        ) . $this->renderPanes($this->panes);
    }

    /**
     * Renders the `.tab-content` container with the panes, or an empty string when {@see $renderTabContent} is
     * `false`.
     *
     * @param list<string> $panes Rendered panes.
     */
    protected function renderPanes(array $panes): string
    {
        if ($this->renderTabContent === false) {
            return '';
        }

        $options = $this->tabContentOptions;

        Html::addCssClass($options, ['widget' => 'tab-content']);

        return "\n"
            . Html::tag(
                'div',
                implode("\n", $panes),
                $options
            );
    }

    /**
     * Marks the first visible, enabled pane item without explicit active state as active.
     *
     * @param array<array-key, string|TabItem> $items Tab items.
     *
     * @return array<array-key, string|TabItem>
     */
    private function activateFirstTab(array $items): array
    {
        foreach ($items as $n => $item) {
            if (
                is_string($item)
                || $item->visible === false
                || $item->disabled
                || $item->active !== null
                || $item->url !== null
                || $item->items !== []
            ) {
                continue;
            }

            $items[$n] = $item->withActive(true);

            break;
        }

        return $items;
    }

    /**
     * Returns the label of an item, HTML-encoded according to its `encode` flag and {@see $encodeLabels}.
     */
    private function encodeLabel(TabItem $item): string
    {
        return ($item->encode ?? $this->encodeLabels) ? Html::encode($item->label) : (string) $item->label;
    }

    /**
     * Returns whether any visible item, including menu items, is explicitly active.
     *
     * @param array<array-key, MenuItem|string|TabItem> $items Tab items.
     */
    private function hasActiveTab(array $items): bool
    {
        foreach ($items as $item) {
            if (is_string($item) || $item->visible === false) {
                continue;
            }

            if ($item->active === true || $this->hasActiveTab($item->items)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns {@see $linkOptions} merged with the item `linkOptions`.
     *
     * @return array<array-key, mixed>
     */
    private function linkOptions(TabItem $item): array
    {
        return [...$this->linkOptions, ...$item->linkOptions];
    }

    /**
     * Converts the children of a menu toggle into {@see Menu} items and adds the panes of their tabs to {@see $panes}.
     *
     * Tabs become menu buttons, tabs with a `url` menu links, and tabs with `items` submenus converted recursively,
     * with `-dd{n}` appended to `$menuSuffix`. Invisible tabs and submenus without rendered children are skipped; menu
     * items and strings are kept as is.
     *
     * @param array<array-key, MenuItem|string|TabItem> $items Children of the menu toggle.
     * @param string $menuSuffix Suffix inserted in the default `id` values of the tab buttons and panes.
     * @param bool $active Set to `true` when a tab or link at any depth is explicitly active.
     *
     * @return list<MenuItem|string>
     */
    private function menuItems(array $items, string $menuSuffix, bool &$active): array
    {
        $menuItems = [];

        foreach ($items as $m => $child) {
            if (!$child instanceof TabItem) {
                $menuItems[] = $child;

                continue;
            }

            if ($child->visible === false) {
                continue;
            }

            if ($child->url !== null) {
                $active = $active || $child->active === true;
                $menuItems[] = MenuItem::link($child->label, $child->url)
                    ->withEncode($child->encode)
                    ->withDisabled($child->disabled)
                    ->withActive($child->active)
                    ->withLinkOptions($this->linkOptions($child));

                continue;
            }

            if ($child->items !== []) {
                $submenu = $this->menuItems($child->items, "{$menuSuffix}-dd{$m}", $active);

                if ($submenu !== []) {
                    $menuItems[] = MenuItem::submenu($child->label, $submenu)
                        ->withEncode($child->encode)
                        ->withDisabled($child->disabled)
                        ->withOptions($child->headerOptions)
                        ->withLinkOptions($this->linkOptions($child));
                }

                continue;
            }

            $active = $active || $child->active === true;
            $menuItems[] = MenuItem::button($child->label)
                ->withEncode($child->encode)
                ->withLinkOptions($this->renderPane(
                    $child,
                    $this->linkOptions($child),
                    "{$menuSuffix}-tab{$m}",
                    "{$menuSuffix}-pane{$m}",
                ));
        }

        return $menuItems;
    }

    /**
     * Converts the array items of {@see $items} through {@see TabItem::fromArray()}, keeping their keys.
     *
     * @throws InvalidConfigException if an item has no `label`.
     *
     * @return array<array-key, string|TabItem>
     */
    private function normalizeItems(): array
    {
        $items = [];

        foreach ($this->items as $key => $item) {
            $items[$key] = is_array($item) ? TabItem::fromArray($item) : $item;
        }

        return $items;
    }

    /**
     * Renders a `.nav-item` holding a tab button, a link, or a menu toggle followed by its {@see Menu}.
     *
     * @param string $tabSuffix Suffix of the default tab button `id`.
     * @param string $paneSuffix Suffix of the default pane `id`.
     * @param string $menuSuffix Suffix inserted in the default `id` values of the menu items.
     */
    private function renderHeader(TabItem $item, string $tabSuffix, string $paneSuffix, string $menuSuffix): string
    {
        $label = $this->encodeLabel($item);
        $headerOptions = [...$this->headerOptions, ...$item->headerOptions];

        Html::addCssClass($headerOptions, ['widget' => 'nav-item']);

        $headerOptions['role'] = 'presentation';
        $linkOptions = $this->linkOptions($item);

        Html::addCssClass($linkOptions, ['widget' => 'nav-link']);

        if ($item->url !== null) {
            $url = $item->url;

            if ($item->disabled) {
                Html::addCssClass($linkOptions, ['disable' => 'disabled']);

                $linkOptions['aria-disabled'] = 'true';
                $url = null;
            } elseif ($item->active === true) {
                Html::addCssClass($linkOptions, ['activate' => 'active']);

                $linkOptions['aria-current'] = 'page';
            }

            return Html::tag('li', Html::a($label, $url, $linkOptions), $headerOptions);
        }

        if ($item->items === []) {
            $linkOptions = $this->renderPane($item, $linkOptions, $tabSuffix, $paneSuffix);

            return Html::tag('li', Html::tag('button', $label, $linkOptions), $headerOptions);
        }

        $active = false;
        $menuItems = $this->menuItems($item->items, $menuSuffix, $active);

        if ($active) {
            Html::addCssClass($linkOptions, ['activate' => 'active']);
        }

        $linkOptions['type'] = 'button';
        $linkOptions['data-bs-toggle'] = 'menu';
        $linkOptions['aria-expanded'] = 'false';

        $menu = $this->menuClass::widget(
            [
                'encodeLabels' => $this->encodeLabels,
                'items' => $menuItems,
                'view' => $this->getView(),
            ],
        );

        return Html::tag(
            'li',
            Html::tag('button', $label, $linkOptions) . $menu,
            $headerOptions,
        );
    }

    /**
     * Adds the tab pane of an item to {@see $panes} and returns the tab button attributes that control it.
     *
     * @param array<array-key, mixed> $linkOptions Tab button attributes.
     * @param string $tabSuffix Suffix of the default tab button `id`.
     * @param string $paneSuffix Suffix of the default pane `id`.
     *
     * @return array<array-key, mixed>
     */
    private function renderPane(TabItem $item, array $linkOptions, string $tabSuffix, string $paneSuffix): array
    {
        /** @var string $id */
        $id = ArrayHelper::getValue($this->options, 'id');
        $options = [...$this->itemOptions, ...$item->options];

        /** @var string $paneId */
        $paneId = $options['id'] ?? $id . $paneSuffix;

        /** @var string $tabId */
        $tabId = $linkOptions['id'] ?? $id . $tabSuffix;
        $active = $item->active === true;

        $linkOptions['id'] = $tabId;
        $linkOptions['type'] = 'button';
        $linkOptions['disabled'] = $item->disabled;
        $linkOptions['data-bs-toggle'] = 'tab';
        $linkOptions['data-bs-target'] = "#{$paneId}";
        $linkOptions['role'] = 'tab';
        $linkOptions['aria-controls'] = $paneId;
        $linkOptions['aria-selected'] = $active ? 'true' : 'false';

        if ($active) {
            Html::addCssClass($linkOptions, ['activate' => 'active']);
        }

        if ($this->renderTabContent) {
            /** @var string $tag */
            $tag = ArrayHelper::remove($options, 'tag', 'div');

            $options['id'] = $paneId;

            Html::addCssClass($options, ['widget' => 'tab-pane']);

            if ($active) {
                Html::addCssClass($options, ['activate' => 'active']);
            }

            $options['role'] = 'tabpanel';
            $options['aria-labelledby'] = $tabId;
            $options['tabindex'] = '0';

            $this->panes[] = Html::tag($tag, $item->content ?? '', $options);
        }

        return $linkOptions;
    }
}
