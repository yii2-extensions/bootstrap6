<?php

declare(strict_types=1);

namespace yii2\extensions\bootstrap6\tests\item;

use Closure;
use PHPUnit\Framework\Attributes\{DataProviderExternal, Group};
use yii\base\InvalidConfigException;
use yii2\extensions\bootstrap6\item\AccordionItem;
use yii2\extensions\bootstrap6\tests\provider\AccordionItemProvider;
use yii2\extensions\bootstrap6\tests\support\TestCase;

/**
 * Unit tests for the {@see AccordionItem} value object.
 *
 * {@see AccordionItemProvider} for test case data providers.
 */
#[Group('item')]
final class AccordionItemTest extends TestCase
{
    /**
     * @param array<array-key, mixed> $config
     */
    #[DataProviderExternal(AccordionItemProvider::class, 'fromArrayCases')]
    public function testFromArray(array $config, AccordionItem $expected): void
    {
        self::assertSame(
            self::exportItem($expected),
            self::exportItem(AccordionItem::fromArray($config)),
            'Array must map to the equivalent value object.',
        );
    }

    /**
     * @param array<array-key, mixed> $config
     */
    #[DataProviderExternal(AccordionItemProvider::class, 'invalidCases')]
    public function testThrowInvalidConfigExceptionForMissingLabelOrContent(array $config, string $message): void
    {
        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage($message);

        AccordionItem::fromArray($config);
    }

    public function testTo(): void
    {
        self::assertSame(
            [
                'class' => AccordionItem::class,
                'label' => 'A',
                'content' => 'B',
                'open' => false,
                'visible' => true,
                'encode' => null,
                'options' => [],
                'headerOptions' => [],
                'bodyOptions' => [],
            ],
            self::exportItem(AccordionItem::to('A', 'B')),
            'Defaults mismatch.',
        );
        self::assertSame(
            [
                'class' => AccordionItem::class,
                'label' => 'A',
                'content' => 'B',
                'open' => true,
                'visible' => false,
                'encode' => false,
                'options' => ['class' => 'o'],
                'headerOptions' => ['class' => 'h'],
                'bodyOptions' => ['class' => 'b'],
            ],
            self::exportItem(
                AccordionItem::to('A', 'B')
                    ->withOpen(true)
                    ->withVisible(false)
                    ->withEncode(false)
                    ->withOptions(['class' => 'o'])
                    ->withHeaderOptions(['class' => 'h'])
                    ->withBodyOptions(['class' => 'b']),
            ),
            'Chained values must be assigned.',
        );
    }

    /**
     * @param Closure(AccordionItem): AccordionItem $with
     */
    #[DataProviderExternal(AccordionItemProvider::class, 'withCases')]
    public function testWith(Closure $with, string $property, mixed $value): void
    {
        $item = AccordionItem::to('Label', 'Body')
            ->withOpen(true)
            ->withVisible(false)
            ->withEncode(true)
            ->withOptions(['class' => 'o'])
            ->withHeaderOptions(['class' => 'h'])
            ->withBodyOptions(['class' => 'b']);

        self::assertWithCopy($item, $with, $property, $value);
    }
}
