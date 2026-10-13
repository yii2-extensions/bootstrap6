<?php

declare(strict_types=1);

namespace yii2\extensions\bootstrap6;

use BackedEnum;

/**
 * Provides fluent methods that modify the HTML attributes of the toggler rendered by the widget.
 *
 * The using class implements {@see togglerOptions()}, which returns its toggler attribute array by reference; every
 * method modifies that array in place and returns the same instance. The attributes the toggler requires (for example
 * `data-bs-toggle` or `aria-controls`) are added while rendering.
 */
trait TogglerAttributesTrait
{
    /**
     * Returns the HTML attributes of the toggler by reference, enabling the toggler when it is disabled.
     *
     * @return array<mixed>
     */
    abstract protected function &togglerOptions(): array;

    /**
     * Sets an HTML attribute value of the toggler.
     *
     * @param string $name Attribute name.
     * @param mixed $value Attribute value, or `null` to omit the attribute when rendering.
     */
    public function addTogglerAttribute(string $name, mixed $value): static
    {
        $options = &$this->togglerOptions();

        $options[$name] = $value;

        return $this;
    }

    /**
     * Adds one or more CSS classes to the existing toggler classes; duplicated classes are collapsed.
     *
     * @param BackedEnum|string|null ...$class CSS class names to add; an enum case adds its value, and `null` is
     * ignored.
     *
     * @see https://html.spec.whatwg.org/#classes
     */
    public function addTogglerClass(BackedEnum|string|null ...$class): static
    {
        $options = &$this->togglerOptions();

        Html::addCssClass($options, $class);

        return $this;
    }

    /**
     * Adds CSS style properties to the `style` attribute of the toggler.
     *
     * Follows the merge rules of {@see AttributesTrait::addCssStyle()}.
     *
     * @param array<string, string>|string $style CSS style, either an array such as
     * `['color' => 'red', 'font-weight' => 'bold']` or a string such as `color: red; font-weight: bold;`.
     * @param bool $overwrite Whether to replace the value of properties already set; when `false`, their existing
     * value is kept.
     *
     * @see https://developer.mozilla.org/en-US/docs/Web/HTML/Global_attributes/style
     */
    public function addTogglerCssStyle(array|string $style, bool $overwrite = true): static
    {
        $options = &$this->togglerOptions();

        Html::addCssStyle($options, $style, $overwrite);

        return $this;
    }
}
