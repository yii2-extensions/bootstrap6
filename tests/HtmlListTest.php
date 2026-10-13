<?php

declare(strict_types=1);

namespace yii2\extensions\bootstrap6\tests;

use PHPUnit\Framework\Attributes\{DataProviderExternal, Group};
use yii2\extensions\bootstrap6\Html;
use yii2\extensions\bootstrap6\tests\provider\HtmlListProvider;
use yii2\extensions\bootstrap6\tests\support\TestCase;

/**
 * Unit tests for the {@see Html} checkbox and radio list helpers.
 *
 * {@see HtmlListProvider} for test case data providers.
 */
#[Group('form')]
final class HtmlListTest extends TestCase
{
    /**
     * @param array<mixed>|string|null $selection
     * @param array<mixed> $items
     * @param array<mixed> $options
     */
    #[DataProviderExternal(HtmlListProvider::class, 'checkboxListCases')]
    public function testCheckboxList(
        string $name,
        array|string|null $selection,
        array $items,
        array $options,
        string $expected,
    ): void {
        self::assertSameHtml(
            $expected,
            Html::checkboxList($name, $selection, $items, $options),
            'Checkbox list markup mismatch.',
        );
    }

    public function testCheckboxListItemCallbackReplacesFieldWrapper(): void
    {
        self::assertSameHtml(
            <<<HTML
            <div class="form-group">0<label>text1 <input type="checkbox" name="test[]" value="value1"></label>
            1<label>text2 <input type="checkbox" name="test[]" value="value2" checked></label></div>
            HTML,
            Html::checkboxList(
                'test',
                ['value2'],
                ['value1' => 'text1', 'value2' => 'text2'],
                [
                    'item' => static fn(int $index, string $label, string $name, bool $checked, string $value): string
                        => $index . Html::label(
                            $label . ' ' . Html::input('checkbox', $name, $value, ['checked' => $checked]),
                        ),
                ],
            ),
            'Callback output must be used verbatim.',
        );
    }

    /**
     * @param array<mixed>|string|null $selection
     * @param array<mixed> $items
     * @param array<mixed> $options
     */
    #[DataProviderExternal(HtmlListProvider::class, 'radioListCases')]
    public function testRadioList(
        string $name,
        array|string|null $selection,
        array $items,
        array $options,
        string $expected,
    ): void {
        self::assertSameHtml(
            $expected,
            Html::radioList($name, $selection, $items, $options),
            'Radio list markup mismatch.',
        );
    }

    public function testRadioListItemCallbackReplacesFieldWrapper(): void
    {
        self::assertSameHtml(
            <<<HTML
            <div class="d-flex flex-wrap gap-5">0<label>text1 <input type="radio" name="test" value="value1"></label>
            1<label>text2 <input type="radio" name="test" value="value2" checked></label></div>
            HTML,
            Html::radioList(
                'test',
                ['value2'],
                ['value1' => 'text1', 'value2' => 'text2'],
                [
                    'inline' => true,
                    'item' => static fn(int $index, string $label, string $name, bool $checked, string $value): string
                        => $index . Html::label(
                            $label . ' ' . Html::input('radio', $name, $value, ['checked' => $checked]),
                        ),
                ],
            ),
            'Callback output must be used verbatim.',
        );
    }
}
