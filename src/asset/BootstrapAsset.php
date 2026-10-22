<?php

declare(strict_types=1);

namespace yii2\extensions\bootstrap6\asset;

/**
 * Delivers the Bootstrap 6 stylesheet.
 *
 * @see https://getbootstrap.com/docs/6.0/getting-started/introduction/
 */
final class BootstrapAsset extends AbstractNpmAsset
{
    /**
     * Returns the `dist/css` directory of the package.
     *
     * @return string Stylesheet directory relative to the npm package root.
     */
    protected function directory(): string
    {
        return 'dist/css';
    }

    /**
     * Returns the `bootstrap` npm package.
     *
     * @return string Bootstrap npm package name.
     */
    protected function package(): string
    {
        return 'bootstrap';
    }

    /**
     * Returns the Bootstrap stylesheet.
     *
     * @return list<string> Stylesheet paths relative to {@see directory()}.
     */
    protected function styles(): array
    {
        return ['bootstrap.css'];
    }
}
