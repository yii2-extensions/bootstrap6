<?php

declare(strict_types=1);

namespace yii2\extensions\bootstrap6\enum;

/**
 * Represents the nav style classes.
 */
enum NavStyle: string
{
    /**
     * Applies the pill-style navigation variant.
     */
    case PILLS = 'nav-pills';

    /**
     * Applies the vertically oriented pill-style navigation variant.
     */
    case PILLS_VERTICAL = 'nav-pills-vertical';

    /**
     * Applies the tab-style navigation variant.
     */
    case TABS = 'nav-tabs';

    /**
     * Applies the underline-style navigation variant.
     */
    case UNDERLINE = 'nav-underline';
}
