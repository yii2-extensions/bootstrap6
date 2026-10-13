<?php

declare(strict_types=1);

namespace yii2\extensions\bootstrap6\tests;

use BackedEnum;
use PHPUnit\Framework\Attributes\{DataProviderExternal, Group};
use Stringable;
use yii\base\{DynamicModel, InvalidConfigException};
use yii\i18n\{MessageSource, MissingTranslationEvent};
use yii\web\JsExpression;
use yii2\extensions\bootstrap6\{BaseHtml, Bootstrap, Html};
use yii2\extensions\bootstrap6\enum\{NavStyle, Size, Theme};
use yii2\extensions\bootstrap6\exception\Message;
use yii2\extensions\bootstrap6\tests\provider\HtmlProvider;
use yii2\extensions\bootstrap6\tests\support\stub\{HtmlStub, StringableValue, StubUnitEnum, User};
use yii2\extensions\bootstrap6\tests\support\TestCase;

/**
 * Unit tests for {@see Html} form controls, badges, close buttons, theme helpers, and CSS identifier escaping.
 *
 * {@see HtmlProvider} for test case data providers.
 */
#[Group('html')]
final class HtmlTest extends TestCase
{
    public function testActiveStaticControlConvertsAttributeValues(): void
    {
        $model = new User(['id' => 5, 'username' => 'admin']);

        self::assertSame(
            '<input type="text" class="form-control-plaintext" value="admin" readonly>',
            Html::activeStaticControl($model, 'username'),
            'String attribute must be rendered.',
        );
        self::assertSame(
            '<input type="text" class="form-control-plaintext" value="5" readonly>',
            Html::activeStaticControl($model, 'id'),
            'Integer attribute must be converted to `string`.',
        );
        self::assertSame(
            '<input type="text" class="form-control-plaintext" value="" readonly>',
            Html::activeStaticControl($model, 'password'),
            '`null` attribute must render an empty value.',
        );
    }

    public function testActiveStaticControlValueOptionOverridesAttribute(): void
    {
        $model = new User(['username' => 'admin']);

        self::assertSame(
            '<input type="text" class="form-control-plaintext" value="custom" readonly>',
            Html::activeStaticControl($model, 'username', ['value' => 'custom']),
            'Option value must win.',
        );
        self::assertSame(
            '<input type="text" class="form-control-plaintext" value="expr" readonly>',
            Html::activeStaticControl($model, 'username', ['value' => new JsExpression('expr')]),
            'Stringable value must be converted.',
        );
        self::assertSame(
            '<input type="text" class="form-control-plaintext" value="" readonly>',
            Html::activeStaticControl($model, 'username', ['value' => ['a']]),
            'Array value must render an empty value.',
        );
        self::assertSame(
            '<input type="text" class="form-control-plaintext" value="" readonly>',
            Html::activeStaticControl($model, 'username', ['value' => null]),
            '`null` option must render an empty value.',
        );
    }

    public function testAddCssClassIgnoresNullEntries(): void
    {
        $options = ['class' => 'a'];

        Html::addCssClass($options, [null, 'b', null]);

        self::assertSame(
            ['class' => 'a b'],
            $options,
            'String class must not gain empty tokens.',
        );

        $options = [];

        Html::addCssClass($options, ['widget' => 'collapse', 'x' => null]);

        self::assertSame(
            ['class' => ['widget' => 'collapse']],
            $options,
            'Keys of the remaining entries must be preserved.',
        );

        $options = [];

        Html::addCssClass($options, [null]);

        self::assertSame(
            [],
            $options,
            'Options must stay unchanged when no entry remains.',
        );

        $options = ['class' => 'a'];

        Html::addCssClass($options, []);

        self::assertSame(
            ['class' => 'a'],
            $options,
            'Empty list must keep the configured class.',
        );
    }

