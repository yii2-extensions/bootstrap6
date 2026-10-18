<?php

declare(strict_types=1);

namespace yii2\extensions\bootstrap6\tests;

use PHPUnit\Framework\Attributes\{DataProviderExternal, Group};
use yii\base\InvalidConfigException;
use yii2\extensions\bootstrap6\asset\BootstrapPluginAsset;
use yii2\extensions\bootstrap6\Datepicker;
use yii2\extensions\bootstrap6\tests\provider\DatepickerProvider;
use yii2\extensions\bootstrap6\tests\support\stub\User;
use yii2\extensions\bootstrap6\tests\support\TestCase;

/**
 * Unit tests for {@see Datepicker} input and inline rendering, data attributes, and plugin registration.
 *
 * {@see DatepickerProvider} for test case data providers.
 */
#[Group('widget')]
final class DatepickerTest extends TestCase
{
    private const string IMPORT = 'import("/assets/js/bootstrap.bundle.js").then((bootstrap) => { ';

    public function testInlineRestoresInputOptions(): void
    {
        $this->mockWebApplication();

        $datepicker = new Datepicker(['name' => 'date', 'inline' => true, 'options' => ['class' => 'custom']]);

        $datepicker->run();

        self::assertSame(
            ['class' => 'custom', 'id' => 'w0'],
            $datepicker->options,
            'Input options must be restored after the plugin registration.',
        );
    }

    public function testLocalizedPopupValueRemainsLazy(): void
    {
        $this->mockWebApplication();

        $html = Datepicker::widget(['name' => 'date', 'value' => '01/02/2026', 'locale' => 'en-GB']);

        self::assertStringContainsString(
            'value="01/02/2026"',
            $html,
            'Localized value must remain unchanged.',
        );
        self::assertNull(
            self::registeredJs(self::webView(), 'w0-plugin'),
            'A popup without explicit preselection must not parse and rewrite its value on page load.',
        );
    }

    public function testPreselectedPopupInitializesByDefault(): void
    {
        $this->mockWebApplication();

        Datepicker::widget(['name' => 'date', 'selectedDates' => ['2026-10-19']]);

        self::assertSame(
            self::IMPORT . 'bootstrap.Datepicker.getOrCreateInstance(document.getElementById("w0"), {}); });',
            self::registeredJs(self::webView(), 'w0-plugin'),
            'Explicit preselection must initialize even in a late fragment.',
        );
    }

