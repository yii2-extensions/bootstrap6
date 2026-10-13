<?php

declare(strict_types=1);

namespace yii2\extensions\bootstrap6\item;

use Stringable;
use yii\base\InvalidConfigException;
use yii2\extensions\bootstrap6\enum\Theme;
use yii2\extensions\bootstrap6\exception\Message;
use yii2\extensions\bootstrap6\Html;

use function is_array;
use function is_bool;
use function is_string;

/**
 * Represents a `Stepper` step: a list item, a `<div>`, or a link when it has a URL.
 */
final readonly class StepItem
{
    use ItemTrait;

    /**
     * @param string|Stringable $label Label of the step.
     * @param string|Stringable|null $description Secondary text rendered under the label, or `null` for none.
     * @param bool $active Whether the step is completed or current (`.active`).
     * @param Theme|string|null $theme Theme color rendered as the `theme-{value}` class, or `null` for the default.
     * @param array<array-key, mixed>|string|null $url URL processed by {@see \yii\helpers\Url::to()}, or `null` for a
     * step without link; any step with a URL renders the stepper as links.
     * @param bool|null $encode Whether to HTML-encode {@see $label} and {@see $description}, or `null` to use
     * `Stepper::$encodeLabels`.
     * @param array<array-key, mixed> $options HTML attributes of the `.stepper-item` element.
     *
     * @throws InvalidConfigException if `$theme` is a `string` that is not a {@see Theme} value.
     */
    private function __construct(
        public string|Stringable $label,
        public string|Stringable|null $description = null,
        public bool $active = false,
        public Theme|string|null $theme = null,
        public array|string|null $url = null,
        public bool|null $encode = null,
        public array $options = [],
    ) {
        Html::enumValue(Theme::class, $theme, 'theme');
    }

    /**
     * Creates a step from the array configuration documented in `Stepper::$items`.
     *
     * Only `true` activates the step and only a `bool` `encode` is used; a `description`, `theme`, `url`, or
     * `options` of another type is ignored.
     *
     * @param array<array-key, mixed> $config Step configuration.
     *
     * @throws InvalidConfigException if the step has no `string|Stringable` `label`, or its `theme` is a `string`
     * that is not a {@see Theme} value.
     */
    public static function fromArray(array $config): self
    {
        $label = self::textValue($config, 'label', Message::ITEM_LABEL_REQUIRED);

        $description = $config['description'] ?? null;
        $theme = $config['theme'] ?? null;
        $url = $config['url'] ?? null;
        $encode = $config['encode'] ?? null;

        return new self(
            $label,
            is_string($description) || $description instanceof Stringable ? $description : null,
            ($config['active'] ?? false) === true,
            is_string($theme) || $theme instanceof Theme ? $theme : null,
            is_string($url) || is_array($url) ? $url : null,
            is_bool($encode) ? $encode : null,
            self::arrayValue($config, 'options'),
        );
    }

    /**
     * Creates a step.
     *
     * @param string|Stringable $label Label of the step.
     * @param string|Stringable|null $description Secondary text rendered under the label, or `null` for none.
     */
    public static function to(string|Stringable $label, string|Stringable|null $description = null): self
    {
        return new self($label, $description);
    }

    /**
     * Returns a copy with the given active state.
     */
    public function withActive(bool $active): self
    {
        return $this->with('active', $active);
    }

    /**
     * Returns a copy with the given description.
     */
    public function withDescription(string|Stringable|null $description): self
    {
        return $this->with('description', $description);
    }

    /**
     * Returns a copy with the given encoding flag.
     */
    public function withEncode(bool|null $encode): self
    {
        return $this->with('encode', $encode);
    }

    /**
     * Returns a copy with the given label.
     */
    public function withLabel(string|Stringable $label): self
    {
        return $this->with('label', $label);
    }

    /**
     * Returns a copy with the given `.stepper-item` attributes.
     *
     * @param array<array-key, mixed> $options HTML attributes of the `.stepper-item` element.
     */
    public function withOptions(array $options): self
    {
        return $this->with('options', $options);
    }

    /**
     * Returns a copy with the given theme color.
     *
     * @throws InvalidConfigException if `$theme` is a `string` that is not a {@see Theme} value.
     */
    public function withTheme(Theme|string|null $theme): self
    {
        return $this->with('theme', $theme);
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
}