    public function testAddCssClassNormalizesEnumsAndStringables(): void
    {
        $options = [];

        Html::addCssClass($options, Theme::PRIMARY);

        self::assertSame(
            ['class' => 'primary'],
            $options,
            'Backed enum must add its value.',
        );

        $options = ['class' => 'a'];

        Html::addCssClass($options, StubUnitEnum::Flush);

        self::assertSame(
            ['class' => 'a Flush'],
            $options,
            'Pure enum must add its name.',
        );

        $options = ['class' => 'a'];

        Html::addCssClass($options, new StringableValue('b'));

        self::assertSame(
            ['class' => 'a b'],
            $options,
            'Stringable must add its `string`.',
        );

        $options = ['class' => 'a'];

        Html::addCssClass($options, [NavStyle::TABS, 'b', new StringableValue('c'), StubUnitEnum::Flush]);

        self::assertSame(
            ['class' => 'a nav-tabs b c Flush'],
            $options,
            'List items must be normalized.',
        );

        $options = ['class' => ['widget' => 'nav']];

        Html::addCssClass($options, ['size' => Size::LG, 'widget' => 'menu']);

        self::assertSame(
            ['class' => ['widget' => 'nav', 'size' => 'lg']],
            $options,
            'Keys must be preserved for the named class merge.',
        );
    }

    /**
     * @param array<string, mixed> $options
     */
    #[DataProviderExternal(HtmlProvider::class, 'badgeCases')]
    public function testBadge(
        string|Stringable $label,
        string|Theme|null $theme,
        array $options,
        string $expected,
    ): void {
        self::assertSame(
            $expected,
            Html::badge($label, $theme, $options),
            'Badge markup mismatch.',
        );
    }

    public function testCheckbox(): void
    {
        self::assertSame(
            '<input type="checkbox" class="check" name="remember" value="1">',
            Html::checkbox('remember'),
            'Checkbox must carry the `check` class.',
        );
        self::assertSame(
            '<input type="checkbox" class="check" name="remember" value="1" checked>',
            Html::checkbox('remember', true),
            'Checked state must be rendered.',
        );
        self::assertSame(
            '<input type="checkbox" class="check" name="remember" value="1">',
            Html::checkbox('remember', true, ['checked' => false]),
            'The `checked` option must take precedence.',
        );
        self::assertSame(
            '<input type="checkbox" class="check theme-success" name="remember" value="yes">',
            Html::checkbox('remember', false, ['class' => 'theme-success', 'value' => 'yes']),
            'Configured class and value must be kept.',
        );
        self::assertSame(
            '<input type="checkbox" class="check" name="n" value="5">',
            Html::checkbox('n', false, ['value' => 5]),
            'Integer value must be converted.',
        );
        self::assertSame(
            '<input type="checkbox" class="check" name="n">',
            Html::checkbox('n', false, ['value' => null]),
            '`null` value must omit the attribute.',
        );
    }

    public function testCheckboxIgnoresNonStringClass(): void
    {
        self::assertSame(
            '<input type="checkbox" class="check" name="a" value="1">',
            Html::checkbox('a', false, ['class' => false]),
            'Invalid configured class must be ignored.',
        );
    }

    public function testCheckboxWithLabel(): void
    {
        self::assertSameHtml(
            <<<HTML
            <input type="checkbox" id="i0" class="check" name="remember" value="1">
            <label for="i0">Remember <b>me</b></label>
            HTML,
            Html::checkbox('remember', false, ['label' => 'Remember <b>me</b>']),
            'Label must follow the input and reference the generated `id`.',
        );
        self::assertSameHtml(
            <<<HTML
            <input type="checkbox" id="agree" class="check" name="agree" value="1">
            <label class="fw-medium" for="agree">Agree</label>
            HTML,
            Html::checkbox(
                'agree',
                false,
                ['id' => 'agree', 'label' => 'Agree', 'labelOptions' => ['class' => 'fw-medium']],
            ),
            'Configured `id` and label options must be used.',
        );
        self::assertSameHtml(
            <<<HTML
            <input type="checkbox" id="i1" class="check" name="n" value="1">
            <label for="i1">7</label>
            HTML,
            Html::checkbox('n', false, ['label' => 7, 'labelOptions' => 'invalid']),
            'Scalar label must be converted and invalid label options ignored.',
        );
        self::assertSame(
            '<input type="checkbox" class="check" name="n" value="1">',
            Html::checkbox('n', false, ['label' => ['invalid']]),
            'Non-scalar label must be ignored.',
        );
    }

    public function testCheckboxWithLabelUsesAutoIdPrefix(): void
    {
        BaseHtml::$autoIdPrefix = 'chk';

        try {
            self::assertSameHtml(
                <<<HTML
                <input type="checkbox" id="chk0" class="check" name="a" value="1">
                <label for="chk0">A</label>
                HTML,
                Html::checkbox('a', false, ['label' => 'A']),
                'Generated `id` must use the prefix.',
            );
        } finally {
            BaseHtml::$autoIdPrefix = 'i';
        }
    }

