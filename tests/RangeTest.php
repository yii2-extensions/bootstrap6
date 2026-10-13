<?php

declare(strict_types=1);

namespace yii2\extensions\bootstrap6\tests;

use PHPUnit\Framework\Attributes\Group;
use yii2\extensions\bootstrap6\asset\BootstrapPluginAsset;
use yii2\extensions\bootstrap6\Range;
use yii2\extensions\bootstrap6\tests\support\stub\User;
use yii2\extensions\bootstrap6\tests\support\TestCase;

/**
 * Unit tests for {@see Range} wrapper, input and tick mark rendering, data attributes, and plugin registration.
 */
#[Group('widget')]
final class RangeTest extends TestCase
{
    private const string IMPORT = 'import("/assets/js/bootstrap.bundle.js").then((bootstrap) => { ';

    public function testRegisterPluginAssetWithoutInitialization(): void
    {
        $this->mockWebApplication();

        Range::widget(['name' => 'volume']);

        self::assertArrayHasKey(
            BootstrapPluginAsset::class,
            self::webView()->assetBundles,
            'Plugin bundle must be registered for the data API.',
        );
        self::assertNull(
            self::registeredJs(self::webView(), 'w0-range-plugin'),
            'Data API must not need an inline init.',
        );
    }

    public function testRegisterPluginOnWrapper(): void
    {
        $this->mockWebApplication();

        Range::widget(
            [
                'name' => 'volume',
                'clientOptions' => ['bubble' => true],
                'clientEvents' => ['changed.bs.range' => 'onChanged'],
            ],
        );

        $import = self::IMPORT;

        self::assertSame(
            <<<JS
            {$import}bootstrap.Range.getOrCreateInstance(document.getElementById("w0-range"), {"bubble":true}); });
            JS,
            self::registeredJs(self::webView(), 'w0-range-plugin'),
            'Plugin must target the wrapper.',
        );
        self::assertSame(
            'document.getElementById("w0-range").addEventListener("changed.bs.range", onChanged);',
            self::registeredJs(self::webView(), 'w0-range-events'),
            'Events must be attached to the wrapper.',
        );
    }

    public function testRenderRestoresInputOptions(): void
    {
        $this->mockWebApplication();

        $range = new Range(['name' => 'volume', 'options' => ['disabled' => true]]);

        $range->run();

        self::assertSame(
            ['disabled' => true, 'id' => 'w0'],
            $range->options,
            'Input options must be restored after the plugin registration.',
        );
    }

    public function testRenderTicksIsOverridable(): void
    {
        $this->mockWebApplication();

        $range = new class (['name' => 'volume', 'ticks' => [0 => null]]) extends Range {
            protected function renderTicks(string $id): string
            {
                return '<ticks>' . parent::renderTicks($id) . '</ticks>';
            }
        };

        self::assertStringEndsWith(
            "<ticks><datalist id=\"w0-ticks\">\n<option value=\"0\"></option>\n</datalist></ticks>",
            $range->run(),
            'Subclass must be able to decorate the datalist.',
        );
    }

    public function testRenderWithAllOptions(): void
    {
        $this->mockWebApplication();

        self::assertSameHtml(
            <<<HTML
            <div id="temperature" class="theme-success form-range" data-bs-bubble>
            <input type="range" id="w0" class="form-range-input" name="temperature" value="50" min="0" max="100" step="0.5" list="w0-ticks">
            </div>
            <datalist id="w0-ticks">
            <option value="0" label="Cold"></option>
            <option value="25"></option>
            <option value="50" label="Mild &amp; nice"></option>
            <option value="75"></option>
            </datalist>
            HTML,
            Range::widget(
                [
                    'name' => 'temperature',
                    'value' => 50,
                    'min' => 0,
                    'max' => 100,
                    'step' => 0.5,
                    'bubble' => true,
                    'ticks' => [0 => 'Cold', 25 => null, 50 => 'Mild & nice', 75 => ''],
                    'containerOptions' => ['id' => 'temperature', 'class' => 'theme-success'],
                ],
            ),
            'Every option must be rendered on the wrapper, input and datalist.',
        );
    }

    public function testRenderWithModel(): void
    {
        $this->mockWebApplication();

        $user = new User();

        $user->id = 3;

        self::assertSameHtml(
            <<<HTML
            <div id="user-id-range" class="form-range">
            <input type="range" id="user-id" class="form-range-input" name="User[id]" value="3" min="1" max="5" step="any">
            </div>
            HTML,
            Range::widget(
                [
                    'model' => $user,
                    'attribute' => 'id',
                    'max' => 5,
                    'step' => 'any',
                    'options' => ['min' => 1, 'max' => 10],
                ],
            ),
            'Model attribute must bind the input and properties must win when set.',
        );
    }
}
