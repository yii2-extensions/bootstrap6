<?php

declare(strict_types=1);

namespace yii2\extensions\bootstrap6;

use Yii;
use yii\helpers\ArrayHelper;

use function is_array;
use function is_string;
use function ob_get_clean;
use function ob_start;

/**
 * Renders a Bootstrap collapsible element, optionally preceded by a toggle button.
 *
 * Content captured between {@see begin()} and {@see end()} is rendered inside the `.collapse` element.
 *
 * @see https://getbootstrap.com/docs/6.0/components/collapse/
 */
class Collapse extends Widget
{
    use TogglerAttributesTrait;

    /**
     * Whether to collapse the inline size instead of the block size (`.collapse-horizontal`).
     */
    public bool $horizontal = false;
    /**
     * Selector of the parent whose other collapsible elements close when this one opens (`data-bs-parent`), or `null`.
     */
    public string|null $parent = null;
    /**
     * Whether the element is initially shown (`.show`).
     */
    public bool $show = false;
    /**
     * Tag name of the collapsible element.
     */
    public string $tagName = 'div';
    /**
     * HTML attributes of the toggle button rendered before the element, or `false` to omit the button.
     *
     * The `label` option sets the content (default translated `Show`), `encode` controls its encoding (default
     * `true`), and `tag` sets the tag name (default `button`).
     *
     * @var array<string, mixed>|false
     */
    public array|false $toggleButton = false;

    public function init(): void
    {
        parent::init();

        ob_start();
    }

    public function run(): string
    {
        $content = ob_get_clean();

        $this->registerPlugin('Collapse');

        $toggleButton = $this->renderToggleButton();

        return ($toggleButton === '' ? '' : $toggleButton . "\n")
            . Html::beginTag($this->tagName, $this->collapseOptions()) . "\n"
            . $content
            . "\n" . Html::endTag($this->tagName);
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
     * Returns the HTML attributes of the collapsible element.
     *
     * @return array<mixed>
     */
    protected function collapseOptions(): array
    {
        $options = $this->options;

        Html::addCssClass($options, ['widget' => 'collapse']);

        if ($this->horizontal) {
            Html::addCssClass($options, ['horizontal' => 'collapse-horizontal']);
        }

        if ($this->show) {
            Html::addCssClass($options, ['show' => 'show']);
        }

        if ($this->parent !== null) {
            $data = is_array($options['data'] ?? null) ? $options['data'] : [];

            $data['bs-parent'] = $this->parent;
            $options['data'] = $data;
        }

        return $options;
    }

    /**
     * Renders the toggle button, or an empty string when {@see $toggleButton} is `false`.
     */
    protected function renderToggleButton(): string
    {
        if ($this->toggleButton === false) {
            return '';
        }

        $id = $this->elementId();

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

        $data['bs-toggle'] = 'collapse';

        if (!isset($options['href'])) {
            $data['bs-target'] ??= '#' . $id;
        }

        $aria['expanded'] = $this->show ? 'true' : 'false';
        $aria['controls'] ??= $id;
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
     * Returns the `id` of the collapsible element.
     */
    private function elementId(): string
    {
        /** @var string */
        return $this->options['id'];
    }
}
