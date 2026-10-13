<?php

declare(strict_types=1);

namespace yii2\extensions\bootstrap6\tests;

use PHPUnit\Framework\Attributes\Group;
use yii\base\InvalidConfigException;
use yii2\extensions\bootstrap6\asset\BootstrapPluginAsset;
use yii2\extensions\bootstrap6\enum\Placement;
use yii2\extensions\bootstrap6\exception\Message;
use yii2\extensions\bootstrap6\tests\support\stub\StringableValue;
use yii2\extensions\bootstrap6\tests\support\TestCase;
use yii2\extensions\bootstrap6\Tooltip;

use function ob_get_clean;
use function ob_get_level;
use function ob_get_status;
use function ob_start;

/**
 * Unit tests for {@see Tooltip} trigger wrapper, data attributes, and plugin registration.
 */
#[Group('widget')]
final class TooltipTest extends TestCase
{
    public function testBeginBuffersContentUntilEnd(): void
    {
        $this->mockWebApplication();

        ob_start();

        $level = ob_get_level();

        Tooltip::begin(['title' => 'Tip']);

        /** @var list<array{buffer_used: int}> $status */
        $status = ob_get_status(true);

        self::assertSame(
            $level + 1,
            ob_get_level(),
            'One buffer must be opened.',
        );
        self::assertSame(
            0,
            $status[$level - 1]['buffer_used'] ?? null,
            'Enclosing buffer must stay empty.',
        );

        echo 'body';

        Tooltip::end();

        self::assertSame(
            $level,
            ob_get_level(),
            'Buffer level must be restored.',
        );
        self::assertSameHtml(
            <<<HTML
            <span id="w0" data-bs-toggle="tooltip" data-bs-title="Tip">body</span>
            HTML,
            (string) ob_get_clean(),
            'Captured content must be wrapped by the markup.',
        );
    }

    public function testClientOptionsInitializePlugin(): void
    {
        $this->mockWebApplication();

        self::render(['clientOptions' => ['delay' => 100], 'title' => 'Tip']);

        self::assertSame(
            <<<JS
            import("/assets/js/bootstrap.bundle.js").then((bootstrap) => { bootstrap.Tooltip.getOrCreateInstance(document.getElementById("w0"), {"delay":100}); });
            JS,
            self::registeredJs(self::webView(), 'w0-plugin'),
            'Plugin must be initialized with the options.',
        );
    }

    public function testDataAttributes(): void
    {
        $this->mockWebApplication();

        self::assertSameHtml(
            <<<HTML
            <button type="button" id="w0" class="btn-solid" data-foo="bar" data-bs-toggle="tooltip" data-bs-title="&lt;em&gt;Tip&lt;/em&gt;" data-bs-placement="bottom" data-bs-trigger="click" data-bs-html="true" data-bs-custom-class="custom-tooltip">trigger</button>
            HTML,
            self::render(
                [
                    'customClass' => 'custom-tooltip',
                    'html' => true,
                    'options' => ['class' => 'btn-solid', 'data' => ['foo' => 'bar'], 'type' => 'button'],
                    'placement' => Placement::BOTTOM,
                    'tagName' => 'button',
                    'title' => '<em>Tip</em>',
                    'trigger' => 'click',
                ],
            ),
            'Every option must be rendered as a data attribute.',
        );
        self::assertSameHtml(
            <<<HTML
            <span id="w1" data-bs-toggle="tooltip" data-bs-placement="top md:right">trigger</span>
            HTML,
            self::render(['placement' => 'top md:right']),
            'Responsive placement must be rendered verbatim.',
        );
    }

    public function testRender(): void
    {
        $this->mockWebApplication();

        self::assertSameHtml(
            <<<HTML
            <span id="w0" data-bs-toggle="tooltip" data-bs-title="Tooltip on top">trigger</span>
            HTML,
            self::render(['title' => 'Tooltip on top']),
            'Wrapper must carry the toggle and title.',
        );
        self::assertNull(
            self::registeredJs(self::webView(), 'w0-plugin'),
            'Tooltips must rely on automatic initialization.',
        );
        self::assertArrayHasKey(
            BootstrapPluginAsset::class,
            self::webView()->assetBundles,
            'Plugin bundle must be registered.',
        );
    }

    public function testRenderStringableContent(): void
    {
        $this->mockWebApplication();

        self::assertSame(
            self::render(['options' => ['id' => 't'], 'title' => 'Tip & top']),
            self::render(['options' => ['id' => 't'], 'title' => new StringableValue('Tip & top')]),
            'Stringable values must render like their `string`.',
        );
    }

    public function testThrowInvalidConfigExceptionForInvalidPlacement(): void
    {
        $this->mockWebApplication();

        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage(
            Message::INVALID_VALUE->getMessage(
                'middle',
                'placement',
                'bottom, bottom-end, bottom-start, end, end-end, end-start, left, left-end, left-start, right, '
                . 'right-end, right-start, start, start-end, start-start, top, top-end, top-start',
            ),
        );

        new Tooltip(['placement' => 'middle']);
    }

    /**
     * @param array<string, mixed> $config
     */
    private static function render(array $config): string
    {
        ob_start();

        Tooltip::begin($config);

        echo 'trigger';

        Tooltip::end();

        return (string) ob_get_clean();
    }
}
