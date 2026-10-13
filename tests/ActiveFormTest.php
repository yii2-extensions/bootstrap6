<?php

declare(strict_types=1);

namespace yii2\extensions\bootstrap6\tests;

use PHPUnit\Framework\Attributes\{DataProviderExternal, Group};
use yii\base\{DynamicModel, InvalidConfigException};
use yii\web\{JqueryAsset, View};
use yii2\extensions\bootstrap6\{ActiveField, ActiveForm};
use yii2\extensions\bootstrap6\asset\BootstrapAsset;
use yii2\extensions\bootstrap6\exception\Message;
use yii2\extensions\bootstrap6\tests\provider\ActiveFormProvider;
use yii2\extensions\bootstrap6\tests\support\stub\{ExtendedActiveField, User};
use yii2\extensions\bootstrap6\tests\support\TestCase;

use function array_filter;
use function array_keys;
use function ob_end_clean;
use function ob_get_clean;
use function ob_start;
use function str_ends_with;

use const ARRAY_FILTER_USE_KEY;

/**
 * Unit tests for {@see ActiveForm} layouts, validation styling, error summary, and field creation.
 *
 * {@see ActiveFormProvider} for test case data providers.
 */
#[Group('form')]
final class ActiveFormTest extends TestCase
{
    public function testDefaultLayout(): void
    {
        $model = new DynamicModel(['attributeName']);

        self::assertSameHtml(
            <<<HTML
            <form id="w0" action="/some-action" method="post"><div class="form-field mb-3 field-dynamicmodel-attributename">
            <label class="form-label" for="dynamicmodel-attributename">Attribute Name</label>
            <input type="text" id="dynamicmodel-attributename" class="form-control" name="DynamicModel[attributeName]">

            <div class="invalid-feedback"></div>
            </div>
            </form>
            HTML,
            self::renderForm(
                ['layout' => ActiveForm::LAYOUT_DEFAULT],
                static function (ActiveForm $form) use ($model): void {
                    echo $form->field($model, 'attributeName'), "\n";
                },
            ),
            'Default layout markup mismatch.',
        );
    }

    public function testDefaultLayoutOmitsFloatingFeedbackStyle(): void
    {
        $model = new DynamicModel(['attributeName']);

        self::renderForm(
            [],
            static function (ActiveForm $form) use ($model): void {
                echo $form->field($model, 'attributeName');
            },
        );
        self::assertSame(
            [],
            self::floatingFeedbackStyles(self::webView()),
            'Only floating fields need the feedback style rule.',
        );
    }

    public function testErrorSummaryUsesDangerAlert(): void
    {
        $model = new User();

        $model->validate();

        self::assertSameHtml(
            <<<HTML
            <form id="w0" action="/some-action" method="post"><div class="alert theme-danger"><p>Please fix the following errors:</p><ul><li>Username cannot be blank.</li>
            <li>Password cannot be blank.</li></ul></div></form>
            HTML,
            self::renderForm(
                [],
                static function (ActiveForm $form) use ($model): void {
                    echo $form->errorSummary($model);
                },
            ),
            'Summary must use the danger alert.',
        );
    }

    public function testFieldUsesConfiguredBootstrapFieldClass(): void
    {
        $form = self::createForm(['fieldClass' => ExtendedActiveField::class]);

        self::assertInstanceOf(
            ExtendedActiveField::class,
            $form->field(new DynamicModel(['a']), 'a'),
            'Configured field class must be created.',
        );
        self::assertSame(
            ActiveField::class,
            self::createForm([])->fieldClass,
            'Default field class must be the Bootstrap field.',
        );
    }

