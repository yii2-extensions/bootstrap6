<?php

declare(strict_types=1);

namespace yii2\extensions\bootstrap6\tests\item;

use Closure;
use PHPUnit\Framework\Attributes\{DataProviderExternal, Group};
use yii\base\InvalidConfigException;
use yii2\extensions\bootstrap6\enum\MenuItemType;
use yii2\extensions\bootstrap6\exception\Message;
use yii2\extensions\bootstrap6\item\MenuItem;
use yii2\extensions\bootstrap6\tests\provider\MenuItemProvider;
use yii2\extensions\bootstrap6\tests\support\TestCase;

/**
 * Unit tests for the {@see MenuItem} value object.
 *
 * {@see MenuItemProvider} for test case data providers.
 */
#[Group('item')]
final class MenuItemTest extends TestCase
{
    public function testButton(): void
    {
        self::assertSame(
            self::expected(['type' => MenuItemType::BUTTON, 'label' => 'A']),
            self::exportItem(MenuItem::button('A')),
            'Defaults mismatch.',
        );
        self::assertSame(
            self::expected(
                [
                    'type' => MenuItemType::BUTTON,
                    'label' => 'A',
                    'active' => true,
                    'disabled' => true,
                    'visible' => false,
                    'encode' => false,
                    'linkOptions' => ['class' => 'l'],
                ],
            ),
            self::exportItem(
                MenuItem::button('A')
                    ->withActive(true)
                    ->withDisabled(true)
                    ->withVisible(false)
                    ->withEncode(false)
                    ->withLinkOptions(['class' => 'l']),
            ),
            'Chained values must be assigned.',
        );
    }

    public function testDivider(): void
    {
        self::assertSame(
            self::expected(['type' => MenuItemType::DIVIDER]),
            self::exportItem(MenuItem::divider()),
            'Defaults mismatch.',
        );
        self::assertSame(
            self::expected(['type' => MenuItemType::DIVIDER, 'options' => ['class' => 'my-2']]),
            self::exportItem(MenuItem::divider()->withOptions(['class' => 'my-2'])),
            'Options must be assigned.',
        );
    }

    /**
     * @param array<array-key, mixed> $config
     */
    #[DataProviderExternal(MenuItemProvider::class, 'fromArrayCases')]
    public function testFromArray(array $config, MenuItem $expected): void
    {
        self::assertSame(
            self::exportItem($expected),
            self::exportItem(MenuItem::fromArray($config)),
            'Array must map to the equivalent value object.',
        );
    }

    public function testHeader(): void
    {
        self::assertSame(
            self::expected(['type' => MenuItemType::HEADER, 'label' => 'H']),
            self::exportItem(MenuItem::header('H')),
            'Defaults mismatch.',
        );
        self::assertSame(
            self::expected(
                [
                    'type' => MenuItemType::HEADER,
                    'label' => 'H',
                    'visible' => false,
                    'encode' => true,
                    'options' => ['class' => 'h'],
                ],
            ),
            self::exportItem(
                MenuItem::header('H')
                    ->withVisible(false)
                    ->withEncode(true)
                    ->withOptions(['class' => 'h']),
            ),
            'Chained values must be assigned.',
        );
    }

    public function testLink(): void
    {
        self::assertSame(
            self::expected(['type' => MenuItemType::LINK, 'label' => 'A', 'url' => '/a']),
            self::exportItem(MenuItem::link('A', '/a')),
            'Defaults mismatch.',
        );
        self::assertSame(
            self::expected(
                [
                    'type' => MenuItemType::LINK,
                    'label' => 'A',
                    'url' => ['site/index'],
                    'active' => false,
                    'disabled' => true,
                    'visible' => false,
                    'encode' => true,
                    'linkOptions' => ['class' => 'l'],
                ],
            ),
            self::exportItem(
                MenuItem::link('A', ['site/index'])
                    ->withActive(false)
                    ->withDisabled(true)
                    ->withVisible(false)
                    ->withEncode(true)
                    ->withLinkOptions(['class' => 'l']),
            ),
            'Chained values must be assigned.',
        );
    }

