<?php

declare(strict_types=1);

namespace yii2\extensions\bootstrap6;

use Stringable;
use Yii;
use yii\base\InvalidConfigException;
use yii\helpers\ArrayHelper;
use yii2\extensions\bootstrap6\asset\{BootstrapAsset, BootstrapPluginAsset};
use yii2\extensions\bootstrap6\enum\{Breakpoint, DrawerPlacement};

use function implode;
use function ob_get_clean;
use function ob_start;

/**
 * Renders a Bootstrap navbar whose content collapses into a drawer below the {@see $expand} breakpoint.
 *
 * Content captured between {@see begin()} and {@see end()} is rendered inside the `.drawer-body`, or directly inside the
 * navbar when {@see $drawerOptions} is `false`.
 *
 * @see https://getbootstrap.com/docs/6.0/components/navbar/
 */
class NavBar extends Widget
{
    use TogglerAttributesTrait;

    /**
     * Source of the brand image, or `false` to render no image.
     */
    public string|false $brandImage = false;
    /**
     * HTML attributes of the brand image.
     *
     * @var array<array-key, mixed>
     */
    public array $brandImageOptions = [];
    /**
     * Brand text, or `false` to render no text.
     */
    public string|Stringable|false $brandLabel = false;
    /**
     * HTML attributes of the `.navbar-brand` element.
     *
     * @var array<array-key, mixed>
     */
    public array $brandOptions = [];
    /**
     * Brand URL processed by {@see \yii\helpers\Url::to()}; `false` uses the application home URL and `null` renders
     * a `<span>` instead of a link.
     *
     * @var array<array-key, mixed>|string|false|null
     */
    public array|string|false|null $brandUrl = false;
    /**
     * Drawer configuration, or `false` to render the content inline without toggler and drawer.
     *
     * - `title`: `string`, drawer title, HTML-encoded. Defaults to the translated `Menu` text.
     * - `placement`: {@see DrawerPlacement}|`string`, side the drawer slides in from, validated when rendered. Defaults
     *   to {@see DrawerPlacement::END}.
     * - `options`: `array`, HTML attributes of the `<dialog>` element; its `id` defaults to `{id}-drawer`.
     * - `bodyOptions`: `array`, HTML attributes of the `.drawer-body` element.
     *
     * @var array<string, mixed>|false
     */
    public array|false $drawerOptions = [];
    /**
     * Whether to HTML-encode {@see $brandLabel}.
     */
    public bool $encodeBrandLabel = true;
    /**
     * Breakpoint from which the navbar expands and the drawer content renders inline; `null` always expands and
     * `false` never expands.
     */
    public Breakpoint|string|false|null $expand = Breakpoint::LG;
    /**
     * HTML attributes of the inner container; the `class` defaults to `container-fluid`.
     *
     * @var array<array-key, mixed>
     */
    public array $innerContainerOptions = [];
    /**
     * Whether to wrap the navbar content in the inner container.
     */
    public bool $renderInnerContainer = true;
    /**
     * Accessible label of the toggler, or `null` to use the translated `Toggle navigation` text.
     */
    public string|Stringable|null $screenReaderToggleText = null;
    /**
     * HTML content of the toggler.
     */
    public string|Stringable $togglerContent = '<span class="navbar-toggler-icon" aria-hidden="true"></span>';
    /**
     * HTML attributes of the toggler button.
     *
     * @var array<array-key, mixed>
     */
    public array $togglerOptions = [];
    /**
     * Tag name of the navbar element, read from the `tag` option.
     */
    private string $tag = 'nav';

    /**
     * Initializes the widget, validates {@see $expand}, and starts capturing the content.
     *
     * @throws InvalidConfigException if {@see $expand} is a `string` that is not a {@see Breakpoint} value.
     */
    public function init(): void
    {
        parent::init();

        if ($this->expand !== false) {
            Html::enumValue(Breakpoint::class, $this->expand, 'expand');
        }

        ob_start();
    }

    /**
     * Returns the complete navbar markup wrapping the captured content.
     */
    public function run(): string
    {
        $content = ob_get_clean();
        $begin = $this->renderBegin();
        $view = $this->getView();

        if ($this->drawerOptions === false) {
            BootstrapAsset::register($view);

            $html = '';
        } else {
            BootstrapPluginAsset::register($view);

            $html = "</div>\n</dialog>\n";
        }

        if ($this->renderInnerContainer) {
            $html .= "</div>\n";
        }

        return "{$begin}{$content}{$html}" . Html::endTag($this->tag);
    }

    /**
     * Returns {@see $togglerOptions} by reference.
     *
     * @return array<mixed>
     */
    protected function &togglerOptions(): array
    {
        return $this->togglerOptions;
    }

