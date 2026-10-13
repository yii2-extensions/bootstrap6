<?php

declare(strict_types=1);

namespace yii2\extensions\bootstrap6;

use Stringable;
use Yii;
use yii\base\InvalidConfigException;
use yii\helpers\{ArrayHelper, Url};
use yii2\extensions\bootstrap6\enum\{ButtonVariant, Placement, Size, Theme};

use function array_filter;
use function is_bool;

/**
 * Renders a button that toggles a {@see Menu}, or a split button group with a separate menu toggle.
 *
 * {@see $options} holds the attributes of the element that carries `data-bs-toggle="menu"`, so its `id` is the target
 * of the Menu plugin initialization and of {@see $clientEvents}.
 *
 * @see https://getbootstrap.com/docs/6.0/components/menu/
 * @see https://getbootstrap.com/docs/6.0/components/button-group/
 */
class ButtonMenu extends Widget
{
    /**
     * Auto close behavior: `true`, `false`, `inside`, or `outside`; `null` keeps the plugin default.
     */
    public bool|string|null $autoClose = null;
    /**
     * HTML attributes of the action button in split mode; the `href` option is used when {@see $tagName} is `a`.
     *
     * @var array<array-key, mixed>
     */
    public array $buttonOptions = [];
    /**
     * HTML attributes of the `.btn-group` container; the `tag` option sets the tag name (default `div`).
     *
     * @var array<array-key, mixed>
     */
    public array $containerOptions = [];
    /**
     * Whether to HTML-encode {@see $label}.
     */
    public bool $encodeLabel = true;
    /**
     * Button label, or `null` to use the translated `Button` text.
     */
    public string|Stringable|null $label = null;
    /**
     * Configuration of the {@see Menu} widget; its `id` defaults to `{id}-menu`.
     *
     * @var array<string, mixed>
     */
    public array $menu = [];
    /**
     * Class of the menu widget.
     *
     * @var class-string<Menu>
     */
    public string $menuClass = Menu::class;
    /**
     * Menu placement, such as `top` or `bottom-start md:bottom-end`; `null` keeps the plugin default.
     */
    public Placement|string|null $placement = null;
    /**
     * Whether to render the `.btn-group` container when {@see $split} is `false`.
     */
    public bool $renderContainer = false;
    /**
     * Button size, or `null` for the default size.
     */
    public Size|string|null $size = null;
    /**
     * Whether to render a split button: an action button followed by a `.menu-toggle-split` toggle.
     */
    public bool $split = false;
    /**
     * HTML rendered inside the split toggle before its visually hidden text, for example an icon.
     */
    public string $splitIcon = '';
    /**
     * Tag name of the main button, either `button` or `a`.
     */
    public string $tagName = 'button';
    /**
     * Theme color, or `null` for the default color.
     */
    public Theme|string|null $theme = null;
    /**
     * Button style class, or `null` to render no style class.
     */
    public ButtonVariant|string|null $variant = ButtonVariant::SOLID;

    /**
     * Initializes the widget and validates {@see $placement}, {@see $size}, {@see $theme}, and {@see $variant}.
     *
     * @throws InvalidConfigException if one of them is not a value of its enum; {@see $placement} also accepts
     * breakpoint-prefixed values.
     */
    public function init(): void
    {
        parent::init();

        Html::enumValue(Placement::class, $this->placement, 'placement', true);
        Html::enumValue(Size::class, $this->size, 'size');
        Html::enumValue(Theme::class, $this->theme, 'theme');
        Html::enumValue(ButtonVariant::class, $this->variant, 'variant');
    }

    /**
     * @throws InvalidConfigException if a menu item has no `label`.
     */
    public function run(): string
    {
        /** @var string $id */
        $id = ArrayHelper::getValue($this->options, 'id');

        $toggle = $this->options;

        $toggle['data-bs-toggle'] = 'menu';

        $placement = Html::enumValue(Placement::class, $this->placement, 'placement', true);

        if ($placement !== null) {
            $toggle['data-bs-placement'] = $placement;
        }

        if ($this->autoClose !== null) {
            $toggle['data-bs-auto-close'] = is_bool($this->autoClose)
                ? ($this->autoClose ? 'true' : 'false')
                : $this->autoClose;
        }

        $toggle['aria-expanded'] = 'false';

        $label = (string) ($this->label ?? Yii::t(Bootstrap::TRANSLATION_CATEGORY, 'Button'));
        $label = $this->encodeLabel ? Html::encode($label) : $label;

        if ($this->split) {
            Html::addCssClass($toggle, ['split' => 'menu-toggle-split']);

            $html = $this->renderButton($this->tagName, $label, $this->buttonOptions) . "\n"
                . $this->renderButton(
                    'button',
                    $this->splitIcon
                    . Html::tag(
                        'span',
                        Yii::t(Bootstrap::TRANSLATION_CATEGORY, 'Toggle menu'),
                        ['class' => 'visually-hidden'],
                    ),
                    $toggle,
                );
        } else {
            $html = $this->renderButton($this->tagName, $label, $toggle);
        }

        $menu = $this->menu;

        /** @var array<string, mixed> $menuOptions */
        $menuOptions = $menu['options'] ?? [];
        $menuOptions['id'] ??= "{$id}-menu";
        $menu['options'] = $menuOptions;
        $menu['view'] = $this->getView();
        $html .= "\n" . $this->menuClass::widget($menu);

        $this->registerPlugin('Menu');

        if ($this->split || $this->renderContainer) {
            $options = $this->containerOptions;

            /** @var string $tag */
            $tag = ArrayHelper::remove($options, 'tag', 'div');

            Html::addCssClass($options, ['widget' => 'btn-group']);

            $html = Html::tag($tag, $html, $options);
        }

        return $html;
    }

    /**
     * Renders a button with the style, theme, and size classes.
     *
     * @param string $tagName Tag name, either `button` or `a`.
     * @param string $content Button content, already encoded.
     * @param array<array-key, mixed> $options HTML attributes of the button.
     */
    protected function renderButton(string $tagName, string $content, array $options): string
    {
        $size = Html::enumValue(Size::class, $this->size, 'size');
        $theme = Html::enumValue(Theme::class, $this->theme, 'theme');

        $classes = array_filter(
            [
                Html::enumValue(ButtonVariant::class, $this->variant, 'variant'),
                $theme === null ? null : "theme-{$theme}",
                $size === null ? null : "btn-{$size}",
            ],
            static fn(string|null $class): bool => $class !== null,
        );

        $configured = $options['class'] ?? [];
        $options['class'] = $classes;

        /** @var array<array-key, string>|string $configured */
        Html::addCssClass($options, $configured);

        if ($tagName === 'a') {
            /** @var array<array-key, mixed>|string $href */
            $href = $options['href'] ?? '#';
            $options['href'] = Url::to($href);
            $options['role'] ??= 'button';
        } else {
            $options['type'] ??= 'button';
        }

        return Html::tag(
            $tagName,
            $content,
            $options,
        );
    }
}
