<?php

declare(strict_types=1);

namespace yii2\extensions\bootstrap6\tests;

use PHPUnit\Framework\Attributes\Group;
use Yii;
use yii\base\{DynamicModel, Model, UnknownPropertyException};
use yii\web\JqueryAsset;
use yii2\extensions\bootstrap6\{ActiveField, ActiveForm};
use yii2\extensions\bootstrap6\asset\BootstrapPluginAsset;
use yii2\extensions\bootstrap6\tests\support\stub\{RecordingActiveField, StubInputWidget};
use yii2\extensions\bootstrap6\tests\support\TestCase;

use function array_unique;
use function ob_end_clean;
use function ob_start;
use function sort;

/**
 * Unit tests for {@see ActiveField} controls, checkboxes, radios, lists, labels, and layout defaults.
 */
#[Group('form')]
final class ActiveFieldTest extends TestCase
{
    public function testCheckbox(): void
    {
        self::assertSameHtml(
            <<<HTML
            <div class="form-field mb-3 field-dynamicmodel-attributename">
            <input type="hidden" name="DynamicModel[attributeName]" value="0"><input type="checkbox" id="dynamicmodel-attributename" class="check" name="DynamicModel[attributeName]" value="1">
            <label for="dynamicmodel-attributename">Attribute Name</label>

            <div class="invalid-feedback"></div>
            </div>
            HTML,
            self::field()->checkbox()->render(),
            'Checkbox must be a direct child of the `.form-field` grid.',
        );
    }

    public function testCheckboxEnclosedByLabel(): void
    {
        self::assertSameHtml(
            <<<HTML
            <div class="form-field mb-3 field-dynamicmodel-attributename">
            <label for="dynamicmodel-attributename">
            <input type="hidden" name="DynamicModel[attributeName]" value="0"><input type="checkbox" id="dynamicmodel-attributename" class="check" name="DynamicModel[attributeName]" value="1">
            Attribute Name
            </label>

            <div class="invalid-feedback"></div>
            </div>
            HTML,
            self::field()->checkbox([], true)->render(),
            'Input must be rendered inside the label.',
        );
    }

    public function testCheckboxEnclosedByLabelWithCustomLabel(): void
    {
        self::assertSameHtml(
            <<<HTML
            <div class="form-field mb-3 field-dynamicmodel-attributename">
            <label class="fw-bold" for="dynamicmodel-attributename">
            <input type="hidden" name="DynamicModel[attributeName]" value="0"><input type="checkbox" id="dynamicmodel-attributename" class="check" name="DynamicModel[attributeName]" value="1">
            Custom <b>Label</b>
            </label>

            <div class="invalid-feedback"></div>
            </div>
            HTML,
            self::field()->checkbox(['label' => 'Custom <b>Label</b>', 'labelOptions' => ['class' => 'fw-bold']], true)
                ->render(),
            'Custom label must replace the model label.',
        );
    }

    public function testCheckboxError(): void
    {
        $model = new DynamicModel(['attributeName']);

        $model->addError('attributeName', 'Test print error message');

        self::assertSameHtml(
            <<<HTML
            <div class="form-field mb-3 field-dynamicmodel-attributename">
            <input type="hidden" name="DynamicModel[attributeName]" value="0"><input type="checkbox" id="dynamicmodel-attributename" class="check is-invalid" name="DynamicModel[attributeName]" value="1" aria-invalid="true">
            <label for="dynamicmodel-attributename">Attribute Name</label>

            <div class="invalid-feedback">Test print error message</div>
            </div>
            HTML,
            self::field([], ActiveForm::LAYOUT_DEFAULT, $model)->checkbox()->render(),
            'Control must carry the error class.',
        );
    }

    public function testCheckboxErrorOnContainer(): void
    {
        $model = new DynamicModel(['attributeName']);

        $model->addError('attributeName', 'Error.');

        $field = self::field([], ActiveForm::LAYOUT_DEFAULT, $model);

        $field->form->validationStateOn = ActiveForm::VALIDATION_STATE_ON_CONTAINER;

        self::assertSameHtml(
            <<<HTML
            <div class="form-field mb-3 field-dynamicmodel-attributename is-invalid">
            <input type="hidden" name="DynamicModel[attributeName]" value="0"><input type="checkbox" id="dynamicmodel-attributename" class="check" name="DynamicModel[attributeName]" value="1" aria-invalid="true">
            <label for="dynamicmodel-attributename">Attribute Name</label>

            <div class="invalid-feedback">Error.</div>
            </div>
            HTML,
            $field->checkbox()->render(),
            'Container must carry the error class instead of the control.',
        );
    }

