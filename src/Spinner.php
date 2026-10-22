<?php

declare(strict_types=1);

namespace yii2\extensions\bootstrap6;

use Stringable;
use Yii;
use yii\base\InvalidConfigException;
use yii2\extensions\bootstrap6\asset\BootstrapAsset;
use yii2\extensions\bootstrap6\enum\Theme;
use yii2\extensions\bootstrap6\exception\Message;

use function implode;
use function in_array;

/**
 * Renders a Bootstrap border or grow spinner with a visually hidden status label.
 *
 * @see https://getbootstrap.com/docs/6.0/components/spinner/
 */
class Spinner extends Widget
{
    /**
     * Border spinner (`spinner-border`).
     */
    public const string TYPE_BORDER = 'border';
    /**
     * Growing spinner (`spinner-grow`).
     */
    public const string TYPE_GROW = 'grow';
    /**
     * Supported values of {@see $type}.
     */
    public const array TYPES = [self::TYPE_BORDER, self::TYPE_GROW];
    /**
     * Whether to HTML-encode {@see $label}.
     */
    public bool $encodeLabel = true;
    /**
     * Visually hidden status text, `null` for the translated `Loading...`, or an empty `string` to omit it.
     */
    public string|Stringable|null $label = null;
    /**
     * HTML attributes of the visually hidden label tag.
     *
     * @var array<string, mixed>
     */
    public array $labelOptions = [];
    /**
     * Whether to render the small spinner (`spinner-sm`).
     */
    public bool $small = false;
    /**
     * Tag name of the spinner element.
     */
    public string $tagName = 'div';
    /**
     * Theme color rendered as the `fg-{value}` text color utility, or `null` to inherit the current color.
     */
    public Theme|string|null $theme = null;
    /**
     * Spinner type, either {@see TYPE_BORDER} or {@see TYPE_GROW}.
     */
    public string $type = self::TYPE_BORDER;

    /**
     * Initializes the widget and validates {@see $theme} and {@see $type}.
     *
     * @throws InvalidConfigException if {@see $theme} or {@see $type} is not supported.
     */
    public function init(): void
    {
        parent::init();

        Html::enumValue(Theme::class, $this->theme, 'theme');

        if (!in_array($this->type, self::TYPES, true)) {
            throw new InvalidConfigException(
                Message::INVALID_VALUE->getMessage($this->type, 'type', implode(', ', self::TYPES)),
            );
        }
    }

    /**
     * Returns the spinner markup with the visually hidden status label.
     *
     * @throws InvalidConfigException if {@see $theme} is not a {@see Theme} value.
     *
     * @return string Rendered Spinner markup.
     */
    public function run(): string
    {
        BootstrapAsset::register($this->getView());

        $options = $this->options;
        $classes = ["spinner-{$this->type}"];

        if ($this->small) {
            $classes[] = 'spinner-sm';
        }

        $theme = Html::enumValue(Theme::class, $this->theme, 'theme');

        if ($theme !== null) {
            $classes[] = "fg-{$theme}";
        }

        Html::addCssClass($options, $classes);

        $options['role'] ??= 'status';

        return Html::tag(
            $this->tagName,
            $this->renderLabel(),
            $options,
        );
    }

    /**
     * Renders the visually hidden status label, or an empty `string` when {@see $label} is empty.
     *
     * @return string Visually hidden label markup, or an empty `string` for an empty label.
     */
    protected function renderLabel(): string
    {
        $label = (string) ($this->label ?? Yii::t(Bootstrap::TRANSLATION_CATEGORY, 'Loading...'));

        if ($label === '') {
            return '';
        }

        $labelOptions = $this->labelOptions;

        Html::addCssClass($labelOptions, 'visually-hidden');

        return "\n"
            . Html::tag(
                'span',
                $this->encodeLabel ? Html::encode($label) : $label,
                $labelOptions
            )
            . "\n";
    }
}