    public function testFloatingLayout(): void
    {
        $model = new DynamicModel(['attributeName', 'selectName', 'checkboxName', 'comment']);

        self::assertSameHtml(
            <<<HTML
            <form id="w0" action="/some-action" method="post"><div class="form-field mb-3 field-dynamicmodel-attributename">
            <div class="form-floating">
            <label for="dynamicmodel-attributename">Attribute Name</label>
            <input type="text" id="dynamicmodel-attributename" class="form-control" name="DynamicModel[attributeName]" placeholder="Attribute Name">
            </div>

            <div class="invalid-feedback"></div>
            </div>
            <div class="form-field mb-3 field-dynamicmodel-selectname">
            <div class="form-floating">
            <label for="dynamicmodel-selectname">Select Name</label>
            <select id="dynamicmodel-selectname" class="form-control" name="DynamicModel[selectName]">
            <option value="1">One</option>
            <option value="2">Two</option>
            </select>
            </div>

            <div class="invalid-feedback"></div>
            </div>
            <div class="form-field mb-3 field-dynamicmodel-checkboxname">
            <input type="hidden" name="DynamicModel[checkboxName]" value="0"><input type="checkbox" id="dynamicmodel-checkboxname" class="check" name="DynamicModel[checkboxName]" value="1">
            <label for="dynamicmodel-checkboxname">Checkbox Name</label>

            <div class="invalid-feedback"></div>
            </div>
            <div class="form-field mb-3 field-dynamicmodel-comment">
            <div class="form-floating">
            <label for="dynamicmodel-comment">Comment</label>
            <textarea id="dynamicmodel-comment" class="form-control" name="DynamicModel[comment]" placeholder="Comment"></textarea>
            </div>
            <div class="form-text">Leave a comment.</div>
            <div class="invalid-feedback"></div>
            </div>
            </form>
            HTML,
            self::renderForm(
                ['layout' => ActiveForm::LAYOUT_FLOATING],
                static function (ActiveForm $form) use ($model): void {
                    echo $form->field($model, 'attributeName'), "\n";
                    echo $form->field($model, 'selectName')->dropDownList(['1' => 'One', '2' => 'Two']), "\n";
                    echo $form->field($model, 'checkboxName')->checkbox(), "\n";
                    echo $form->field($model, 'comment')->textarea()->hint('Leave a comment.'), "\n";
                },
            ),
            'Floating layout must render the label before the control.',
        );
    }

    public function testFloatingLayoutEscapesSelectorIdentifiers(): void
    {
        $model = new DynamicModel(['attributeName']);

        self::renderForm(
            [
                'errorCssClass' => 'is-invalid has:error',
                'layout' => ActiveForm::LAYOUT_FLOATING,
                'options' => ['id' => 'account:edit'],
            ],
            static function (ActiveForm $form) use ($model): void {
                echo $form->field($model, 'attributeName');
            },
        );
        self::assertSame(
            [
                'account:edit-floating-feedback' => <<<HTML
                <style>#account\\:edit :is(.form-floating:has(.is-invalid.has\\:error), .is-invalid.has\\:error > .form-floating) ~ :is(.invalid-feedback, .invalid-tooltip) { display: block; }</style>
                HTML,
            ],
            self::floatingFeedbackStyles(self::webView()),
            'Selector must escape the `id` and each class; the key keeps the raw `id`.',
        );
    }

    public function testFloatingLayoutMarksWrapperInvalid(): void
    {
        $model = new DynamicModel(['attributeName']);

        $model->addError('attributeName', 'Invalid value.');

        self::assertSameHtml(
            <<<HTML
            <form id="w0" action="/some-action" method="post"><div class="form-field mb-3 field-dynamicmodel-attributename">
            <div class="form-floating is-invalid">
            <label for="dynamicmodel-attributename">Attribute Name</label>
            <input type="text" id="dynamicmodel-attributename" class="form-control is-invalid" name="DynamicModel[attributeName]" placeholder="Attribute Name" aria-invalid="true">
            </div>

            <div class="invalid-feedback">Invalid value.</div>
            </div>
            </form>
            HTML,
            self::renderForm(
                ['layout' => ActiveForm::LAYOUT_FLOATING],
                static function (ActiveForm $form) use ($model): void {
                    echo $form->field($model, 'attributeName'), "\n";
                },
            ),
            'Wrapper must carry the error class so the feedback is displayed.',
        );
    }

    public function testFloatingLayoutMatchesEveryErrorCssClass(): void
    {
        $model = new DynamicModel(['attributeName']);

        self::renderForm(
            [
                'errorCssClass' => 'is-invalid has-error',
                'layout' => ActiveForm::LAYOUT_FLOATING,
                'options' => ['id' => 'login-form'],
            ],
            static function (ActiveForm $form) use ($model): void {
                echo $form->field($model, 'attributeName');
            },
        );
        self::assertSame(
            [
                'login-form-floating-feedback' => <<<HTML
                <style>#login-form :is(.form-floating:has(.is-invalid.has-error), .is-invalid.has-error > .form-floating) ~ :is(.invalid-feedback, .invalid-tooltip) { display: block; }</style>
                HTML,
            ],
            self::floatingFeedbackStyles(self::webView()),
            'Space-separated classes must be matched together.',
        );
    }

