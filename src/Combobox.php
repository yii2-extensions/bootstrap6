<?php

declare(strict_types=1);

namespace yii2\extensions\bootstrap6;

use Stringable;
use Yii;
use yii\base\{InvalidConfigException, Model};
use yii\helpers\Json;
use yii\web\View;
use yii2\extensions\bootstrap6\asset\BootstrapPluginAsset;
use yii2\extensions\bootstrap6\enum\Placement;
use yii2\extensions\bootstrap6\exception\Message;

use function implode;
use function in_array;
use function is_array;
use function is_scalar;
use function is_string;

/**
 * Renders a Bootstrap combobox: a `.combobox-toggle` button followed by a `.menu` of selectable items.
 *
 * The plugin submits the selection through a hidden input that it creates from `data-bs-name`, so the toggle
 * carries the input name of the model attribute or {@see $name}. Multiple selections are submitted as a
 * comma-separated list.
 *
 * @see https://getbootstrap.com/docs/6.0/forms/combobox/
 */
class Combobox extends InputWidget
{
    /**
     * Caret rendered inside the toggle (not encoded).
     *
     * `null` renders the {@see IconSet::$comboboxCaret} default, and a {@see \UIAwesome\Html\Svg\Svg} instance
     * receives the `combobox-caret` class automatically.
     */
    public string|Stringable|null $caretIcon = null;
    /**
     * Whether to render the `.menu-item-check` icon that marks the selected items.
     */
    public bool $check = false;
    /**
     * Check mark rendered inside each item when {@see $check} is `true` (not encoded).
     *
     * `null` renders the {@see IconSet::$menuItemCheck} default, and a {@see \UIAwesome\Html\Svg\Svg} instance
     * receives the `menu-item-check` class automatically.
     */
    public string|Stringable|null $checkIcon = null;
    /**
     * Whether to HTML-encode the item labels and {@see $placeholder}.
     */
    public bool $encodeLabels = true;
    /**
     * @var array<int|string, string> Selectable items as value => label.
     */
    public array $items = [];
    /**
     * @var array<string, mixed> HTML attributes of the `.menu` container.
     */
    public array $menuOptions = [];
    /**
     * Whether to allow selecting several items.
     */
    public bool $multiple = false;
    /**
     * Text of the `.combobox-no-results` element rendered when {@see $search} is `true`.
     */
    public string|Stringable $noResultsText = 'No results found';
    /**
     * Text shown in the toggle when nothing is selected.
     */
    public string $placeholder = '';
    /**
     * Menu placement, or `null` for the plugin default (`bottom-start`).
     */
    public Placement|string|null $placement = null;
    /**
     * Whether to render the search field that filters the items.
     */
    public bool $search = false;
    /**
     * Placeholder and accessible label of the search field.
     */
    public string|Stringable $searchPlaceholder = 'Search...';

    /**
     * Caret markup resolved once per {@see run()} and rendered by {@see renderToggle()}.
     */
    private string $caretMarkup = '';
    /**
     * Check mark markup resolved once per {@see run()} and rendered by {@see renderItem()}.
     */
    private string $checkMarkup = '';

    /**
     * Initializes the widget and validates {@see $items} and {@see $placement}.
     *
     * @throws InvalidConfigException if neither a model attribute nor a name is set, {@see $items} is empty, or
     * {@see $placement} is invalid.
     */
    public function init(): void
    {
        parent::init();

        if ($this->items === []) {
            throw new InvalidConfigException(Message::INVALID_ITEMS->getMessage());
        }

        Html::enumValue(Placement::class, $this->placement, 'placement', true);
    }

    /**
     * Returns the toggle button followed by the menu, and registers the `Combobox` plugin with the script that
     * keeps its generated hidden input in sync with the `disabled` and `form` attributes of the toggle.
     *
     * @throws InvalidConfigException if {@see $placement} is not a {@see Placement} value, optionally
     * breakpoint-prefixed.
     *
     * @return string Rendered Combobox markup.
     */
    public function run(): string
    {
        $this->registerPlugin('Combobox', true);
        $this->registerInputAttributes();

        $icons = Yii::createObject(IconSet::class);

        $caret = $this->caretIcon ?? $icons->comboboxCaret;
        $check = $this->checkIcon ?? $icons->menuItemCheck;

        $this->caretMarkup = IconSet::render($caret, 'combobox-caret');
        $this->checkMarkup = IconSet::render($check, 'menu-item-check');

        $selected = $this->selectedValues();

        return $this->renderToggle($selected) . "\n" . $this->renderMenu($selected);
    }

    /**
     * Renders a menu item.
     *
     * @param int|string $value Value submitted when the item is selected.
     * @param string $label Item label, encoded according to {@see $encodeLabels}.
     * @param list<string> $selected Selected values.
     *
     * @return string Rendered item markup.
     */
    protected function renderItem(int|string $value, string $label, array $selected): string
    {
        $options = [
            'type' => 'button',
            'class' => 'menu-item',
            'data-bs-value' => $value,
        ];

        if (in_array((string) $value, $selected, true)) {
            Html::addCssClass($options, 'selected');

            $options['aria-selected'] = 'true';
        }

        $content = $this->encodeLabels ? Html::encode($label) : $label;

        if ($this->check) {
            $content .= "\n" . $this->checkMarkup;
        }

        return Html::tag(
            'button',
            $content,
            $options,
        );
    }