    public function testCheckboxInHorizontalLayoutMergesWrapperOptions(): void
    {
        self::assertSameHtml(
            <<<HTML
            <div class="row mb-3 field-dynamicmodel-attributename">
            <div class="mt-2 sm:col-10 sm:offset-2">
            <div class="form-field">
            <input type="hidden" name="DynamicModel[attributeName]" value="0"><input type="checkbox" id="dynamicmodel-attributename" class="check" name="DynamicModel[attributeName]" value="1">
            <label class="fw-bold" for="dynamicmodel-attributename">Attribute Name</label>

            <div class="invalid-feedback"></div>
            </div>
            </div>
            </div>
            HTML,
            self::field([], ActiveForm::LAYOUT_HORIZONTAL)
                ->checkbox(['labelOptions' => ['class' => 'fw-bold'], 'wrapperOptions' => ['class' => 'mt-2']], true)
                ->render(),
            'Grid label classes must be removed and the offset added.',
        );
    }

    public function testCheckboxInInlineLayout(): void
    {
        self::assertSameHtml(
            <<<HTML
            <div class="col-12 field-dynamicmodel-attributename">
            <div class="form-field">
            <input type="hidden" name="DynamicModel[attributeName]" value="0"><input type="checkbox" id="dynamicmodel-attributename" class="check" name="DynamicModel[attributeName]" value="1">
            <label for="dynamicmodel-attributename">Attribute Name</label>


            </div>
            </div>
            HTML,
            self::field([], ActiveForm::LAYOUT_INLINE)->checkbox()->render(),
            'Label must be visible and wrapped in `.form-field`.',
        );
    }

    public function testCheckboxKeepsVisuallyHiddenLabelOutsideInlineLayout(): void
    {
        self::assertStringContainsString(
            '<label class="visually-hidden" for="dynamicmodel-attributename">Attribute Name</label>',
            self::field()->checkbox(['labelOptions' => ['class' => 'visually-hidden']])->render(),
            'Configured hidden label must be kept.',
        );
    }

    public function testCheckboxList(): void
    {
        self::assertSameHtml(
            <<<HTML
            <div class="form-field mb-3 field-dynamicmodel-attributename">
            <label class="form-label">Attribute Name</label>
            <input type="hidden" name="DynamicModel[attributeName]" value=""><div id="dynamicmodel-attributename" class="form-group"><div class="form-field"><input type="checkbox" id="i0" class="check" name="DynamicModel[attributeName][]" value="1">
            <label for="i0">name1</label></div>
            <div class="form-field"><input type="checkbox" id="i1" class="check" name="DynamicModel[attributeName][]" value="2">
            <label for="i1">name2</label></div></div>

            <div class="invalid-feedback"></div>
            </div>
            HTML,
            self::field()->checkboxList([1 => 'name1', 2 => 'name2'])->render(),
            'Each item must be a `.form-field` and the label must not target a control.',
        );
    }

    public function testCheckboxListCustomIdNamesContainer(): void
    {
        self::assertStringStartsWith(
            "<div class=\"form-field mb-3 field-custom\">\n<label class=\"form-label\">Attribute Name</label>",
            self::field()->checkboxList([1 => 'name1'], ['id' => 'custom'])->render(),
            'Container class must use the configured `id`.',
        );
    }

    public function testCheckboxListError(): void
    {
        $model = new DynamicModel(['attributeName']);

        $model->addError('attributeName', 'Test print error message');

        self::assertSameHtml(
            <<<HTML
            <div class="form-field mb-3 field-dynamicmodel-attributename">
            <label class="form-label">Attribute Name</label>
            <input type="hidden" name="DynamicModel[attributeName]" value=""><div id="dynamicmodel-attributename" class="is-invalid form-group" aria-invalid="true"><div class="form-field"><input type="checkbox" id="i0" class="check" name="DynamicModel[attributeName][]" value="1">
            <label for="i0">name1</label></div></div>

            <div class="invalid-feedback">Test print error message</div>
            </div>
            HTML,
            self::field([], ActiveForm::LAYOUT_DEFAULT, $model)->checkboxList([1 => 'name1'])->render(),
            'List container must carry the error class before the feedback.',
        );
    }

    public function testCheckboxListInline(): void
    {
        self::assertStringContainsString(
            '<div id="dynamicmodel-attributename" class="d-flex flex-wrap gap-5">',
            self::field()->inline()->checkboxList([1 => 'name1', 2 => 'name2'])->render(),
            'Inline container classes must be applied.',
        );
        self::assertStringContainsString(
            '<div id="dynamicmodel-attributename" class="form-group">',
            self::field(['inline' => true])->checkboxList([1 => 'name1'], ['inline' => false])->render(),
            'Per-call option must override the property.',
        );
        self::assertStringContainsString(
            '<div id="dynamicmodel-attributename" class="form-group">',
            self::field(['inline' => true])->inline(false)->checkboxList([1 => 'name1'])->render(),
            'Disabled inline mode must stack the items.',
        );
    }

