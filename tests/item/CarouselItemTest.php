<?php

declare(strict_types=1);

namespace yii2\extensions\bootstrap6\tests\item;

use Closure;
use PHPUnit\Framework\Attributes\{DataProviderExternal, Group};
use yii\base\InvalidConfigException;
use yii2\extensions\bootstrap6\exception\Message;
use yii2\extensions\bootstrap6\item\CarouselItem;
use yii2\extensions\bootstrap6\tests\provider\CarouselItemProvider;
use yii2\extensions\bootstrap6\tests\support\TestCase;

/**
 * Unit tests for the {@see CarouselItem} value object.
 *
 * {@see CarouselItemProvider} for test case data providers.
 */
#[Group('item')]
final class CarouselItemTest extends TestCase
{
    /**
     * @param array<array-key, mixed> $config
     */
    #[DataProviderExternal(CarouselItemProvider::class, 'fromArrayCases')]
    public function testFromArray(array $config, CarouselItem $expected): void
    {
        self::assertSame(
            self::exportItem($expected),
            self::exportItem(CarouselItem::fromArray($config)),
            'Array must map to the equivalent value object.',
        );
    }

    /**
     * @param array<array-key, mixed> $config
     */
    #[DataProviderExternal(CarouselItemProvider::class, 'invalidContentCases')]
    public function testThrowInvalidConfigExceptionForMissingContent(array $config): void
    {
        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage(
            Message::ITEM_CONTENT_REQUIRED->getMessage(),
        );

        CarouselItem::fromArray($config);
    }

    public function testTo(): void
    {
        self::assertSame(
            ['class' => CarouselItem::class, 'content' => 'A', 'options' => []],
            self::exportItem(CarouselItem::to('A')),
            'Defaults mismatch.',
        );
        self::assertSame(
            ['class' => CarouselItem::class, 'content' => 'A', 'options' => ['class' => 'o']],
            self::exportItem(CarouselItem::to('A')->withOptions(['class' => 'o'])),
            'Chained values must be assigned.',
        );
    }

    /**
     * @param Closure(CarouselItem): CarouselItem $with
     */
    #[DataProviderExternal(CarouselItemProvider::class, 'withCases')]
    public function testWith(Closure $with, string $property, mixed $value): void
    {
        $item = CarouselItem::to('Slide')->withOptions(['class' => 'o']);

        self::assertWithCopy($item, $with, $property, $value);
    }
}
