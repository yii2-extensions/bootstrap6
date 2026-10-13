<?php

declare(strict_types=1);

namespace yii2\extensions\bootstrap6;

use Stringable;
use Yii;
use yii\base\InvalidConfigException;
use yii\helpers\ArrayHelper;
use yii2\extensions\bootstrap6\enum\{Breakpoint, DialogSize};
use yii2\extensions\bootstrap6\exception\Message;

use function implode;
use function in_array;
use function is_array;
use function is_string;
use function ob_get_clean;
use function ob_start;

/**
 * Renders a Bootstrap dialog on the native `<dialog>` element, optionally preceded by a toggle button.
 *
 * Content captured between {@see begin()} and {@see end()} is rendered inside the `.dialog-body` element.
 *
 * @see https://getbootstrap.com/docs/6.0/components/dialog/
 */
class Dialog extends Widget
{
    use TogglerAttributesTrait;

    /**
     * Backdrop behavior: `true` closes the dialog on backdrop click, `false` hides the backdrop, and `static` keeps the
     * dialog open on backdrop click.
     */
    public bool|string $backdrop = true;
    /**
     * HTML attributes of the `.dialog-body` element.
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
     * Content of the `.dialog-footer` element (not encoded), or `null` to omit the footer.
     */
    public string|Stringable|null $footer = null;
    /**
     * HTML attributes of the `.dialog-footer` element.
     *
     * @var array<string, mixed>
     */
    public array $footerOptions = [];
    /**
     * Breakpoint below which the dialog covers the viewport (`md` renders `md-down:dialog-fullscreen`), or `null`.
     */
    public Breakpoint|string|null $fullscreenBelow = null;
    /**
     * HTML attributes of the `.dialog-header` element.
     *
     * @var array<string, mixed>
     */
    public array $headerOptions = [];
    /**
     * Whether to disable the open and close animations (`.dialog-instant`).
     */
    public bool $instant = false;
    /**
     * Whether the Escape key closes the dialog.
     */
    public bool $keyboard = true;
    /**
     * Whether to open the dialog as non-modal, without backdrop and focus trap (`data-bs-modal="false"`).
     */
    public bool $nonModal = false;
    /**
     * Whether the body scrolls while the header and footer stay fixed (`.dialog-scrollable`).
     */
    public bool $scrollable = false;
    /**
     * Size class, for example {@see DialogSize::LG}, or `null` for the default size.
     */
    public DialogSize|string|null $size = null;
    /**
     * Slide-in animation direction, either `up` or `down`, or `null` for the default fade.
     */
    public string|null $slide = null;
    /**
     * Content of the `.dialog-title` element, or `null` to omit the title.
     */
    public string|Stringable|null $title = null;
    /**
     * HTML attributes of the `.dialog-title` element; the `tag` option sets the tag name (default `h1`).
     *
     * @var array<string, mixed>
     */
    public array $titleOptions = [];
    /**
     * HTML attributes of the toggle button rendered before the dialog, or `false` to omit the button.
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
     * @throws InvalidConfigException if {@see $backdrop}, {@see $fullscreenBelow}, {@see $size}, or {@see $slide} has
     * an unsupported value.
     */
    public function init(): void
    {
        parent::init();

        Html::enumValue(Breakpoint::class, $this->fullscreenBelow, 'fullscreenBelow');
        Html::enumValue(DialogSize::class, $this->size, 'size');

        if (is_string($this->backdrop) && $this->backdrop !== 'static') {
            throw new InvalidConfigException(
                Message::INVALID_VALUE->getMessage($this->backdrop, 'backdrop', 'true, false, static'),
            );
        }

        if ($this->slide !== null && !in_array($this->slide, ['down', 'up'], true)) {
            throw new InvalidConfigException(Message::INVALID_VALUE->getMessage($this->slide, 'slide', 'down, up'));
        }

        ob_start();
    }