    public function testCheckboxListItemOptions(): void
    {
        self::assertSameHtml(
            <<<HTML
            <div class="form-field mb-3 field-dynamicmodel-attributename">
            <label class="form-label">Attribute Name</label>
            <input type="hidden" name="DynamicModel[attributeName]" value=""><div id="dynamicmodel-attributename" class="form-group"><div class="form-field form-field-card"><input type="checkbox" id="i0" class="check check-lg" name="DynamicModel[attributeName][]" value="1" data-attribute="test">
            <label for="i0">name1</label></div></div>

            <div class="invalid-feedback"></div>
            </div>
            HTML,
            self::field(['checkOptions' => ['class' => 'check-lg', 'wrapperOptions' => ['class' => 'form-field-card']]])
                ->checkboxList([1 => 'name1'], ['itemOptions' => ['data-attribute' => 'test']])
                ->render(),
            'Item options must be merged over the check options.',
        );
    }

    public function testCheckboxSuppressesLabelWhenOptionIsFalse(): void
    {
        self::assertSameHtml(
            <<<HTML
            <div class="form-field mb-3 field-dynamicmodel-attributename">
            <input type="hidden" name="DynamicModel[attributeName]" value="0"><input type="checkbox" id="dynamicmodel-attributename" class="check" name="DynamicModel[attributeName]" value="1">


            <div class="invalid-feedback"></div>
            </div>
            HTML,
            self::field()->checkbox(['label' => false])->render(),
            'Label must be omitted.',
        );
    }

    public function testCheckboxSwitch(): void
    {
        self::assertSameHtml(
            <<<HTML
            <div class="form-field mb-3 field-dynamicmodel-attributename">
            <input type="hidden" name="DynamicModel[attributeName]" value="0"><div class="switch theme-success"><input type="checkbox" id="dynamicmodel-attributename" name="DynamicModel[attributeName]" value="1" role="switch" switch></div>
            <label for="dynamicmodel-attributename">Attribute Name</label>

            <div class="invalid-feedback"></div>
            </div>
            HTML,
            self::field()->checkbox(['switch' => true, 'switchOptions' => ['class' => 'theme-success']])->render(),
            'Switch must render the `.switch` wrapper.',
        );
    }

    public function testCheckboxWithCustomIdAndLabel(): void
    {
        self::assertSameHtml(
            <<<HTML
            <div class="form-field mb-3 field-custom">
            <input type="hidden" name="DynamicModel[attributeName]" value="0"><input type="checkbox" id="custom" class="check" name="DynamicModel[attributeName]" value="1">
            <label class="fw-bold" for="custom">Custom <b>Label</b></label>

            <div class="invalid-feedback"></div>
            </div>
            HTML,
            self::field()
                ->checkbox(['id' => 'custom', 'label' => 'Custom <b>Label</b>', 'labelOptions' => ['class' => 'fw-bold']])
                ->render(),
            'Label must target the custom `id` and keep its content raw.',
        );
    }

    public function testCheckOptionsAndTemplatesFromContainer(): void
    {
        Yii::$container->set(
            ActiveField::class,
            [
                'checkOptions' => ['class' => 'check-sm'],
                'checkTemplate' => "<div class=\"form-field\">\n{input}\n{label}\n</div>",
                'radioOptions' => ['class' => 'radio-sm'],
            ],
        );

        self::assertSameHtml(
            <<<HTML
            <div class="form-field mb-3 field-dynamicmodel-attributename">
            <div class="form-field">
            <input type="hidden" name="DynamicModel[attributeName]" value="0"><input type="checkbox" id="dynamicmodel-attributename" class="check check-sm" name="DynamicModel[attributeName]" value="1">
            <label for="dynamicmodel-attributename">Attribute Name</label>
            </div>
            </div>
            HTML,
            self::field()->checkbox()->render(),
            'Container definitions must configure checkboxes.',
        );
        self::assertSameHtml(
            <<<HTML
            <div class="form-field mb-3 field-dynamicmodel-attributename">
            <div class="form-field">
            <input type="hidden" name="DynamicModel[attributeName]" value="0"><input type="radio" id="dynamicmodel-attributename" class="radio radio-sm" name="DynamicModel[attributeName]" value="1">
            <label for="dynamicmodel-attributename">Attribute Name</label>
            </div>
            </div>
            HTML,
            self::field()->radio()->render(),
            'Container definitions must configure radios.',
        );
        self::assertStringContainsString(
            '<input type="radio" id="i0" class="radio radio-sm" name="DynamicModel[attributeName]" value="1">',
            self::field()->radioList([1 => 'name1'])->render(),
            'Radio options must apply to list items.',
        );
    }

    public function testColorInput(): void
    {
        self::assertSameHtml(
            <<<HTML
            <div class="form-field mb-3 field-dynamicmodel-attributename">
            <label class="form-label" for="dynamicmodel-attributename">Attribute Name</label>
            <input type="color" id="dynamicmodel-attributename" class="form-control form-control-color" name="DynamicModel[attributeName]">

            <div class="invalid-feedback"></div>
            </div>
            HTML,
            self::field()->colorInput()->render(),
            'Color input must carry the color control classes.',
        );
        self::assertStringContainsString(
            'class="w-25 form-control form-control-color"',
            self::field()->colorInput(['class' => 'w-25'])->render(),
            'Configured class must be kept.',
        );
    }

