<?php

declare(strict_types=1);

namespace yii2\extensions\bootstrap6\tests\support\stub;

use yii2\extensions\bootstrap6\BaseHtml;

/**
 * HTML helper subclass exposing the protected helpers of {@see BaseHtml}.
 */
final class HtmlStub extends BaseHtml
{
    public static function defaultCheckbox(string $name): string
    {
        return static::booleanInput('checkbox', $name);
    }

    public static function nextId(): string
    {
        return static::getId();
    }
}