    public function run(): string
    {
        $content = ob_get_clean();

        $this->registerPlugin('Dialog');

        $toggleButton = $this->renderToggleButton();
        $header = $this->renderHeader();
        $bodyOptions = $this->bodyOptions;

        Html::addCssClass($bodyOptions, ['widget' => 'dialog-body']);

        return ($toggleButton === '' ? '' : $toggleButton . "\n")
            . Html::beginTag('dialog', $this->dialogOptions()) . "\n"
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
    protected function dialogOptions(): array
    {
        $options = $this->options;
        $data = is_array($options['data'] ?? null) ? $options['data'] : [];
        $aria = is_array($options['aria'] ?? null) ? $options['aria'] : [];

        Html::addCssClass($options, ['widget' => 'dialog']);

        $breakpoint = Html::enumValue(Breakpoint::class, $this->fullscreenBelow, 'fullscreenBelow');
        $size = Html::enumValue(DialogSize::class, $this->size, 'size');

        if ($size !== null) {
            Html::addCssClass($options, ['size' => $size]);
        }

        if ($breakpoint !== null) {
            Html::addCssClass($options, ['fullscreen' => "{$breakpoint}-down:dialog-fullscreen"]);
        }

        if ($this->scrollable) {
            Html::addCssClass($options, ['scrollable' => 'dialog-scrollable']);
        }

        if ($this->slide !== null) {
            Html::addCssClass($options, ['slide' => "dialog-slide-{$this->slide}"]);
        }

        if ($this->instant) {
            Html::addCssClass($options, ['instant' => 'dialog-instant']);
        }

        if ($this->title !== null && !isset($aria['label']) && !isset($aria['labelledby'])) {
            $aria['labelledby'] = $this->titleId();
        }

        if ($this->backdrop !== true) {
            $data['bs-backdrop'] = $this->backdrop === false ? 'false' : $this->backdrop;
        }

        if ($this->keyboard === false) {
            $data['bs-keyboard'] = 'false';
        }

        if ($this->nonModal) {
            $data['bs-modal'] = 'false';
        }

        $options['aria'] = $aria;
        $options['data'] = $data;

        return $options;
    }

    /**
     * Renders the `.dialog-footer` element prefixed with a line break, or an empty string without {@see $footer}.
     */
    protected function renderFooter(): string
    {
        if ($this->footer === null) {
            return '';
        }

        $options = $this->footerOptions;

        Html::addCssClass($options, ['widget' => 'dialog-footer']);

        return "\n"
            . Html::tag(
                'div',
                "\n{$this->footer}\n",
                $options,
            );
    }

    /**
     * Renders the `.dialog-header` element, or an empty string when there is neither a title nor a close button.
     */
    protected function renderHeader(): string
    {
        $parts = [];

        if ($this->title !== null) {
            $options = $this->titleOptions;
            $tag = ArrayHelper::remove($options, 'tag', 'h1');

            $options['id'] = $this->titleId();

            Html::addCssClass($options, ['widget' => 'dialog-title']);

            $parts[] = Html::tag(
                is_string($tag) ? $tag : 'h1',
                $this->encodeTitle ? Html::encode($this->title) : $this->title,
                $options,
            );
        }

        if ($this->closeButton !== false) {
            $closeButton = $this->closeButton;
            $data = is_array($closeButton['data'] ?? null) ? $closeButton['data'] : [];

            $data['bs-dismiss'] ??= 'dialog';
            $closeButton['data'] = $data;

            $parts[] = Html::closeButton($closeButton);
        }

        if ($parts === []) {
            return '';
        }

        $options = $this->headerOptions;

        Html::addCssClass($options, ['widget' => 'dialog-header']);

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

        $options['class'] ??= ['btn-solid', 'theme-primary'];

        if ($tag === 'button') {
            $options['type'] ??= 'button';
        }

        $data['bs-toggle'] = 'dialog';

        if (!isset($options['href'])) {
            $data['bs-target'] ??= '#' . $this->elementId();
        }

        $options['data'] = $data;

        $label = is_string($label) ? $label : '';

        return Html::tag(
            $tag,
            $encode === false ? $label : Html::encode($label),
            $options,
        );
    }

    /**
     * Returns the `id` of the dialog element.
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
