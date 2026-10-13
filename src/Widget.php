<?php

declare(strict_types=1);

namespace yii2\extensions\bootstrap6;

/**
 * Provides the base class for Bootstrap 6 widgets.
 */
class Widget extends \yii\base\Widget
{
    use AttributesTrait;
    use BootstrapWidgetTrait;

    /**
     * HTML attributes of the widget container tag.
     *
     * @var array<string, mixed>
     */
    public array $options = [];
}
