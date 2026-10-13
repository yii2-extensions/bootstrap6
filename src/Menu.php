<?php

declare(strict_types=1);

namespace yii2\extensions\bootstrap6;

use yii\base\InvalidConfigException;
use yii2\extensions\bootstrap6\enum\MenuItemType;
use yii2\extensions\bootstrap6\item\MenuItem;

use function array_merge;
use function implode;
use function is_array;
use function is_string;

/**
 * Renders the Bootstrap `.menu` panel with links, buttons, headers, dividers, text, and nested submenus.
 *
 * The widget renders only the panel. Place it right after the element that carries `data-bs-toggle="menu"`, or use
 * {@see ButtonMenu} or {@see Nav}, which render the toggle. The Menu plugin binds to that toggle through the data API,
 * so its options belong on the toggle as `data-bs-*` attributes.
 *
 * @see https://getbootstrap.com/docs/6.0/components/menu/
 */
class Menu extends Widget
{
    /**
     * Whether to HTML-encode the item labels; the item `encode` option takes precedence.
     */
    public bool $encodeLabels = true;
    /**
     * Menu items: {@see MenuItem} instances, arrays converted through {@see MenuItem::fromArray()}, or strings.
     *
     * An array item accepts the following options:
     *
     * - `label`: `string|Stringable`, required for links and buttons.
     * - `url`: `array|string`, processed by {@see \yii\helpers\Url::to()}. Without it the item renders a `<button>`.
     * - `active`: `bool`, adds the `active` class and `aria-current="true"`.
     * - `disabled`: `bool`, disables the item; links lose their `href` and get `aria-disabled="true"`.
     * - `visible`: `bool`, whether to render the item. Defaults to `true`.
     * - `encode`: `bool`, whether to HTML-encode the label, header, or text.
     * - `linkOptions`: `array`, HTML attributes of the `.menu-item` element.
     * - `items`: `array`, items of a nested submenu, rendered inside a `.submenu` wrapper.
     * - `options`: `array`, HTML attributes of the `.submenu` wrapper, header, text, or divider.
     * - `submenuOptions`: `array`, HTML attributes of the nested `.menu`, merged over {@see $submenuOptions}.
     * - `header`: `string|Stringable`, renders `<h6 class="menu-header">` instead of an item.
     * - `text`: `string|Stringable`, renders `<span class="menu-text">` instead of an item.
     * - `divider`: `bool`, renders `<hr class="menu-divider">` instead of an item.
     *
     * The string `-` renders a divider; any other string is rendered as is.
     *
     * @var array<array-key, array<array-key, mixed>|MenuItem|string>
     */
    public array $items = [];
    /**
     * HTML attributes of every nested `.menu` panel.
     *
     * @var array<array-key, mixed>
     */
    public array $submenuOptions = [];

    /**
     * @throws InvalidConfigException if an item has no `label`.
     */
    public function run(): string
    {
        $options = $this->options;

        Html::addCssClass($options, ['widget' => 'menu']);

        $items = $this->normalizeItems();

        $this->registerPlugin('Menu');

        return $this->renderItems($items, $options);
    }

    /**
     * Renders the menu items inside a `.menu` panel.
     *
     * @param array<array-key, MenuItem|string> $items Items to render, see {@see $items}.
     * @param array<array-key, mixed> $options HTML attributes of the panel.
     */
    protected function renderItems(array $items, array $options): string
    {
        $lines = [];

        foreach ($items as $item) {
            if (is_string($item)) {
                $lines[] = $item === '-' ? Html::tag('hr', '', ['class' => 'menu-divider']) : $item;
            } elseif ($item->visible) {
                $lines[] = $this->renderItem($item);
            }
        }

        return Html::tag(
            'div',
            implode("\n", $lines),
            $options,
        );
    }

    /**
     * Returns the item label HTML-encoded according to the item `encode` flag and {@see $encodeLabels}.
     */
    private function encode(MenuItem $item): string
    {
        return ($item->encode ?? $this->encodeLabels) ? Html::encode($item->label) : (string) $item->label;
    }

    /**
     * Converts the array items of {@see $items} through {@see MenuItem::fromArray()}, keeping their keys.
     *
     * @throws InvalidConfigException if an item has no `label`.
     *
     * @return array<array-key, MenuItem|string>
     */
    private function normalizeItems(): array
    {
        $items = [];

        foreach ($this->items as $key => $item) {
            $items[$key] = is_array($item) ? MenuItem::fromArray($item) : $item;
        }

        return $items;
    }

    /**
     * Renders a `<button>` menu item.
     *
     * @param array<array-key, mixed> $options HTML attributes of the button.
     */
    private function renderButton(string $label, array $options, bool $disabled): string
    {
        $options['type'] ??= 'button';

        if ($disabled) {
            $options['disabled'] = true;
        }

        return Html::tag(
            'button',
            $label,
            $options,
        );
    }

    /**
     * Renders a single menu item, header, text, divider, or submenu.
     */
    private function renderItem(MenuItem $item): string
    {
        $options = $item->options;

        $element = match ($item->type) {
            MenuItemType::DIVIDER => ['hr', 'menu-divider'],
            MenuItemType::HEADER => ['h6', 'menu-header'],
            MenuItemType::TEXT => ['span', 'menu-text'],
            default => null,
        };

        if ($element !== null) {
            Html::addCssClass($options, ['widget' => $element[1]]);

            return Html::tag($element[0], $this->encode($item), $options);
        }

        $label = $this->encode($item);
        $linkOptions = $item->linkOptions;

        Html::addCssClass($linkOptions, ['widget' => 'menu-item']);

        if ($item->disabled === false && $item->active === true) {
            Html::addCssClass($linkOptions, ['activate' => 'active']);

            $linkOptions['aria-current'] = 'true';
        }

        if ($item->items !== []) {
            $submenuOptions = array_merge($this->submenuOptions, $item->submenuOptions);

            Html::addCssClass($options, ['widget' => 'submenu']);
            Html::addCssClass($submenuOptions, ['widget' => 'menu']);

            return Html::tag(
                'div',
                $this->renderButton($label, $linkOptions, $item->disabled)
                . "\n"
                . $this->renderItems($item->items, $submenuOptions),
                $options,
            );
        }

        if ($item->type !== MenuItemType::LINK) {
            return $this->renderButton($label, $linkOptions, $item->disabled);
        }

        if ($item->disabled) {
            Html::addCssClass($linkOptions, ['disable' => 'disabled']);

            $linkOptions['aria-disabled'] = 'true';

            return Html::tag('a', $label, $linkOptions);
        }

        return Html::a(
            $label,
            $item->url,
            $linkOptions,
        );
    }
}