    public function testCheckboxWithUncheck(): void
    {
        self::assertSameHtml(
            <<<HTML
            <input type="hidden" name="a" value="0" form="f" disabled><input type="checkbox" class="check" name="a" value="1" form="f" disabled data-x="y">
            HTML,
            Html::checkbox('a', false, ['data-x' => 'y', 'disabled' => true, 'form' => 'f', 'uncheck' => '0']),
            'Hidden input must copy only `form` and `disabled`.',
        );
        self::assertSame(
            '<input type="hidden" name="a" value="0"><input type="checkbox" class="check" name="a" value="1">',
            Html::checkbox('a', false, ['uncheck' => 0]),
            'Integer uncheck value must be converted.',
        );
        self::assertSameHtml(
            <<<HTML
            <input type="hidden" name="a" value="0"><input type="checkbox" id="i0" class="check" name="a" value="1">
            <label for="i0">A</label>
            HTML,
            Html::checkbox('a', false, ['label' => 'A', 'uncheck' => '0']),
            'Hidden input must precede the labelled control.',
        );
    }

    public function testCheckboxWrapInput(): void
    {
        self::assertSame(
            '<label for="i0"><input type="checkbox" id="i0" class="check" name="a" value="1">Accept</label>',
            Html::checkbox('a', false, ['label' => 'Accept', 'wrapInput' => true]),
            'Input must be rendered inside the label.',
        );
    }

    public function testCloseButton(): void
    {
        $this->mockWebApplication();

        self::assertSame(
            '<button type="button" class="btn-close" aria-label="Close"></button>',
            Html::closeButton(),
            'Default close button mismatch.',
        );
        self::assertSame(
            '<button type="submit" class="btn-close ms-auto" aria-label="Dismiss" data-bs-dismiss="alert"></button>',
            Html::closeButton(
                ['aria-label' => 'Dismiss', 'class' => 'ms-auto', 'data-bs-dismiss' => 'alert', 'type' => 'submit'],
            ),
            'Configured attributes must be kept.',
        );
    }

    public function testCloseButtonLabelIsTranslated(): void
    {
        $this->mockWebApplication(
            [
                'components' => [
                    'i18n' => [
                        'translations' => [
                            Bootstrap::TRANSLATION_CATEGORY => [
                                'class' => MessageSource::class,
                                'on missingTranslation' => static function (MissingTranslationEvent $event): void {
                                    $event->translatedMessage = "{$event->category}:{$event->message}";
                                },
                            ],
                        ],
                    ],
                ],
                'language' => 'es',
            ],
        );

        self::assertSame(
            '<button type="button" class="btn-close" aria-label="yii.bootstrap6:Close"></button>',
            Html::closeButton(),
            'Label must be translated in the package category.',
        );
    }

    #[DataProviderExternal(HtmlProvider::class, 'enumValueCases')]
    public function testEnumValue(
        string $enum,
        BackedEnum|string|null $value,
        bool $responsive,
        string|null $expected,
    ): void {
        /** @var class-string<BackedEnum> $enum */
        self::assertSame(
            $expected,
            Html::enumValue($enum, $value, 'placement', $responsive),
            'Normalized value mismatch.',
        );
    }

    public function testError(): void
    {
        $model = new DynamicModel();

        $model->addError('foo', 'Some error message.');

        self::assertSame(
            '<div class="invalid-feedback">Some error message.</div>',
            Html::error($model, 'foo'),
            'Default class must be `invalid-feedback`.',
        );
        self::assertSame(
            '<div class="custom-class">Some error message.</div>',
            Html::error($model, 'foo', ['class' => 'custom-class']),
            'Configured class must replace the default.',
        );
        self::assertSame(
            '<div>Some error message.</div>',
            Html::error($model, 'foo', ['class' => null]),
            '`null` class must remove the attribute.',
        );
        self::assertSame(
            '<p class="invalid-feedback">Some error message.</p>',
            Html::error($model, 'foo', ['tag' => 'p']),
            'Configured tag must be used.',
        );
    }

