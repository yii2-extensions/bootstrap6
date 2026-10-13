<?php

declare(strict_types=1);

namespace yii2\extensions\bootstrap6;

use Yii;
use yii\base\InvalidConfigException;
use yii\web\Request;
use yii2\extensions\bootstrap6\asset\BootstrapAsset;
use yii2\extensions\bootstrap6\enum\{NavLayout, NavStyle};
use yii2\extensions\bootstrap6\item\{MenuItem, NavItem};

use function implode;
use function is_array;
use function is_string;
use function ltrim;
use function str_starts_with;

/**
 * Renders a Bootstrap nav with links, active state detection by route, and items that toggle a {@see Menu}.
 *
 * Menu toggles bind the Menu plugin through the data API; plugin events bubble up to the nav element, so
 * {@see $clientEvents} such as `shown.bs.menu` can be attached to it.
 *
 * @see https://getbootstrap.com/docs/6.0/components/nav/
 */
class Nav extends Widget
{
    /**
     * Whether to activate items whose `url` route and parameters match {@see $route} and {@see $params}.
     */
    public bool $activateItems = true;
    /**
     * Whether to activate a parent item when one of its menu items is active.
     */
    public bool $activateParents = false;
    /**
     * Whether to HTML-encode the item labels; the item `encode` option takes precedence.
     */
    public bool $encodeLabels = true;
    /**
     * Nav items: {@see NavItem} instances, arrays converted through {@see NavItem::fromArray()}, or strings rendered
     * as is.
     *
     * An array item accepts the following options:
     *
     * - `label`: `string|Stringable`, required.
     * - `url`: `array|string`, processed by {@see \yii\helpers\Url::to()}. Defaults to `#`.
     * - `active`: `bool`, forces the active state; otherwise it is detected from `url`.
     * - `disabled`: `bool`, renders the link without `href` and with `aria-disabled="true"`.
     * - `visible`: `bool`, whether to render the item. Defaults to `true`.
     * - `encode`: `bool`, whether to HTML-encode the label.
     * - `linkOptions`: `array`, HTML attributes of the `.nav-link` element.
     * - `options`: `array`, HTML attributes of the `.nav-item` element.
     * - `items`: `array|string`, {@see Menu::$items} of the menu toggled by the item, or the menu HTML.
     * - `menuOptions`: `array`, HTML attributes of the `.menu` panel.
     *
     * @var array<array-key, array<array-key, mixed>|NavItem|string>
     */
    public array $items = [];
    /**
     * Layout class, such as {@see NavLayout::FILL}, or `null` for the default layout.
     */
    public NavLayout|string|null $layout = null;
    /**
     * Class of the menu widget.
     *
     * @var class-string<Menu>
     */
    public string $menuClass = Menu::class;
    /**
     * Query parameters used to detect active items, or `null` to use the current request parameters.
     *
     * @var array<array-key, mixed>|null
     */
    public array|null $params = null;
    /**
     * Route used to detect active items, or `null` to use the route of the current controller.
     */
    public string|null $route = null;
    /**
     * Style class, such as {@see NavStyle::TABS}, or `null` for the base nav.
     */
    public NavStyle|string|null $style = null;
    /**
     * Whether a rendered item toggles a menu.
     */
    private bool $hasMenu = false;

    /**
     * Initializes the widget, validates {@see $layout} and {@see $style}, and resolves {@see $route} and
     * {@see $params}.
     *
     * @throws InvalidConfigException if {@see $layout} or {@see $style} is not a value of its enum.
     */
    public function init(): void
    {
        parent::init();

        Html::enumValue(NavLayout::class, $this->layout, 'layout');
        Html::enumValue(NavStyle::class, $this->style, 'style');

        $this->route ??= Yii::$app->controller?->getRoute();

        if ($this->params === null) {
            /** @var Request $request */
            $request = Yii::$app->getRequest();

            $this->params = $request->getQueryParams();
        }
    }

    /**
     * @throws InvalidConfigException if an item has no `label`.
     */
    public function run(): string
    {
        $options = $this->options;

        Html::addCssClass($options, ['widget' => 'nav']);
        Html::addCssClass($options, self::styleClasses($this->style));

        $layout = Html::enumValue(NavLayout::class, $this->layout, 'layout');

        if ($layout !== null) {
            Html::addCssClass($options, $layout);
        }

        $items = [];

        foreach ($this->normalizeItems() as $item) {
            if (is_string($item) || $item->visible) {
                $items[] = $this->renderItem($item);
            }
        }

        $html = Html::tag('ul', implode("\n", $items), $options);

        if ($this->hasMenu) {
            $this->registerPlugin('Menu');
        } else {
            BootstrapAsset::register($this->getView());
        }

        return $html;
    }

    /**
     * Returns the classes of a nav style; {@see NavStyle::PILLS_VERTICAL} also needs {@see NavStyle::PILLS}.
     *
     * @throws InvalidConfigException if `$style` is a `string` that is not a {@see NavStyle} value.
     *
     * @return list<string>
     */
    public static function styleClasses(NavStyle|string|null $style): array
    {
        $style = Html::enumValue(NavStyle::class, $style, 'style');

        return match ($style) {
            null => [],
            NavStyle::PILLS_VERTICAL->value => [NavStyle::PILLS->value, $style],
            default => [$style],
        };
    }

