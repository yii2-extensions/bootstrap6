<?php

declare(strict_types=1);

namespace yii2\extensions\bootstrap6\tests;

use PHPUnit\Framework\Attributes\{DataProviderExternal, Group, TestWith};
use yii\base\{InvalidConfigException, Model};
use yii\web\JsExpression;
use yii2\extensions\bootstrap6\asset\BootstrapAsset;
use yii2\extensions\bootstrap6\enum\{ButtonVariant, Size, Theme};
use yii2\extensions\bootstrap6\exception\Message;
use yii2\extensions\bootstrap6\tests\provider\ToggleButtonGroupProvider;
use yii2\extensions\bootstrap6\tests\support\TestCase;
use yii2\extensions\bootstrap6\ToggleButtonGroup;

/**
 * Unit tests for {@see ToggleButtonGroup} checkbox and radio toggle buttons with models and named inputs.
 *
 * {@see ToggleButtonGroupProvider} for test case data providers.
 */
#[Group('widget')]
final class ToggleButtonGroupTest extends TestCase
{
    public function testCheckbox(): void
    {
        $this->mockWebApplication();

        self::assertSameHtml(
            <<<HTML
            <input type="hidden" name="Toggle[value]" value=""><div id="toggle-value" class="btn-group" role="group"><label class="btn-check btn-outline"><input type="checkbox" name="Toggle[value][]" value="1" autocomplete="off">item 1</label>
            <label class="btn-check btn-outline"><input type="checkbox" name="Toggle[value][]" value="2" autocomplete="off">item 2</label></div>
            HTML,
            ToggleButtonGroup::widget(
                ['attribute' => 'value', 'items' => ['1' => 'item 1', '2' => 'item 2'], 'model' => self::model(null)],
            ),
            'Checkbox toggle buttons must nest the input in the label.',
        );
        self::assertArrayHasKey(
            BootstrapAsset::class,
            self::webView()->assetBundles,
            'CSS bundle must be registered.',
        );
    }

    public function testCheckboxChecked(): void
    {
        $this->mockWebApplication();

        $html = ToggleButtonGroup::widget(
            [
                'attribute' => 'value',
                'items' => ['1' => 'item 1', '2' => 'item 2', '3' => 'item 3'],
                'model' => self::model(['2', 3]),
            ],
        );

        self::assertStringContainsString(
            '<input type="checkbox" name="Toggle[value][]" value="1" autocomplete="off">',
            $html,
            'Unselected value must not be checked.',
        );
        self::assertStringContainsString(
            '<input type="checkbox" name="Toggle[value][]" value="2" checked autocomplete="off">',
            $html,
            'Selected `string` value must be checked.',
        );
        self::assertStringContainsString(
            '<input type="checkbox" name="Toggle[value][]" value="3" checked autocomplete="off">',
            $html,
            'Selected `int` value must be checked.',
        );
    }
    #[TestWith([false])]
    #[TestWith([true])]
    public function testExternalFormOwnsUnselectInput(bool $disabled): void
    {
        $this->mockWebApplication();

        $html = ToggleButtonGroup::widget(
            [
                'model' => self::model([]),
                'attribute' => 'value',
                'items' => ['a' => 'Alpha'],
                'inputOptions' => ['form' => 'external', 'disabled' => $disabled],
            ],
        );
        $disabledAttribute = $disabled ? ' disabled' : '';

        self::assertStringContainsString(
            '<input type="hidden" name="Toggle[value]" value="" form="external"' . $disabledAttribute . '>',
            $html,
            'Unselect input must preserve disabled state and external form ownership.',
        );
    }

    public function testNamedInputWithoutUnselect(): void
    {
        $this->mockWebApplication();

        self::assertSameHtml(
            <<<HTML
            <div id="w0" class="btn-group btn-group-lg" role="group"><label class="btn-check btn-text theme-info"><input type="checkbox" name="tags[]" value="a" checked autocomplete="off">&lt;A&gt;</label></div>
            HTML,
            ToggleButtonGroup::widget(
                [
                    'items' => ['a' => '<A>'],
                    'name' => 'tags',
                    'size' => 'lg',
                    'theme' => 'info',
                    'value' => 'a',
                    'variant' => 'btn-text',
                ],
            ),
            'Named input must not render a hidden input and must encode labels.',
        );
    }

    public function testNamedInputWithStylingAndOptions(): void
    {
        $this->mockWebApplication();

        self::assertSameHtml(
            <<<HTML
            <input type="hidden" name="color" value="0" disabled><div id="colors" class="btn-group btn-group-sm" aria-label="Colors" role="radiogroup"><label class="me-1 btn-check btn-solid theme-success"><input type="radio" name="color" value="r" checked disabled autocomplete="on"><b>Red</b></label>
            <label class="me-1 btn-check btn-solid theme-success"><input type="radio" name="color" value="g" disabled autocomplete="on"><i>Green</i></label></div>
            HTML,
            ToggleButtonGroup::widget(
                [
                    'encodeLabels' => false,
                    'inputOptions' => ['autocomplete' => 'on', 'disabled' => true],
                    'items' => ['r' => '<b>Red</b>', 'g' => '<i>Green</i>'],
                    'labelOptions' => ['class' => 'me-1'],
                    'name' => 'color',
                    'options' => ['aria-label' => 'Colors', 'id' => 'colors', 'role' => 'radiogroup', 'unselect' => 0],
                    'size' => Size::SM,
                    'theme' => Theme::SUCCESS,
                    'type' => ToggleButtonGroup::TYPE_RADIO,
                    'value' => new JsExpression('r'),
                    'variant' => ButtonVariant::SOLID,
                ],
            ),
            'Named radios must honor the configured styling and attributes.',
        );
    }

