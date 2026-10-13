<?php

declare(strict_types=1);

namespace yii2\extensions\bootstrap6\tests;

use PHPUnit\Framework\Attributes\{DataProviderExternal, Group};
use yii\base\InvalidConfigException;
use yii2\extensions\bootstrap6\asset\BootstrapAsset;
use yii2\extensions\bootstrap6\enum\{Size, Theme};
use yii2\extensions\bootstrap6\exception\Message;
use yii2\extensions\bootstrap6\Placeholder;
use yii2\extensions\bootstrap6\tests\provider\PlaceholderProvider;
use yii2\extensions\bootstrap6\tests\support\TestCase;

/**
 * Unit tests for {@see Placeholder} width, size, color, animation, and container rendering.
 *
 * {@see PlaceholderProvider} for test case data providers.
 */
#[Group('widget')]
final class PlaceholderTest extends TestCase
{
    public function testAnimationOnElementWithoutContainer(): void
    {
        $this->mockWebApplication();

        self::assertSame(
            '<span id="w0" class="placeholder w-75 placeholder-wave"></span>',
            Placeholder::widget(['animation' => 'wave', 'width' => 'w-75']),
            'Animation class must be added to the element itself.',
        );
    }

    public function testContainerCarriesAnimation(): void
    {
        $this->mockWebApplication();

        self::assertSame(
            '<p class="h5 placeholder-pulse"><span id="w0" class="placeholder col-12"></span></p>',
            Placeholder::widget(
                ['animation' => 'pulse', 'container' => true, 'containerOptions' => ['class' => 'h5'], 'width' => 12],
            ),
            'Animation class must move to the container.',
        );
    }

    public function testContainerTagAndStaticContainer(): void
    {
        $this->mockWebApplication();

        self::assertSame(
            '<div><span id="w0" class="placeholder"></span></div>',
            Placeholder::widget(['container' => true, 'containerOptions' => ['tag' => 'div']]),
            'Container tag must be configurable.',
        );
        self::assertSame(
            '<p><span id="w1" class="placeholder"></span></p>',
            Placeholder::widget(['container' => true, 'containerOptions' => ['tag' => null]]),
            'Non-string container tag must fall back to `p`.',
        );
    }

    public function testRenderDefault(): void
    {
        $this->mockWebApplication();

        self::assertSame(
            '<span id="w0" class="placeholder"></span>',
            Placeholder::widget(),
            'Default placeholder must render the base class.',
        );
        self::assertArrayHasKey(
            BootstrapAsset::class,
            self::webView()->assetBundles,
            'CSS bundle must be registered.',
        );
    }

    public function testSizeThemeAndWidth(): void
    {
        $this->mockWebApplication();

        self::assertSame(
            '<div id="w0" class="mt-2 placeholder col-6 placeholder-lg bg-danger" aria-hidden="true"></div>',
            Placeholder::widget(
                [
                    'options' => ['aria-hidden' => 'true', 'class' => 'mt-2'],
                    'size' => Size::LG,
                    'tagName' => 'div',
                    'theme' => Theme::DANGER,
                    'width' => 'col-6',
                ],
            ),
            'Enum size and theme must be rendered as classes.',
        );
        self::assertSame(
            '<span id="w1" class="placeholder placeholder-xs bg-info"></span>',
            Placeholder::widget(['size' => 'xs', 'theme' => 'info']),
            'String size and theme must be rendered as classes.',
        );
    }

    /**
     * @param array<string, mixed> $config
     */
    #[DataProviderExternal(PlaceholderProvider::class, 'invalidEnumValueCases')]
    public function testThrowInvalidConfigExceptionForInvalidEnumValue(array $config, string $message): void
    {
        $this->mockWebApplication();

        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage(
            $message,
        );

        new Placeholder([...$config]);
    }

    public function testThrowInvalidConfigExceptionWhenAnimationIsInvalid(): void
    {
        $this->mockWebApplication();

        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage(
            Message::INVALID_VALUE->getMessage('shine', 'animation', 'glow, pulse, wave'),
        );

        Placeholder::widget(['animation' => 'shine']);
    }
}
