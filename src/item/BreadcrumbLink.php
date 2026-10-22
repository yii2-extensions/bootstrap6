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
 * Represents a `Breadcrumbs` item: a link of the trail, or the active item.
 */
final readonly class BreadcrumbLink
{
    use ItemTrait;

    /**
     * Initializes the immutable BreadcrumbLink configuration.
     *
     * @param string|Stringable $label Label of the `.breadcrumb-link` element.
     * @param array<array-key, mixed>|string|null $url URL processed by {@see \yii\helpers\Url::to()}, or `null` for no
     * `href`.
     * @param bool|null $active Whether the item is active, or `null` to make it active when {@see $url} is `null`.
     * @param bool|null $encode Whether to HTML-encode {@see $label}, or `null` to use `Breadcrumbs::$encodeLabels`.
     * @param string|null $template Template of the item, where `{link}` is replaced by the rendered link, or `null`
     * to use the widget item templates.
     * @param array<array-key, mixed> $options HTML attributes of the `.breadcrumb-link` element.
     */
    private function __construct(
        public string|Stringable $label,
        public array|string|null $url = null,
        public bool|null $active = null,
        public bool|null $encode = null,
        public string|null $template = null,
        public array $options = [],
    ) {}

    /**
     * Creates an item from the array configuration documented in `Breadcrumbs::$links`.
     *
     * The `active` and `encode` keys, even `null`, make their value explicit; a `url` or `template` of another type
     * is ignored; every other key becomes an attribute of the link.
     *
     * @param array<array-key, mixed> $config Item configuration.
     *
     * @throws InvalidConfigException if the item has no `string|Stringable` `label`.
     *
     * @return self A new instance.
     */
    public static function fromArray(array $config): self
    {
        $label = self::textValue($config, 'label', Message::ITEM_LABEL_REQUIRED);

        $url = $config['url'] ?? null;
        $template = $config['template'] ?? null;
        $active = array_key_exists('active', $config) ? $config['active'] === true : null;
        $encode = array_key_exists('encode', $config) ? $config['encode'] !== false : null;

        unset($config['active'], $config['encode'], $config['label'], $config['template'], $config['url']);

        return new self(
            $label,
            is_string($url) || is_array($url) ? $url : null,
            $active,
            $encode,
            is_string($template) ? $template : null,
            $config,
        );
    }

    /**
     * Creates an item.
     *
     * @param string|Stringable $label Link label.
     * @param array<array-key, mixed>|string|null $url URL processed by {@see \yii\helpers\Url::to()}, or `null` for
     * the active item.
     *
     * @return self A new instance.
     */
    public static function to(string|Stringable $label, array|string|null $url = null): self
    {
        return new self($label, url: $url);
    }

    /**
     * Returns a copy with the given active state.
     *
     * @param bool|null $active Whether the item is active, or `null` to make it active when {@see $url} is `null`.
     *
     * @return self A new instance with the updated configuration.
     */
    public function withActive(bool|null $active): self
    {
        return $this->with('active', $active);
    }

    /**
     * Returns a copy with the given encoding flag.
     *
     * @param bool|null $encode Whether to HTML-encode {@see $label}, or `null` to use `Breadcrumbs::$encodeLabels`.
     *
     * @return self A new instance with the updated configuration.
     */
    public function withEncode(bool|null $encode): self
    {
        return $this->with('encode', $encode);
    }

    /**
     * Returns a copy with the given label.
     *
     * @param string|Stringable $label Label of the `.breadcrumb-link` element.
     *
     * @return self A new instance with the updated configuration.
     */
    public function withLabel(string|Stringable $label): self
    {
        return $this->with('label', $label);
    }

    /**
     * Returns a copy with the given link attributes.
     *
     * @param array<array-key, mixed> $options HTML attributes of the link.
     *
     * @return self A new instance with the updated configuration.
     */
    public function withOptions(array $options): self
    {
        return $this->with('options', $options);
    }

    /**
     * Returns a copy with the given template.
     *
     * @param string|null $template Template of the item, where `{link}` is replaced by the rendered link, or `null`
     * to use the widget item templates.
     *
     * @return self A new instance with the updated configuration.
     */
    public function withTemplate(string|null $template): self
    {
        return $this->with('template', $template);
    }

    /**
     * Returns a copy with the given URL.
     *
     * @param array<array-key, mixed>|string|null $url URL processed by {@see \yii\helpers\Url::to()}.
     *
     * @return self A new instance with the updated configuration.
     */
    public function withUrl(array|string|null $url): self
    {
        return $this->with('url', $url);
    }
}
