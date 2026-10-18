<?php

declare(strict_types=1);

namespace yii2\extensions\bootstrap6\tests;

use PHPUnit\Framework\Attributes\{DataProviderExternal, Group};
use yii\base\Widget;
use yii2\extensions\bootstrap6\Datepicker;
use yii2\extensions\bootstrap6\tests\provider\FragmentInitializationProvider;
use yii2\extensions\bootstrap6\tests\support\TestCase;

/**
 * Regression tests for explicitly initialized widgets rendered after the document-ready lifecycle.
 *
 * {@see FragmentInitializationProvider} for initialization opt-out cases.
 */
#[Group('widget')]
final class FragmentInitializationTest extends TestCase
{
    /**
     * @param class-string<Widget> $class Widget class.
     * @param array<string, mixed> $config Widget configuration.
     */
    #[DataProviderExternal(FragmentInitializationProvider::class, 'explicitOptOutCases')]
    public function testExplicitOptOutSkipsInitialization(string $class, array $config, string $id): void
    {
        $this->mockWebApplication();

        $class::widget([...$config, 'clientOptions' => false]);

        self::assertNull(
            self::registeredJs(self::webView(), "{$id}-plugin"),
            'Opt-out must not register an initializer.',
        );
        foreach (['input', 'selection'] as $suffix) {
            self::assertStringNotContainsString(
                '.getOrCreateInstance(',
                self::registeredJs(self::webView(), "{$id}-{$suffix}") ?? '',
                'Opt-out integration must only synchronize instances created elsewhere.',
            );
        }
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
