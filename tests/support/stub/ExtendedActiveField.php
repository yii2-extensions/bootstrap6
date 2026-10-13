<?php

declare(strict_types=1);

namespace yii2\extensions\bootstrap6\tests\support\stub;

use yii2\extensions\bootstrap6\ActiveField;

/**
 * Field subclass redefining the horizontal grid classes, used to test {@see ActiveField::createLayoutConfig()}.
 */
final class ExtendedActiveField extends ActiveField
{
    public array $horizontalCssClasses = [
        'error' => 'md:col-3',
        'hint' => 'md:col-3',
        'label' => 'md:col-4',
        'offset' => 'md:offset-4',
        'wrapper' => 'md:col-6',
    ];
}
