<?php

declare(strict_types=1);

namespace yii2\extensions\bootstrap6;

use BackedEnum;

/**
 * Provides fluent methods that modify the HTML attributes of the widget element.
 *
 * The using class must declare a public `$options` array; every method modifies it in place and returns the same
 * instance, so calls chain on the value returned by `begin()`, `new`, or `Yii::createObject()`. The classes a widget
 * requires (for example `collapse` or `navbar`) are added while rendering, from a copy of `$options`, so these methods
 * never remove them.
 */
trait AttributesTrait
{
    /**
     * Adds a set of HTML attributes; attributes already set with the same name are replaced.
     *
     * @param array<string, mixed> $attributes Attribute values indexed by attribute names, for example
     * `['id' => 'my-id']`.
     */
    public function addAttributes(array $attributes): static
    {
        $this->options = [...$this->options, ...$attributes];

        return $this;
    }

    /**
     * Adds one or more CSS classes to the existing classes; duplicated classes are collapsed.
     *
     * @param BackedEnum|string|null ...$class CSS class names to add; an enum case adds its value, and `null` is
     * ignored.
     *
     * @see https://html.spec.whatwg.org/#classes
     */
    public function addClass(BackedEnum|string|null ...$class): static
    {
        Html::addCssClass($this->options, $class);

        return $this;
    }

    /**
     * Adds CSS style properties to the `style` attribute.
     *
     * Without an existing style, a `string` is stored as is and an array renders as `color: red;`. With an existing
     * style, both are merged and normalized to `name: value;` pairs: new properties are appended, and a property
     * already set keeps its position.
     *
     * @param array<string, string>|string $style CSS style, either an array such as
     * `['color' => 'red', 'font-weight' => 'bold']` or a string such as `color: red; font-weight: bold;`.
     * @param bool $overwrite Whether to replace the value of properties already set; when `false`, their existing
     * value is kept.
     *
     * @see https://developer.mozilla.org/en-US/docs/Web/HTML/Global_attributes/style
     */
    public function addCssStyle(array|string $style, bool $overwrite = true): static
    {
        Html::addCssStyle($this->options, $style, $overwrite);

        return $this;
    }

    /**
     * Sets an HTML attribute value.
     *
     * @param string $name Attribute name.
     * @param mixed $value Attribute value, or `null` to omit the attribute when rendering.
     */
    public function attribute(string $name, mixed $value): static
    {
        $this->options[$name] = $value;

        return $this;
    }

    /**
     * Replaces the existing CSS classes with the specified ones.
     *
     * Only the classes held in `$options` are replaced; the classes the widget requires are still added while
     * rendering.
     *
     * @param BackedEnum|string|null ...$class CSS class names to set; an enum case sets its value, and `null` is
     * ignored. When no class remains, the `class` attribute is removed.
     *
     * @see https://html.spec.whatwg.org/#classes
     */
    public function class(BackedEnum|string|null ...$class): static
    {
        unset($this->options['class']);

        Html::addCssClass($this->options, $class);

        return $this;
    }
}