    public function testSubmenu(): void
    {
        self::assertSame(
            self::expected(
                [
                    'type' => MenuItemType::SUBMENU,
                    'label' => 'S',
                    'items' => [self::exportItem(MenuItem::divider()), '<hr>'],
                ],
            ),
            self::exportItem(MenuItem::submenu('S', [MenuItem::divider(), '<hr>'])),
            'Defaults mismatch.',
        );
        self::assertSame(
            self::expected(
                [
                    'type' => MenuItemType::SUBMENU,
                    'label' => 'S',
                    'active' => true,
                    'disabled' => true,
                    'visible' => false,
                    'encode' => false,
                    'options' => ['class' => 'o'],
                    'linkOptions' => ['class' => 'l'],
                    'items' => ['-'],
                    'submenuOptions' => ['class' => 's'],
                ],
            ),
            self::exportItem(
                MenuItem::submenu('S', ['-'])
                    ->withActive(true)
                    ->withDisabled(true)
                    ->withVisible(false)
                    ->withEncode(false)
                    ->withOptions(['class' => 'o'])
                    ->withLinkOptions(['class' => 'l'])
                    ->withSubmenuOptions(['class' => 's']),
            ),
            'Chained values must be assigned.',
        );
    }

    public function testSubmenuConvertsArrayItems(): void
    {
        $item = MenuItem::submenu('S', [['label' => 'A', 'url' => '/a'], '-']);

        self::assertSame(
            [self::exportItem(MenuItem::link('A', '/a')), '-'],
            self::exportValue($item->items),
            'Array children must become menu items; strings stay.',
        );
    }

    public function testText(): void
    {
        self::assertSame(
            self::expected(['type' => MenuItemType::TEXT, 'label' => 'T']),
            self::exportItem(MenuItem::text('T')),
            'Defaults mismatch.',
        );
        self::assertSame(
            self::expected(
                [
                    'type' => MenuItemType::TEXT,
                    'label' => 'T',
                    'visible' => false,
                    'encode' => false,
                    'options' => ['class' => 't'],
                ],
            ),
            self::exportItem(MenuItem::text('T')->withVisible(false)->withEncode(false)->withOptions(['class' => 't'])),
            'Chained values must be assigned.',
        );
    }

    /**
     * @param array<array-key, mixed> $config
     */
    #[DataProviderExternal(MenuItemProvider::class, 'invalidLabelCases')]
    public function testThrowInvalidConfigExceptionForMissingLabel(array $config): void
    {
        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage(
            Message::ITEM_LABEL_REQUIRED->getMessage(),
        );

        MenuItem::fromArray($config);
    }

    /**
     * @param Closure(MenuItem): MenuItem $with
     */
    #[DataProviderExternal(MenuItemProvider::class, 'withCases')]
    public function testWith(Closure $with, string $property, mixed $value): void
    {
        $item = MenuItem::submenu('Label', [MenuItem::link('Child', '#')])
            ->withActive(true)
            ->withDisabled(true)
            ->withVisible(false)
            ->withEncode(false)
            ->withOptions(['class' => 'o'])
            ->withLinkOptions(['class' => 'l'])
            ->withSubmenuOptions(['class' => 's'])
            ->withUrl('/u');

        self::assertWithCopy($item, $with, $property, $value);
    }

    public function testWithItemsConvertsArrayItems(): void
    {
        $item = MenuItem::button('B')->withItems([['label' => 'C']]);

        self::assertSame(
            [self::exportItem(MenuItem::button('C'))],
            self::exportValue($item->items),
            'Array children must become menu items.',
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
                'class' => MenuItem::class,
                'type' => MenuItemType::LINK,
                'label' => '',
                'url' => null,
                'active' => null,
                'disabled' => false,
                'visible' => true,
                'encode' => null,
                'options' => [],
                'linkOptions' => [],
                'items' => [],
                'submenuOptions' => [],
            ],
            ...$overrides,
        ];
    }
}
