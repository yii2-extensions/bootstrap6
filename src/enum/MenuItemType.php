<?php

declare(strict_types=1);

namespace yii2\extensions\bootstrap6\enum;

/**
 * Represents the kinds of {@see \yii2\extensions\bootstrap6\item\MenuItem} rendered by the `Menu` widget.
 */
enum MenuItemType: string
{
    /**
     * Renders the menu item as a button.
     */
    case BUTTON = 'button';

    /**
     * Renders a divider between menu items.
     */
    case DIVIDER = 'divider';

    /**
     * Renders a heading for a group of menu items.
     */
    case HEADER = 'header';

    /**
     * Renders the menu item as a link.
     */
    case LINK = 'link';

    /**
     * Renders the menu item as a submenu.
     */
    case SUBMENU = 'submenu';

    /**
     * Renders non-interactive text in the menu.
     */
    case TEXT = 'text';
}