    public function testFloatingLayoutOmitsFeedbackStyleWhenErrorCssClassIsEmpty(): void
    {
        $model = new DynamicModel(['attributeName']);

        self::renderForm(
            ['errorCssClass' => " \t ", 'layout' => ActiveForm::LAYOUT_FLOATING],
            static function (ActiveForm $form) use ($model): void {
                echo $form->field($model, 'attributeName');
            },
        );
        self::assertSame(
            [],
            self::floatingFeedbackStyles(self::webView()),
            'Whitespace-only class list must not produce a rule.',
        );
    }

    public function testFloatingLayoutOmitsFeedbackStyleWhenErrorIsDisabled(): void
    {
        $model = new DynamicModel(['attributeName']);

        self::renderForm(
            ['layout' => ActiveForm::LAYOUT_FLOATING],
            static function (ActiveForm $form) use ($model): void {
                echo $form->field($model, 'attributeName', ['enableError' => false]);
            },
        );
        self::assertSame(
            [],
            self::floatingFeedbackStyles(self::webView()),
            'No feedback element means no style rule.',
        );
    }

    public function testFloatingLayoutRegistersFeedbackStyleOncePerForm(): void
    {
        $model = new DynamicModel(['attributeName', 'comment']);

        self::renderForm(
            [
                'errorCssClass' => 'has-error',
                'layout' => ActiveForm::LAYOUT_FLOATING,
                'options' => ['id' => 'login-form'],
            ],
            static function (ActiveForm $form) use ($model): void {
                echo $form->field($model, 'attributeName');
                echo $form->field($model, 'comment')->textarea();
            },
        );
        self::assertSame(
            [
                'login-form-floating-feedback' => <<<HTML
                <style>#login-form :is(.form-floating:has(.has-error), .has-error > .form-floating) ~ :is(.invalid-feedback, .invalid-tooltip) { display: block; }</style>
                HTML,
            ],
            self::floatingFeedbackStyles(self::webView()),
            'Rule must cover control and container states, keyed by form.',
        );
    }

    public function testFloatingLayoutRegistersFeedbackStyleWithoutClientScript(): void
    {
        $model = new DynamicModel(['attributeName']);

        self::renderForm(
            ['enableClientScript' => false, 'layout' => ActiveForm::LAYOUT_FLOATING],
            static function (ActiveForm $form) use ($model): void {
                echo $form->field($model, 'attributeName');
            },
        );
        self::assertSame(
            ['w0-floating-feedback'],
            array_keys(self::floatingFeedbackStyles(self::webView())),
            'Style rule must not depend on a client validation layer.',
        );
    }

    public function testFloatingLayoutRendersListsStacked(): void
    {
        $model = new DynamicModel(['radios']);

        self::assertSameHtml(
            <<<HTML
            <form id="w0" action="/some-action" method="post"><div class="form-field mb-3 field-dynamicmodel-radios">
            <label class="form-label">Radios</label>
            <input type="hidden" name="DynamicModel[radios]" value=""><div id="dynamicmodel-radios" class="form-group" role="radiogroup"><div class="form-field"><input type="radio" id="i0" class="radio" name="DynamicModel[radios]" value="a">
            <label for="i0">A</label></div></div>

            <div class="invalid-feedback"></div>
            </div>
            </form>
            HTML,
            self::renderForm(
                ['layout' => ActiveForm::LAYOUT_FLOATING],
                static function (ActiveForm $form) use ($model): void {
                    echo $form->field($model, 'radios')->radioList(['a' => 'A']), "\n";
                },
            ),
            'Lists must not be wrapped in `.form-floating`.',
        );
    }

    public function testFormHasNoRoleAttribute(): void
    {
        self::assertStringNotContainsString(
            'role="form"',
            self::renderForm([], static function (): void {}),
            'Form must not carry a `role`.',
        );
    }