    #[DataProviderExternal(HtmlProvider::class, 'escapeCssIdentifierCases')]
    public function testEscapeCssIdentifier(string $identifier, string $expected): void
    {
        self::assertSame(
            $expected,
            Html::escapeCssIdentifier($identifier),
            'Escaped identifier must equal the `CSS.escape()` output.',
        );
    }

    public function testGetIdIsAvailableToSubclasses(): void
    {
        self::assertSame(
            'i0',
            HtmlStub::nextId(),
            'Generated `id` must be reachable from subclasses.',
        );
    }

    public function testNormalizeClassAttributeRemovesDuplicates(): void
    {
        self::assertSame(
            '<span class="badge">New</span>',
            Html::badge('New', null, ['class' => ['badge', 'badge']]),
            'Duplicated classes must be removed.',
        );
    }

    public function testRadio(): void
    {
        self::assertSame(
            '<input type="radio" class="radio" name="plan" value="1" checked>',
            Html::radio('plan', true),
            'Radio must carry the `radio` class.',
        );
        self::assertSameHtml(
            <<<HTML
            <input type="radio" id="i0" class="radio" name="plan" value="pro">
            <label for="i0">Pro</label>
            HTML,
            Html::radio('plan', false, ['label' => 'Pro', 'value' => 'pro']),
            'Label must follow the radio.',
        );
        self::assertSame(
            '<input type="radio" class="radio" name="plan" value="1">',
            Html::radio('plan', false, ['switch' => true]),
            'Switch option must be ignored for radios.',
        );
    }

    /**
     * @param array<string, mixed> $options
     */
    #[DataProviderExternal(HtmlProvider::class, 'staticControlCases')]
    public function testStaticControl(string $value, array $options, string $expected): void
    {
        self::assertSame(
            $expected,
            Html::staticControl($value, $options),
            'Static control markup mismatch.',
        );
    }

    public function testSwitch(): void
    {
        self::assertSameHtml(
            <<<HTML
            <div class="switch"><input type="checkbox" id="i0" name="notify" value="1" role="switch" switch></div>
            <label for="i0">Enable notifications</label>
            HTML,
            Html::checkbox('notify', false, ['label' => 'Enable notifications', 'switch' => true]),
            'Switch must wrap the input and add `role` and `switch`.',
        );
        self::assertSameHtml(
            <<<HTML
            <input type="hidden" name="n" value="0"><div class="switch switch-lg theme-success"><input type="checkbox" name="n" value="1" checked role="button" switch></div>
            HTML,
            Html::checkbox(
                'n',
                true,
                [
                    'role' => 'button',
                    'switch' => true,
                    'switchOptions' => ['class' => 'switch-lg theme-success'],
                    'uncheck' => '0',
                ],
            ),
            'Hidden input must precede the wrapper and configured attributes must be kept.',
        );
        self::assertSame(
            '<input type="checkbox" class="check" name="n" value="1">',
            Html::checkbox('n', false, ['switch' => 1]),
            'Only `true` must enable the switch.',
        );
    }

    /**
     * @param array<string, mixed> $options
     * @param array<string, mixed> $expected
     */
    #[DataProviderExternal(HtmlProvider::class, 'themeCases')]
    public function testTheme(string|Theme|null $theme, array $options, array $expected): void
    {
        Html::theme($options, $theme);

        self::assertSame(
            $expected,
            $options,
            'Theme class mismatch.',
        );
    }

    #[DataProviderExternal(HtmlProvider::class, 'invalidEnumValueCases')]
    public function testThrowInvalidConfigExceptionForInvalidEnumValue(
        string $enum,
        string $value,
        bool $responsive,
        string $message,
    ): void {
        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage(
            $message,
        );

        /** @var class-string<BackedEnum> $enum */
        Html::enumValue($enum, $value, $enum === Theme::class ? 'theme' : 'placement', $responsive);
    }

    public function testThrowInvalidConfigExceptionForPrefixedValueWithoutResponsiveFlag(): void
    {
        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage(
            Message::INVALID_VALUE->getMessage('md:lg', 'size', 'lg, sm, xl, xs'),
        );

        Html::enumValue(Size::class, 'md:lg', 'size');
    }

    public function testUncheckedByDefault(): void
    {
        self::assertSame(
            '<input type="checkbox" class="check" name="a" value="1">',
            HtmlStub::defaultCheckbox('a'),
            'Omitted state must be unchecked.',
        );
    }
}
