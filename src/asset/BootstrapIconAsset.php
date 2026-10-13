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
    protected function directory(): string
    {
        return 'font';
    }

    protected function package(): string
    {
        return 'bootstrap-icons';
    }

    protected function publishPatterns(): array
    {
        return ['fonts/*'];
    }

    protected function styles(): array
    {
        return ['bootstrap-icons.css'];
    }
}
