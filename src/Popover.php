<?php

declare(strict_types=1);

namespace yii2\extensions\bootstrap6;

use Stringable;

/**
 * Renders an element that wraps the trigger content and shows a Bootstrap popover on click.
 *
 * Content captured between {@see begin()} and {@see end()} is the trigger. Bootstrap initializes popovers on first
 * interaction, so the plugin is initialized explicitly only when {@see $clientOptions} is not empty.
 *
 * @see https://getbootstrap.com/docs/6.0/components/popover/
 */
class Popover extends Tooltip
{
    /**
     * Body of the popover (`data-bs-content`), or `null`.
     */
    public string|Stringable|null $content = null;

    /**
     * Returns the inherited `data-bs-*` attributes, adding `bs-content` when {@see $content} is set.
     *
     * @throws \yii\base\InvalidConfigException if {@see $placement} is not a valid placement, optionally
     * breakpoint-prefixed.
     *
     * @return array<string, string|Stringable> Inherited plugin attributes with the optional popover content.
     */
    protected function dataAttributes(): array
    {
        $data = parent::dataAttributes();

        if ($this->content !== null) {
            $data['bs-content'] = $this->content;
        }

        return $data;
    }

    /**
     * Returns the `Popover` plugin class exported by the bundle.
     *
     * @return string Popover plugin class name.
     */
    protected function pluginName(): string
    {
        return 'Popover';
    }
}