    public function testHintRendering(): void
    {
        $model = new User();

        self::assertSameHtml(
            <<<HTML
            <form id="w0" action="/some-action" method="post"><div class="form-field mb-3 field-user-firstname">
            <label class="form-label" for="user-firstname">First Name</label>
            <input type="text" id="user-firstname" class="form-control" name="User[firstName]">

            <div class="invalid-feedback"></div>
            </div>
            <div class="form-field mb-3 field-user-username required">
            <label class="form-label" for="user-username">Username</label>
            <input type="text" id="user-username" class="form-control" name="User[username]" aria-required="true">
            <div class="form-text">Your username must be at least 4 characters long</div>
            <div class="invalid-feedback"></div>
            </div>
            <div class="form-field mb-3 field-user-password required">
            <label class="form-label" for="user-password">Password</label>
            <input type="password" id="user-password" class="form-control" name="User[password]" aria-required="true">
            <div class="form-text">Your password must be 8-20 characters long, contain letters and numbers, and must not contain spaces, special characters, or emoji.</div>
            <div class="invalid-feedback"></div>
            </div>
            </form>
            HTML,
            self::renderForm(
                [],
                static function (ActiveForm $form) use ($model): void {
                    echo $form->field($model, 'firstName'), "\n";
                    echo $form->field($model, 'username'), "\n";
                    echo $form->field($model, 'password')->passwordInput(), "\n";
                },
            ),
            'Hints must use the `form-text` class.',
        );
    }

    public function testHorizontalCssClassesFromFieldConfig(): void
    {
        $model = new DynamicModel(['attributeName', 'checkbox']);

        self::assertSameHtml(
            <<<HTML
            <form id="w0" action="/some-action" method="post"><div class="row g-3 field-dynamicmodel-attributename">
            <label class="lg:col-3 col-form-label" for="dynamicmodel-attributename">Attribute Name</label>
            <div class="sm:col-10">
            <input type="text" id="dynamicmodel-attributename" class="form-control" name="DynamicModel[attributeName]">
            <div class="form-text lg:offset-3">Hint.</div>
            <div class="invalid-feedback"></div>
            </div>
            </div>
            <div class="row g-3 field-dynamicmodel-checkbox">
            <div class="sm:col-10 lg:offset-3">
            <div class="form-field">
            <input type="hidden" name="DynamicModel[checkbox]" value="0"><input type="checkbox" id="dynamicmodel-checkbox" class="check" name="DynamicModel[checkbox]" value="1">
            <label for="dynamicmodel-checkbox">Checkbox</label>

            <div class="invalid-feedback"></div>
            </div>
            </div>
            </div>
            </form>
            HTML,
            self::renderForm(
                [
                    'fieldConfig' => [
                        'horizontalCssClasses' => [
                            'error' => '',
                            'field' => ['row', 'g-3'],
                            'hint' => 'lg:offset-3',
                            'label' => ['lg:col-3', 'col-form-label'],
                            'offset' => 'lg:offset-3',
                        ],
                    ],
                    'layout' => ActiveForm::LAYOUT_HORIZONTAL,
                ],
                static function (ActiveForm $form) use ($model): void {
                    echo $form->field($model, 'attributeName')->hint('Hint.'), "\n";
                    echo $form->field($model, 'checkbox')->checkbox(), "\n";
                },
            ),
            'Configured grid classes must replace the defaults key by key.',
        );
    }

    public function testHorizontalCssClassesFromSubclass(): void
    {
        $model = new DynamicModel(['attributeName']);

        self::assertSameHtml(
            <<<HTML
            <form id="w0" action="/some-action" method="post"><div class="row mb-3 field-dynamicmodel-attributename">
            <label class="md:col-4" for="dynamicmodel-attributename">Attribute Name</label>
            <div class="md:col-6">
            <input type="text" id="dynamicmodel-attributename" class="form-control" name="DynamicModel[attributeName]">
            <div class="form-text md:col-3">Hint.</div>
            <div class="invalid-feedback md:col-3"></div>
            </div>
            </div>
            </form>
            HTML,
            self::renderForm(
                ['fieldClass' => ExtendedActiveField::class, 'layout' => ActiveForm::LAYOUT_HORIZONTAL],
                static function (ActiveForm $form) use ($model): void {
                    echo $form->field($model, 'attributeName')->hint('Hint.'), "\n";
                },
            ),
            'Grid classes redefined by a subclass must be applied.',
        );
    }

