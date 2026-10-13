<?php

declare(strict_types=1);

namespace yii2\extensions\bootstrap6;

use Stringable;
use Yii;
use yii\base\InvalidConfigException;
use yii\helpers\ArrayHelper;
use yii2\extensions\bootstrap6\enum\{Breakpoint, DrawerPlacement};
use yii2\extensions\bootstrap6\exception\Message;

use function implode;
use function is_array;
use function is_string;
use function ob_get_clean;
use function ob_start;

/**
 * Renders a Bootstrap drawer on the native `<dialog>` element, optionally preceded by a toggle button.
 *
 * Content captured between {@see begin()} and {@see end()} is rendered inside the `.drawer-body` element.
 *
 * @see https://getbootstrap.com/docs/6.0/components/drawer/
 */
class Drawer extends Widget
{
    use TogglerAttributesTrait;

    /**
     * Backdrop behavior: `true` closes the drawer on backdrop click, `false` hides the backdrop, and `static` keeps the
     * drawer open on backdrop click.
     */
    public bool|string $backdrop = true;
    /**
     * HTML attributes of the `.drawer-body` element.
     *
     * @var array<string, mixed>
     */
    public array $bodyOptions = [];
    /**
     * HTML attributes of the close button, see {@see Html::closeButton()}, or `false` to omit the button.
     *
     * @var array<string, mixed>|false
     */
    public array|false $closeButton = [];
    /**
     * Whether to HTML-encode {@see $title}.
     */
    public bool $encodeTitle = true;
    /**
     * Content of the `.drawer-footer` element (not encoded), or `null` to omit the footer.
     */
    public string|Stringable|null $footer = null;
    /**
     * HTML attributes of the `.drawer-footer` element.
     *
     * @var array<string, mixed>
     */
    public array $footerOptions = [];
    /**
     * Whether the drawer covers the entire viewport (`.drawer-fullscreen`); replaces the {@see $placement} class.
     */
    public bool $fullscreen = false;
    /**
     * HTML attributes of the `.drawer-header` element.
     *
     * @var array<string, mixed>
     */
    public array $headerOptions = [];
    /**
     * Whether the Escape key closes the drawer.
     */
    public bool $keyboard = true;
    /**
     * Placement class, for example {@see DrawerPlacement::END}, or `null` to omit it.
     */
    public DrawerPlacement|string|null $placement = DrawerPlacement::START;
    /**
     * Breakpoint from which the content renders inline instead of in a drawer (`md` renders `md:drawer`), or `null`.
     */
    public Breakpoint|string|null $responsive = null;
    /**
     * Whether the page body keeps scrolling while the drawer is open.
     */
    public bool $scroll = false;
    /**
     * Whether the drawer renders flush against the viewport edge (`.drawer-sheet`).
     */
    public bool $sheet = false;
    /**
     * Content of the `.drawer-title` element, or `null` to omit the title.
     */
    public string|Stringable|null $title = null;
    /**
     * HTML attributes of the `.drawer-title` element; the `tag` option sets the tag name (default `h5`).
     *
     * @var array<string, mixed>
     */
    public array $titleOptions = [];
    /**
     * HTML attributes of the toggle button rendered before the drawer, or `false` to omit the button.
     *
     * The `label` option sets the content (default translated `Show`), `encode` controls its encoding (default
     * `true`), and `tag` sets the tag name (default `button`).
     *
     * @var array<string, mixed>|false
     */
    public array|false $toggleButton = false;

    /**
     * Initializes the widget, validates its options, and starts capturing the content.
     *
     * @throws InvalidConfigException if {@see $backdrop}, {@see $placement}, or {@see $responsive} has an unsupported
     * value.
     */
    public function init(): void
    {
        parent::init();

        Html::enumValue(DrawerPlacement::class, $this->placement, 'placement');
        Html::enumValue(Breakpoint::class, $this->responsive, 'responsive');

        if (is_string($this->backdrop) && $this->backdrop !== 'static') {
            throw new InvalidConfigException(
                Message::INVALID_VALUE->getMessage($this->backdrop, 'backdrop', 'true, false, static'),
            );
        }

        ob_start();
    }

    public function run(): string
    {
        $content = ob_get_clean();

        $this->registerPlugin('Drawer');

        $toggleButton = $this->renderToggleButton();
        $header = $this->renderHeader();
        $bodyOptions = $this->bodyOptions;

        Html::addCssClass($bodyOptions, ['widget' => 'drawer-body']);

        return ($toggleButton === '' ? '' : $toggleButton . "\n")
            . Html::beginTag('dialog', $this->drawerOptions()) . "\n"
            . ($header === '' ? '' : $header . "\n")
            . Html::beginTag('div', $bodyOptions) . "\n"
            . $content
            . "\n" . Html::endTag('div') . $this->renderFooter() . "\n" . Html::endTag('dialog');
    }

    /**
     * Returns {@see $toggleButton} by reference, replacing `false` with an empty array to enable the toggle button.
     *
     * @return array<string, mixed>
     */
    protected function &togglerOptions(): array
    {
        if ($this->toggleButton === false) {
            $this->toggleButton = [];
        }

        return $this->toggleButton;
    }

