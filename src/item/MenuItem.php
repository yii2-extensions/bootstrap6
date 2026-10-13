<?php

declare(strict_types=1);

namespace yii2\extensions\bootstrap6\item;

use Stringable;
use yii\base\InvalidConfigException;
use yii2\extensions\bootstrap6\enum\MenuItemType;
use yii2\extensions\bootstrap6\exception\Message;

use function array_key_exists;
use function is_array;

/**
 * Represents a `Menu` item: a link, a button, a submenu, a header, a text, or a divider.
 */
final readonly class MenuItem
{
    use ItemTrait;

    /**
     * @param MenuItemType $type Kind of the item, which selects the rendered element.
     * @param string|Stringable $label Label of a link, button, or submenu toggle, or content of a header or text;
     * empty for a divider.
     * @param array<array-key, mixed>|string|null $url URL of a link, processed by {@see \yii\helpers\Url::to()}.
     * @param bool|null $active Whether the item is active, or `null` to let `Nav` detect it from {@see $url}.
     * @param bool $disabled Whether the item is disabled; it takes precedence over {@see $active}.
     * @param bool $visible Whether to render the item.
     * @param bool|null $encode Whether to HTML-encode {@see $label}, or `null` to use the widget `encodeLabels`.
     * @param array<array-key, mixed> $options HTML attributes of the `.submenu` wrapper, header, text, or divider.
     * @param array<array-key, mixed> $linkOptions HTML attributes of the `.menu-item` element.
     * @param array<array-key, MenuItem|string> $items Items of the nested submenu; the string `-` renders a divider,
     * any other string is rendered as is.
     * @param array<array-key, mixed> $submenuOptions HTML attributes of the nested `.menu`, merged over the widget
     * `submenuOptions`.
     */
    private function __construct(
        public MenuItemType $type,
        public string|Stringable $label,
        public array|string|null $url = null,
        public bool|null $active = null,
        public bool $disabled = false,
        public bool $visible = true,
        public bool|null $encode = null,
        public array $options = [],
        public array $linkOptions = [],
        public array $items = [],
        public array $submenuOptions = [],
    ) {}

    /**
     * Creates a `<button>` item.
     *
     * @param string|Stringable $label Button label.
     */
    public static function button(string|Stringable $label): self
    {
        return new self(MenuItemType::BUTTON, $label);
    }

    /**
     * Creates an `<hr class="menu-divider">` item.
     */
    public static function divider(): self
    {
        return new self(MenuItemType::DIVIDER, '');
    }

    /**
     * Creates an item from the array configuration documented in `Menu::$items`.
     *
     * The `divider`, `header`, and `text` keys select those kinds, in that order; otherwise, a non-empty `items` key
     * selects a submenu, a `url` key (even `null`) a link, and its absence a button. Nested `items` arrays are
     * converted recursively; strings are kept.
     *
     * @param array<array-key, mixed> $config Item configuration.
     *
     * @throws InvalidConfigException if a link, button, or submenu has no `string|Stringable` `label`.
     */
    public static function fromArray(array $config): self
    {
        /** @var array<array-key, mixed>|string|null $url */
        $url = $config['url'] ?? null;

        /** @var array<array-key, mixed> $items */
        $items = $config['items'] ?? [];
        $items = self::normalize($items);

        if (($config['divider'] ?? false) === true) {
            $type = MenuItemType::DIVIDER;
            $label = '';
        } elseif (isset($config['header'])) {
            $type = MenuItemType::HEADER;

            /** @var string|Stringable $label */
            $label = $config['header'];
        } elseif (isset($config['text'])) {
            $type = MenuItemType::TEXT;

            /** @var string|Stringable $label */
            $label = $config['text'];
        } else {
            $label = self::textValue($config, 'label', Message::ITEM_LABEL_REQUIRED);

            $type = match (true) {
                $items !== [] => MenuItemType::SUBMENU,
                array_key_exists('url', $config) => MenuItemType::LINK,
                default => MenuItemType::BUTTON,
            };
        }

        /** @var array<array-key, mixed> $options */
        $options = $config['options'] ?? [];

        /** @var array<array-key, mixed> $linkOptions */
        $linkOptions = $config['linkOptions'] ?? [];

        /** @var array<array-key, mixed> $submenuOptions */
        $submenuOptions = $config['submenuOptions'] ?? [];

        return new self(
            $type,
            $label,
            $url,
            isset($config['active']) ? $config['active'] === true : null,
            ($config['disabled'] ?? false) === true,
            ($config['visible'] ?? true) !== false,
            isset($config['encode']) ? $config['encode'] !== false : null,
            $options,
            $linkOptions,
            $items,
            $submenuOptions,
        );
    }

    /**
     * Creates an `<h6 class="menu-header">` item.
     *
     * @param string|Stringable $content Header content.
     */
    public static function header(string|Stringable $content): self
    {
        return new self(MenuItemType::HEADER, $content);
    }

    /**
     * Creates a link item.
     *
     * @param string|Stringable $label Link label.
     * @param array<array-key, mixed>|string|null $url Link URL, processed by {@see \yii\helpers\Url::to()}; `null`
     * renders no `href`.
     */
    public static function link(string|Stringable $label, array|string|null $url): self
    {
        return new self(MenuItemType::LINK, $label, url: $url);
    }

    /**
     * Creates a submenu: a toggle button followed by a nested `.menu`, inside a `.submenu` wrapper.
     *
     * @param string|Stringable $label Toggle label.
     * @param array<array-key, array<string, mixed>|MenuItem|string> $items Items of the nested menu; arrays are
     * converted through {@see fromArray()}.
     */
    public static function submenu(string|Stringable $label, array $items): self
    {
        return new self(MenuItemType::SUBMENU, $label, items: self::normalize($items));
    }

    /**
     * Creates a `<span class="menu-text">` item.
     *
     * @param string|Stringable $content Text content.
     */
    public static function text(string|Stringable $content): self
    {
        return new self(MenuItemType::TEXT, $content);
    }

    /**
     * Returns a copy with the given active state.
     */
    public function withActive(bool|null $active): self
    {
        return $this->with('active', $active);
    }

    /**
     * Returns a copy with the given disabled state.
     */
    public function withDisabled(bool $disabled): self
    {
        return $this->with('disabled', $disabled);
    }

    /**
     * Returns a copy with the given encoding flag.
     */
    public function withEncode(bool|null $encode): self
    {
        return $this->with('encode', $encode);
    }

    /**
     * Returns a copy with the given nested items.
     *
     * @param array<array-key, array<string, mixed>|MenuItem|string> $items Items of the nested menu; arrays are
     * converted through {@see fromArray()}.
     */
    public function withItems(array $items): self
    {
        return $this->with('items', self::normalize($items));
    }

    /**
     * Returns a copy with the given label or content.
     */
    public function withLabel(string|Stringable $label): self
    {
        return $this->with('label', $label);
    }

    /**
     * Returns a copy with the given `.menu-item` attributes.
     *
     * @param array<array-key, mixed> $linkOptions HTML attributes of the `.menu-item` element.
     */
    public function withLinkOptions(array $linkOptions): self
    {
        return $this->with('linkOptions', $linkOptions);
    }

    /**
     * Returns a copy with the given wrapper, header, text, or divider attributes.
     *
     * @param array<array-key, mixed> $options HTML attributes.
     */
    public function withOptions(array $options): self
    {
        return $this->with('options', $options);
    }

    /**
     * Returns a copy with the given nested `.menu` attributes.
     *
     * @param array<array-key, mixed> $submenuOptions HTML attributes of the nested `.menu`.
     */
    public function withSubmenuOptions(array $submenuOptions): self
    {
        return $this->with('submenuOptions', $submenuOptions);
    }

    /**
     * Returns a copy with the given kind.
     */
    public function withType(MenuItemType $type): self
    {
        return $this->with('type', $type);
    }

    /**
     * Returns a copy with the given URL.
     *
     * @param array<array-key, mixed>|string|null $url URL processed by {@see \yii\helpers\Url::to()}.
     */
    public function withUrl(array|string|null $url): self
    {
        return $this->with('url', $url);
    }

    /**
     * Returns a copy with the given visibility.
     */
    public function withVisible(bool $visible): self
    {
        return $this->with('visible', $visible);
    }

    /**
     * Converts the array items of a list through {@see fromArray()}, keeping keys, instances, and strings.
     *
     * @param array<array-key, mixed> $items Items to convert.
     *
     * @throws InvalidConfigException if an item has no `label`.
     *
     * @return array<array-key, MenuItem|string>
     */
    private static function normalize(array $items): array
    {
        foreach ($items as $key => $item) {
            if (is_array($item)) {
                $items[$key] = self::fromArray($item);
            }
        }

        /** @var array<array-key, MenuItem|string> $items */
        return $items;
    }
}