    public function testHorizontalCssClassesListsReplaceDefaults(): void
    {
        $form = self::createForm(
            [
                'fieldConfig' => ['horizontalCssClasses' => ['offset' => ['lg:offset-3']]],
                'layout' => ActiveForm::LAYOUT_HORIZONTAL,
            ],
        );

        $field = $form->field(new DynamicModel(['a']), 'a');

        self::assertSame(
            ['lg:offset-3'],
            $field->horizontalCssClasses['offset'] ?? null,
            'List value must replace the default.',
        );
        self::assertStringContainsString(
            "<div class=\"sm:col-10 lg:offset-3\">\n<div class=\"form-field\">",
            $field->checkbox()->render(),
            'Wrapper must use only the configured offset.',
        );
        self::assertStringContainsString(
            "<div class=\"md:col-8 sm:col-10 sm:offset-2\">\n<div class=\"form-field\">",
            self::createForm(
                [
                    'fieldConfig' => ['horizontalCssClasses' => ['wrapper' => 'md:col-8']],
                    'layout' => ActiveForm::LAYOUT_HORIZONTAL,
                ],
            )->field(new DynamicModel(['a']), 'a')->checkbox()->render(),
            'Default offset must be kept for a partial configuration.',
        );
    }

    public function testHorizontalLayout(): void
    {
        $model = new DynamicModel(['attributeName', 'checkbox', 'gridRadios', 'noLabel']);

        self::assertSameHtml(
            <<<HTML
            <form id="w0" action="/some-action" method="post"><div class="row mb-3 field-dynamicmodel-attributename">
            <label class="sm:col-2 col-form-label" for="dynamicmodel-attributename">Attribute Name</label>
            <div class="sm:col-10">
            <input type="text" id="dynamicmodel-attributename" class="form-control" name="DynamicModel[attributeName]">

            <div class="invalid-feedback"></div>
            </div>
            </div>
            <div class="row mb-3 field-dynamicmodel-checkbox">
            <div class="sm:col-10 sm:offset-2">
            <div class="form-field">
            <input type="hidden" name="DynamicModel[checkbox]" value="0"><input type="checkbox" id="dynamicmodel-checkbox" class="check" name="DynamicModel[checkbox]" value="1">
            <label for="dynamicmodel-checkbox">Checkbox</label>

            <div class="invalid-feedback"></div>
            </div>
            </div>
            </div>
            <div class="row mb-3 field-dynamicmodel-gridradios">
            <label class="sm:col-2 col-form-label">Grid Radios</label>
            <div class="sm:col-10">
            <input type="hidden" name="DynamicModel[gridRadios]" value=""><div id="dynamicmodel-gridradios" class="form-group" role="radiogroup"><div class="form-field"><input type="radio" id="i0" class="radio" name="DynamicModel[gridRadios]" value="option1">
            <label for="i0">First radio</label></div>
            <div class="form-field"><input type="radio" id="i1" class="radio" name="DynamicModel[gridRadios]" value="option2">
            <label for="i1">Second radio</label></div></div>

            <div class="invalid-feedback"></div>
            </div>
            </div>
            <div class="row mb-3 field-dynamicmodel-nolabel">

            <div class="sm:col-10 sm:offset-2">
            <input type="text" id="dynamicmodel-nolabel" class="form-control" name="DynamicModel[noLabel]">

            <div class="invalid-feedback"></div>
            </div>
            </div>
            </form>
            HTML,
            self::renderForm(
                ['layout' => ActiveForm::LAYOUT_HORIZONTAL],
                static function (ActiveForm $form) use ($model): void {
                    echo $form->field($model, 'attributeName'), "\n";
                    echo $form->field($model, 'checkbox')->checkbox(), "\n";
                    echo $form->field($model, 'gridRadios')->radioList(
                        ['option1' => 'First radio', 'option2' => 'Second radio'],
                    ), "\n";
                    echo $form->field($model, 'noLabel')->label(false), "\n";
                },
            ),
            'Horizontal layout markup mismatch.',
        );
    }

