<?php

declare(strict_types=1);

namespace yii2\extensions\bootstrap6\enum;

/**
 * Represents the Floating UI placements used by menus, tooltips, and popovers.
 *
 * The logical placements (`start`, `end`, and their `-start`/`-end` alignments) flip with the text direction and are
 * supported by menus only.
 */
enum Placement: string
{
    /**
     * Places the floating element below its reference.
     */
    case BOTTOM = 'bottom';

    /**
     * Places the floating element below its reference, aligned to the end.
     */
    case BOTTOM_END = 'bottom-end';

    /**
     * Places the floating element below its reference, aligned to the start.
     */
    case BOTTOM_START = 'bottom-start';

    /**
     * Places the floating element at the end of its reference.
     */
    case END = 'end';

    /**
     * Places the floating element at the end of its reference, aligned to the end.
     */
    case END_END = 'end-end';

    /**
     * Places the floating element at the end of its reference, aligned to the start.
     */
    case END_START = 'end-start';

    /**
     * Places the floating element to the left of its reference.
     */
    case LEFT = 'left';

    /**
     * Places the floating element to the left of its reference, aligned to the end.
     */
    case LEFT_END = 'left-end';

    /**
     * Places the floating element to the left of its reference, aligned to the start.
     */
    case LEFT_START = 'left-start';

    /**
     * Places the floating element to the right of its reference.
     */
    case RIGHT = 'right';

    /**
     * Places the floating element to the right of its reference, aligned to the end.
     */
    case RIGHT_END = 'right-end';

    /**
     * Places the floating element to the right of its reference, aligned to the start.
     */
    case RIGHT_START = 'right-start';

    /**
     * Places the floating element at the start of its reference.
     */
    case START = 'start';

    /**
     * Places the floating element at the start of its reference, aligned to the end.
     */
    case START_END = 'start-end';

    /**
     * Places the floating element at the start of its reference, aligned to the start.
     */
    case START_START = 'start-start';

    /**
     * Places the floating element above its reference.
     */
    case TOP = 'top';

    /**
     * Places the floating element above its reference, aligned to the end.
     */
    case TOP_END = 'top-end';

    /**
     * Places the floating element above its reference, aligned to the start.
     */
    case TOP_START = 'top-start';
}
