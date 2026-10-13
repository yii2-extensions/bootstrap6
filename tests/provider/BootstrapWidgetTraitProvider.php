<?php

declare(strict_types=1);

namespace yii2\extensions\bootstrap6\tests\provider;

/**
 * Data provider for {@see \yii2\extensions\bootstrap6\tests\BootstrapWidgetTraitTest} test cases.
 *
 * Provides the plugin names exported by the Bootstrap 6 bundle.
 */
final class BootstrapWidgetTraitProvider
{
    /**
     * @return iterable<string, array{string}>
     */
    public static function pluginCases(): iterable
    {
        foreach (
            [
                'Alert',
                'Button',
                'Carousel',
                'Chips',
                'Collapse',
                'Combobox',
                'Datepicker',
                'Dialog',
                'Drawer',
                'Menu',
                'NavOverflow',
                'OtpInput',
                'Popover',
                'Range',
                'ScrollSpy',
                'Strength',
                'Tab',
                'Toast',
                'Toggler',
                'Tooltip',
            ] as $plugin
        ) {
            yield $plugin => [$plugin];
        }
    }
}
