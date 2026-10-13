<?php

declare(strict_types=1);

namespace yii2\extensions\bootstrap6\tests;

use PHPUnit\Framework\Attributes\Group;
use yii2\extensions\bootstrap6\enum\Placement;
use yii2\extensions\bootstrap6\{Popover, Tooltip};
use yii2\extensions\bootstrap6\tests\support\stub\StringableValue;
use yii2\extensions\bootstrap6\tests\support\TestCase;

use function ob_get_clean;
use function ob_get_level;
use function ob_get_status;
use function ob_start;

/**
 * Unit tests for {@see Popover} trigger wrapper, data attributes, and plugin registration.
 */
#[Group('widget')]
final class PopoverTest extends TestCase
{
    public function testBeginBuffersContentUntilEnd(): void
    {
        $this->mockWebApplication();

        ob_start();

        $level = ob_get_level();

        Popover::begin(['content' => 'Body']);

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

        Popover::end();

        self::assertSame(
            $level,
            ob_get_level(),
            'Buffer level must be restored.',
        );
        self::assertSameHtml(
            <<<HTML
            <span id="w0" data-bs-toggle="popover" data-bs-content="Body">body</span>
            HTML,
            (string) ob_get_clean(),
            'Captured content must be wrapped by the markup.',
        );
    }

    public function testClientOptionsInitializePlugin(): void
    {
        $this->mockWebApplication();

        self::render(['clientOptions' => ['trigger' => 'focus'], 'content' => 'Body']);

        self::assertSame(
            <<<JS
            import("/assets/js/bootstrap.bundle.js").then((bootstrap) => { bootstrap.Popover.getOrCreateInstance(document.getElementById("w0"), {"trigger":"focus"}); });
            JS,
            self::registeredJs(self::webView(), 'w0-plugin'),
            'Popover plugin must be initialized with the options.',
        );
    }

    public function testPluginNameOverridesTooltipOnSamePage(): void
    {
        $this->mockWebApplication();

        ob_start();

        Tooltip::begin(['title' => 'Tip']);

        echo 'tip';

        Tooltip::end();

        $tooltip = (string) ob_get_clean();

        self::assertStringContainsString(
            'data-bs-toggle="tooltip"',
            $tooltip,
            'Tooltip must keep its own toggle.',
        );
        self::assertStringContainsString(
            'data-bs-toggle="popover"',
            self::render(['content' => 'Body']),
            'Popover must use its own toggle.',
        );
    }

    public function testRender(): void
    {
        $this->mockWebApplication();

        self::assertSameHtml(
            <<<HTML
            <span id="w0" data-bs-toggle="popover" data-bs-title="Popover title" data-bs-placement="right" data-bs-content="Popover &amp; body">trigger</span>
            HTML,
            self::render(['content' => 'Popover & body', 'placement' => Placement::RIGHT, 'title' => 'Popover title']),
            'Wrapper must carry the toggle, title, and content.',
        );
        self::assertNull(
            self::registeredJs(self::webView(), 'w0-plugin'),
            'Popovers must rely on automatic initialization.',
        );
    }

    public function testRenderStringableContent(): void
    {
        $this->mockWebApplication();

        self::assertSame(
            self::render(['content' => 'C & c', 'options' => ['id' => 'p'], 'title' => 'T']),
            self::render(
                [
                    'content' => new StringableValue('C & c'),
                    'options' => ['id' => 'p'],
                    'title' => new StringableValue('T'),
                ],
            ),
            'Stringable values must render like their `string`.',
        );
    }

    public function testRenderWithoutContent(): void
    {
        $this->mockWebApplication();

        self::assertSameHtml(
            <<<HTML
            <a id="w0" href="#" role="button" tabindex="0" data-bs-toggle="popover" data-bs-title="Title" data-bs-trigger="focus">trigger</a>
            HTML,
            self::render(
                [
                    'options' => ['href' => '#', 'role' => 'button', 'tabindex' => '0'],
                    'tagName' => 'a',
                    'title' => 'Title',
                    'trigger' => 'focus',
                ],
            ),
            'Content attribute must be omitted.',
        );
    }

    /**
     * @param array<string, mixed> $config
     */
    private static function render(array $config): string
    {
        ob_start();

        Popover::begin($config);

        echo 'trigger';

        Popover::end();

        return (string) ob_get_clean();
    }
}
