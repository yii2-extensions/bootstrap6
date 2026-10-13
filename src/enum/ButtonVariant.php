<?php

declare(strict_types=1);

namespace yii2\extensions\bootstrap6\enum;

/**
 * Represents the button style classes.
 */
enum ButtonVariant: string
{
    /**
     * Applies the icon button style.
     */
    case ICON = 'btn-icon';

    /**
     * Styles the button as a link.
     */
    case LINK = 'btn-link';

    /**
     * Applies the outlined button style.
     */
    case OUTLINE = 'btn-outline';

    /**
     * Applies the solid button style.
     */
    case SOLID = 'btn-solid';

    /**
     * Applies the styled button variant.
     */
    case STYLED = 'btn-styled';

    /**
     * Applies the subtle button style.
     */
    case SUBTLE = 'btn-subtle';

    /**
     * Applies the text button style.
     */
    case TEXT = 'btn-text';
}
