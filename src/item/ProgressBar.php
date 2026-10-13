<?php

declare(strict_types=1);

namespace yii2\extensions\bootstrap6\item;

use Stringable;
use yii\base\InvalidConfigException;
use yii2\extensions\bootstrap6\enum\Theme;
use yii2\extensions\bootstrap6\exception\Message;
use yii2\extensions\bootstrap6\Html;

use function get_debug_type;
use function is_numeric;
use function is_scalar;
use function is_string;

/**
 * Represents a `Progress` bar: the single bar, or a segment of a stacked bar.
 *
 * The range of {@see $percent} depends on the widget `min` and `max`, so the widget validates it when rendering.
 */
final readonly class ProgressBar
{
    use ItemTrait;

    /**
     * @param float|int $percent Current value, between the widget `min` and `max`.
     * @param string|Stringable $label Content of the `.progress-bar` element.
     * @param Theme|string|null $theme Theme color rendered as the `theme-{value}` class, or `null` for the default.
     * @param bool $striped Whether to render stripes (`progress-bar-striped`).
     * @param bool $animated Whether to animate the stripes (`progress-bar-animated`).
     * @param string|null $ariaLabel Accessible name rendered as `aria-label`, or `null` to omit it.
     * @param array<array-key, mixed> $options HTML attributes of the `.progress-bar` element.
     *
     * @throws InvalidConfigException if `$theme` is a `string` that is not a {@see Theme} value.
     */
    private function __construct(
        public float|int $percent,
        public string|Stringable $label = '',
        public Theme|string|null $theme = null,
        public bool $striped = false,
        public bool $animated = false,
        public string|null $ariaLabel = null,
        public array $options = [],
    ) {
        Html::enumValue(Theme::class, $theme, 'theme');
    }

    /**
     * Creates a bar from the array configuration documented in `Progress::$bars`.
     *
     * Only `true` enables `striped` and `animated`; a `label`, `theme`, `ariaLabel`, or `options` of another type is
     * ignored.
     *
     * @param array<array-key, mixed> $config Bar configuration.
     *
     * @throws InvalidConfigException if the bar has no `float|int` `percent`, or its `theme` is a `string` that is not
     * a {@see Theme} value.
     */
    public static function fromArray(array $config): self
    {
        $percent = $config['percent'] ?? null;

        if ($percent === null) {
            throw new InvalidConfigException(Message::OPTION_REQUIRED->getMessage('percent'));
        }

        if (!is_numeric($percent)) {
            throw new InvalidConfigException(
                Message::INVALID_VALUE->getMessage(
                    is_scalar($percent) ? (string) $percent : get_debug_type($percent),
                    'percent',
                    'a number',
                ),
            );
        }

        if (is_string($percent)) {
            $percent = (float) $percent;
        }

        $label = $config['label'] ?? '';
        $theme = $config['theme'] ?? null;
        $ariaLabel = $config['ariaLabel'] ?? null;

        return new self(
            $percent,
            is_string($label) || $label instanceof Stringable ? $label : '',
            is_string($theme) || $theme instanceof Theme ? $theme : null,
            ($config['striped'] ?? false) === true,
            ($config['animated'] ?? false) === true,
            is_string($ariaLabel) ? $ariaLabel : null,
            self::arrayValue($config, 'options'),
        );
    }

    /**
     * Creates a bar.
     *
     * @param float|int $percent Current value, between the widget `min` and `max`.
     * @param string|Stringable $label Content of the `.progress-bar` element.
     */
    public static function to(float|int $percent, string|Stringable $label = ''): self
    {
        return new self($percent, $label);
    }

    /**
     * Returns a copy with the given stripe animation flag.
     */
    public function withAnimated(bool $animated): self
    {
        return $this->with('animated', $animated);
    }

    /**
     * Returns a copy with the given accessible name.
     */
    public function withAriaLabel(string|null $ariaLabel): self
    {
        return $this->with('ariaLabel', $ariaLabel);
    }

    /**
     * Returns a copy with the given label.
     */
    public function withLabel(string|Stringable $label): self
    {
        return $this->with('label', $label);
    }

    /**
     * Returns a copy with the given `.progress-bar` attributes.
     *
     * @param array<array-key, mixed> $options HTML attributes of the `.progress-bar` element.
     */
    public function withOptions(array $options): self
    {
        return $this->with('options', $options);
    }

    /**
     * Returns a copy with the given value.
     */
    public function withPercent(float|int $percent): self
    {
        return $this->with('percent', $percent);
    }

    /**
     * Returns a copy with the given stripes flag.
     */
    public function withStriped(bool $striped): self
    {
        return $this->with('striped', $striped);
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
}
