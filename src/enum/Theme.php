<?php

declare(strict_types=1);

namespace yii2\extensions\bootstrap6\enum;

/**
 * Represents the Bootstrap theme colors applied through the `theme-{value}` classes.
 */
enum Theme: string
{
    /**
     * Applies the accent theme color.
     */
    case ACCENT = 'accent';

    /**
     * Applies the danger theme color.
     */
    case DANGER = 'danger';

    /**
     * Applies the informational theme color.
     */
    case INFO = 'info';

    /**
     * Applies the inverse theme color.
     */
    case INVERSE = 'inverse';

    /**
     * Applies the primary theme color.
     */
    case PRIMARY = 'primary';

    /**
     * Applies the secondary theme color.
     */
    case SECONDARY = 'secondary';

    /**
     * Applies the success theme color.
     */
    case SUCCESS = 'success';

    /**
     * Applies the warning theme color.
     */
    case WARNING = 'warning';
}
