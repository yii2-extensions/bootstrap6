<?php

declare(strict_types=1);

namespace yii2\extensions\bootstrap6\item;

use Stringable;
use yii\base\InvalidConfigException;
use yii2\extensions\bootstrap6\exception\Message;

/**
 * Represents a `Carousel` slide: a `.carousel-item` element with its content.
 */
final readonly class CarouselItem
{
    use ItemTrait;

    /**
     * Initializes the immutable CarouselItem configuration.
     *
     * @param string|Stringable $content Content of the slide (not encoded).
     * @param array<array-key, mixed> $options HTML attributes of the `.carousel-item` element.
     */
    private function __construct(public string|Stringable $content, public array $options = []) {}

    /**
     * Creates a slide from the array configuration documented in `Carousel::$items`.
     *
     * An `options` key that is not an array is ignored.
     *
     * @param array<array-key, mixed> $config Slide configuration.
     *
     * @throws InvalidConfigException if the slide has no `string|Stringable` `content`.
     *
     * @return self A new instance.
     */
    public static function fromArray(array $config): self
    {
        $content = self::textValue($config, 'content', Message::ITEM_CONTENT_REQUIRED);

        return new self(
            $content,
            self::arrayValue($config, 'options'),
        );
    }

    /**
     * Creates a slide.
     *
     * @param string|Stringable $content Content of the slide (not encoded).
     *
     * @return self A new instance.
     */
    public static function to(string|Stringable $content): self
    {
        return new self($content);
    }

    /**
     * Returns a copy with the given content.
     *
     * @param string|Stringable $content Content of the slide (not encoded).
     *
     * @return self A new instance with the updated configuration.
     */
    public function withContent(string|Stringable $content): self
    {
        return $this->with('content', $content);
    }

    /**
     * Returns a copy with the given `.carousel-item` attributes.
     *
     * @param array<array-key, mixed> $options HTML attributes of the `.carousel-item` element.
     *
     * @return self A new instance with the updated configuration.
     */
    public function withOptions(array $options): self
    {
        return $this->with('options', $options);
    }
}
