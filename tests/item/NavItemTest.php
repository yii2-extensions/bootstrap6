<?php

declare(strict_types=1);

namespace yii2\extensions\bootstrap6\tests\item;

use Closure;
use PHPUnit\Framework\Attributes\{DataProviderExternal, Group};
use yii\base\InvalidConfigException;
use yii2\extensions\bootstrap6\exception\Message;
use yii2\extensions\bootstrap6\item\{MenuItem, NavItem};
use yii2\extensions\bootstrap6\tests\provider\NavItemProvider;
use yii2\extensions\bootstrap6\tests\support\TestCase;

/**
 * Unit tests for the {@see NavItem} value object.
 *
 * {@see NavItemProvider} for test case data providers.
 */
#[Group('item')]
final class NavItemTest extends TestCase
{
    /**
     * @param array<array-key, mixed> $config
     */
    #[DataProviderExternal(NavItemProvider::class, 'fromArrayCases')]
    public function testFromArray(array $config, NavItem $expected): void
    {
        self::assertSame(
            self::exportItem($expected),
            self::exportItem(NavItem::fromArray($config)),
            'Array must map to the equivalent value object.',
        );
    }

    public function testMenu(): void
    {
        self::assertSame(
            self::expected(['label' => 'M', 'items' => [self::exportItem(MenuItem::button('A')), '-']]),
            self::exportItem(NavItem::menu('M', [MenuItem::button('A'), '-'])),
            'Defaults mismatch.',
        );
        self::assertSame(
            self::expected(
                [
                    'label' => 'M',
                    'active' => false,
                    'disabled' => true,
                    'visible' => false,
                    'encode' => true,
                    'options' => ['class' => 'o'],
                    'linkOptions' => ['class' => 'l'],
                    'items' => '<div></div>',
                    'menuOptions' => ['class' => 'm'],
                ],
            ),
            self::exportItem(
                NavItem::menu('M', '<div></div>')
                    ->withActive(false)
                    ->withDisabled(true)
                    ->withVisible(false)
                    ->withEncode(true)
                    ->withOptions(['class' => 'o'])
                    ->withLinkOptions(['class' => 'l'])
                    ->withMenuOptions(['class' => 'm']),
            ),
            'Chained values must be assigned.',
        );
    }

    public function testMenuConvertsArrayItems(): void
    {
        $item = NavItem::menu('M', [['label' => 'A', 'url' => '/a'], '-']);

        self::assertSame(
            [self::exportItem(MenuItem::link('A', '/a')), '-'],
            self::exportValue($item->items),
            'Array children must become menu items; strings stay.',
        );
    }

    /**
     * @param array<array-key, mixed> $config
     */
    #[DataProviderExternal(NavItemProvider::class, 'invalidLabelCases')]
    public function testThrowInvalidConfigExceptionForMissingLabel(array $config): void
    {
        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage(
            Message::ITEM_LABEL_REQUIRED->getMessage(),
        );

        NavItem::fromArray($config);
    }

    public function testTo(): void
    {
        self::assertSame(self::expected(['label' => 'A']), self::exportItem(NavItem::to('A')), 'Defaults mismatch.');
        self::assertSame(
            self::expected(
                [
                    'label' => 'A',
                    'url' => ['site/index'],
                    'active' => true,
                    'disabled' => true,
                    'visible' => false,
                    'encode' => false,
                    'options' => ['class' => 'o'],
                    'linkOptions' => ['class' => 'l'],
                ],
            ),
            self::exportItem(
                NavItem::to('A', ['site/index'])
                    ->withActive(true)
                    ->withDisabled(true)
                    ->withVisible(false)
                    ->withEncode(false)
                    ->withOptions(['class' => 'o'])
                    ->withLinkOptions(['class' => 'l']),
            ),
            'Chained values must be assigned.',
        );
        self::assertNull(
            NavItem::to('A', null)->url,
            'URL `null` must be kept.',
        );
    }

    /**
     * @param Closure(NavItem): NavItem $with
     */
    #[DataProviderExternal(NavItemProvider::class, 'withCases')]
    public function testWith(Closure $with, string $property, mixed $value): void
    {
        $item = NavItem::menu('Label', [MenuItem::link('Child', '#')])
            ->withActive(true)
            ->withDisabled(true)
            ->withVisible(false)
            ->withEncode(false)
            ->withOptions(['class' => 'o'])
            ->withLinkOptions(['class' => 'l'])
            ->withMenuOptions(['class' => 'm'])
            ->withUrl('/u');

        self::assertWithCopy($item, $with, $property, $value);
    }

    public function testWithItemsConvertsArrayItems(): void
    {
        $item = NavItem::to('N')->withItems([['label' => 'B']]);

        self::assertSame(
            [self::exportItem(MenuItem::button('B'))],
            self::exportValue($item->items),
            'Array children must become menu items.',
        );
        self::assertSame(
            'html',
            NavItem::to('N')->withItems('html')->items,
            'Menu HTML must be kept verbatim.',
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
                'class' => NavItem::class,
                'label' => '',
                'url' => '#',
                'active' => null,
                'disabled' => false,
                'visible' => true,
                'encode' => null,
                'options' => [],
                'linkOptions' => [],
                'items' => [],
                'menuOptions' => [],
            ],
            ...$overrides,
        ];
    }
}
