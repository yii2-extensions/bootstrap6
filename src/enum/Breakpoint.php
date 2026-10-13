<?php

declare(strict_types=1);

namespace yii2\extensions\bootstrap6\enum;

/**
 * Represents the responsive breakpoints used as class prefixes, such as `md:drawer`.
 */
enum Breakpoint: string
{
    /**
     * Applies from the large breakpoint upward.
     */
    case LG = 'lg';

    /**
     * Applies from the medium breakpoint upward.
     */
    case MD = 'md';

    /**
     * Applies from the small breakpoint upward.
     */
    case SM = 'sm';

    /**
     * Applies from the extra-large breakpoint upward.
     */
    case XL = 'xl';

    /**
     * Applies from the 2x-large breakpoint upward.
     */
    case XXL = '2xl';
}
