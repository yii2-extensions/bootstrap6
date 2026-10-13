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
    protected function directory(): string
    {
        return 'dist/css';
    }

    protected function package(): string
    {
        return 'bootstrap';
    }

    protected function styles(): array
    {
        return ['bootstrap.css'];
    }
}