    /**
     * Returns the HTML attributes of the `<dialog>` element.
     *
     * @return array<mixed>
     */
    protected function drawerOptions(): array
    {
        $options = $this->options;

        $data = is_array($options['data'] ?? null) ? $options['data'] : [];
        $aria = is_array($options['aria'] ?? null) ? $options['aria'] : [];

        $placement = Html::enumValue(DrawerPlacement::class, $this->placement, 'placement');
        $responsive = Html::enumValue(Breakpoint::class, $this->responsive, 'responsive');

        Html::addCssClass($options, ['widget' => $responsive === null ? 'drawer' : "{$responsive}:drawer"]);

        if ($this->fullscreen) {
            Html::addCssClass($options, ['placement' => 'drawer-fullscreen']);
        } elseif ($placement !== null) {
            Html::addCssClass($options, ['placement' => $placement]);
        }

        if ($this->sheet) {
            Html::addCssClass($options, ['sheet' => 'drawer-sheet']);
        }

        $options['tabindex'] ??= '-1';

        if ($this->title !== null && !isset($aria['label']) && !isset($aria['labelledby'])) {
            $aria['labelledby'] = $this->titleId();
        }

        if ($this->backdrop !== true) {
            $data['bs-backdrop'] = $this->backdrop === false ? 'false' : $this->backdrop;
        }

        if ($this->keyboard === false) {
            $data['bs-keyboard'] = 'false';
        }

        if ($this->scroll) {
            $data['bs-scroll'] = 'true';
        }

        $options['aria'] = $aria;
        $options['data'] = $data;

        return $options;
    }

    /**
     * Renders the `.drawer-footer` element prefixed with a line break, or an empty string without {@see $footer}.
     */
    protected function renderFooter(): string
    {
        if ($this->footer === null) {
            return '';
        }

        $options = $this->footerOptions;

        Html::addCssClass($options, ['widget' => 'drawer-footer']);

        return "\n"
            . Html::tag(
                'div',
                "\n{$this->footer}\n",
                $options,
            );
    }

    /**
     * Renders the `.drawer-header` element, or an empty string when there is neither a title nor a close button.
     */
    protected function renderHeader(): string
    {
        $parts = [];

        if ($this->title !== null) {
            $options = $this->titleOptions;

            $tag = ArrayHelper::remove($options, 'tag', 'h5');

            $options['id'] = $this->titleId();

            Html::addCssClass($options, ['widget' => 'drawer-title']);

            $parts[] = Html::tag(
                is_string($tag) ? $tag : 'h5',
                $this->encodeTitle ? Html::encode($this->title) : $this->title,
                $options,
            );
        }

        if ($this->closeButton !== false) {
            $closeButton = $this->closeButton;
            $data = is_array($closeButton['data'] ?? null) ? $closeButton['data'] : [];

            $data['bs-dismiss'] ??= 'drawer';
            $closeButton['data'] = $data;

            $parts[] = Html::closeButton($closeButton);
        }

        if ($parts === []) {
            return '';
        }

        $options = $this->headerOptions;

        Html::addCssClass($options, ['widget' => 'drawer-header']);

        return Html::tag(
            'div',
            "\n" . implode("\n", $parts) . "\n",
            $options,
        );
    }

    /**
     * Renders the toggle button, or an empty string when {@see $toggleButton} is `false`.
     */
    protected function renderToggleButton(): string
    {
        if ($this->toggleButton === false) {
            return '';
        }

        $options = $this->toggleButton;

        $label = ArrayHelper::remove($options, 'label', Yii::t(Bootstrap::TRANSLATION_CATEGORY, 'Show'));
        $encode = ArrayHelper::remove($options, 'encode', true);
        $tag = ArrayHelper::remove($options, 'tag', 'button');

        $tag = is_string($tag) ? $tag : 'button';
        $data = is_array($options['data'] ?? null) ? $options['data'] : [];
        $aria = is_array($options['aria'] ?? null) ? $options['aria'] : [];

        $options['class'] ??= ['btn-solid', 'theme-primary'];

        if ($tag === 'button') {
            $options['type'] ??= 'button';
        }

        $data['bs-toggle'] = 'drawer';

        if (!isset($options['href'])) {
            $data['bs-target'] ??= '#' . $this->elementId();
        }

        $aria['controls'] ??= $this->elementId();
        $options['data'] = $data;
        $options['aria'] = $aria;
        $label = is_string($label) ? $label : '';

        return Html::tag(
            $tag,
            $encode === false ? $label : Html::encode($label),
            $options,
        );
    }

    /**
     * Returns the `id` of the drawer element.
     */
    private function elementId(): string
    {
        /** @var string */
        return $this->options['id'];
    }

    /**
     * Returns the `id` of the title element.
     */
    private function titleId(): string
    {
        $id = $this->titleOptions['id'] ?? null;

        return is_string($id) ? $id : $this->elementId() . '-label';
    }
}
