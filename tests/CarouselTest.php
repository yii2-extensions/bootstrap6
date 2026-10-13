<?php

declare(strict_types=1);

namespace yii2\extensions\bootstrap6\tests;

use PHPUnit\Framework\Attributes\Group;
use yii\base\InvalidConfigException;
use yii2\extensions\bootstrap6\Carousel;
use yii2\extensions\bootstrap6\enum\CarouselEnds;
use yii2\extensions\bootstrap6\exception\Message;
use yii2\extensions\bootstrap6\item\CarouselItem;
use yii2\extensions\bootstrap6\tests\support\stub\StringableValue;
use yii2\extensions\bootstrap6\tests\support\TestCase;

/**
 * Unit tests for {@see Carousel} slides, controls, indicators, data attributes, and plugin registration.
 */
#[Group('widget')]
final class CarouselTest extends TestCase
{
    private const string IMPORT = 'import("/assets/js/bootstrap.bundle.js").then((bootstrap) => { ';

    public function testCarouselOptions(): void
    {
        $this->mockWebApplication();

        self::assertSameHtml(
            <<<HTML
            <div id="w0" class="my-carousel carousel carousel-fade" data-foo="bar" data-bs-autoplay="true" data-bs-ends="stop" data-bs-interval="2000" data-bs-pause="false" data-bs-keyboard="false">
            <div class="carousel-inner">
            <div class="carousel-item active">A</div>
            </div>
            </div>
            HTML,
            Carousel::widget(
                [
                    'autoplay' => true,
                    'controls' => false,
                    'crossfade' => true,
                    'ends' => CarouselEnds::STOP,
                    'interval' => 2000,
                    'items' => ['A'],
                    'keyboard' => false,
                    'options' => ['class' => 'my-carousel', 'data' => ['foo' => 'bar']],
                    'pause' => false,
                    'showIndicators' => false,
                ],
            ),
            'Every option must be rendered as a data attribute.',
        );
        self::assertSameHtml(
            <<<HTML
            <div id="w1" class="carousel slide" data-bs-ends="wrap" data-bs-pause="hover" data-bs-keyboard="true">
            <div class="carousel-inner">
            <div class="carousel-item active">A</div>
            </div>
            </div>
            HTML,
            Carousel::widget(
                [
                    'controls' => false,
                    'ends' => 'wrap',
                    'items' => ['A'],
                    'keyboard' => true,
                    'pause' => 'hover',
                    'showIndicators' => false,
                ],
            ),
            'String values must be rendered verbatim.',
        );
    }

    public function testControlLabels(): void
    {
        $this->mockWebApplication();

        self::assertSameHtml(
            <<<HTML
            <div id="w0" class="carousel slide">
            <div class="carousel-inner">
            <div class="carousel-item active">A</div>
            </div>
            <div class="my-controls d-flex justify-content-between align-items-center">
            <div>
            <button type="button" class="btn-icon btn-sm" data-bs-target="#w0" data-bs-slide="prev">
            <span class="carousel-icon-prev" aria-hidden="true"></span>
            <span class="visually-hidden">Back &amp; forth</span>
            </button>
            <button type="button" class="btn-icon btn-sm" data-bs-target="#w0" data-bs-slide="next">
            <span class="carousel-icon-next" aria-hidden="true"></span>
            <span class="visually-hidden">Next</span>
            </button>
            </div>
            </div>
            </div>
            HTML,
            Carousel::widget(
                [
                    'controls' => ['Back & forth'],
                    'controlsOptions' => ['class' => 'my-controls'],
                    'items' => ['A'],
                    'showIndicators' => false,
                ],
            ),
            'Configured label must be encoded; the missing one must default.',
        );
        self::assertStringContainsString(
            '<span class="visually-hidden">Forward</span>',
            Carousel::widget(['controls' => ['Back', 'Forward'], 'items' => ['A'], 'showIndicators' => false]),
            'Next label must be configurable.',
        );
    }

    public function testIndicatorsOnly(): void
    {
        $this->mockWebApplication();

        self::assertSameHtml(
            <<<HTML
            <div id="w0" class="carousel slide">
            <div class="carousel-inner">
            <div class="carousel-item active">A</div>
            <div class="carousel-item">B</div>
            <div class="carousel-item">C</div>
            </div>
            <div class="d-flex justify-content-between align-items-center">
            <div class="carousel-indicators">
            <button type="button" class="active" data-bs-target="#w0" data-bs-slide-to="0" aria-current="true" aria-label="Diapositiva 1"></button>
            <button type="button" data-bs-target="#w0" data-bs-slide-to="1" aria-label="Diapositiva 2"></button>
            <button type="button" data-bs-target="#w0" data-bs-slide-to="2" aria-label="Diapositiva 3"></button>
            </div>
            </div>
            </div>
            HTML,
            Carousel::widget(
                ['controls' => false, 'indicatorLabel' => 'Diapositiva {number}', 'items' => ['A', 'B', 'C']],
            ),
            'Indicators must target each slide.',
        );
    }