    public function testHorizontalLayoutTemplateOverride(): void
    {
        $model = new DynamicModel(['checkboxName']);

        self::assertSameHtml(
            <<<HTML
            <form id="w0" action="/some-action" method="post"><div class="row mb-3 field-dynamicmodel-checkboxname">
            <div class="form-field lg:offset-1 lg:col-3">
            <input type="hidden" name="DynamicModel[checkboxName]" value="0"><input type="checkbox" id="dynamicmodel-checkboxname" class="check" name="DynamicModel[checkboxName]" value="1">
            <label for="dynamicmodel-checkboxname">Checkbox Name</label>
            </div>
            <div class="lg:col-8"><div class="invalid-feedback"></div></div>
            </div>
            </form>
            HTML,
            self::renderForm(
                ['layout' => ActiveForm::LAYOUT_HORIZONTAL],
                static function (ActiveForm $form) use ($model): void {
                    echo $form->field($model, 'checkboxName')->checkbox(
                        [
                            'template' => <<<HTML
                            <div class="form-field lg:offset-1 lg:col-3">
                            {input}
                            {label}
                            </div>
                            <div class="lg:col-8">{error}</div>
                            HTML,
                        ],
                    ), "\n";
                },
            ),
            'Configured template must replace the horizontal template.',
        );
    }

    public function testInlineLayout(): void
    {
        $model = new DynamicModel(['attributeName', 'selectName', 'checkboxName']);

        self::assertSameHtml(
            <<<HTML
            <form id="w0" class="row lg:row-cols-auto g-3 align-items-center" action="/some-action" method="post"><div class="col-12 field-dynamicmodel-attributename">
            <label class="visually-hidden" for="dynamicmodel-attributename">Attribute Name</label>
            <input type="text" id="dynamicmodel-attributename" class="form-control" name="DynamicModel[attributeName]" placeholder="Attribute Name">


            </div>
            <div class="col-12 field-dynamicmodel-selectname">
            <label class="visually-hidden" for="dynamicmodel-selectname">Select Name</label>
            <input type="hidden" name="DynamicModel[selectName]" value=""><select id="dynamicmodel-selectname" class="form-control" name="DynamicModel[selectName]" size="4">
            <option value="1">One</option>
            <option value="2">Two</option>
            </select>


            </div>
            <div class="col-12 field-dynamicmodel-checkboxname">
            <div class="form-field">
            <input type="hidden" name="DynamicModel[checkboxName]" value="0"><input type="checkbox" id="dynamicmodel-checkboxname" class="check" name="DynamicModel[checkboxName]" value="1">
            <label for="dynamicmodel-checkboxname">Checkbox Name</label>


            </div>
            </div>
            </form>
            HTML,
            self::renderForm(
                ['layout' => ActiveForm::LAYOUT_INLINE],
                static function (ActiveForm $form) use ($model): void {
                    echo $form->field($model, 'attributeName'), "\n";
                    echo $form->field($model, 'selectName')->listBox(['1' => 'One', '2' => 'Two']), "\n";
                    echo $form->field($model, 'checkboxName')->checkbox(), "\n";
                },
            ),
            'Inline layout markup mismatch.',
        );
    }

    public function testInlineLayoutKeepsConfiguredFormClass(): void
    {
        self::assertSameHtml(
            <<<HTML
            <form id="w0" class="my-form row lg:row-cols-auto g-3 align-items-center" action="/some-action" method="post"></form>
            HTML,
            self::renderForm(
                ['layout' => ActiveForm::LAYOUT_INLINE, 'options' => ['class' => 'my-form']],
                static function (): void {},
            ),
            'Layout classes must be appended.',
        );
    }

    public function testInputTemplate(): void
    {
        $model = new User();

        $model->validate();

        self::assertSameHtml(
            <<<HTML
            <form id="w0" action="/some-action" method="post"><div class="form-field mb-3 field-user-username required">
            <label class="form-label" for="user-username">Username</label>
            <div class="input-group"><span class="input-group-text">@</span><input type="text" id="user-username" class="form-control is-invalid" name="User[username]" aria-required="true" aria-invalid="true"></div>
            <div class="form-text">Your username must be at least 4 characters long</div>
            <div class="invalid-feedback">Username cannot be blank.</div>
            </div>
            </form>
            HTML,
            self::renderForm(
                [],
                static function (ActiveForm $form) use ($model): void {
                    echo $form->field(
                        $model,
                        'username',
                        ['inputTemplate' => '<div class="input-group"><span class="input-group-text">@</span>{input}</div>'],
                    ), "\n";
                },
            ),
            'Input template must wrap the validated control.',
        );
    }

