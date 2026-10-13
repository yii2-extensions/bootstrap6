<?php

declare(strict_types=1);

namespace yii2\extensions\bootstrap6\tests\item;

use Closure;
use PHPUnit\Framework\Attributes\{DataProviderExternal, Group};
use yii\base\InvalidConfigException;
use yii2\extensions\bootstrap6\enum\Theme;
use yii2\extensions\bootstrap6\item\ProgressBar;
use yii2\extensions\bootstrap6\tests\provider\ProgressBarProvider;
use yii2\extensions\bootstrap6\tests\support\TestCase;

/**
 * Unit tests for the {@see ProgressBar} value object.
 *
 * {@see ProgressBarProvider} for test case data providers.
 */
#[Group('item')]
final class ProgressBarTest extends TestCase
{
    /**
     * @param array<array-key, mixed> $config
     */
    #[DataProviderExternal(ProgressBarProvider::class, 'fromArrayCases')]
    public function testFromArray(array $config, ProgressBar $expected): void
    {
        self::assertSame(
            self::exportItem($expected),
            self::exportItem(ProgressBar::fromArray($config)),
            'Array must map to the equivalent value object.',
        );
    }

    /**
     * @param Closure(): ProgressBar $create
     */
    #[DataProviderExternal(ProgressBarProvider::class, 'invalidCases')]
    public function testThrowInvalidConfigExceptionForInvalidPercentOrTheme(Closure $create, string $message): void
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
                'class' => ProgressBar::class,
                'percent' => 10,
                'label' => '',
                'theme' => null,
                'striped' => false,
                'animated' => false,
                'ariaLabel' => null,
                'options' => [],
            ],
            self::exportItem(ProgressBar::to(10)),
            'Defaults mismatch.',
        );
        self::assertSame(
            [
                'class' => ProgressBar::class,
                'percent' => 10.5,
                'label' => 'L',
                'theme' => Theme::DANGER,
                'striped' => true,
                'animated' => true,
                'ariaLabel' => 'A',
                'options' => ['class' => 'o'],
            ],
            self::exportItem(
                ProgressBar::to(10.5, 'L')
                    ->withTheme(Theme::DANGER)
                    ->withStriped(true)
                    ->withAnimated(true)
                    ->withAriaLabel('A')
                    ->withOptions(['class' => 'o']),
            ),
            'Chained values must be assigned.',
        );
    }

    /**
     * @param Closure(ProgressBar): ProgressBar $with
     */
    #[DataProviderExternal(ProgressBarProvider::class, 'withCases')]
    public function testWith(Closure $with, string $property, mixed $value): void
    {
        $bar = ProgressBar::to(40, 'Label')
            ->withTheme('success')
            ->withStriped(true)
            ->withAnimated(true)
            ->withAriaLabel('Aria')
            ->withOptions(['class' => 'o']);

        self::assertWithCopy($bar, $with, $property, $value);
    }
}
