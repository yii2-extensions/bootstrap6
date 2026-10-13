<?php

declare(strict_types=1);

namespace yii2\extensions\bootstrap6\enum;

/**
 * Represents the component size modifiers.
 */
enum Size: string
{
    /**
     * Applies the large size modifier.
     */
    case LG = 'lg';

    /**
     * Applies the small size modifier.
     */
    case SM = 'sm';

    /**
     * Applies the extra-large size modifier.
     */
    case XL = 'xl';

    /**
     * Applies the extra-small size modifier.
     */
    case XS = 'xs';
}
