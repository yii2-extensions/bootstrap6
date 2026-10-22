<?php

declare(strict_types=1);

namespace yii2\extensions\bootstrap6\item;

use Stringable;
use yii\base\InvalidConfigException;
use yii2\extensions\bootstrap6\exception\Message;

/**
 * Represents an `Accordion` item: a `<details>` element with its `<summary>` label and `.accordion-body` content.
 */
final readonly class AccordionItem
{
    use ItemTrait;

    /**
     * Initializes the immutable AccordionItem configuration.
     *
     * @param string|Stringable $label Content of the `<summary>` element.
     * @param string|Stringable $content Content of the `.accordion-body` element (not encoded).
     * @param bool $open Whether the item is initially expanded.
     * @param bool $visible Whether to render the item.
     * @param bool|null $encode Whether to HTML-encode {@see $label}, or `null` to use `Accordion::$encodeLabels`.
     * @param array<array-key, mixed> $options HTML attributes of the `<details>` element.
     * @param array<array-key, mixed> $headerOptions HTML attributes of the `<summary>` element.
     * @param array<array-key, mixed> $bodyOptions HTML attributes of the `.accordion-body` element.
     */
    private function __construct(
        public string|Stringable $label,
        public string|Stringable $content,
        public bool $open = false,
        public bool $visible = true,
        public bool|null $encode = null,
        public array $options = [],
        public array $headerOptions = [],
        public array $bodyOptions = [],
    ) {}

    /**
     * Creates an item from the array configuration documented in `Accordion::$items`.
     *
     * Only `true` opens the item and only `false` hides it; an `encode` other than `null` is compared with `false`;
     * attribute keys that are not arrays are ignored.
     *
     * @param array<array-key, mixed> $config Item configuration.
     *
     * @throws InvalidConfigException if the item has no `string|Stringable` `label` or `content`.
     *
     * @return self A new instance.
     */
    public static function fromArray(array $config): self
    {
        $label = self::textValue($config, 'label', Message::ITEM_LABEL_REQUIRED);
        $content = self::textValue($config, 'content', Message::ITEM_CONTENT_REQUIRED);

        return new self(
            $label,
            $content,
            ($config['open'] ?? false) === true,
            ($config['visible'] ?? true) !== false,
            isset($config['encode']) ? $config['encode'] !== false : null,
            self::arrayValue($config, 'options'),
            self::arrayValue($config, 'headerOptions'),
            self::arrayValue($config, 'bodyOptions'),
        );
    }

    /**
     * Creates an item.
     *
     * @param string|Stringable $label Content of the `<summary>` element.
     * @param string|Stringable $content Content of the `.accordion-body` element (not encoded).
     *
     * @return self A new instance.
     */
    public static function to(string|Stringable $label, string|Stringable $content): self
    {
        return new self($label, $content);
    }

    /**
     * Returns a copy with the given `.accordion-body` attributes.
     *
     * @param array<array-key, mixed> $bodyOptions HTML attributes of the `.accordion-body` element.
     *
     * @return self A new instance with the updated configuration.
     */
    public function withBodyOptions(array $bodyOptions): self
    {
        return $this->with('bodyOptions', $bodyOptions);
    }

    /**
     * Returns a copy with the given content.
     *
     * @param string|Stringable $content Content of the `.accordion-body` element (not encoded).
     *
     * @return self A new instance with the updated configuration.
     */
    public function withContent(string|Stringable $content): self
    {
        return $this->with('content', $content);
    }

    /**
     * Returns a copy with the given encoding flag.
     *
     * @param bool|null $encode Whether to HTML-encode {@see $label}, or `null` to use `Accordion::$encodeLabels`.
     *
     * @return self A new instance with the updated configuration.
     */
    public function withEncode(bool|null $encode): self
    {
        return $this->with('encode', $encode);
    }

    /**
     * Returns a copy with the given `<summary>` attributes.
     *
     * @param array<array-key, mixed> $headerOptions HTML attributes of the `<summary>` element.
     *
     * @return self A new instance with the updated configuration.
     */
    public function withHeaderOptions(array $headerOptions): self
    {
        return $this->with('headerOptions', $headerOptions);
    }

    /**
     * Returns a copy with the given label.
     *
     * @param string|Stringable $label Content of the `<summary>` element.
     *
     * @return self A new instance with the updated configuration.
     */
    public function withLabel(string|Stringable $label): self
    {
        return $this->with('label', $label);
    }

    /**
     * Returns a copy with the given expanded state.
     *
     * @param bool $open Whether the item is initially expanded.
     *
     * @return self A new instance with the updated configuration.
     */
    public function withOpen(bool $open): self
    {
        return $this->with('open', $open);
    }

    /**
     * Returns a copy with the given `<details>` attributes.
     *
     * @param array<array-key, mixed> $options HTML attributes of the `<details>` element.
     *
     * @return self A new instance with the updated configuration.
     */
    public function withOptions(array $options): self
    {
        return $this->with('options', $options);
    }

    /**
     * Returns a copy with the given visibility.
     *
     * @param bool $visible Whether to render the item.
     *
     * @return self A new instance with the updated configuration.
     */
    public function withVisible(bool $visible): self
    {
        return $this->with('visible', $visible);
    }
}
