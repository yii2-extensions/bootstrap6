<?php

declare(strict_types=1);

namespace yii2\extensions\bootstrap6\tests\i18n;

use PHPUnit\Framework\Attributes\{DataProviderExternal, Group};
use PHPUnit\Framework\TestCase;
use yii2\extensions\bootstrap6\tests\provider\MessagesProvider;

use function array_keys;
use function count;
use function dirname;
use function in_array;
use function iterator_to_array;
use function mb_check_encoding;
use function sort;

/**
 * Unit tests for the {@see \yii2\extensions\bootstrap6\Bootstrap} message catalogue under `messages/`.
 *
 * {@see MessagesProvider} for test case data providers.
 */
#[Group('i18n')]
final class MessagesTest extends TestCase
{
    /**
     * Source messages of the `yii.bootstrap6` category, sorted alphabetically.
     */
    private const array CATALOGUE = [
        'Button',
        'Close',
        'Loading...',
        'Menu',
        'Next',
        'Pause',
        'Play',
        'Previous',
        'Show',
        'Toggle menu',
        'Toggle navigation',
    ];

    /**
     * Source messages whose translation may legitimately equal the source text.
     */
    private const array IDENTICAL_ALLOWED = ['Menu'];

    public function testCatalogueShipsAtLeastFiftyLanguages(): void
    {
        self::assertGreaterThanOrEqual(
            50,
            count(iterator_to_array(MessagesProvider::languageCases())),
            'Language directories on disk must be at least 50.',
        );
    }

    #[DataProviderExternal(MessagesProvider::class, 'languageCases')]
    public function testKeysMatchCatalogue(string $language): void
    {
        $keys = array_keys(self::loadMessages($language));

        sort($keys);

        self::assertSame(
            self::CATALOGUE,
            $keys,
            "Key set for '{$language}' must equal the catalogue.",
        );
    }

    #[DataProviderExternal(MessagesProvider::class, 'languageCases')]
    public function testReturnsArrayOfStrings(string $language): void
    {
        $messages = require self::messageFile($language);

        self::assertIsArray(
            $messages,
            "File for '{$language}' must return an `array`.",
        );

        foreach ($messages as $key => $value) {
            self::assertIsString(
                $key,
                "Keys for '{$language}' must be of type `string`.",
            );
            self::assertIsString(
                $value,
                "Value of '{$key}' for '{$language}' must be of type `string`.",
            );
        }
    }

    #[DataProviderExternal(MessagesProvider::class, 'languageCases')]
    public function testValuesAreNonEmptyUtf8Strings(string $language): void
    {
        foreach (self::loadMessages($language) as $key => $value) {
            self::assertNotSame(
                '',
                $value,
                "Value of '{$key}' for '{$language}' must not be empty.",
            );
            self::assertTrue(
                mb_check_encoding($value, 'UTF-8'),
                "Value of '{$key}' for '{$language}' must be valid UTF-8.",
            );
        }
    }

    #[DataProviderExternal(MessagesProvider::class, 'languageCases')]
    public function testValuesDifferFromSourceKeys(string $language): void
    {
        foreach (self::loadMessages($language) as $key => $value) {
            if (in_array($key, self::IDENTICAL_ALLOWED, true)) {
                continue;
            }

            self::assertNotSame(
                $key,
                $value,
                "Value of '{$key}' for '{$language}' must be translated.",
            );
        }
    }

    /**
     * @return array<string, string>
     */
    private static function loadMessages(string $language): array
    {
        $messages = require self::messageFile($language);

        self::assertIsArray($messages, "File for '{$language}' must return an `array`.");

        $result = [];

        foreach ($messages as $key => $value) {
            self::assertIsString(
                $key,
                "Keys for '{$language}' must be of type `string`.",
            );
            self::assertIsString(
                $value,
                "Value of '{$key}' for '{$language}' must be of type `string`.",
            );

            $result[$key] = $value;
        }

        return $result;
    }

    private static function messageFile(string $language): string
    {
        return dirname(__DIR__, 2) . "/messages/{$language}/yii.bootstrap6.php";
    }
}