    public function testConstructIgnoresIntegerKeys(): void
    {
        $field = Yii::createObject(['class' => ActiveField::class, 0 => 'ignored', 'inline' => true]);

        self::assertTrue($field->inline, 'String keys must be applied.');
    }

    public function testConstructWithoutBootstrapFormUsesDefaultLayout(): void
    {
        $field = new ActiveField(['form' => new \yii\widgets\ActiveForm()]);

        ob_end_clean();

        self::assertSame(
            ['class' => 'form-field mb-3'],
            $field->options,
            'Default field options must be kept.',
        );
        self::assertSame(
            "{label}\n{input}\n{hint}\n{error}",
            $field->template,
            'Default template must be kept.',
        );
        self::assertSame(
            ['class' => 'form-label'],
            $field->labelOptions,
            'Default label options must be kept.',
        );
        self::assertSame(
            ['class' => 'form-text'],
            $field->hintOptions,
            'Default hint options must be kept.',
        );
        self::assertSame(
            ['class' => 'invalid-feedback'],
            $field->errorOptions,
            'Default error options must be kept.',
        );
        self::assertSame(
            ['class' => 'form-control'],
            $field->inputOptions,
            'Default input options must be kept.',
        );
        self::assertSame(
            [],
            $field->horizontalCssClasses,
            'Grid classes must stay empty.',
        );
        self::assertTrue(
            $field->enableError,
            'Errors must be enabled.',
        );
        self::assertTrue(
            $field->enableLabel,
            'Labels must be enabled.',
        );
        self::assertNull(
            $field->inputTemplate,
            'Input template must be unset.',
        );
        self::assertFalse(
            $field->inline,
            'Lists must be stacked.',
        );
    }

    public function testCoreFormRendersDefaultLayout(): void
    {
        ob_start();

        $form = \yii\widgets\ActiveForm::begin(['action' => '/something', 'enableClientScript' => false]);

        \yii\widgets\ActiveForm::end();
        ob_end_clean();

        $field = new ActiveField(
            ['attribute' => 'attributeName', 'form' => $form, 'model' => new DynamicModel(['attributeName'])],
        );

        self::assertStringContainsString(
            "<input type=\"hidden\" name=\"DynamicModel[attributeName]\" value=\"0\"><input type=\"checkbox\" id=\"dynamicmodel-attributename\" class=\"check\" name=\"DynamicModel[attributeName]\" value=\"1\">\n<label for=\"dynamicmodel-attributename\">Attribute Name</label>",
            $field->label(false)->label(true)->checkbox()->render(),
            'Core form must use the default templates.',
        );
    }

    public function testDropDownList(): void
    {
        self::assertSameHtml(
            <<<HTML
            <div class="form-field mb-3 field-dynamicmodel-attributename">
            <label class="form-label" for="dynamicmodel-attributename">Attribute Name</label>
            <select id="dynamicmodel-attributename" class="form-control" name="DynamicModel[attributeName]">
            <option value="1">One</option>
            </select>

            <div class="invalid-feedback"></div>
            </div>
            HTML,
            self::field()->dropDownList(['1' => 'One'])->render(),
            'Select must use `form-control`.',
        );
        self::assertStringContainsString(
            '<select id="dynamicmodel-attributename" class="form-control form-control-lg" name="DynamicModel[attributeName]" placeholder="Pick">',
            self::field()->dropDownList(['1' => 'One'], ['class' => 'form-control-lg', 'placeholder' => 'Pick'])->render(),
            'Size class must keep `form-control` and an explicit placeholder must be kept.',
        );
        self::assertStringContainsString(
            '<select id="dynamicmodel-attributename" class="form-control" name="DynamicModel[attributeName]">',
            self::field([], ActiveForm::LAYOUT_INLINE)->dropDownList(['1' => 'One'])->render(),
            'Inherited placeholder must be dropped.',
        );
    }

    public function testEnableErrorFalse(): void
    {
        self::assertSameHtml(
            <<<HTML
            <div class="form-field mb-3 field-dynamicmodel-attributename">
            <label class="form-label" for="dynamicmodel-attributename">Attribute Name</label>
            <input type="text" id="dynamicmodel-attributename" class="form-control" name="DynamicModel[attributeName]">


            </div>
            HTML,
            self::field(['enableError' => false])->render(),
            'Error must be omitted.',
        );
    }

