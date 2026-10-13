<?php

declare(strict_types=1);

namespace yii2\extensions\bootstrap6\tests;

use PHPUnit\Framework\Attributes\Group;
use yii2\extensions\bootstrap6\{InputWidget, Widget};
use yii2\extensions\bootstrap6\tests\support\stub\{StubInputWidget, StubWidget, User};
use yii2\extensions\bootstrap6\tests\support\TestCase;

/**
 * Unit tests for {@see Widget} and {@see InputWidget} `id` assignment.
 */
#[Group('widget')]
final class WidgetTest extends TestCase
{
    public function testAssignsSequentialIdsWhenMissing(): void
    {
        $this->mockWebApplication();

        self::assertSame(
            '<div id="w0"></div>',
            StubWidget::widget(),
            'First widget must receive `w0`.',
        );
        self::assertSame(
            '<div id="w1"></div>',
            StubWidget::widget(),
            'Second widget must receive `w1`.',
        );
    }

    public function testCounterIsResetBetweenTests(): void
    {
        $this->mockWebApplication();

        self::assertSame(
            '<div id="w0"></div>',
            StubWidget::widget(),
            'Counter must start at zero.',
        );
    }

    public function testInputWidgetUsesModelInputId(): void
    {
        $this->mockWebApplication();

        $html = StubInputWidget::widget(
            ['attribute' => 'username', 'clientOptions' => ['bubble' => true], 'model' => new User()],
        );

        self::assertSame(
            '<input type="range" id="user-username" name="User[username]">',
            $html,
            'Input must use the model input `id`.',
        );
        self::assertStringContainsString(
            'bootstrap.Range.getOrCreateInstance(document.getElementById("user-username"), {"bubble":true});',
            self::registeredJs(self::webView(), 'user-username-plugin') ?? '',
            'Plugin must target the input `id`.',
        );
    }

    public function testInputWidgetWithNameUsesAutoId(): void
    {
        $this->mockWebApplication();

        self::assertSame(
            '<input type="range" id="w0" name="volume">',
            StubInputWidget::widget(['name' => 'volume']),
            'Input without model must receive the widget `id`.',
        );
        self::assertSame(
            [],
            self::webView()->js,
            'Empty options must not initialize the plugin by default.',
        );
    }

    public function testKeepsConfiguredId(): void
    {
        $this->mockWebApplication();

        self::assertSame(
            '<div id="toast" class="toast"></div>',
            StubWidget::widget(['options' => ['id' => 'toast', 'class' => 'toast']]),
            'Configured `id` must be kept.',
        );
    }
}
