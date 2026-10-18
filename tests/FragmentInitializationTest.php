<?php

declare(strict_types=1);

namespace yii2\extensions\bootstrap6\tests;

use PHPUnit\Framework\Attributes\{Group, TestWith};
use yii\base\Widget;
use yii2\extensions\bootstrap6\{Chips, Combobox, Datepicker, NavOverflow, OtpInput, Range, Strength};
use yii2\extensions\bootstrap6\tests\support\TestCase;

/**
 * Regression tests for explicitly initialized widgets rendered after the document-ready lifecycle.
 */
#[Group('widget')]
final class FragmentInitializationTest extends TestCase
{
    /**
     * @param class-string<Widget> $class Widget class.
     * @param array<string, mixed> $config Widget configuration.
     */
    #[TestWith([Chips::class, ['name' => 'tags'], 'w0-chips'])]
    #[TestWith([Combobox::class, ['name' => 'choice', 'items' => ['a' => 'Alpha']], 'w0'])]
    #[TestWith([Datepicker::class, ['name' => 'date'], 'w0'])]
    #[TestWith([Datepicker::class, ['name' => 'date', 'inline' => true], 'w0-datepicker'])]
    #[TestWith([NavOverflow::class, [], 'w0'])]
    #[TestWith([OtpInput::class, ['name' => 'code'], 'w0-otp'])]
    #[TestWith([Range::class, ['name' => 'amount'], 'w0-range'])]
    #[TestWith([Strength::class, [], 'w0'])]
    public function testExplicitOptOutSkipsInitialization(string $class, array $config, string $id): void
    {
        $this->mockWebApplication();

        $class::widget([...$config, 'clientOptions' => false]);

        self::assertNull(
            self::registeredJs(self::webView(), "{$id}-plugin"),
            'Opt-out must not register an initializer.',
        );
        self::assertNull(
            self::registeredJs(self::webView(), "{$id}-input"),
            'Opt-out must not register input integration.',
        );
    }

    public function testInlineCalendarSelectorEscapesCustomContainerId(): void
    {
        $this->mockWebApplication();

        $html = Datepicker::widget(
            ['name' => 'date', 'inline' => true, 'containerOptions' => ['id' => 'date:calendar']],
        );

        self::assertStringContainsString(
            'data-bs-position-element="#date\\:calendar-calendar"',
            $html,
            'Calendar selectors must escape CSS metacharacters in the container identifier.',
        );
        self::assertStringContainsString(
            '<div id="date:calendar-calendar"></div>',
            $html,
            'Mount must retain its raw id.',
        );
    }

    public function testInlineDatepickerInitializesItsContainerByDefault(): void
    {
        $this->mockWebApplication();

        Datepicker::widget(['name' => 'date', 'inline' => true]);

        self::assertSame(
            <<<JS
            import("/assets/js/bootstrap.bundle.js").then((bootstrap) => { bootstrap.Datepicker.getOrCreateInstance(document.getElementById("w0-datepicker"), {}); });
            JS,
            self::registeredJs(self::webView(), 'w0-datepicker-plugin'),
            'Inline initialization must target the container rather than the hidden input.',
        );
    }
}