    /**
     * Renders the `.menu` container with the search field and the items.
     *
     * @param list<string> $selected Selected values.
     *
     * @return string Menu panel markup.
     */
    protected function renderMenu(array $selected): string
    {
        $lines = [];

        if ($this->search) {
            $lines[] = Html::tag(
                'div',
                Html::input(
                    'text',
                    null,
                    null,
                    [
                        'class' => 'form-control combobox-search-input',
                        'placeholder' => $this->searchPlaceholder,
                        'autocomplete' => 'off',
                        'aria-label' => $this->searchPlaceholder,
                    ],
                ),
                ['class' => 'combobox-search'],
            );
        }

        foreach ($this->items as $value => $label) {
            $lines[] = $this->renderItem($value, $label, $selected);
        }

        if ($this->search) {
            $lines[] = Html::tag('div', Html::encode($this->noResultsText), ['class' => 'combobox-no-results d-none']);
        }

        $options = $this->menuOptions;

        Html::addCssClass($options, 'menu');

        return Html::tag(
            'div',
            "\n" . implode("\n", $lines) . "\n",
            $options,
        );
    }

    /**
     * Renders the toggle button.
     *
     * @param list<string> $selected Selected values.
     *
     * @throws InvalidConfigException if {@see $placement} is not a {@see Placement} value, optionally
     * breakpoint-prefixed.
     *
     * @return string Combobox toggle button markup.
     */
    protected function renderToggle(array $selected): string
    {
        $options = $this->options;

        Html::addCssClass($options, ['form-control', 'combobox-toggle']);

        $options['type'] = 'button';
        $options['data-bs-toggle'] = 'combobox';
        $options['data-bs-name'] = $this->inputName();

        if ($this->placeholder !== '') {
            $options['data-bs-placeholder'] = $this->placeholder;
        }

        if ($this->multiple) {
            $options['data-bs-multiple'] = 'true';
        }

        if ($this->search) {
            $options['data-bs-search'] = 'true';
        }

        $options['data-bs-placement'] = Html::enumValue(Placement::class, $this->placement, 'placement', true);

        $text = $this->placeholder;

        foreach ($this->items as $value => $label) {
            if (in_array((string) $value, $selected, true)) {
                $text = $label;

                break;
            }
        }

        $value = Html::tag('span', $this->encodeLabels ? Html::encode($text) : $text, ['class' => 'combobox-value']);

        return Html::tag(
            'button',
            "{$value}\n{$this->caretMarkup}",
            $options,
        );
    }

    /**
     * Returns the name of the hidden input the plugin creates.
     *
     * @return string Model-derived input name, or the configured name.
     */
    private function inputName(): string
    {
        if ($this->model instanceof Model && is_string($this->attribute)) {
            return Html::getInputName($this->model, $this->attribute);
        }

        return (string) $this->name;
    }

    /**
     * Keeps the plugin-generated input associated with the toggle's form and disabled state.
     */
    private function registerInputAttributes(): void
    {
        $view = $this->getView();

        $bundle = BootstrapPluginAsset::register($view);

        /** @var array{id: string} $options */
        $options = $this->options;
        $id = Json::htmlEncode($options['id']);

        $code = <<<JS
        const toggle = document.getElementById({$id});
        const sync = () => {
            const input = bootstrap.Combobox.getInstance(toggle)?._hiddenInput;
            if (!input) {
                return;
            }
            input.disabled = toggle.disabled;
            const form = toggle.getAttribute('form');
            if (form === null) {
                input.removeAttribute('form');
            } else {
                input.setAttribute('form', form);
            }
        };
        let parent = toggle.parentElement;
        const observe = () => {
            observer.observe(toggle, {attributes: true, attributeFilter: ['disabled', 'form']});
            if (parent) {
                observer.observe(parent, {childList: true});
            }
        };
        const observer = new MutationObserver(() => {
            if (toggle.parentElement !== parent) {
                observer.disconnect();
                parent = toggle.parentElement;
                if (!parent) {
                    return;
                }
                observe();
            }
            sync();
        });
        observe();
        sync();
        JS;

        $view->registerJs($bundle->importJs($view, $code), View::POS_END, "{$options['id']}-input");
    }

    /**
     * Returns the selected values from the model attribute or {@see $value}.
     *
     * @return list<string> Selected scalar values converted to strings.
     */
    private function selectedValues(): array
    {
        $value = $this->model instanceof Model && is_string($this->attribute)
            ? Html::getAttributeValue($this->model, $this->attribute)
            : $this->value;

        $values = is_array($value) ? $value : [$value];
        $selected = [];

        foreach ($values as $item) {
            if (is_scalar($item)) {
                $selected[] = (string) $item;
            }
        }

        return $selected;
    }
}
