<?php

declare(strict_types=1);

namespace yii2\extensions\bootstrap6\item;

use Stringable;
use yii\base\InvalidConfigException;
use yii2\extensions\bootstrap6\exception\Message;

use function get_object_vars;
use function is_array;
use function is_string;

/**
 * Provides the operations shared by the item value objects: the copy with one property replaced, and the readers of
 * the array configuration accepted by `fromArray()`.
 *
 * The using class declares a private constructor whose promoted parameters are its only properties, so the copy
 * spreads them as named arguments and the constructor validates the new value.
 */
trait ItemTrait
{
    /**
     * Returns an array entry of an item configuration, or `[]` when it is missing or not an array.
     *
     * @param array<array-key, mixed> $config Item configuration.
     * @param string $key Configuration key.
     *
     * @return array<array-key, mixed>
     */
    private static function arrayValue(array $config, string $key): array
    {
        $value = $config[$key] ?? [];

        return is_array($value) ? $value : [];
    }

    /**
     * Returns a text entry of an item configuration.
     *
     * @param array<array-key, mixed> $config Item configuration.
     * @param string $key Configuration key.
     * @param Message $message Message of the exception thrown when the entry is missing or not text.
     *
     * @throws InvalidConfigException if the entry is not a `string|Stringable`.
     */
    private static function textValue(array $config, string $key, Message $message): string|Stringable
    {
        $value = $config[$key] ?? null;

        if (!is_string($value) && !$value instanceof Stringable) {
            throw new InvalidConfigException($message->getMessage());
        }

        return $value;
    }

    /**
     * Returns a copy with the given property replaced; the other properties keep their values.
     *
     * @param string $property Name of a promoted constructor parameter.
     * @param mixed $value Value of the property in the copy.
     */
    private function with(string $property, mixed $value): static
    {
        // @phpstan-ignore argument.type (get_object_vars() loses the property types)
        return new static(...[...get_object_vars($this), $property => $value]);
    }
}
