<?php

declare(strict_types=1);

namespace yii2\extensions\bootstrap6\tests;

use PHPUnit\Framework\Attributes\{DataProviderExternal, Group};
use yii\base\InvalidConfigException;
use yii2\extensions\bootstrap6\asset\{BootstrapAsset, BootstrapPluginAsset};
use yii2\extensions\bootstrap6\Avatar;
use yii2\extensions\bootstrap6\enum\{Size, Theme};
use yii2\extensions\bootstrap6\exception\Message;
use yii2\extensions\bootstrap6\tests\provider\AvatarProvider;
use yii2\extensions\bootstrap6\tests\support\TestCase;

/**
 * Unit tests for {@see Avatar} image, initials, size, status, and theme rendering.
 *
 * {@see AvatarProvider} for test case data providers.
 */
#[Group('widget')]
final class AvatarTest extends TestCase
{
    public function testRegisterCssAssetOnly(): void
    {
        $this->mockWebApplication();

        Avatar::widget(['initials' => 'AB']);

        self::assertArrayHasKey(
            BootstrapAsset::class,
            self::webView()->assetBundles,
            'CSS bundle must be registered.',
        );
        self::assertArrayNotHasKey(
            BootstrapPluginAsset::class,
            self::webView()->assetBundles,
            'Plugin bundle must not be registered.',
        );
    }

    public function testRenderEmpty(): void
    {
        $this->mockWebApplication();

        self::assertSameHtml(
            '<span id="w0" class="avatar"></span>',
            Avatar::widget(),
            'Avatar must render empty.',
        );
    }

    public function testRenderImage(): void
    {
        $this->mockWebApplication();

        self::assertSameHtml(
            <<<HTML
            <span id="w0" class="avatar">
            <img class="avatar-img" src="https://github.com/mdo.png" alt="mdo">
            </span>
            HTML,
            Avatar::widget(['src' => 'https://github.com/mdo.png', 'alt' => 'mdo', 'initials' => 'MD']),
            'Image must take precedence over initials.',
        );
    }

    public function testRenderImageWithoutAlt(): void
    {
        $this->mockWebApplication();

        self::assertStringContainsString(
            '<img class="avatar-img" src="/a.png" alt="">',
            Avatar::widget(['src' => '/a.png']),
            'Alternative text must default to empty.',
        );
    }

    public function testRenderInitials(): void
    {
        $this->mockWebApplication();

        self::assertSameHtml(
            '<span id="user" class="custom avatar avatar-subtle theme-accent">A&amp;B</span>',
            Avatar::widget(
                [
                    'initials' => 'A&B',
                    'subtle' => true,
                    'theme' => Theme::ACCENT,
                    'options' => ['id' => 'user', 'class' => 'custom'],
                ],
            ),
            'Initials must be encoded inside the themed avatar.',
        );
    }

    public function testRenderInitialsWithoutEncoding(): void
    {
        $this->mockWebApplication();

        self::assertSameHtml(
            '<span id="w0" class="avatar theme-secondary"><b>+5</b></span>',
            Avatar::widget(['initials' => '<b>+5</b>', 'encodeLabel' => false, 'theme' => 'secondary']),
            'Initials must be rendered raw.',
        );
    }

    public function testRenderInitialsWithStatus(): void
    {
        $this->mockWebApplication();

        self::assertSameHtml(
            <<<HTML
            <span id="w0" class="avatar">AB
            <span class="avatar-status status-busy" role="img" aria-label="Do not disturb"></span>
            </span>
            HTML,
            Avatar::widget(['initials' => 'AB', 'status' => 'busy', 'statusLabel' => 'Do not disturb']),
            'Custom status label must be used.',
        );
    }

    #[DataProviderExternal(AvatarProvider::class, 'sizeCases')]
    public function testRenderSize(Size|string $size, string $class): void
    {
        $this->mockWebApplication();

        self::assertSameHtml(
            "<span id=\"w0\" class=\"avatar {$class}\">AB</span>",
            Avatar::widget(['initials' => 'AB', 'size' => $size]),
            'Size must be rendered as its class.',
        );
    }

    #[DataProviderExternal(AvatarProvider::class, 'statusCases')]
    public function testRenderStatus(string $status, string $label): void
    {
        $this->mockWebApplication();

        self::assertSameHtml(
            <<<HTML
            <span id="w0" class="avatar avatar-lg">
            <img class="avatar-img" src="/a.png" alt="mdo">
            <span class="avatar-status status-{$status}" role="img" aria-label="{$label}"></span>
            </span>
            HTML,
            Avatar::widget(['src' => '/a.png', 'alt' => 'mdo', 'size' => Size::LG, 'status' => $status]),
            'Status indicator must follow the image.',
        );
    }

    /**
     * @param array<string, mixed> $config
     */
    #[DataProviderExternal(AvatarProvider::class, 'invalidEnumValueCases')]
    public function testThrowInvalidConfigExceptionForInvalidEnumValue(array $config, string $message): void
    {
        $this->mockWebApplication();

        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage(
            $message,
        );

        new Avatar([...$config]);
    }

    public function testThrowInvalidConfigExceptionForInvalidSize(): void
    {
        $this->mockWebApplication();

        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage(
            Message::INVALID_VALUE->getMessage('md', 'size', 'lg, sm, xl, xs'),
        );

        Avatar::widget(['size' => 'md']);
    }

    public function testThrowInvalidConfigExceptionForInvalidStatus(): void
    {
        $this->mockWebApplication();

        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage(
            Message::INVALID_VALUE->getMessage('idle', 'status', 'online, offline, busy, away'),
        );

        Avatar::widget(['status' => 'idle']);
    }
}