    public function testEnableLabelFalseClearsLabelParts(): void
    {
        self::assertSameHtml(
            <<<HTML
            <div class="form-field mb-3 field-dynamicmodel-attributename">

            <input type="hidden" name="DynamicModel[attributeName]" value="0"><input type="checkbox" id="dynamicmodel-attributename" class="check" name="DynamicModel[attributeName]" value="1">



            <div class="invalid-feedback"></div>
            </div>
            HTML,
            self::field()->checkbox([], true)->label(false)->render(),
            'Every label part must be blank.',
        );
        self::assertSameHtml(
            <<<HTML
            <div class="form-field mb-3 field-dynamicmodel-attributename">

            <input type="text" id="dynamicmodel-attributename" class="form-control" name="DynamicModel[attributeName]">

            <div class="invalid-feedback"></div>
            </div>
            HTML,
            self::field(['enableLabel' => false])->render(),
            'Label must be omitted.',
        );
    }

    public function testError(): void
    {
        $model = new DynamicModel(['attributeName']);

        $model->addError('attributeName', 'Error.');

        self::assertStringContainsString(
            "\n<p class=\"invalid-feedback\">Error.</p>\n",
            self::field([], ActiveForm::LAYOUT_DEFAULT, $model)->error(['tag' => 'p'])->render(),
            'Options must be merged over the error options.',
        );
        self::assertStringNotContainsString(
            'invalid-feedback',
            self::field([], ActiveForm::LAYOUT_DEFAULT, $model)->error(false)->render(),
            '`false` must omit the error.',
        );
    }

    public function testFileInput(): void
    {
        self::assertSameHtml(
            <<<HTML
            <div class="form-field mb-3 field-dynamicmodel-attributename">
            <label class="form-label" for="dynamicmodel-attributename">Attribute Name</label>
            <input type="hidden" name="DynamicModel[attributeName]" value=""><input type="file" id="dynamicmodel-attributename" class="form-control" name="DynamicModel[attributeName]">

            <div class="invalid-feedback"></div>
            </div>
            HTML,
            self::field()->fileInput()->render(),
            'File input must use `form-control`.',
        );
        self::assertStringContainsString(
            'class="form-control form-control-sm"',
            self::field()->fileInput(['class' => 'form-control-sm'])->render(),
            'Size class must keep `form-control`.',
        );
    }

    public function testHint(): void
    {
        self::assertStringContainsString(
            "\n<small class=\"form-text\">Hint.</small>\n",
            self::field()->hint('Hint.', ['tag' => 'small'])->render(),
            'Hint must use the hint options.',
        );
        self::assertSameHtml(
            <<<HTML
            <div class="row mb-3 field-dynamicmodel-attributename">
            <label class="sm:col-2 col-form-label" for="dynamicmodel-attributename">Attribute Name</label>
            <div class="sm:col-10">
            <input type="text" id="dynamicmodel-attributename" class="form-control" name="DynamicModel[attributeName]">
            <div class="form-text">Hint.</div>
            <div class="invalid-feedback"></div>
            </div>
            </div>
            HTML,
            self::field(['horizontalCssClasses' => ['error' => 5]], ActiveForm::LAYOUT_HORIZONTAL)->hint('Hint.')->render(),
            'Empty or invalid grid classes must not alter the hint and error.',
        );
    }

    public function testInput(): void
    {
        self::assertStringContainsString(
            '<input type="email" id="dynamicmodel-attributename" class="form-control" name="DynamicModel[attributeName]">',
            self::field()->input('email')->render(),
            'Input type must be rendered.',
        );
    }

    public function testInputTemplateWrapsRenderedControl(): void
    {
        self::assertStringContainsString(
            <<<HTML
            <div class="input-group"><select id="dynamicmodel-attributename" class="form-control" name="DynamicModel[attributeName]">
            <option value="1">One</option>
            </select></div>
            HTML,
            self::field(['inputTemplate' => '<div class="input-group">{input}</div>'])
                ->dropDownList(['1' => 'One'])
                ->render(),
            'Rendered control must be wrapped.',
        );
    }

    public function testLabel(): void
    {
        self::assertStringContainsString(
            '<label class="form-label fw-bold" for="dynamicmodel-attributename">Custom</label>',
            self::field()->label('Custom', ['class' => ['form-label', 'fw-bold']])->render(),
            'Custom label must be rendered.',
        );
        self::assertStringContainsString(
            '<label class="form-label" for="dynamicmodel-attributename">Attribute Name</label>',
            self::field()->label(null, null)->render(),
            '`null` options must be accepted.',
        );
        self::assertStringContainsString(
            '<label class="form-label" for="dynamicmodel-attributename">Attribute Name</label>',
            self::field(['enableLabel' => false])->label(true)->render(),
            '`true` must enable the label.',
        );
        self::assertSameHtml(
            <<<HTML
            <div class="form-field mb-3 field-dynamicmodel-attributename">

            <input type="text" id="dynamicmodel-attributename" class="form-control" name="DynamicModel[attributeName]">

            <div class="invalid-feedback"></div>
            </div>
            HTML,
            self::field(['wrapperOptions' => ['class' => 'w']])->label(false)->render(),
            'Disabled label outside the horizontal layout must not add the offset.',
        );
    }