    /**
     * Renders the `.navbar-brand` element, or an empty string when there is no brand image and no brand label.
     */
    protected function renderBrand(): string
    {
        $content = [];

        if ($this->brandImage !== false) {
            $content[] = Html::img($this->brandImage, $this->brandImageOptions);
        }

        if ($this->brandLabel !== false) {
            $content[] = $this->encodeBrandLabel ? Html::encode($this->brandLabel) : $this->brandLabel;
        }

        if ($content === []) {
            return '';
        }

        $options = $this->brandOptions;

        Html::addCssClass($options, ['widget' => 'navbar-brand']);

        if ($this->brandUrl === null) {
            return Html::tag('span', implode(' ', $content), $options);
        }

        return Html::a(
            implode(' ', $content),
            $this->brandUrl === false ? Yii::$app->homeUrl : $this->brandUrl,
            $options,
        );
    }

    /**
     * Renders the opening part of the drawer: the `<dialog>`, its header, and the opening `.drawer-body` tag.
     */
    protected function renderDrawerBegin(): string
    {
        /** @var array<string, mixed> $drawer */
        $drawer = $this->drawerOptions;

        /** @var array<array-key, mixed> $options */
        $options = $drawer['options'] ?? [];

        /** @var array<array-key, mixed> $bodyOptions */
        $bodyOptions = $drawer['bodyOptions'] ?? [];

        /** @var DrawerPlacement|string $placement */
        $placement = $drawer['placement'] ?? DrawerPlacement::END;

        /** @var string $title */
        $title = $drawer['title'] ?? Yii::t(Bootstrap::TRANSLATION_CATEGORY, 'Menu');
        $id = $this->drawerId();

        $options['id'] = $id;
        $options['tabindex'] ??= '-1';
        $options['aria-labelledby'] = "{$id}-label";

        Html::addCssClass(
            $options,
            ['widget' => 'drawer', Html::enumValue(DrawerPlacement::class, $placement, 'placement')],
        );
        Html::addCssClass($bodyOptions, ['widget' => 'drawer-body']);

        return implode(
            "\n",
            [
                Html::beginTag('dialog', $options),
                Html::beginTag('div', ['class' => 'drawer-header']),
                Html::tag('h5', Html::encode($title), ['class' => 'drawer-title', 'id' => "{$id}-label"]),
                Html::closeButton(['data-bs-dismiss' => 'drawer']),
                Html::endTag('div'),
                Html::beginTag('div', $bodyOptions),
            ],
        );
    }

    /**
     * Renders the toggler button that opens the drawer.
     */
    protected function renderToggler(): string
    {
        $id = $this->drawerId();
        $options = $this->togglerOptions;

        Html::addCssClass($options, ['widget' => 'btn-icon navbar-toggler']);

        $options['type'] = 'button';
        $options['data-bs-toggle'] = 'drawer';
        $options['data-bs-target'] = "#{$id}";
        $options['aria-controls'] = $id;
        $options['aria-expanded'] = 'false';
        $options['aria-label'] = $this->screenReaderToggleText
            ?? Yii::t(Bootstrap::TRANSLATION_CATEGORY, 'Toggle navigation');

        return Html::tag(
            'button',
            $this->togglerContent,
            $options,
        );
    }

    /**
     * Returns the `id` of the drawer.
     */
    private function drawerId(): string
    {
        /** @var array<string, mixed> $drawer */
        $drawer = $this->drawerOptions;

        /** @var array<array-key, mixed> $options */
        $options = $drawer['options'] ?? [];

        /** @var string $widgetId */
        $widgetId = ArrayHelper::getValue($this->options, 'id');

        /** @var string */
        return $options['id'] ?? "{$widgetId}-drawer";
    }

    /**
     * Renders the opening part of the navbar up to the opening `.drawer-body` tag, and resolves {@see $tag}.
     */
    private function renderBegin(): string
    {
        $options = $this->options;

        /** @var string $tag */
        $tag = ArrayHelper::remove($options, 'tag', 'nav');
        $this->tag = $tag;

        Html::addCssClass($options, ['widget' => 'navbar']);

        if ($this->expand !== false) {
            $expand = Html::enumValue(Breakpoint::class, $this->expand, 'expand');

            Html::addCssClass($options, $expand === null ? 'navbar-expand' : "{$expand}:navbar-expand");
        }

        $lines = [Html::beginTag($tag, $options)];

        if ($this->renderInnerContainer) {
            $containerOptions = $this->innerContainerOptions;
            $containerOptions['class'] ??= 'container-fluid';
            $lines[] = Html::beginTag('div', $containerOptions);
        }

        $brand = $this->renderBrand();

        if ($brand !== '') {
            $lines[] = $brand;
        }

        if ($this->drawerOptions !== false) {
            $lines[] = $this->renderToggler();
            $lines[] = $this->renderDrawerBegin();
        }

        return implode("\n", $lines) . "\n";
    }
}
