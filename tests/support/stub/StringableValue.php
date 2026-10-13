<?php

declare(strict_types=1);

namespace yii2\extensions\bootstrap6\tests\support\stub;

use Stringable;

/**
 * {@see Stringable} value object used to test the content properties that accept `string|Stringable`.
 */
final readonly class StringableValue implements Stringable
{
    public function __construct(private string $value) {}

    public function __toString(): string
    {
        return $this->value;
    }
}
