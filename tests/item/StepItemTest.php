<?php

declare(strict_types=1);

namespace yii2\extensions\bootstrap6\tests\item;

use Closure;
use PHPUnit\Framework\Attributes\{DataProviderExternal, Group};
use yii\base\InvalidConfigException;
use yii2\extensions\bootstrap6\enum\Theme;
use yii2\extensions\bootstrap6\item\StepItem;
use yii2\extensions\bootstrap6\tests\provider\StepItemProvider;
use yii2\extensions\bootstrap6\tests\support\TestCase;

/**
 * Unit tests for the {@see StepItem} value object.
 *
 * {@see StepItemProvider} for test case data providers.
 */
#[Group('item')]
final class StepItemTest extends TestCase
{
    /**
     * @param array<array-key, mixed> $config
     */
    #[DataProviderExternal(StepItemProvider::class, 'fromArrayCases')]
    public function testFromArray(array $config, StepItem $expected): void
    {
        self::assertSame(
            self::exportItem($expected),
            self::exportItem(StepItem::fromArray($config)),
            'Array must map to the equivalent value object.',
        );
    }

    /**
     * @param Closure(): StepItem $create
     */
    #[DataProviderExternal(StepItemProvider::class, 'invalidCases')]
    public function testThrowInvalidConfigExceptionForMissingLabelOrInvalidTheme(Closure $create, string $message): void
    {
        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage(
            $message,
        );

        $create();
    }

    public function testTo(): void
    {
        self::assertSame(
            [
                'class' => StepItem::class,
                'label' => 'A',
                'description' => null,
                'active' => false,
                'theme' => null,
                'url' => null,
                'encode' => null,
                'options' => [],
            ],
            self::exportItem(StepItem::to('A')),
            'Defaults mismatch.',
        );
        self::assertSame(
            [
                'class' => StepItem::class,
                'label' => 'A',
                'description' => 'D',
                'active' => true,
                'theme' => 'warning',
                'url' => ['site/index'],
                'encode' => false,
                'options' => ['class' => 'o'],
            ],
            self::exportItem(
                StepItem::to('A', 'D')
                    ->withActive(true)
                    ->withTheme('warning')
                    ->withUrl(['site/index'])
                    ->withEncode(false)
                    ->withOptions(['class' => 'o']),
            ),
            'Chained values must be assigned.',
        );
    }

    /**
     * @param Closure(StepItem): StepItem $with
     */
    #[DataProviderExternal(StepItemProvider::class, 'withCases')]
    public function testWith(Closure $with, string $property, mixed $value): void
    {
        $item = StepItem::to('Label', 'Description')
            ->withActive(true)
            ->withTheme(Theme::INFO)
            ->withUrl('/u')
            ->withEncode(false)
            ->withOptions(['class' => 'o']);

        self::assertWithCopy($item, $with, $property, $value);
    }
}
