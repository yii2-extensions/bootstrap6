<?php

declare(strict_types=1);

namespace yii2\extensions\bootstrap6\item;

use Stringable;
use yii\base\InvalidConfigException;
use yii2\extensions\bootstrap6\exception\Message;

use function array_key_exists;
use function is_array;

/**
 * Represents a `Tabs` item: a tab with its pane, a plain link, or a toggle that opens a `Menu` of tabs.
 */
final readonly class TabItem
{
    use ItemTrait;

    /**
     * @param string|Stringable $label Label of the tab button, link, or menu toggle.
     * @param string|Stringable|null $content HTML content of the pane, or `null` for an empty pane.
     * @param array<array-key, mixed>|string|null $url URL of a plain link tab, processed by
     * {@see \yii\helpers\Url::to()}, or `null` for a tab with a pane.
     * @param bool|null $active Whether the tab and its pane, or the link, are active, or `null` to let `Tabs` activate
     * the first eligible tab when none is active.
     * @param bool $disabled Whether the tab button, the link, or the menu toggle is disabled; a disabled link loses its
     * `href` and takes precedence over {@see $active}, and a disabled toggle is never marked active.
     * @param bool $visible Whether to render the tab and its pane.
     * @param bool|null $encode Whether to HTML-encode {@see $label}, or `null` to use `Tabs::$encodeLabels`.
     * @param array<array-key, mixed> $headerOptions HTML attributes of the `.nav-item` element.
     * @param array<array-key, mixed> $linkOptions HTML attributes of the tab button; its `id` defaults to
     * `{id}-tab{n}`.
     * @param array<array-key, mixed> $options HTML attributes of the pane; its `id` defaults to `{id}-pane{n}`.
     * @param array<array-key, MenuItem|string|TabItem> $items Items of the menu toggled by the tab: tabs are rendered
     * as menu buttons with their panes, tabs with a {@see $url} as menu links, tabs with their own items as nested
     * `.submenu` panels; menu items and strings are passed to the `Menu` as is.
     */
    private function __construct(
        public string|Stringable $label,
        public string|Stringable|null $content = null,
        public array|string|null $url = null,
        public bool|null $active = null,
        public bool $disabled = false,
        public bool $visible = true,
        public bool|null $encode = null,
        public array $headerOptions = [],
        public array $linkOptions = [],
        public array $options = [],
        public array $items = [],
    ) {}

    /**
     * Creates an item from the array configuration documented in `Tabs::$items`.
     *
     * An `active` key, even `null`, makes the state explicit. Nested `items` arrays with a `url` or without a `label`
     * are converted through {@see MenuItem::fromArray()}, the others recursively through this method.
     *
     * @param array<array-key, mixed> $config Item configuration.
     *
     * @throws InvalidConfigException if the item, or one of its nested items, has no `string|Stringable` `label`.
     */
    public static function fromArray(array $config): self
    {
        $label = self::textValue($config, 'label', Message::ITEM_LABEL_REQUIRED);

        /** @var string|Stringable|null $content */
        $content = $config['content'] ?? null;
        /** @var array<array-key, mixed>|string|null $url */
        $url = $config['url'] ?? null;
        /** @var array<array-key, mixed> $items */
        $items = $config['items'] ?? [];
        $items = self::normalize($items);
        /** @var array<array-key, mixed> $headerOptions */
        $headerOptions = $config['headerOptions'] ?? [];
        /** @var array<array-key, mixed> $linkOptions */
        $linkOptions = $config['linkOptions'] ?? [];
        /** @var array<array-key, mixed> $options */
        $options = $config['options'] ?? [];

        return new self(
            $label,
            $content,
            $url,
            array_key_exists('active', $config) ? $config['active'] === true : null,
            ($config['disabled'] ?? false) === true,
            ($config['visible'] ?? true) !== false,
            isset($config['encode']) ? $config['encode'] !== false : null,
            $headerOptions,
            $linkOptions,
            $options,
            $items,
        );
    }

    /**
     * Creates a plain link tab.
     *
     * @param string|Stringable $label Link label.
     * @param array<array-key, mixed>|string $url URL processed by {@see \yii\helpers\Url::to()}.
     */
    public static function link(string|Stringable $label, array|string $url): self
    {
        return new self($label, url: $url);
    }

    /**
     * Creates a toggle that opens a `Menu` of tabs.
     *
     * @param string|Stringable $label Toggle label.
     * @param array<array-key, array<string, mixed>|MenuItem|string|TabItem> $items Tabs rendered as menu buttons (menu
     * links when they have a URL, nested `.submenu` panels when they have items), menu items, or strings; arrays are
     * converted as in {@see fromArray()}.
     */
    public static function menu(string|Stringable $label, array $items): self
    {
        return new self($label, items: self::normalize($items));
    }

    /**
     * Creates a tab with its pane.
     *
     * @param string|Stringable $label Tab label.
     * @param string|Stringable|null $content HTML content of the pane.
     */
    public static function to(string|Stringable $label, string|Stringable|null $content = null): self
    {
        return new self($label, content: $content);
    }

    /**
     * Returns a copy with the given active state.
     */
    public function withActive(bool|null $active): self
    {
        return $this->with('active', $active);
    }

    /**
     * Returns a copy with the given pane content.
     */
    public function withContent(string|Stringable|null $content): self
    {
        return $this->with('content', $content);
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
     * Returns a copy with the given `.nav-item` attributes.
     *
     * @param array<array-key, mixed> $headerOptions HTML attributes of the `.nav-item` element.
     */
    public function withHeaderOptions(array $headerOptions): self
    {
        return $this->with('headerOptions', $headerOptions);
    }

    /**
     * Returns a copy with the given menu items.
     *
     * @param array<array-key, array<string, mixed>|MenuItem|string|TabItem> $items Tabs rendered as menu buttons (menu
     * links when they have a URL, nested `.submenu` panels when they have items), menu items, or strings; arrays are
     * converted as in {@see fromArray()}.
     */
    public function withItems(array $items): self
    {
        return $this->with('items', self::normalize($items));
    }

    /**
     * Returns a copy with the given label.
     */
    public function withLabel(string|Stringable $label): self
    {
        return $this->with('label', $label);
    }

    /**
     * Returns a copy with the given tab button attributes.
     *
     * @param array<array-key, mixed> $linkOptions HTML attributes of the tab button.
     */
    public function withLinkOptions(array $linkOptions): self
    {
        return $this->with('linkOptions', $linkOptions);
    }

    /**
     * Returns a copy with the given pane attributes.
     *
     * @param array<array-key, mixed> $options HTML attributes of the pane.
     */
    public function withOptions(array $options): self
    {
        return $this->with('options', $options);
    }

    /**
     * Returns a copy with the given URL.
     *
     * @param array<array-key, mixed>|string|null $url URL processed by {@see \yii\helpers\Url::to()}, or `null` for a
     * tab with a pane.
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
     * Converts the array items of a menu list, keeping keys, instances, and strings: arrays with a `url` or without a
     * `label` become menu items through {@see MenuItem::fromArray()}, the others tabs through {@see fromArray()}.
     *
     * @param array<array-key, mixed> $items Items to convert.
     *
     * @throws InvalidConfigException if an array item has no `label`.
     *
     * @return array<array-key, MenuItem|string|TabItem>
     */
    private static function normalize(array $items): array
    {
        foreach ($items as $key => $item) {
            if (is_array($item)) {
                $items[$key] = isset($item['url']) || !isset($item['label'])
                    ? MenuItem::fromArray($item)
                    : self::fromArray($item);
            }
        }

        /** @var array<array-key, MenuItem|string|TabItem> $items */
        return $items;
    }
}
