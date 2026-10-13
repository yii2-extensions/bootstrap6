<?php

declare(strict_types=1);

namespace yii2\extensions\bootstrap6\enum;

/**
 * Represents the dialog size classes.
 */
enum DialogSize: string
{
    /**
     * Expands the dialog to fill the viewport.
     */
    case FULLSCREEN = 'dialog-fullscreen';

    /**
     * Applies the large dialog size.
     */
    case LG = 'dialog-lg';

    /**
     * Applies the small dialog size.
     */
    case SM = 'dialog-sm';

    /**
     * Applies the extra-large dialog size.
     */
    case XL = 'dialog-xl';
}
