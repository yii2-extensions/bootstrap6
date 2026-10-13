<?php

declare(strict_types=1);

namespace yii2\extensions\bootstrap6;

/**
 * Provides the base class for Bootstrap 6 form input widgets.
 */
abstract class InputWidget extends \yii\widgets\InputWidget
{
    use AttributesTrait;
    use BootstrapWidgetTrait;
}