    public function testLabelPartsRenderedWhenLabelPartIsPreset(): void
    {
        $field = self::field();

        $field->parts['{label}'] = '';
        $field->template = '{beginLabel}{labelTitle}{endLabel}';

        self::assertSame(
            <<<HTML
            <div class="form-field mb-3 field-dynamicmodel-attributename">
            <label class="form-label" for="dynamicmodel-attributename">Attribute Name</label>
            </div>
            HTML,
            $field->render(),
            'Label parts must be rendered.',
        );

        $field = self::field();

        $field->parts['{labelTitle}'] = 'Title';
        $field->template = '{beginLabel}{labelTitle}{endLabel}';

        self::assertSame(
            <<<HTML
            <div class="form-field mb-3 field-dynamicmodel-attributename">
            <label class="form-label" for="dynamicmodel-attributename">Title</label>
            </div>
            HTML,
            $field->render(),
            'Preset title must be kept.',
        );
    }

    public function testLabelPartsUseOptionLabelAndConfiguredTarget(): void
    {
        self::assertSameHtml(
            <<<HTML
            <div class="form-field mb-3 field-dynamicmodel-attributename">
            <label for="target">
            <input type="hidden" name="DynamicModel[attributeName]" value="0"><input type="checkbox" id="dynamicmodel-attributename" class="check" name="DynamicModel[attributeName]" value="1">
            From option
            </label>

            <div class="invalid-feedback"></div>
            </div>
            HTML,
            self::field(['labelOptions' => ['for' => 'target', 'label' => 'From option']])->checkbox([], true)->render(),
            'Option label and target must be used.',
        );
        self::assertStringContainsString(
            "<label for=\"dynamicmodel-attributename\">\n",
            self::field(['labelOptions' => ['label' => 5]])->checkbox([], true)->render(),
            'Non-string option label must fall back to the model label.',
        );
    }

    public function testLabelTrueInHorizontalLayoutKeepsWrapper(): void
    {
        self::assertStringContainsString(
            <<<HTML
            <label class="sm:col-2 col-form-label" for="dynamicmodel-attributename">Attribute Name</label>
            <div class="sm:col-10">
            HTML,
            self::field([], ActiveForm::LAYOUT_HORIZONTAL)->label(true)->render(),
            'Enabled label must not add the offset.',
        );
    }

    public function testListBox(): void
    {
        self::assertSameHtml(
            <<<HTML
            <div class="form-field mb-3 field-dynamicmodel-attributename">
            <div class="form-floating">
            <label for="dynamicmodel-attributename">Attribute Name</label>
            <input type="hidden" name="DynamicModel[attributeName]" value=""><select id="dynamicmodel-attributename" class="form-control" name="DynamicModel[attributeName]" size="4">
            <option value="1">One</option>
            </select>
            </div>

            <div class="invalid-feedback"></div>
            </div>
            HTML,
            self::field([], ActiveForm::LAYOUT_FLOATING)->listBox(['1' => 'One'])->render(),
            'Listbox must use `form-control` without the inherited placeholder.',
        );
        self::assertStringContainsString(
            'class="form-control form-control-sm"',
            self::field()->listBox(['1' => 'One'], ['class' => 'form-control-sm'])->render(),
            'Size class must keep `form-control`.',
        );
    }

    public function testPasswordInput(): void
    {
        self::assertStringContainsString(
            '<input type="password" id="dynamicmodel-attributename" class="form-control" name="DynamicModel[attributeName]" autocomplete="off">',
            self::field()->passwordInput(['autocomplete' => 'off'])->render(),
            'Password input must be rendered.',
        );
    }

    public function testPresetPartsAreKept(): void
    {
        $field = self::field([], ActiveForm::LAYOUT_HORIZONTAL);

        $field->parts['{beginLabel}'] = '<b>';
        $field->parts['{beginWrapper}'] = '<section>';
        $field->parts['{endLabel}'] = '</b>';
        $field->parts['{endWrapper}'] = '</section>';
        $field->parts['{label}'] = '';
        $field->parts['{labelTitle}'] = 'Title';
        $field->template = '{beginWrapper}{beginLabel}{labelTitle}{endLabel}{endWrapper}';

        self::assertSame(
            "<div class=\"row mb-3 field-dynamicmodel-attributename\">\n<section><b>Title</b></section>\n</div>",
            $field->render(),
            'Preset parts must not be rebuilt.',
        );
    }