    public function testNameIsUsedWhenAttributeHasNoModel(): void
    {
        $this->mockWebApplication();

        self::assertSameHtml(
            <<<HTML
            <div id="w0" class="btn-group" role="group"><label class="btn-check btn-outline"><input type="radio" name="n" value="1" autocomplete="off">One</label></div>
            HTML,
            ToggleButtonGroup::widget(
                ['attribute' => 'value', 'items' => ['1' => 'One'], 'name' => 'n', 'type' => 'radio'],
            ),
            'Attribute without model must fall back to the name.',
        );
    }

    public function testRadio(): void
    {
        $this->mockWebApplication();

        self::assertSameHtml(
            <<<HTML
            <div id="toggle-value" class="btn-group" role="group"><label class="btn-check btn-outline"><input type="radio" name="Toggle[value]" value="1" autocomplete="off">item 1</label>
            <label class="btn-check btn-outline"><input type="radio" name="Toggle[value]" value="2" checked autocomplete="off">item 2</label></div>
            HTML,
            ToggleButtonGroup::widget(
                [
                    'attribute' => 'value',
                    'items' => ['1' => 'item 1', '2' => 'item 2'],
                    'model' => self::model(2),
                    'options' => ['unselect' => null],
                    'type' => ToggleButtonGroup::TYPE_RADIO,
                ],
            ),
            'Radio toggle buttons must use the attribute name without suffix.',
        );
    }

    public function testRenderItemIsExtensionPoint(): void
    {
        $this->mockWebApplication();

        $group = new class (['items' => ['1' => 'One'], 'name' => 'n']) extends ToggleButtonGroup {
            protected function renderItem(string $label, string $name, bool $checked, string $value): string
            {
                return '[' . parent::renderItem($label, $name, $checked, $value) . ']';
            }
        };

        self::assertSameHtml(
            <<<HTML
            <div id="w0" class="btn-group" role="group">[<label class="btn-check btn-outline"><input type="checkbox" name="n[]" value="1" autocomplete="off">One</label>]</div>
            HTML,
            $group->run(),
            'Overridden helper must decorate the parent output.',
        );
    }

    public function testSelectionIgnoresNonScalarValues(): void
    {
        $this->mockWebApplication();

        self::assertStringNotContainsString(
            'checked',
            ToggleButtonGroup::widget(['items' => ['' => 'Empty'], 'name' => 'n', 'value' => [null, ['x']]]),
            'Non-scalar selections must not match.',
        );
    }

    /**
     * @param array<string, mixed> $config
     */
    #[DataProviderExternal(ToggleButtonGroupProvider::class, 'invalidEnumValueCases')]
    public function testThrowInvalidConfigExceptionForInvalidEnumValue(array $config, string $message): void
    {
        $this->mockWebApplication();

        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage(
            $message,
        );

        new ToggleButtonGroup(['name' => 't', ...$config]);
    }

    public function testThrowInvalidConfigExceptionWhenTypeIsInvalid(): void
    {
        $this->mockWebApplication();

        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage(
            Message::INVALID_VALUE->getMessage('select', 'type', 'checkbox, radio'),
        );

        ToggleButtonGroup::widget(['name' => 'n', 'type' => 'select']);
    }

    public function testUnselectInputIsDisabledWithInputs(): void
    {
        $this->mockWebApplication();

        self::assertSameHtml(
            <<<HTML
            <input type="hidden" name="Toggle[value]" value="" disabled><div id="toggle-value" class="btn-group" role="group"><label class="btn-check btn-outline"><input type="checkbox" name="Toggle[value][]" value="1" disabled autocomplete="off">item 1</label></div>
            HTML,
            ToggleButtonGroup::widget(
                [
                    'attribute' => 'value',
                    'inputOptions' => ['disabled' => true],
                    'items' => ['1' => 'item 1'],
                    'model' => self::model(null),
                ],
            ),
            'Hidden input must carry `disabled` like the inputs.',
        );
        self::assertSameHtml(
            <<<HTML
            <input type="hidden" name="Toggle[value]" value=""><div id="toggle-value" class="btn-group" role="group"><label class="btn-check btn-outline"><input type="checkbox" name="Toggle[value][]" value="1" autocomplete="off">item 1</label></div>
            HTML,
            ToggleButtonGroup::widget(
                [
                    'attribute' => 'value',
                    'inputOptions' => ['disabled' => false],
                    'items' => ['1' => 'item 1'],
                    'model' => self::model(null),
                ],
            ),
            'Hidden input must stay enabled when `disabled` is `false`.',
        );
    }

    private static function model(mixed $value): Model
    {
        $model = new class extends Model {
            public mixed $value = null;

            public function formName(): string
            {
                return 'Toggle';
            }
        };

        $model->value = $value;

        return $model;
    }
}
