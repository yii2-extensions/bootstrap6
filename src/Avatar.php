<?php

declare(strict_types=1);

namespace yii2\extensions\bootstrap6;

use yii\base\InvalidConfigException;
use yii2\extensions\bootstrap6\asset\BootstrapAsset;
use yii2\extensions\bootstrap6\enum\{Size, Theme};
use yii2\extensions\bootstrap6\exception\Message;

use function implode;
use function in_array;
use function ucfirst;

/**
 * Renders a Bootstrap avatar with an image or initials and an optional status indicator.
 *
 * @see https://getbootstrap.com/docs/6.0/components/avatar/
 */
class Avatar extends Widget
{
    /**
     * Allowed values of {@see $status}.
     *
     * @var list<string>
     */
    protected const array STATUSES = ['online', 'offline', 'busy', 'away'];

    /**
     * Alternative text of the image.
     */
    public string $alt = '';
    /**
     * Whether to HTML-encode {@see $initials}.
     */
    public bool $encodeLabel = true;
    /**
     * Initials rendered when {@see $src} is `null`.
     */
    public string|null $initials = null;
    /**
     * Avatar size rendered as the `avatar-{value}` class, or `null` for the default size.
     */
    public Size|string|null $size = null;
    /**
     * Image URL rendered as the `.avatar-img` element, or `null` to render {@see $initials}.
     */
    public string|null $src = null;
    /**
     * Status indicator (`online`, `offline`, `busy`, `away`), or `null` for none.
     */
    public string|null $status = null;
    /**
     * Accessible label of the status indicator, or `null` to use the capitalized {@see $status}.
     */
    public string|null $statusLabel = null;
    /**
     * Whether to render the subtle variant (`avatar-subtle`).
     */
    public bool $subtle = false;
    /**
     * Theme color rendered as the `theme-{value}` class, or `null` for the neutral color.
     */
    public Theme|string|null $theme = null;

    /**
     * Initializes the widget and validates {@see $size}, {@see $status}, and {@see $theme}.
     *
     * @throws InvalidConfigException if {@see $size}, {@see $status}, or {@see $theme} is invalid.
     */
    public function init(): void
    {
        parent::init();

        Html::enumValue(Size::class, $this->size, 'size');
        Html::enumValue(Theme::class, $this->theme, 'theme');

        if ($this->status !== null && !in_array($this->status, static::STATUSES, true)) {
            throw new InvalidConfigException(
                Message::INVALID_VALUE->getMessage($this->status, 'status', implode(', ', static::STATUSES)),
            );
        }
    }

    public function run(): string
    {
        BootstrapAsset::register($this->getView());

        $options = $this->options;
        $size = Html::enumValue(Size::class, $this->size, 'size');

        Html::addCssClass($options, 'avatar');

        if ($size !== null) {
            Html::addCssClass($options, "avatar-{$size}");
        }

        if ($this->subtle) {
            Html::addCssClass($options, 'avatar-subtle');
        }

        Html::theme($options, $this->theme);

        $content = $this->src !== null
            ? "\n" . Html::img($this->src, ['class' => 'avatar-img', 'alt' => $this->alt])
            : ($this->encodeLabel ? Html::encode($this->initials ?? '') : $this->initials ?? '');

        if ($this->status !== null) {
            $content .= "\n" . Html::tag(
                'span',
                '',
                [
                    'class' => "avatar-status status-{$this->status}",
                    'role' => 'img',
                    'aria-label' => $this->statusLabel ?? ucfirst($this->status),
                ],
            );
        }

        if ($this->src !== null || $this->status !== null) {
            $content .= "\n";
        }

        return Html::tag(
            'span',
            $content,
            $options,
        );
    }
}