    public function testProtectedExtensionPointsAreOverridable(): void
    {
        $field = self::field(['class' => RecordingActiveField::class], ActiveForm::LAYOUT_HORIZONTAL);

        self::assertInstanceOf(RecordingActiveField::class, $field, 'Configured class must be created.');

        $field->checkbox()->label(false)->render();
        $field->label('Custom')->radioList([1 => 'name1'])->render();

        ob_start();
        $form = ActiveForm::begin(['action' => '/something', 'layout' => ActiveForm::LAYOUT_FLOATING]);
        ActiveForm::end();
        ob_end_clean();

        $floating = $form->field(
            new DynamicModel(['attributeName']),
            'attributeName',
            ['class' => RecordingActiveField::class],
        );

        self::assertInstanceOf(
            RecordingActiveField::class,
            $floating,
            'Configured class must be created.',
        );

        $floating->render();

        $calls = array_unique([...$field->calls, ...$floating->calls]);

        sort($calls);

        self::assertSame(
            [
                'createLayoutConfig',
                'horizontalCssClass',
                'layout',
                'registerFloatingFeedbackStyle',
                'renderBooleanInput',
                'renderLabelParts',
                'renderListInput',
                'renderWrapperParts',
            ],
            $calls,
            'Every extension point must be reached through the subclass.',
        );
    }

    public function testRadio(): void
    {
        self::assertSameHtml(
            <<<HTML
            <div class="form-field mb-3 field-dynamicmodel-attributename">
            <input type="hidden" name="DynamicModel[attributeName]" value="0"><input type="radio" id="dynamicmodel-attributename" class="radio" name="DynamicModel[attributeName]" value="1">
            <label for="dynamicmodel-attributename">Attribute Name</label>

            <div class="invalid-feedback"></div>
            </div>
            HTML,
            self::field()->radio()->render(),
            'Radio must be a direct child of the `.form-field` grid.',
        );
        self::assertStringNotContainsString(
            '<label',
            self::field()->radio(['label' => false])->render(),
            'Label must be omitted.',
        );
        self::assertStringContainsString(
            "<label for=\"dynamicmodel-attributename\">\n<input type=\"hidden\" name=\"DynamicModel[attributeName]\" value=\"0\"><input type=\"radio\" id=\"dynamicmodel-attributename\" class=\"radio\" name=\"DynamicModel[attributeName]\" value=\"1\">\nCustom Label\n</label>",
            self::field()->radio(['label' => 'Custom Label'], true)->render(),
            'Enclosing label must use the custom label.',
        );
    }

    public function testRadioError(): void
    {
        $model = new DynamicModel(['attributeName']);

        $model->addError('attributeName', 'Test print error message');

        self::assertSameHtml(
            <<<HTML
            <div class="form-field mb-3 field-dynamicmodel-attributename">
            <input type="hidden" name="DynamicModel[attributeName]" value="0"><input type="radio" id="dynamicmodel-attributename" class="radio is-invalid" name="DynamicModel[attributeName]" value="1" aria-invalid="true">
            <label for="dynamicmodel-attributename">Attribute Name</label>

            <div class="invalid-feedback">Test print error message</div>
            </div>
            HTML,
            self::field([], ActiveForm::LAYOUT_DEFAULT, $model)->radio()->render(),
            'Control must carry the error class.',
        );
    }

    public function testRadioList(): void
    {
        self::assertSameHtml(
            <<<HTML
            <div class="form-field mb-3 field-dynamicmodel-attributename">
            <label class="form-label">Attribute Name</label>
            <input type="hidden" name="DynamicModel[attributeName]" value=""><div id="dynamicmodel-attributename" class="form-group" role="radiogroup"><div class="form-field"><input type="radio" id="i0" class="radio" name="DynamicModel[attributeName]" value="1">
            <label for="i0">name1</label></div>
            <div class="form-field"><input type="radio" id="i1" class="radio" name="DynamicModel[attributeName]" value="2">
            <label for="i1">name2</label></div></div>

            <div class="invalid-feedback"></div>
            </div>
            HTML,
            self::field()->radioList([1 => 'name1', 2 => 'name2'])->render(),
            'Radio group markup mismatch.',
        );
    }

    public function testRadioListError(): void
    {
        $model = new DynamicModel(['attributeName']);

        $model->addError('attributeName', 'Test print error message');

        self::assertSameHtml(
            <<<HTML
            <div class="form-field mb-3 field-dynamicmodel-attributename">
            <label class="form-label">Attribute Name</label>
            <input type="hidden" name="DynamicModel[attributeName]" value=""><div id="dynamicmodel-attributename" class="is-invalid form-group" role="radiogroup" aria-invalid="true"><div class="form-field"><input type="radio" id="i0" class="radio" name="DynamicModel[attributeName]" value="1">
            <label for="i0">name1</label></div></div>

            <div class="invalid-feedback">Test print error message</div>
            </div>
            HTML,
            self::field([], ActiveForm::LAYOUT_DEFAULT, $model)->radioList([1 => 'name1'])->render(),
            'List container must carry the error class before the feedback.',
        );
    }

