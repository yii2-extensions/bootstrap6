<?php

declare(strict_types=1);

namespace yii2\extensions\bootstrap6\asset;

/**
 * Delivers the Bootstrap Icons web font and stylesheet from the optional `bootstrap-icons` npm package.
 *
 * @see https://icons.getbootstrap.com/
 */
final class BootstrapIconAsset extends AbstractNpmAsset
{
    /**
     * Returns the `font` directory of the package.
     *
     * @return string Icon font directory relative to the npm package root.
     */
    protected function directory(): string
    {
        return 'font';
    }

    /**
     * Returns the `bootstrap-icons` npm package.
     *
     * @return string Bootstrap Icons npm package name.
     */
    protected function package(): string
    {
        return 'bootstrap-icons';
    }

    /**
     * Returns the `fonts/*` pattern that publishes the web font files referenced by the stylesheet.
     *
     * @return list<string> Font file patterns relative to {@see directory()}.
     */
    protected function publishPatterns(): array
    {
        return ['fonts/*'];
    }

    /**
     * Returns the Bootstrap Icons stylesheet.
     *
     * @return list<string> Stylesheet paths relative to {@see directory()}.
     */
    protected function styles(): array
    {
        return ['bootstrap-icons.css'];
    }
}