    #[DataProviderExternal(ActiveFormProvider::class, 'nativeValidationCases')]
    public function testNativeValidation(bool|string|null $nativeValidation, string $expected): void
    {
        self::assertSame(
            $expected,
            self::renderForm(['nativeValidation' => $nativeValidation], static function (): void {}),
            'Validation attribute mismatch.',
        );
    }

    public function testNativeValidationNullKeepsConfiguredAttribute(): void
    {
        self::assertSame(
            '<form id="w0" action="/some-action" method="post" data-bs-validate="valid"></form>',
            self::renderForm(['options' => ['data-bs-validate' => 'valid']], static function (): void {}),
            'Configured attribute must be kept.',
        );
    }

    public function testRegistersCssAsset(): void
    {
        self::renderForm([], static function (): void {});

        self::assertArrayHasKey(
            BootstrapAsset::class,
            self::webView()->assetBundles,
            'CSS bundle must be registered.',
        );
    }

    public function testStaticControlRendering(): void
    {
        $model = new User(['firstName' => 'John', 'id' => 1]);

        self::assertSameHtml(
            <<<HTML
            <form id="w0" action="/some-action" method="post"><div class="form-field mb-3 field-user-id">
            <label class="form-label" for="user-id">Id</label>
            <input type="text" id="user-id" class="form-control-plaintext" value="1" readonly>

            <div class="invalid-feedback"></div>
            </div>
            <div class="form-field mb-3 field-user-firstname">
            <label class="form-label" for="user-firstname">First Name</label>
            <input type="text" id="user-firstname" class="form-control-plaintext" value="John" readonly>

            <div class="invalid-feedback"></div>
            </div>
            </form>
            HTML,
            self::renderForm(
                [],
                static function (ActiveForm $form) use ($model): void {
                    echo $form->field($model, 'id')->staticControl(), "\n";
                    echo $form->field($model, 'firstName')->staticControl(), "\n";
                },
            ),
            'Static controls must be labelled by `id`.',
        );
    }

    /**
     * @param array<string, mixed> $config
     */
    #[DataProviderExternal(ActiveFormProvider::class, 'invalidConfigCases')]
    public function testThrowInvalidConfigExceptionForInvalidConfig(array $config, string $message): void
    {
        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage(
            $message,
        );

        new ActiveForm($config);
    }

    public function testThrowInvalidConfigExceptionWhenFieldClassIsNotBootstrapField(): void
    {
        $form = self::createForm(['fieldClass' => \yii\widgets\ActiveField::class]);

        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage(
            Message::INVALID_VALUE->getMessage(
                'yii\\widgets\\ActiveField',
                'fieldClass',
                'yii2\\extensions\\bootstrap6\\ActiveField',
            ),
        );

        $form->field(new DynamicModel(['a']), 'a');
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockWebApplication(
            [
                'components' => [
                    // client script tests assert the inline scripts only; Yii 2.0.x resolves jQuery from `@bower`
                    'assetManager' => ['bundles' => [JqueryAsset::class => false]],
                    'request' => ['enableCsrfValidation' => false],
                ],
            ],
        );
    }

    /**
     * @param array<string, mixed> $config
     */
    private static function createForm(array $config): ActiveForm
    {
        ob_start();

        $form = ActiveForm::begin(['action' => '/some-action', 'enableClientScript' => false, ...$config]);

        ActiveForm::end();
        ob_end_clean();

        return $form;
    }

    /**
     * Returns the floating feedback style blocks registered on the view, indexed by key.
     *
     * @return array<mixed>
     */
    private static function floatingFeedbackStyles(View $view): array
    {
        return array_filter(
            $view->css,
            static fn(mixed $key): bool => str_ends_with((string) $key, '-floating-feedback'),
            ARRAY_FILTER_USE_KEY,
        );
    }

    /**
     * @param array<string, mixed> $config
     * @param callable(ActiveForm): void $fields
     */
    private static function renderForm(array $config, callable $fields): string
    {
        ob_start();

        $form = ActiveForm::begin(['action' => '/some-action', 'enableClientScript' => false, ...$config]);

        $fields($form);

        ActiveForm::end();

        return (string) ob_get_clean();
    }
}