    public function testRegisterInlinePluginOnContainer(): void
    {
        $this->mockWebApplication();

        Datepicker::widget(
            [
                'name' => 'date',
                'inline' => true,
                'clientOptions' => ['vcpOptions' => ['disableDatesPast' => true]],
                'clientEvents' => ['change.bs.datepicker' => 'onChange'],
            ],
        );

        $import = self::IMPORT;

        self::assertSame(
            <<<JS
            {$import}bootstrap.Datepicker.getOrCreateInstance(document.getElementById("w0-datepicker"), {"vcpOptions":{"disableDatesPast":true}}); });
            JS,
            self::registeredJs(self::webView(), 'w0-datepicker-plugin'),
            'Inline plugin must target the container.',
        );
        self::assertSame(
            'document.getElementById("w0-datepicker").addEventListener("change.bs.datepicker", onChange);',
            self::registeredJs(self::webView(), 'w0-datepicker-events'),
            'Events must be attached to the container.',
        );
    }

    public function testRegisterPluginAssetWithoutInitialization(): void
    {
        $this->mockWebApplication();

        Datepicker::widget(['name' => 'date']);

        self::assertArrayHasKey(
            BootstrapPluginAsset::class,
            self::webView()->assetBundles,
            'Plugin bundle must be registered for the data API.',
        );
        self::assertNull(
            self::registeredJs(self::webView(), 'w0-plugin'),
            'Popup inputs must keep lazy initialization.',
        );
    }

    public function testRegisterPluginOnInput(): void
    {
        $this->mockWebApplication();

        Datepicker::widget(['name' => 'date', 'clientOptions' => ['displayElement' => false]]);

        $import = self::IMPORT;

        self::assertSame(
            <<<JS
            {$import}bootstrap.Datepicker.getOrCreateInstance(document.getElementById("w0"), {"displayElement":false}); });
            JS,
            self::registeredJs(self::webView(), 'w0-plugin'),
            'Plugin must target the input.',
        );
    }

    public function testRenderAllOptions(): void
    {
        $this->mockWebApplication();

        self::assertSameHtml(
            <<<HTML
            <input type="text" id="w0" class="form-control" name="date" value="2026-06-10" autocomplete="off" data-bs-toggle="datepicker" data-bs-selection-mode="multiple-ranged" data-bs-date-format='{"month":"short","day":"numeric"}' data-bs-date-min="2026-01-01" data-bs-date-max="2026-12-31" data-bs-locale="de-DE" data-bs-first-weekday="0" data-bs-display-months-count="2" data-bs-placement="center" data-bs-selected-dates='["2026-06-10","2026-06-18"]'>
            HTML,
            Datepicker::widget(
                [
                    'name' => 'date',
                    'value' => '2026-06-10',
                    'selectionMode' => 'multiple-ranged',
                    'dateFormat' => ['month' => 'short', 'day' => 'numeric'],
                    'dateMin' => '2026-01-01',
                    'dateMax' => '2026-12-31',
                    'locale' => 'de-DE',
                    'firstWeekday' => 0,
                    'displayMonthsCount' => 2,
                    'placement' => 'center',
                    'selectedDates' => ['2026-06-10', '2026-06-18'],
                ],
            ),
            'Every option must be emitted as its data attribute.',
        );
    }

    public function testRenderInlineWithContainerOptions(): void
    {
        $this->mockWebApplication();

        self::assertSameHtml(
            <<<HTML
            <div id="calendar" class="custom" data-bs-toggle="datepicker" data-bs-inline="true" data-bs-display-months-count="2" data-bs-position-element="#calendar-calendar">
            <input type="hidden" id="w0" name="date">
            <div id="calendar-calendar"></div>
            </div>
            HTML,
            Datepicker::widget(
                [
                    'name' => 'date',
                    'inline' => true,
                    'displayMonthsCount' => 2,
                    'containerOptions' => ['id' => 'calendar', 'class' => 'custom'],
                ],
            ),
            'Container options must apply to the calendar container.',
        );
    }

    public function testRenderInlineWithEmptyValue(): void
    {
        $this->mockWebApplication();

        self::assertSameHtml(
            <<<HTML
            <div id="w0-datepicker" data-bs-toggle="datepicker" data-bs-inline="true" data-bs-position-element="#w0-datepicker-calendar">
            <input type="hidden" id="w0" name="date" value="">
            <div id="w0-datepicker-calendar"></div>
            </div>
            HTML,
            Datepicker::widget(['name' => 'date', 'value' => '', 'inline' => true]),
            'Empty value must not preselect dates.',
        );
    }

    public function testRenderInlineWithModel(): void
    {
        $this->mockWebApplication();

        $user = new User();

        $user->username = '2026-06-10,2026-06-18';

        self::assertSameHtml(
            <<<HTML
            <div id="user-username-datepicker" data-bs-toggle="datepicker" data-bs-inline="true" data-bs-selection-mode="multiple" data-bs-selected-dates='["2026-06-10","2026-06-18"]' data-bs-position-element="#user-username-datepicker-calendar">
            <input type="hidden" id="user-username" name="User[username]" value="2026-06-10,2026-06-18">
            <div id="user-username-datepicker-calendar"></div>
            </div>
            HTML,
            Datepicker::widget(
                ['model' => $user, 'attribute' => 'username', 'inline' => true, 'selectionMode' => 'multiple'],
            ),
            'Model value must preselect the dates.',
        );
    }

    public function testRenderInlineWithNonStringValue(): void
    {
        $this->mockWebApplication();

        self::assertSameHtml(
            <<<HTML
            <div id="w0-datepicker" data-bs-toggle="datepicker" data-bs-inline="true" data-bs-position-element="#w0-datepicker-calendar">
            <input type="hidden" id="w0" name="date" value="20260101">
            <div id="w0-datepicker-calendar"></div>
            </div>
            HTML,
            Datepicker::widget(['name' => 'date', 'value' => 20260101, 'inline' => true]),
            'Non-string value must not preselect dates.',
        );
    }

    public function testRenderInlineWithSelectedDatesOverridingValue(): void
    {
        $this->mockWebApplication();

        self::assertSameHtml(
            <<<HTML
            <div id="w0-datepicker" data-bs-toggle="datepicker" data-bs-inline="true" data-bs-selected-dates='["2026-03-01"]' data-bs-position-element="#w0-datepicker-calendar">
            <input type="hidden" id="w0" name="date" value="2026-01-01">
            <div id="w0-datepicker-calendar"></div>
            </div>
            HTML,
            Datepicker::widget(
                ['name' => 'date', 'value' => '2026-01-01', 'inline' => true, 'selectedDates' => ['2026-03-01']],
            ),
            'Explicit dates must win over the value.',
        );
    }

    public function testRenderMethodsAreOverridable(): void
    {
        $this->mockWebApplication();

        $datepicker = new class (['name' => 'date', 'inline' => true]) extends Datepicker {
            protected function dataAttributes(array $selectedDates): array
            {
                return [...parent::dataAttributes($selectedDates), 'data-custom' => 'yes'];
            }

            protected function renderInline(): string
            {
                return '<inline>' . parent::renderInline() . '</inline>';
            }
        };

        self::assertSameHtml(
            <<<HTML
            <inline><div id="w0-datepicker" data-bs-toggle="datepicker" data-bs-inline="true" data-custom="yes" data-bs-position-element="#w0-datepicker-calendar">
            <input type="hidden" id="w0" name="date">
            <div id="w0-datepicker-calendar"></div>
            </div></inline>
            HTML,
            $datepicker->run(),
            'Subclass must be able to decorate the markup.',
        );
    }

    public function testRenderModelWithoutAttributeUsesValue(): void
    {
        $this->mockWebApplication();

        $user = new User();

        $user->username = '2026-01-01';

        self::assertStringContainsString(
            'data-bs-selected-dates=\'["2026-02-02"]\'',
            Datepicker::widget(['model' => $user, 'name' => 'date', 'value' => '2026-02-02', 'inline' => true]),
            'Name mode must ignore the model value.',
        );
    }

    #[DataProviderExternal(DatepickerProvider::class, 'validOptionCases')]
    public function testRenderValidOption(string $option, int|string $value, string $attribute): void
    {
        $this->mockWebApplication();

        self::assertStringContainsString(
            $attribute,
            Datepicker::widget(['name' => 'date', $option => $value]),
            'Allowed value must be emitted.',
        );
    }

    public function testRenderWithModel(): void
    {
        $this->mockWebApplication();

        $user = new User();

        $user->username = '2026-06-10';

        self::assertSameHtml(
            <<<HTML
            <input type="text" id="user-username" class="w-12 form-control" name="User[username]" value="2026-06-10" autocomplete="on" placeholder="Choose date" data-bs-toggle="datepicker">
            HTML,
            Datepicker::widget(
                [
                    'model' => $user,
                    'attribute' => 'username',
                    'options' => ['class' => 'w-12', 'autocomplete' => 'on', 'placeholder' => 'Choose date'],
                ],
            ),
            'Model attribute must bind the text input.',
        );
    }

    #[DataProviderExternal(DatepickerProvider::class, 'invalidOptionCases')]
    public function testThrowInvalidConfigExceptionForInvalidOption(
        string $option,
        int|string $value,
        string $message,
    ): void {
        $this->mockWebApplication();

        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage(
            $message,
        );

        Datepicker::widget(['name' => 'date', $option => $value]);
    }
}
