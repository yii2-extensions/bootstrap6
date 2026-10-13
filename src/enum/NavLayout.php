<?php

declare(strict_types=1);

namespace yii2\extensions\bootstrap6\enum;

/**
 * Represents the nav layout classes.
 */
enum NavLayout: string
{
    /**
     * Expands nav items to fill the available horizontal space.
     */
    case FILL = 'nav-fill';

    /**
     * Gives nav items equal widths across the available horizontal space.
     */
    case JUSTIFIED = 'nav-justified';

    /**
     * Stacks nav items vertically.
     */
    case VERTICAL = 'flex-column';
}
