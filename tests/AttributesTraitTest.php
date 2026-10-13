<?php

declare(strict_types=1);

namespace yii2\extensions\bootstrap6\tests;

use BackedEnum;
use PHPUnit\Framework\Attributes\{DataProviderExternal, Group};
use yii2\extensions\bootstrap6\{AttributesTrait, Collapse};
use yii2\extensions\bootstrap6\enum\Theme;
use yii2\extensions\bootstrap6\tests\provider\AttributesTraitProvider;
use yii2\extensions\bootstrap6\tests\support\stub\{StubInputWidget, StubWidget};
use yii2\extensions\bootstrap6\tests\support\TestCase;

use function ob_get_clean;
use function ob_start;

/**
 * Unit tests for {@see AttributesTrait} fluent HTML attribute methods on widgets and input widgets.
 *
 * {@see AttributesTraitProvider} for test case data providers.
 */
#[Group('widget')]
final class AttributesTraitTest extends TestCase
{
    public function testAddAttributesMergesWithConfiguredOptions(): void
    {
        $this->mockWebApplication();

        $widget = new StubWidget(['options' => ['class' => 'toast', 'data-id' => '1']]);

        self::assertSame(
            '<div id="w0" class="toast" title="Hi" data-id="2"></div>',
            $widget->addAttributes(['data-id' => '2', 'title' => 'Hi'])->run(),
            'Colliding key must be overwritten and unrelated attributes kept.',
        );
    }

    /**
     * @param array<string, mixed> $options
     * @param list<BackedEnum|string|null> $classes
     */
    #[DataProviderExternal(AttributesTraitProvider::class, 'addClassCases')]
    public function testAddClass(array $options, array $classes, string $expected): void
    {
        $this->mockWebApplication();

        $widget = new StubWidget(['options' => $options]);

        self::assertSame(
            $expected,
            $widget->addClass(...$classes)->run(),
            'Classes must be merged into the configured `class` attribute.',
        );
    }

    public function testAddClassAccumulatesAcrossCalls(): void
    {
        $this->mockWebApplication();

        $widget = new StubWidget();

        self::assertSame(
            '<div id="w0" class="a b c"></div>',
            $widget->addClass('a')->addClass('b', 'c')->run(),
            'Every call must append its classes.',
        );
    }

    /**
     * @param array<string, mixed> $options
     * @param array<string, string>|string $style
     */
    #[DataProviderExternal(AttributesTraitProvider::class, 'addCssStyleCases')]
    public function testAddCssStyle(array $options, array|string $style, bool $overwrite, string $expected): void
    {
        $this->mockWebApplication();

        $widget = new StubWidget(['options' => $options]);

        self::assertSame(
            $expected,
            $widget->addCssStyle($style, $overwrite)->run(),
            'Style must follow the merge rules.',
        );
    }

    public function testAddCssStyleOverwritesByDefault(): void
    {
        $this->mockWebApplication();

        $widget = new StubWidget(['options' => ['style' => 'color: red;']]);

        self::assertSame(
            '<div id="w0" style="color: blue; margin: 0;"></div>',
            $widget->addCssStyle('color: blue; margin: 0;')->run(),
            'Conflicting property must take the new value.',
        );
    }

    public function testAttributeSetsAndOverwritesValues(): void
    {
        $this->mockWebApplication();

        $widget = new StubWidget(['options' => ['data-id' => '1']]);

        self::assertSame(
            '<div id="w0" title="Second"></div>',
            $widget->attribute('title', 'First')->attribute('title', 'Second')->attribute('data-id', null)->run(),
            'Last value must win and `null` must omit the attribute.',
        );
    }

    public function testBeginReturnValueAcceptsChain(): void
    {
        $this->mockWebApplication();

        ob_start();

        Collapse::begin(['options' => ['class' => 'a']])
            ->class('b')
            ->addClass(Theme::PRIMARY)
            ->addCssStyle(['width' => '10rem'])
            ->attribute('title', 'Panel');

        echo 'body';

        Collapse::end();

        self::assertSameHtml(
            <<<HTML
            <div id="w0" class="b primary collapse" title="Panel" style="width: 10rem;">
            body
            </div>
            HTML,
            (string) ob_get_clean(),
            'Structural class must be added after the fluent classes.',
        );
    }

    public function testChainingReturnsSameInstance(): void
    {
        $this->mockWebApplication();

        $widget = new StubWidget();

        self::assertSame(
            $widget,
            $widget->addAttributes(['title' => 'x']),
            'Same instance must be returned after adding attributes.',
        );
        self::assertSame(
            $widget,
            $widget->addClass('x'),
            'Same instance must be returned after adding a class.',
        );
        self::assertSame(
            $widget,
            $widget->addCssStyle('color: red;'),
            'Same instance must be returned after adding a style.',
        );
        self::assertSame(
            $widget,
            $widget->attribute('title', 'y'),
            'Same instance must be returned after setting an attribute.',
        );
        self::assertSame(
            $widget,
            $widget->class('y'),
            'Same instance must be returned after replacing the classes.',
        );
    }

    public function testClassReplacesConfiguredClasses(): void
    {
        $this->mockWebApplication();

        $widget = new StubWidget(['options' => ['class' => 'a b']]);

        self::assertSame(
            '<div id="w0" class="c primary"></div>',
            $widget->class('c', null, Theme::PRIMARY)->run(),
            'Both configured tokens must be replaced.',
        );

        $widget = new StubWidget(['options' => ['class' => ['widget' => 'a']]]);

        self::assertSame(
            '<div id="w1" class="c"></div>',
            $widget->addClass('b')->class('c')->run(),
            'Configured and added array classes must be replaced.',
        );
    }

    public function testClassWithOnlyNullRemovesAttribute(): void
    {
        $this->mockWebApplication();

        $widget = new StubWidget(['options' => ['class' => 'a']]);

        $widget->class(null);

        self::assertArrayNotHasKey(
            'class',
            $widget->options,
            'No `class` key must remain.',
        );
        self::assertSame(
            '<div id="w0"></div>',
            $widget->run(),
            'No `class` attribute must be rendered.',
        );
    }

    public function testInputWidgetExposesAttributeMethods(): void
    {
        $this->mockWebApplication();

        $widget = new StubInputWidget(['name' => 'volume']);

        self::assertSame(
            '<input type="range" id="w0" class="form-range" name="volume" min="0" style="width: 10rem;">',
            $widget->addClass('form-range')->attribute('min', 0)->addCssStyle(['width' => '10rem'])->run(),
            'Attributes must be rendered on the input.',
        );
    }
}