    /**
     * Marks the active menu items and returns them.
     *
     * A menu item with active children is marked active as well. When {@see $activateParents} is `true`, `$active` is
     * set to `true` if any item is active.
     *
     * @param array<array-key, MenuItem|string> $items Menu items, see {@see Menu::$items}.
     * @param bool $active Whether the parent item is active.
     *
     * @return array<array-key, MenuItem|string> Copies of the items, marked through {@see MenuItem::withActive()}.
     */
    protected function isChildActive(array $items, bool &$active): array
    {
        foreach ($items as $i => $child) {
            if (is_string($child) || $child->visible === false) {
                continue;
            }

            if ($this->isItemActive($child)) {
                $child = $child->withActive(true);

                if ($this->activateParents) {
                    $active = true;
                }
            }

            $activeParent = false;
            $child = $child->withItems($this->isChildActive($child->items, $activeParent));

            if ($activeParent) {
                $child = $child->withActive(true);
                $active = true;
            }

            $items[$i] = $child;
        }

        return $items;
    }

    /**
     * Returns whether the item is active.
     *
     * The item `active` flag takes precedence. Otherwise, an item is active when the first element of its array `url`
     * is the route {@see $route} and the other elements match {@see $params}. Relative routes are resolved against the
     * module of the current controller.
     *
     * @param MenuItem|NavItem $item Nav item, or item of a menu toggled by the nav.
     */
    protected function isItemActive(MenuItem|NavItem $item): bool
    {
        if ($this->activateItems === false) {
            return false;
        }

        if ($item->active !== null) {
            return $item->active;
        }

        $url = $item->url;

        if (!is_array($url) || !isset($url[0]) || !is_string($url[0])) {
            return false;
        }

        $route = $url[0];
        $controller = Yii::$app->controller;

        if (!str_starts_with($route, '/') && $controller !== null) {
            $route = $controller->module->getUniqueId() . '/' . $route;
        }

        if (ltrim($route, '/') !== $this->route) {
            return false;
        }

        unset($url[0], $url['#']);

        foreach ($url as $name => $value) {
            if ($value !== null && ($this->params[$name] ?? null) !== $value) {
                return false;
            }
        }

        return true;
    }

    /**
     * Renders a nav item.
     *
     * @param NavItem|string $item Nav item, or a string rendered as is.
     */
    protected function renderItem(NavItem|string $item): string
    {
        if (is_string($item)) {
            return $item;
        }

        $label = ($item->encode ?? $this->encodeLabels) ? Html::encode($item->label) : $item->label;
        $options = $item->options;
        $linkOptions = $item->linkOptions;
        $url = $item->url;
        $menuItems = $item->items;
        $active = $this->isItemActive($item);
        $menu = '';

        Html::addCssClass($options, ['widget' => 'nav-item']);
        Html::addCssClass($linkOptions, ['widget' => 'nav-link']);

        if ($menuItems !== []) {
            $this->hasMenu = true;

            $linkOptions['data-bs-toggle'] = 'menu';
            $linkOptions['role'] = 'button';
            $linkOptions['aria-expanded'] = 'false';

            if (is_array($menuItems)) {
                $menuItems = $this->isChildActive($menuItems, $active);
                $menuItems = $this->renderMenu($menuItems, $item->menuOptions);
            }

            $menu = $menuItems;
        }

        if ($item->disabled) {
            Html::addCssClass($linkOptions, ['disable' => 'disabled']);

            $linkOptions['aria-disabled'] = 'true';
            $url = null;
        } elseif ($active) {
            Html::addCssClass($linkOptions, ['activate' => 'active']);

            if ($menu === '') {
                $linkOptions['aria-current'] = 'page';
            }
        }

        return Html::tag(
            'li',
            Html::a($label, $url, $linkOptions) . $menu,
            $options,
        );
    }

    /**
     * Renders the menu toggled by an item.
     *
     * @param array<array-key, MenuItem|string> $items Menu items, see {@see Menu::$items}.
     * @param array<array-key, mixed> $options HTML attributes of the `.menu` panel.
     */
    protected function renderMenu(array $items, array $options): string
    {
        return $this->menuClass::widget(
            [
                'encodeLabels' => $this->encodeLabels,
                'items' => $items,
                'options' => $options,
                'view' => $this->getView(),
            ],
        );
    }

    /**
     * Converts the array items of {@see $items} through {@see NavItem::fromArray()}, keeping their keys.
     *
     * @throws InvalidConfigException if an item has no `label`.
     *
     * @return array<array-key, NavItem|string>
     */
    private function normalizeItems(): array
    {
        $items = [];

        foreach ($this->items as $key => $item) {
            $items[$key] = is_array($item) ? NavItem::fromArray($item) : $item;
        }

        return $items;
    }
}
