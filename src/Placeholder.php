<?php

declare(strict_types=1);

namespace yii2\extensions\bootstrap6;

use yii\base\InvalidConfigException;
use yii\helpers\ArrayHelper;
use yii2\extensions\bootstrap6\asset\BootstrapAsset;
use yii2\extensions\bootstrap6\enum\{Size, Theme};
use yii2\extensions\bootstrap6\exception\Message;

use function implode;
use function in_array;
use function is_int;
use function is_string;

/**
 * Renders a Bootstrap loading placeholder (skeleton) element.
 *
 * @see https://getbootstrap.com/docs/6.0/components/placeholder/
 */
class Placeholder extends Widget
{
    /**
     * Animations rendered as the `placeholder-{animation}` class.
     */
    public const array ANIMATIONS = ['glow', 'pulse', 'wave'];
    /**
     * Loading animation (`wave`, `pulse`, or `glow`), or `null` for a static placeholder.
     *
     * The `pulse` and `glow` animations apply to descendant placeholders only, so they require {@see $container}.
     */
    public string|null $animation = null;
    /**
     * Whether to wrap the placeholder in a container element that carries the animation class.
     */
    public bool $container = false;
    /**
     * HTML attributes of the container tag; the `tag` option sets the tag name (default `p`).
     *
     * @var array<string, mixed>
     */
    public array $containerOptions = [];
    /**
     * Size modifier rendered as the `placeholder-{value}` class (`xs`, `sm`, or `lg`), or `null` for the default.
     */
    public Size|string|null $size = null;
    /**
     * Tag name of the placeholder element.
     */
    public string $tagName = 'span';
    /**
     * Theme color rendered as the `bg-{value}` background utility, or `null` to use the current color.
     */
    public Theme|string|null $theme = null;
    /**
     * Width as a grid column count rendered as `col-{value}`, a width class such as `w-75`, or `null` to omit it.
     */
    public int|string|null $width = null;

    /**
     * Initializes the widget and validates {@see $animation}, {@see $size}, and {@see $theme}.
     *
     * @throws InvalidConfigException if {@see $animation}, {@see $size}, or {@see $theme} is not supported.
     */
    public function init(): void
    {
        parent::init();

        Html::enumValue(Size::class, $this->size, 'size');
        Html::enumValue(Theme::class, $this->theme, 'theme');

        if ($this->animation !== null && !in_array($this->animation, self::ANIMATIONS, true)) {
            throw new InvalidConfigException(
                Message::INVALID_VALUE->getMessage($this->animation, 'animation', implode(', ', self::ANIMATIONS)),
            );
        }
    }

    public function run(): string
    {
        BootstrapAsset::register($this->getView());

        $options = $this->options;
        $classes = ['placeholder'];

        if ($this->width !== null) {
            $classes[] = is_int($this->width) ? "col-{$this->width}" : $this->width;
        }

        $size = Html::enumValue(Size::class, $this->size, 'size');
        $theme = Html::enumValue(Theme::class, $this->theme, 'theme');

        if ($size !== null) {
            $classes[] = "placeholder-{$size}";
        }

        if ($theme !== null) {
            $classes[] = "bg-{$theme}";
        }

        if ($this->animation !== null && $this->container === false) {
            $classes[] = "placeholder-{$this->animation}";
        }

        Html::addCssClass($options, $classes);

        $placeholder = Html::tag($this->tagName, '', $options);

        if ($this->container === false) {
            return $placeholder;
        }

        $containerOptions = $this->containerOptions;
        $tag = ArrayHelper::remove($containerOptions, 'tag', 'p');

        if ($this->animation !== null) {
            Html::addCssClass($containerOptions, "placeholder-{$this->animation}");
        }

        return Html::tag(
            is_string($tag) ? $tag : 'p',
            $placeholder,
            $containerOptions,
        );
    }
}
