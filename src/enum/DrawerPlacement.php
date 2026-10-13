<?php

declare(strict_types=1);

namespace yii2\extensions\bootstrap6\enum;

/**
 * Represents the drawer placement classes.
 */
enum DrawerPlacement: string
{
    /**
     * Places the drawer along the bottom edge.
     */
    case BOTTOM = 'drawer-bottom';

    /**
     * Places the drawer along the end edge.
     */
    case END = 'drawer-end';

    /**
     * Places the drawer along the start edge.
     */
    case START = 'drawer-start';

    /**
     * Places the drawer along the top edge.
     */
    case TOP = 'drawer-top';
}