    public function testIndicatorsWithMixedItems(): void
    {
        $this->mockWebApplication();

        self::assertSameHtml(
            <<<HTML
            <div id="w0" class="carousel slide">
            <div class="carousel-inner">
            <div class="my-item carousel-item active">A</div>
            <div class="carousel-item">B</div>
            <div class="carousel-item">C</div>
            </div>
            <div class="d-flex justify-content-between align-items-center">
            <div class="carousel-indicators">
            <button type="button" class="active" data-bs-target="#w0" data-bs-slide-to="0" aria-current="true" aria-label="Slide 1"></button>
            <button type="button" data-bs-target="#w0" data-bs-slide-to="1" aria-label="Slide 2"></button>
            <button type="button" data-bs-target="#w0" data-bs-slide-to="2" aria-label="Slide 3"></button>
            </div>
            </div>
            </div>
            HTML,
            Carousel::widget(
                [
                    'controls' => false,
                    'items' => [CarouselItem::to('A')->withOptions(['class' => 'my-item']), ['content' => 'B'], 'C'],
                ],
            ),
            'Indicators must count every slide.',
        );
    }

    public function testItemsWithStringKeys(): void
    {
        $this->mockWebApplication();

        self::assertSameHtml(
            <<<HTML
            <div id="w0" class="carousel slide">
            <div class="carousel-inner">
            <div class="carousel-item active">A</div>
            <div class="carousel-item">B</div>
            </div>
            </div>
            HTML,
            Carousel::widget(['controls' => false, 'items' => ['first' => 'A', 'second' => 'B'], 'showIndicators' => false]),
            'Keys must not affect the slide positions.',
        );
    }

    public function testOverlayWithPlayPause(): void
    {
        $this->mockWebApplication(['language' => 'es']);

        self::assertSameHtml(
            <<<HTML
            <div id="w0" class="carousel slide carousel-overlay" data-bs-autoplay="true">
            <div class="carousel-inner">
            <div class="carousel-item active">A</div>
            </div>
            <div class="carousel-overlay-controls">
            <div>
            <button type="button" class="btn-icon btn-sm btn-subtle theme-secondary carousel-control-play-pause" data-bs-target="#w0" data-bs-pause-label="Pausar" data-bs-play-label="Reproducir" aria-label="Pausar">
            <span class="carousel-icon-pause" aria-hidden="true"></span>
            <span class="carousel-icon-play" aria-hidden="true"></span>
            </button>
            <button type="button" class="btn-icon btn-sm btn-subtle theme-secondary" data-bs-target="#w0" data-bs-slide="prev">
            <span class="carousel-icon-prev" aria-hidden="true"></span>
            <span class="visually-hidden">Anterior</span>
            </button>
            <button type="button" class="btn-icon btn-sm btn-subtle theme-secondary" data-bs-target="#w0" data-bs-slide="next">
            <span class="carousel-icon-next" aria-hidden="true"></span>
            <span class="visually-hidden">Siguiente</span>
            </button>
            </div>
            </div>
            </div>
            HTML,
            Carousel::widget(
                ['autoplay' => true, 'items' => ['A'], 'overlay' => true, 'playPause' => true, 'showIndicators' => false],
            ),
            'Overlay controls must be themed and labels translated.',
        );
    }

    public function testPlayPauseWithoutAutoplay(): void
    {
        $this->mockWebApplication();

        self::assertSameHtml(
            <<<HTML
            <div id="w0" class="carousel slide">
            <div class="carousel-inner">
            <div class="carousel-item active">A</div>
            </div>
            <div class="d-flex justify-content-between align-items-center">
            <div>
            <button type="button" class="btn-icon btn-sm carousel-control-play-pause" data-bs-target="#w0" data-bs-pause-label="Pause" data-bs-play-label="Play" aria-label="Play">
            <span class="carousel-icon-pause" aria-hidden="true"></span>
            <span class="carousel-icon-play" aria-hidden="true"></span>
            </button>
            </div>
            </div>
            </div>
            HTML,
            Carousel::widget(['controls' => false, 'items' => ['A'], 'playPause' => true, 'showIndicators' => false]),
            'Stopped carousel must label the control `Play`.',
        );
    }

    public function testProtectedMethodsAreExtensionPoints(): void
    {
        $this->mockWebApplication();

        $carousel = new class (['controls' => false, 'items' => ['A']]) extends Carousel {
            protected function carouselOptions(): array
            {
                return [...parent::carouselOptions(), 'data-extended' => 'true'];
            }

            protected function renderControls(): string
            {
                return '<!-- controls -->' . parent::renderControls();
            }

            protected function renderIndicators(): string
            {
                return '<!-- indicators -->' . parent::renderIndicators();
            }

            protected function renderItem(CarouselItem $item, int $index): string
            {
                return '<!-- item -->' . parent::renderItem($item, $index);
            }

            protected function renderItems(): string
            {
                return '<!-- items -->' . parent::renderItems();
            }
        };

        self::assertSameHtml(
            <<<HTML
            <div id="w0" class="carousel slide" data-extended="true">
            <!-- items --><div class="carousel-inner">
            <!-- item --><div class="carousel-item active">A</div>
            </div>
            <!-- controls --><div class="d-flex justify-content-between align-items-center">
            <!-- indicators --><div class="carousel-indicators">
            <button type="button" class="active" data-bs-target="#w0" data-bs-slide-to="0" aria-current="true" aria-label="Slide 1"></button>
            </div>
            </div>
            </div>
            HTML,
            $carousel->run(),
            'Overridden renderers must be used.',
        );
    }

