<?php

declare(strict_types=1);

namespace yii2\extensions\bootstrap6\tests\support\stub;

use yii2\extensions\bootstrap6\InputWidget;

/**
 * Input widget registering the `Range` plugin, used to test {@see InputWidget}.
 */
final class StubInputWidget extends InputWidget
{
    public function run(): string
    {
        $this->registerPlugin('Range');

        return $this->renderInputHtml('range');
    }
}
