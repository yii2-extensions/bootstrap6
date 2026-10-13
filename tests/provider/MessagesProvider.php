<?php

declare(strict_types=1);

namespace yii2\extensions\bootstrap6\tests\provider;

use function basename;
use function dirname;
use function glob;

/**
 * Data provider for {@see \yii2\extensions\bootstrap6\tests\i18n\MessagesTest} test cases.
 */
final class MessagesProvider
{
    /**
     * @return iterable<string, array{string}>
     */
    public static function languageCases(): iterable
    {
        $directories = glob(dirname(__DIR__, 2) . '/messages/*', GLOB_ONLYDIR);

        if ($directories === false) {
            return;
        }

        foreach ($directories as $directory) {
            $language = basename($directory);

            yield $language => [$language];
        }
    }
}