    public function testRender(): void
    {
        $this->mockWebApplication();

        self::assertSameHtml(
            <<<HTML
            <div id="w0" class="carousel slide">
            <div class="carousel-inner">
            <div class="carousel-item active"><img src="a.png" alt="A"></div>
            <div class="my-item carousel-item" data-bs-interval="3000">B</div>
            </div>
            <div class="d-flex justify-content-between align-items-center">
            <div>
            <button type="button" class="btn-icon btn-sm" data-bs-target="#w0" data-bs-slide="prev">
            <span class="carousel-icon-prev" aria-hidden="true"></span>
            <span class="visually-hidden">Previous</span>
            </button>
            <button type="button" class="btn-icon btn-sm" data-bs-target="#w0" data-bs-slide="next">
            <span class="carousel-icon-next" aria-hidden="true"></span>
            <span class="visually-hidden">Next</span>
            </button>
            </div>
            <div class="carousel-indicators">
            <button type="button" class="active" data-bs-target="#w0" data-bs-slide-to="0" aria-current="true" aria-label="Slide 1"></button>
            <button type="button" data-bs-target="#w0" data-bs-slide-to="1" aria-label="Slide 2"></button>
            </div>
            </div>
            </div>
            HTML,
            Carousel::widget(
                [
                    'items' => [
                        '<img src="a.png" alt="A">',
                        ['content' => 'B', 'options' => ['class' => 'my-item', 'data-bs-interval' => '3000']],
                    ],
                ],
            ),
            'Default carousel must render controls and indicators.',
        );
        self::assertSame(
            self::IMPORT . 'bootstrap.Carousel.getOrCreateInstance(document.getElementById("w0"), {}); });',
            self::registeredJs(self::webView(), 'w0-plugin'),
            'Plugin must always be initialized.',
        );
    }

    public function testRenderStringableContent(): void
    {
        $this->mockWebApplication();

        self::assertSame(
            Carousel::widget(['indicatorLabel' => 'Go {number}', 'items' => [['content' => 'A'], 'B'], 'options' => ['id' => 'c']]),
            Carousel::widget([
                'indicatorLabel' => new StringableValue('Go {number}'),
                'items' => [['content' => new StringableValue('A')], 'B'],
                'options' => ['id' => 'c'],
            ]),
            'Stringable values must render like their `string`.',
        );
    }

    public function testRenderValueObjects(): void
    {
        $this->mockWebApplication();

        self::assertSame(
            Carousel::widget(
                [
                    'items' => [
                        '<img src="a.png" alt="A">',
                        ['content' => 'B', 'options' => ['class' => 'my-item', 'data-bs-interval' => '3000']],
                    ],
                    'options' => ['id' => 'c'],
                ],
            ),
            Carousel::widget(
                [
                    'items' => [
                        CarouselItem::to('<img src="a.png" alt="A">'),
                        CarouselItem::to('B')->withOptions(['class' => 'my-item', 'data-bs-interval' => '3000']),
                    ],
                    'options' => ['id' => 'c'],
                ],
            ),
            'Value objects must render like their array form.',
        );
    }

    public function testRenderWithoutControlsAndIndicators(): void
    {
        $this->mockWebApplication();

        self::assertSameHtml(
            <<<HTML
            <div id="w0" class="carousel slide">
            <div class="carousel-inner">
            <div class="carousel-item active">A</div>
            <div class="carousel-item">B</div>
            </div>
            </div>
            HTML,
            Carousel::widget(['controls' => false, 'items' => ['A', ['content' => 'B', 'options' => 'x']], 'showIndicators' => false]),
            'Controls wrapper must be omitted.',
        );
    }

    public function testThrowInvalidConfigExceptionForInvalidEnds(): void
    {
        $this->mockWebApplication();

        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage(
            Message::INVALID_VALUE->getMessage('bounce', 'ends', 'loop, stop, wrap'),
        );

        new Carousel(['ends' => 'bounce']);
    }

    public function testThrowInvalidConfigExceptionWhenContentIsMissing(): void
    {
        $this->mockWebApplication();

        try {
            Carousel::widget(['items' => [['options' => []]]]);

            self::fail(
                'Exception must be thrown.',
            );
        } catch (InvalidConfigException $exception) {
            self::assertSame(
                Message::ITEM_CONTENT_REQUIRED->getMessage(),
                $exception->getMessage(),
                'Message must report the missing content.',
            );
        }

        self::assertNull(
            self::registeredJs(self::webView(), 'w0-plugin'),
            'Invalid configuration must not register the plugin.',
        );
    }

    public function testThrowInvalidConfigExceptionWhenItemsAreEmpty(): void
    {
        $this->mockWebApplication();

        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage(
            Message::INVALID_ITEMS->getMessage(),
        );

        Carousel::widget();
    }
}
