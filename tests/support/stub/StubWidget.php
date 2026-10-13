<?php

declare(strict_types=1);

namespace yii2\extensions\bootstrap6\tests\support\stub;

use yii2\extensions\bootstrap6\{Html, Widget};

/**
 * Widget registering a configurable plugin, used to test {@see \yii2\extensions\bootstrap6\BootstrapWidgetTrait}.
 */
final class StubWidget extends Widget
{
    public bool $always = false;
    /**
     * Whether the overridden {@see registerClientEvents()} delegates to the trait.
     */
    public bool $events = true;
    public string $plugin = 'Toast';

    public function run(): string
    {
        $this->registerPlugin($this->plugin, $this->always);

        return Html::tag('div', '', $this->options);
    }

    protected function registerClientEvents(): void
    {
        if ($this->events) {
            parent::registerClientEvents();
        }
    }
}
