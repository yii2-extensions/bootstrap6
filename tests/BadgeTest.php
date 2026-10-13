<?php

declare(strict_types=1);

namespace yii2\extensions\bootstrap6\tests;

use PHPUnit\Framework\Attributes\{DataProviderExternal, Group};
use yii\base\InvalidConfigException;
use yii2\extensions\bootstrap6\asset\BootstrapAsset;
use yii2\extensions\bootstrap6\Badge;
use yii2\extensions\bootstrap6\exception\Message;
use yii2\extensions\bootstrap6\tests\provider\BadgeProvider;
use yii2\extensions\bootstrap6\tests\support\stub\StringableValue;
use yii2\extensions\bootstrap6\tests\support\TestCase;

/**
 * Unit tests for {@see Badge} variants, sizes, labels, and asset registration.
 *
 * {@see BadgeProvider} for test case data providers.
 */
#[Group('widget')]
final class BadgeTest extends TestCase
{
    public function testRegistersCssAsset(): void
    {
        $this->mockWebApplication();

        Badge::widget(['label' => 'New']);

        self::assertArrayHasKey(
            BootstrapAsset::class,
            self::webView()->assetBundles,
            'CSS bundle must be registered.',
        );
    }

    /**
     * @param array<string, mixed> $config
     */
    #[DataProviderExternal(BadgeProvider::class, 'renderCases')]
    public function testRender(array $config, string $expected): void
    {
        $this->mockWebApplication();

        self::assertSame(
            $expected,
            Badge::widget($config),
            'Markup must match the configuration.',
        );
    }

    public function testRenderStringableContent(): void
    {
        $this->mockWebApplication();

        self::assertSame(
            Badge::widget(['label' => '<b>N</b>', 'options' => ['id' => 'b']]),
            Badge::widget(['label' => new StringableValue('<b>N</b>'), 'options' => ['id' => 'b']]),
            'Stringable values must render like their `string`.',
        );
    }

    public function testThrowInvalidConfigExceptionForInvalidTheme(): void
    {
        $themes = 'accent, danger, info, inverse, primary, secondary, success, warning';

        $this->mockWebApplication();

        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage(
            Message::INVALID_VALUE->getMessage('light', 'theme', $themes),
        );

        new Badge(['theme' => 'light']);
    }
}
