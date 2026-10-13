<?php

declare(strict_types=1);

namespace yii2\extensions\bootstrap6\tests;

use PHPUnit\Framework\Attributes\Group;
use Yii;
use yii\helpers\ReplaceArrayValue;
use yii\i18n\PhpMessageSource;
use yii2\extensions\bootstrap6\Bootstrap;
use yii2\extensions\bootstrap6\tests\support\TestCase;

use function dirname;

/**
 * Unit tests for {@see Bootstrap} message source registration.
 */
#[Group('i18n')]
final class BootstrapTest extends TestCase
{
    public function testKeepsApplicationMessageSource(): void
    {
        $this->mockWebApplication(
            [
                'bootstrap' => new ReplaceArrayValue([]),
                'components' => [
                    'i18n' => [
                        'translations' => [
                            Bootstrap::TRANSLATION_CATEGORY => [
                                'class' => PhpMessageSource::class,
                                'basePath' => '@app',
                            ],
                        ],
                    ],
                ],
            ],
        );

        (new Bootstrap())->bootstrap(Yii::$app);

        $translations = Yii::$app->getI18n()->translations[Bootstrap::TRANSLATION_CATEGORY] ?? null;

        self::assertIsArray(
            $translations,
            'Configured source must not be replaced.',
        );
        self::assertSame(
            '@app',
            $translations['basePath'] ?? null,
            'Application configuration must win.',
        );
    }

    public function testRegistersMessageSourceWhenMissing(): void
    {
        $this->mockWebApplication(['bootstrap' => new ReplaceArrayValue([])]);

        self::assertArrayNotHasKey(
            Bootstrap::TRANSLATION_CATEGORY,
            Yii::$app->getI18n()->translations,
            'Source must be absent before bootstrap.',
        );

        (new Bootstrap())->bootstrap(Yii::$app);

        self::assertSame(
            [
                'class' => PhpMessageSource::class,
                'basePath' => dirname(__DIR__) . '/messages',
                'sourceLanguage' => 'en-US',
            ],
            Yii::$app->getI18n()->translations['yii.bootstrap6'] ?? null,
            'Source must be registered under `yii.bootstrap6` and point to the package messages.',
        );
    }
}
