<?php

declare(strict_types=1);

namespace yii2\extensions\bootstrap6\enum;

/**
 * Represents the carousel behaviors at the first and last slides.
 */
enum CarouselEnds: string
{
    /**
     * Repeats the slides when the carousel reaches either end.
     */
    case LOOP = 'loop';

    /**
     * Stops the carousel at the first and last slides.
     */
    case STOP = 'stop';

    /**
     * Wraps to the opposite end when the carousel reaches either end.
     */
    case WRAP = 'wrap';
}