    public function testRadioListInlineAndItemOptions(): void
    {
        self::assertStringContainsString(
            <<<HTML
            <div id="dynamicmodel-attributename" class="d-flex flex-wrap gap-5" role="list"><div class="form-field"><input type="radio" id="i0" class="radio" name="DynamicModel[attributeName]" value="1" data-attribute="test">
            HTML,
            self::field(['inline' => true])
                ->radioList([1 => 'name1'], ['itemOptions' => ['data-attribute' => 'test'], 'role' => 'list'])
                ->render(),
            'Inline container, configured role, and item options must be applied.',
        );
    }

    public function testRangeInput(): void
    {
        self::assertSameHtml(
            <<<HTML
            <div class="form-field mb-3 field-dynamicmodel-attributename">
            <label class="form-label" for="dynamicmodel-attributename">Attribute Name</label>
            <div class="form-range"><input type="range" id="dynamicmodel-attributename" class="form-range-input" name="DynamicModel[attributeName]"></div>

            <div class="invalid-feedback"></div>
            </div>
            HTML,
            self::field()->rangeInput()->render(),
            'Range input must be wrapped in `.form-range`.',
        );
        self::assertArrayHasKey(
            BootstrapPluginAsset::class,
            self::webView()->assetBundles,
            'Plugin bundle must be registered to initialize the range.',
        );
        self::assertStringContainsString(
            '<div class="form-range theme-success" data-bs-bubble><input type="range" id="dynamicmodel-attributename" class="w-50 form-range-input" name="DynamicModel[attributeName]" max="5" min="0">',
            self::field()
                ->rangeInput(
                    [
                        'class' => 'w-50',
                        'max' => 5,
                        'min' => 0,
                        'rangeOptions' => ['class' => 'theme-success', 'data-bs-bubble' => true],
                    ],
                )
                ->render(),
            'Wrapper and input options must be applied.',
        );
    }

    public function testRenderWithContent(): void
    {
        self::assertSame(
            "<div class=\"form-field mb-3 field-dynamicmodel-attributename\">\ncustom\n</div>",
            self::field()->render('custom'),
            'Given content must be used.',
        );
    }

    public function testStaticControl(): void
    {
        self::assertStringContainsString(
            <<<HTML
            <label class="form-label" for="static">Attribute Name</label>
            <input type="text" id="static" class="form-control-plaintext" value="custom" readonly>
            HTML,
            self::field()->staticControl(['id' => 'static', 'value' => 'custom'])->render(),
            'Configured `id` must be labelled.',
        );
    }

    public function testTextarea(): void
    {
        self::assertStringContainsString(
            '<textarea id="dynamicmodel-attributename" class="form-control" name="DynamicModel[attributeName]" rows="3"></textarea>',
            self::field()->textarea(['rows' => 3])->render(),
            'Textarea must use `form-control`.',
        );
    }

    public function testTextInput(): void
    {
        self::assertStringContainsString(
            '<input type="text" id="dynamicmodel-attributename" class="form-control" name="DynamicModel[attributeName]" maxlength="5">',
            self::field()->textInput(['maxlength' => 5])->render(),
            'Text input must use `form-control`.',
        );
    }

    public function testThrowUnknownPropertyExceptionWhenConfigHasUnknownProperty(): void
    {
        $this->expectException(UnknownPropertyException::class);

        new ActiveField(['unknown' => true]);
    }

    public function testWidget(): void
    {
        self::assertStringContainsString(
            '<input type="range" id="dynamicmodel-attributename" class="form-control" name="DynamicModel[attributeName]">',
            self::field()->widget(StubInputWidget::class)->render(),
            'Input widget must inherit the input options.',
        );
    }

    public function testWrapperTag(): void
    {
        self::assertStringContainsString(
            "<section class=\"form-floating\">\n<label for=\"dynamicmodel-attributename\">",
            self::field(['wrapperOptions' => ['class' => 'form-floating', 'tag' => 'section']], ActiveForm::LAYOUT_FLOATING)
                ->render(),
            'Configured wrapper tag must be used.',
        );
        self::assertStringContainsString(
            "<div class=\"form-floating\">\n<label for=\"dynamicmodel-attributename\">",
            self::field(['wrapperOptions' => ['class' => 'form-floating', 'tag' => 5]], ActiveForm::LAYOUT_FLOATING)
                ->render(),
            'Invalid wrapper tag must fall back to `div`.',
        );
    }

    protected function setUp(): void
    {
        parent::setUp();

        // client script tests assert the inline scripts only; Yii 2.0.x resolves jQuery from `@bower`
        $this->mockWebApplication(['components' => ['assetManager' => ['bundles' => [JqueryAsset::class => false]]]]);
    }

    /**
     * @param array<string, mixed> $config
     */
    private static function field(
        array $config = [],
        string $layout = ActiveForm::LAYOUT_DEFAULT,
        Model|null $model = null,
    ): ActiveField {
        ob_start();

        $form = ActiveForm::begin(['action' => '/something', 'enableClientScript' => false, 'layout' => $layout]);

        ActiveForm::end();
        ob_end_clean();

        return $form->field($model ?? new DynamicModel(['attributeName']), 'attributeName', $config);
    }
}
