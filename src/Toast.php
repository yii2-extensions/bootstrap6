<?php

declare(strict_types=1);

namespace yii2\extensions\bootstrap6;

use DateTimeInterface;
use Stringable;
use Yii;
use yii\helpers\ArrayHelper;

use function implode;
use function is_array;
use function is_string;
use function ob_get_clean;
use function ob_start;

/**
 * Renders a Bootstrap toast with an optional header and initializes its plugin.
 *
 * Content captured between {@see begin()} and {@see end()} is rendered inside the `.toast-body` element, before
 * {@see $body}. The toast stays hidden until `show()` is called on its plugin instance, or until the `show` class is
 * added through {@see $options}.
 *
 * @see https://getbootstrap.com/docs/6.0/components/toasts/
 */
class Toast extends Widget
{
    /**
     * Content appended to the `.toast-body` element (not encoded), or `null`.
     */
    public string|Stringable|null $body = null;
    /**
     * HTML attributes of the `.toast-body` element.
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
     * Time rendered in the header as a relative time (for example, `2 hours ago`), or `null` to omit it.
     */
    public DateTimeInterface|int|string|null $dateTime = null;
    /**
     * HTML attributes of the time element; the `tag` option sets the tag name (default `small`).
     *
     * @var array<string, mixed>
     */
    public array $dateTimeOptions = [];
    /**
     * Whether to HTML-encode {@see $title}.
     */
    public bool $encodeTitle = true;
    /**
     * HTML attributes of the `.toast-header` element.
     *
     * @var array<string, mixed>
     */
    public array $headerOptions = [];
    /**
     * Whether to disable the fade animation (`.toast-instant`).
     */
    public bool $instant = false;
    /**
     * Content of the header title, or `null` to omit it.
     */
    public string|Stringable|null $title = null;
    /**
     * HTML attributes of the header title; the `tag` option sets the tag name (default `strong`).
     *
     * @var array<string, mixed>
     */
    public array $titleOptions = [];

    public function init(): void
    {
        parent::init();

        ob_start();
    }

    public function run(): string
    {
        $content = ob_get_clean();

        $this->registerPlugin('Toast', true);

        $header = $this->renderHeader();
        $bodyOptions = $this->bodyOptions;

        Html::addCssClass($bodyOptions, ['widget' => 'toast-body']);

        return Html::beginTag('div', $this->toastOptions()) . "\n"
            . ($header === '' ? '' : "{$header}\n")
            . Html::beginTag('div', $bodyOptions) . "\n"
            . $content
            . $this->body . "\n" . Html::endTag('div') . "\n" . Html::endTag('div');
    }

    /**
     * Renders the `.toast-header` element, or an empty string when there is no title, time, or close button.
     */
    protected function renderHeader(): string
    {
        $parts = [];

        if ($this->title !== null) {
            $options = $this->titleOptions;

            $tag = ArrayHelper::remove($options, 'tag', 'strong');

            Html::addCssClass($options, ['widget' => 'me-auto']);

            $parts[] = Html::tag(
                is_string($tag) ? $tag : 'strong',
                $this->encodeTitle ? Html::encode($this->title) : $this->title,
                $options,
            );
        }

        if ($this->dateTime !== null) {
            $options = $this->dateTimeOptions;

            $tag = ArrayHelper::remove($options, 'tag', 'small');

            $parts[] = Html::tag(
                is_string($tag) ? $tag : 'small',
                Html::encode(Yii::$app->getFormatter()->asRelativeTime($this->dateTime)),
                $options,
            );
        }

        if ($this->closeButton !== false) {
            $closeButton = $this->closeButton;

            $data = is_array($closeButton['data'] ?? null) ? $closeButton['data'] : [];

            $data['bs-dismiss'] ??= 'toast';
            $closeButton['data'] = $data;

            $parts[] = Html::closeButton($closeButton);
        }

        if ($parts === []) {
            return '';
        }

        $options = $this->headerOptions;

        Html::addCssClass($options, ['widget' => 'toast-header']);

        return Html::tag(
            'div',
            "\n" . implode("\n", $parts) . "\n",
            $options,
        );
    }

    /**
     * Returns the HTML attributes of the `.toast` element.
     *
     * The `role`, `aria-live`, and `aria-atomic` attributes default to `alert`, `assertive`, and `true`; values given
     * in {@see $options}, either flat (`aria-live`) or nested (`aria => ['live' => ...]`), are kept.
     *
     * @return array<mixed>
     */
    protected function toastOptions(): array
    {
        $options = $this->options;
        $aria = is_array($options['aria'] ?? null) ? $options['aria'] : [];

        Html::addCssClass($options, ['widget' => 'toast']);

        if ($this->instant) {
            Html::addCssClass($options, ['instant' => 'toast-instant']);
        }

        $options['role'] ??= 'alert';

        if (!isset($options['aria-live'])) {
            $aria['live'] ??= 'assertive';
        }

        if (!isset($options['aria-atomic'])) {
            $aria['atomic'] ??= 'true';
        }

        $options['aria'] = $aria;

        return $options;
    }
}
