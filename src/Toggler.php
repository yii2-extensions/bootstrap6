<?php

declare(strict_types=1);

namespace yii2\extensions\bootstrap6;

use Stringable;

use function is_array;

/**
 * Renders an element that toggles a class or an attribute on click through the Bootstrap toggler plugin.
 *
 * Without {@see $target}, the element toggles {@see $value} on itself. With {@see $target}, the element toggles the
 * matching elements, which must carry their own `data-bs-value` and `data-bs-attribute` attributes.
 *
 * @see https://getbootstrap.com/docs/6.0/components/toggler/
 */
class Toggler extends Widget
{
    /**
     * Attribute toggled on the element (`data-bs-attribute`), for example `class` or `hidden`.
     */
    public string $attribute = 'class';
    /**
     * Whether to HTML-encode {@see $label}.
     */
    public bool $encodeLabel = true;
    /**
     * Content of the element.
     */
    public string|Stringable $label = '';
    /**
     * Tag name of the element; a `button` receives `type="button"`.
     */
    public string $tagName = 'button';
    /**
     * Selector of the elements to toggle (`data-bs-target`), or `null` to toggle the element itself.
     */
    public string|null $target = null;
    /**
     * Value toggled on the element (`data-bs-value`), for example a class name, or `null` to omit it.
     */
    public string|null $value = null;

    public function run(): string
    {
        $options = $this->options;
        $data = is_array($options['data'] ?? null) ? $options['data'] : [];

        if ($this->tagName === 'button') {
            $options['type'] ??= 'button';
        }

        $data['bs-toggle'] = 'toggler';

        if ($this->target !== null) {
            $data['bs-target'] = $this->target;
        }

        if ($this->value !== null) {
            $data['bs-value'] = $this->value;
            $data['bs-attribute'] = $this->attribute;
        }

        $options['data'] = $data;

        $this->registerPlugin('Toggler');

        return Html::tag(
            $this->tagName,
            $this->encodeLabel ? Html::encode($this->label) : $this->label,
            $options,
        );
    }
}
