<?php

declare(strict_types=1);

namespace yii2\extensions\bootstrap6\tests\item;

use Closure;
use PHPUnit\Framework\Attributes\{DataProviderExternal, Group};
use yii\base\InvalidConfigException;
use yii2\extensions\bootstrap6\exception\Message;
use yii2\extensions\bootstrap6\item\BreadcrumbLink;
use yii2\extensions\bootstrap6\tests\provider\BreadcrumbLinkProvider;
use yii2\extensions\bootstrap6\tests\support\TestCase;

/**
 * Unit tests for the {@see BreadcrumbLink} value object.
 *
 * {@see BreadcrumbLinkProvider} for test case data providers.
 */
#[Group('item')]
final class BreadcrumbLinkTest extends TestCase
{
    /**
     * @param array<array-key, mixed> $config
     */
    #[DataProviderExternal(BreadcrumbLinkProvider::class, 'fromArrayCases')]
    public function testFromArray(array $config, BreadcrumbLink $expected): void
    {
        self::assertSame(
            self::exportItem($expected),
            self::exportItem(BreadcrumbLink::fromArray($config)),
            'Array must map to the equivalent value object.',
        );
    }

    /**
     * @param array<array-key, mixed> $config
     */
    #[DataProviderExternal(BreadcrumbLinkProvider::class, 'invalidLabelCases')]
    public function testThrowInvalidConfigExceptionForMissingLabel(array $config): void
    {
        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage(
            Message::ITEM_LABEL_REQUIRED->getMessage(),
        );

        BreadcrumbLink::fromArray($config);
    }

    public function testTo(): void
    {
        self::assertSame(
            [
                'class' => BreadcrumbLink::class,
                'label' => 'A',
                'url' => null,
                'active' => null,
                'encode' => null,
                'template' => null,
                'options' => [],
            ],
            self::exportItem(BreadcrumbLink::to('A')),
            'Defaults mismatch.',
        );
        self::assertSame(
            [
                'class' => BreadcrumbLink::class,
                'label' => 'A',
                'url' => ['site/index'],
                'active' => false,
                'encode' => true,
                'template' => '<li>{link}</li>',
                'options' => ['class' => 'c'],
            ],
            self::exportItem(
                BreadcrumbLink::to('A', ['site/index'])
                    ->withActive(false)
                    ->withEncode(true)
                    ->withTemplate('<li>{link}</li>')
                    ->withOptions(['class' => 'c']),
            ),
            'Chained values must be assigned.',
        );
    }

    /**
     * @param Closure(BreadcrumbLink): BreadcrumbLink $with
     */
    #[DataProviderExternal(BreadcrumbLinkProvider::class, 'withCases')]
    public function testWith(Closure $with, string $property, mixed $value): void
    {
        $link = BreadcrumbLink::to('Label', '/u')
            ->withActive(true)
            ->withEncode(false)
            ->withTemplate('<li>{link}</li>')
            ->withOptions(['class' => 'c']);

        self::assertWithCopy($link, $with, $property, $value);
    }
}
