<?php

declare(strict_types=1);

namespace yii2\extensions\bootstrap6\tests\item;

use Closure;
use PHPUnit\Framework\Attributes\{DataProviderExternal, Group};
use yii\base\InvalidConfigException;
use yii2\extensions\bootstrap6\exception\Message;
use yii2\extensions\bootstrap6\item\{MenuItem, TabItem};
use yii2\extensions\bootstrap6\tests\provider\TabItemProvider;
use yii2\extensions\bootstrap6\tests\support\TestCase;

/**
 * Unit tests for the {@see TabItem} value object.
 *
 * {@see TabItemProvider} for test case data providers.
 */
#[Group('item')]
final class TabItemTest extends TestCase
{
    /**
     * @param array<array-key, mixed> $config
     */
    #[DataProviderExternal(TabItemProvider::class, 'fromArrayCases')]
    public function testFromArray(array $config, TabItem $expected): void
    {
        self::assertSame(
            self::exportItem($expected),
            self::exportItem(TabItem::fromArray($config)),
            'Array must map to the equivalent value object.',
        );
    }

    public function testLink(): void
    {
        self::assertSame(
            self::expected(['label' => 'A', 'url' => '/a']),
            self::exportItem(TabItem::link('A', '/a')),
            'Defaults mismatch.',
        );
        self::assertSame(
            self::expected(
                [
                    'label' => 'A',
                    'url' => ['site/index'],
                    'visible' => false,
                    'encode' => false,
                    'headerOptions' => ['class' => 'h'],
                    'linkOptions' => ['class' => 'l'],
                ],
            ),
            self::exportItem(
                TabItem::link('A', ['site/index'])
                    ->withVisible(false)
                    ->withEncode(false)
                    ->withHeaderOptions(['class' => 'h'])
                    ->withLinkOptions(['class' => 'l']),
            ),
            'Chained values must be assigned.',
        );
    }

    public function testMenu(): void
    {
        self::assertSame(
            self::expected(['label' => 'M', 'items' => [self::exportItem(TabItem::to('T')), '-']]),
            self::exportItem(TabItem::menu('M', [TabItem::to('T'), '-'])),
            'Defaults mismatch.',
        );
        self::assertSame(
            self::expected(
                [
                    'label' => 'M',
                    'visible' => false,
                    'encode' => true,
                    'headerOptions' => ['class' => 'h'],
                    'linkOptions' => ['class' => 'l'],
                    'items' => [self::exportItem(MenuItem::divider())],
                ],
            ),
            self::exportItem(
                TabItem::menu('M', [MenuItem::divider()])
                    ->withVisible(false)
                    ->withEncode(true)
                    ->withHeaderOptions(['class' => 'h'])
                    ->withLinkOptions(['class' => 'l']),
            ),
            'Chained values must be assigned.',
        );
    }

    public function testMenuConvertsArrayItems(): void
    {
        $item = TabItem::menu(
            'M',
            [
                ['label' => 'A', 'url' => '/a'],
                ['label' => 'P', 'url' => null],
                ['label' => 'B', 'content' => 'b'],
                '-',
            ],
        );

        self::assertSame(
            [
                self::exportItem(MenuItem::link('A', '/a')),
                self::exportItem(MenuItem::link('P', null)),
                self::exportItem(TabItem::to('B', 'b')),
                '-',
            ],
            self::exportValue($item->items),
            'Arrays with a `url` key, even `null`, become menu items, the others tabs; strings stay.',
        );
    }

    /**
     * @param array<array-key, mixed> $config
     */
    #[DataProviderExternal(TabItemProvider::class, 'invalidLabelCases')]
    public function testThrowInvalidConfigExceptionForMissingLabel(array $config): void
    {
        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage(
            Message::ITEM_LABEL_REQUIRED->getMessage(),
        );

        TabItem::fromArray($config);
    }

    public function testTo(): void
    {
        self::assertSame(
            self::expected(['label' => 'A']),
            self::exportItem(TabItem::to('A')),
            'Defaults mismatch.',
        );
        self::assertSame(
            self::expected(
                [
                    'label' => 'A',
                    'content' => 'c',
                    'active' => false,
                    'disabled' => true,
                    'visible' => false,
                    'encode' => true,
                    'headerOptions' => ['class' => 'h'],
                    'linkOptions' => ['class' => 'l'],
                    'options' => ['class' => 'o'],
                ],
            ),
            self::exportItem(
                TabItem::to('A', 'c')
                    ->withActive(false)
                    ->withDisabled(true)
                    ->withVisible(false)
                    ->withEncode(true)
                    ->withHeaderOptions(['class' => 'h'])
                    ->withLinkOptions(['class' => 'l'])
                    ->withOptions(['class' => 'o']),
            ),
            'Chained values must be assigned.',
        );
    }

    /**
     * @param Closure(TabItem): TabItem $with
     */
    #[DataProviderExternal(TabItemProvider::class, 'withCases')]
    public function testWith(Closure $with, string $property, mixed $value): void
    {
        $item = TabItem::to('Label', 'c')
            ->withActive(true)
            ->withDisabled(true)
            ->withVisible(false)
            ->withEncode(false)
            ->withHeaderOptions(['class' => 'h'])
            ->withLinkOptions(['class' => 'l'])
            ->withOptions(['class' => 'o'])
            ->withUrl('/u')
            ->withItems([TabItem::to('Child')]);

        self::assertWithCopy($item, $with, $property, $value);
    }

    public function testWithItemsConvertsArrayItems(): void
    {
        $item = TabItem::to('T')->withItems([['header' => 'H'], ['label' => 'B', 'content' => 'b']]);

        self::assertSame(
            [self::exportItem(MenuItem::header('H')), self::exportItem(TabItem::to('B', 'b'))],
            self::exportValue($item->items),
            'Arrays without `label` become menu items, the others tabs.',
        );
    }

    /**
     * @param array<string, mixed> $overrides
     *
     * @return array<string, mixed>
     */
    private static function expected(array $overrides): array
    {
        return [
            ...[
                'class' => TabItem::class,
                'label' => '',
                'content' => null,
                'url' => null,
                'active' => null,
                'disabled' => false,
                'visible' => true,
                'encode' => null,
                'headerOptions' => [],
                'linkOptions' => [],
                'options' => [],
                'items' => [],
            ],
            ...$overrides,
        ];
    }
}
