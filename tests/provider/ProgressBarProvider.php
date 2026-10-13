<?php

declare(strict_types=1);

namespace yii2\extensions\bootstrap6\tests\provider;

use Closure;
use yii2\extensions\bootstrap6\enum\Theme;
use yii2\extensions\bootstrap6\exception\Message;
use yii2\extensions\bootstrap6\item\ProgressBar;
use yii2\extensions\bootstrap6\tests\support\stub\StringableValue;

/**
 * Data provider for {@see \yii2\extensions\bootstrap6\tests\item\ProgressBarTest} test cases.
 *
 * Provides array configurations with their equivalent value objects, `with*()` mutations, and invalid configurations.
 */
final class ProgressBarProvider
{
    /**
     * @return iterable<string, array{array<array-key, mixed>, ProgressBar}>
     */
    public static function fromArrayCases(): iterable
    {
        $label = new StringableValue('Label');

        yield 'percent only' => [
            ['percent' => 25],
            ProgressBar::to(25),
        ];
        yield 'percent `float`' => [
            ['percent' => 2.5],
            ProgressBar::to(2.5),
        ];
        yield 'Stringable label' => [
            ['label' => $label, 'percent' => 25],
            ProgressBar::to(25, $label),
        ];
        yield 'every key' => [
            [
                'animated' => true,
                'ariaLabel' => 'Upload',
                'label' => '25%',
                'options' => ['class' => 'bar'],
                'percent' => 25,
                'striped' => true,
                'theme' => Theme::SUCCESS,
            ],
            ProgressBar::to(25, '25%')
                ->withTheme(Theme::SUCCESS)
                ->withStriped(true)
                ->withAnimated(true)
                ->withAriaLabel('Upload')
                ->withOptions(['class' => 'bar']),
        ];
        yield 'label non-`string`' => [
            ['label' => 5, 'percent' => 25],
            ProgressBar::to(25),
        ];
        yield 'theme `string`' => [
            ['percent' => 25, 'theme' => 'info'],
            ProgressBar::to(25)->withTheme('info'),
        ];
        yield 'theme non-`string`' => [
            ['percent' => 25, 'theme' => 5],
            ProgressBar::to(25),
        ];
        yield 'striped non-`bool`' => [
            ['percent' => 25, 'striped' => 1],
            ProgressBar::to(25),
        ];
        yield 'animated non-`bool`' => [
            ['animated' => 1, 'percent' => 25],
            ProgressBar::to(25),
        ];
        yield 'ariaLabel non-`string`' => [
            ['ariaLabel' => 5, 'percent' => 25],
            ProgressBar::to(25),
        ];
        yield 'options non-`array`' => [
            ['options' => 'x', 'percent' => 25],
            ProgressBar::to(25),
        ];
        yield 'percent numeric `string`' => [
            ['percent' => '50'],
            ProgressBar::to(50.0),
        ];
        yield 'percent decimal `string`' => [
            ['percent' => '12.5'],
            ProgressBar::to(12.5),
        ];
    }

    /**
     * @return iterable<string, array{Closure(): ProgressBar, string}>
     */
    public static function invalidCases(): iterable
    {
        $percent = Message::OPTION_REQUIRED->getMessage('percent');
        $theme = Message::INVALID_VALUE->getMessage(
            'light',
            'theme',
            'accent, danger, info, inverse, primary, secondary, success, warning',
        );

        yield 'empty' => [
            static fn(): ProgressBar => ProgressBar::fromArray([]),
            $percent,
        ];
        yield 'label without percent' => [
            static fn(): ProgressBar => ProgressBar::fromArray(['label' => 'x']),
            $percent,
        ];
        yield 'percent `null`' => [
            static fn(): ProgressBar => ProgressBar::fromArray(['percent' => null]),
            $percent,
        ];
        yield 'percent non-numeric' => [
            static fn(): ProgressBar => ProgressBar::fromArray(['percent' => 'half']),
            Message::INVALID_VALUE->getMessage('half', 'percent', 'a number'),
        ];
        yield 'percent `array`' => [
            static fn(): ProgressBar => ProgressBar::fromArray(['percent' => [50]]),
            Message::INVALID_VALUE->getMessage('array', 'percent', 'a number'),
        ];
        yield 'percent `bool`' => [
            static fn(): ProgressBar => ProgressBar::fromArray(['percent' => true]),
            Message::INVALID_VALUE->getMessage('1', 'percent', 'a number'),
        ];
        yield 'theme in array' => [
            static fn(): ProgressBar => ProgressBar::fromArray(['percent' => 5, 'theme' => 'light']),
            $theme,
        ];
        yield 'theme in copy' => [
            static fn(): ProgressBar => ProgressBar::to(5)->withTheme('light'),
            $theme,
        ];
    }

    /**
     * @return iterable<string, array{Closure(ProgressBar): ProgressBar, string, mixed}>
     */
    public static function withCases(): iterable
    {
        yield 'animated' => [
            static fn(ProgressBar $bar): ProgressBar => $bar->withAnimated(false),
            'animated',
            false,
        ];
        yield 'ariaLabel' => [
            static fn(ProgressBar $bar): ProgressBar => $bar->withAriaLabel(null),
            'ariaLabel',
            null,
        ];
        yield 'label' => [
            static fn(ProgressBar $bar): ProgressBar => $bar->withLabel('New'),
            'label',
            'New',
        ];
        yield 'options' => [
            static fn(ProgressBar $bar): ProgressBar => $bar->withOptions(['id' => 'o']),
            'options',
            ['id' => 'o'],
        ];
        yield 'percent' => [
            static fn(ProgressBar $bar): ProgressBar => $bar->withPercent(7.5),
            'percent',
            7.5,
        ];
        yield 'striped' => [
            static fn(ProgressBar $bar): ProgressBar => $bar->withStriped(false),
            'striped',
            false,
        ];
        yield 'theme' => [
            static fn(ProgressBar $bar): ProgressBar => $bar->withTheme(null),
            'theme',
            null,
        ];
    }
}
