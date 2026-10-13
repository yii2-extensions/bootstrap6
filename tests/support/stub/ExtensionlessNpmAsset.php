<?php

declare(strict_types=1);

namespace yii2\extensions\bootstrap6\tests\support\stub;

use yii2\extensions\bootstrap6\asset\{AbstractNpmAsset, BootstrapAsset};

/**
 * npm asset bundle with dotted and extensionless file names and a non-npm dependency.
 */
final class ExtensionlessNpmAsset extends AbstractNpmAsset
{
    public static function dependencies(): array
    {
        return [BootstrapAsset::class, PlainAsset::class];
    }

    protected function package(): string
    {
        return 'bootstrap';
    }

    protected function scripts(): array
    {
        return ['dist/js/bootstrap.bundle.js', 'LICENSE'];
    }
}
