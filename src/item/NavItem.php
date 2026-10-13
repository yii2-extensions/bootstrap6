<?php

declare(strict_types=1);

namespace yii2\extensions\bootstrap6\item;

use Stringable;
use yii\base\InvalidConfigException;
use yii2\extensions\bootstrap6\exception\Message;

use function array_key_exists;
use function is_array;
use function is_string;

/**
 * Represents a `Nav` item: a link, or a toggle that opens a `Menu`.
 */
final readonly class NavItem
{
    use ItemTrait;

    /**
     * @param string|Stringable $label Label of the `.nav-link` element.
     * @param array<array-key, mixed>|string|null $url URL processed by {@see \yii\helpers\Url::to()}, or `null` for no
     * `href`; a route array also drives the active state detection.
     * @param bool|null $active Whether the item is active, or `null` to detect it from the {@see $url} route.
     * @param bool $disabled Whether the item is disabled; it takes precedence over {@see $active}.
     * @param bool $visible Whether to render the item.
     * @param bool|null $encode Whether to HTML-encode {@see $label}, or `null` to use `Nav::$encodeLabels`.
     * @param array<array-key, mixed> $options HTML attributes of the `.nav-item` element.
     * @param array<array-key, mixed> $linkOptions HTML attributes of the `.nav-link` element.
     * @param array<array-key, MenuItem|string>|string $items Items of the menu toggled by the item, or the menu HTML;
     * `[]` renders a plain link.
     * @param array<array-key, mixed> $menuOptions HTML attributes of the `.menu` panel.
     */
    private function __construct(
        public string|Stringable $label,
        public array|string|null $url = '#',
        public bool|null $active = null,
        public bool $disabled = false,
        public bool $visible = true,
        public bool|null $encode = null,
        public array $options = [],
        public array $linkOptions = [],
        public array|string $items = [],
        public array $menuOptions = [],
    ) {}

    /**
     * Creates an item from the array configuration documented in `Nav::$items`.
     *
     * A missing `url` defaults to `#`; an explicit `null` renders no `href`. Nested `items` arrays are converted
     * through {@see MenuItem::fromArray()}.
     *
     * @param array<array-key, mixed> $config Item configuration.
     *
     * @throws InvalidConfigException if the item, or one of its menu items, has no `string|Stringable` `label`.
     */
    public static function fromArray(array $config): self
    {
        $label = self::textValue($config, 'label', Message::ITEM_LABEL_REQUIRED);

        /** @var array<array-key, mixed>|string|null $url */
        $url = array_key_exists('url', $config) ? $config['url'] : '#';
        /** @var array<array-key, mixed>|string $items */
        $items = $config['items'] ?? [];
        $items = self::normalize($items);
        /** @var array<array-key, mixed> $options */
        $options = $config['options'] ?? [];
        /** @var array<array-key, mixed> $linkOptions */
        $linkOptions = $config['linkOptions'] ?? [];
        /** @var array<array-key, mixed> $menuOptions */
        $menuOptions = $config['menuOptions'] ?? [];

        return new self(
            $label,
            $url,
            isset($config['active']) ? $config['active'] === true : null,
            ($config['disabled'] ?? false) === true,
            ($config['visible'] ?? true) !== false,
            isset($config['encode']) ? $config['encode'] !== false : null,
            $options,
            $linkOptions,
            $items,
            $menuOptions,
        );
    }

    /**
     * Creates a toggle that opens a `Menu`.
     *
     * @param string|Stringable $label Toggle label.
     * @param array<array-key, array<string, mixed>|MenuItem|string>|string $items Menu items, or the menu HTML; arrays
     * are converted through {@see MenuItem::fromArray()}.
     */
    public static function menu(string|Stringable $label, array|string $items): self
    {
        return new self($label, items: self::normalize($items));
    }

    /**
     * Creates a link item.
     *
     * @param string|Stringable $label Link label.
     * @param array<array-key, mixed>|string|null $url URL processed by {@see \yii\helpers\Url::to()}; `null` renders
     * no `href`.
     */
    public static function to(string|Stringable $label, array|string|null $url = '#'): self
    {
        return new self($label, url: $url);
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
     * Returns a copy with the given menu items or menu HTML.
     *
     * @param array<array-key, array<string, mixed>|MenuItem|string>|string $items Menu items, or the menu HTML; arrays
     * are converted through {@see MenuItem::fromArray()}.
     */
    public function withItems(array|string $items): self
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
     * Returns a copy with the given `.nav-link` attributes.
     *
     * @param array<array-key, mixed> $linkOptions HTML attributes of the `.nav-link` element.
     */
    public function withLinkOptions(array $linkOptions): self
    {
        return $this->with('linkOptions', $linkOptions);
    }

    /**
     * Returns a copy with the given `.menu` panel attributes.
     *
     * @param array<array-key, mixed> $menuOptions HTML attributes of the `.menu` panel.
     */
    public function withMenuOptions(array $menuOptions): self
    {
        return $this->with('menuOptions', $menuOptions);
    }

    /**
     * Returns a copy with the given `.nav-item` attributes.
     *
     * @param array<array-key, mixed> $options HTML attributes of the `.nav-item` element.
     */
    public function withOptions(array $options): self
    {
        return $this->with('options', $options);
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
     * Converts the array items of a menu list through {@see MenuItem::fromArray()}, keeping keys, instances, strings,
     * and a menu HTML string.
     *
     * @param array<array-key, mixed>|string $items Menu items, or the menu HTML.
     *
     * @throws InvalidConfigException if an array item has no `label`.
     *
     * @return array<array-key, MenuItem|string>|string
     */
    private static function normalize(array|string $items): array|string
    {
        if (is_string($items)) {
            return $items;
        }

        foreach ($items as $key => $item) {
            if (is_array($item)) {
                $items[$key] = MenuItem::fromArray($item);
            }
        }

        /** @var array<array-key, MenuItem|string> $items */
        return $items;
    }
}
