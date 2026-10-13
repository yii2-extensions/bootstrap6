<?php

declare(strict_types=1);

namespace yii2\extensions\bootstrap6\tests;

use PHPUnit\Framework\Attributes\Group;
use yii2\extensions\bootstrap6\tests\support\stub\StringableValue;
use yii2\extensions\bootstrap6\tests\support\TestCase;
use yii2\extensions\bootstrap6\Toast;

use function ob_get_clean;
use function ob_get_level;
use function ob_get_status;
use function ob_start;
use function time;

/**
 * Unit tests for {@see Toast} markup, accessibility attributes, and plugin registration.
 */
#[Group('widget')]
final class ToastTest extends TestCase
{
    private const string IMPORT = 'import("/assets/js/bootstrap.bundle.js").then((bootstrap) => { ';

    public function testAriaAndRoleFromOptionsAreKept(): void
    {
        $this->mockWebApplication();

        self::assertSameHtml(
            <<<HTML
            <div id="w0" class="toast" aria-atomic="false" aria-live="polite" role="status">
            <div class="toast-body">
            body
            </div>
            </div>
            HTML,
            self::render(
                ['closeButton' => false, 'options' => ['aria-atomic' => 'false', 'aria-live' => 'polite', 'role' => 'status']],
            ),
            'Flat attributes must be kept.',
        );
        self::assertSameHtml(
            <<<HTML
            <div id="w1" class="toast" aria-atomic="false" aria-describedby="x" aria-live="polite" role="alert">
            <div class="toast-body">
            body
            </div>
            </div>
            HTML,
            self::render(
                [
                    'closeButton' => false,
                    'options' => ['aria' => ['atomic' => 'false', 'describedby' => 'x', 'live' => 'polite']],
                ],
            ),
            'Nested attributes must be kept.',
        );
    }

    public function testBeginBuffersContentUntilEnd(): void
    {
        $this->mockWebApplication();

        ob_start();

        $level = ob_get_level();

        Toast::begin(['closeButton' => false]);

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

        Toast::end();

        self::assertSame(
            $level,
            ob_get_level(),
            'Buffer level must be restored.',
        );
        self::assertSameHtml(
            <<<HTML
            <div id="w0" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-body">
            body
            </div>
            </div>
            HTML,
            (string) ob_get_clean(),
            'Captured content must be wrapped by the markup.',
        );
    }

    public function testClientOptionsInitializePlugin(): void
    {
        $this->mockWebApplication();

        self::render(['clientOptions' => ['autohide' => false]]);

        self::assertSame(
            self::IMPORT . 'bootstrap.Toast.getOrCreateInstance(document.getElementById("w0"), {"autohide":false}); });',
            self::registeredJs(self::webView(), 'w0-plugin'),
            'Plugin must be initialized with the options.',
        );
    }

    public function testCloseButtonOptions(): void
    {
        $this->mockWebApplication();

        self::assertSameHtml(
            <<<HTML
            <div id="w0" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="my-header toast-header">
            <button type="button" class="btn-close ms-auto" data-foo="bar" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="toast-body">
            body
            </div>
            </div>
            HTML,
            self::render(
                [
                    'closeButton' => ['class' => 'ms-auto', 'data' => ['foo' => 'bar']],
                    'headerOptions' => ['class' => 'my-header'],
                ],
            ),
            'Close button must merge its options with the dismiss attribute.',
        );
        self::assertSameHtml(
            <<<HTML
            <div id="w1" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-header">
            <button type="button" class="btn-close" data-bs-dismiss="other" aria-label="Close"></button>
            </div>
            <div class="toast-body">
            body
            </div>
            </div>
            HTML,
            self::render(['closeButton' => ['data' => ['bs-dismiss' => 'other']]]),
            'Configured dismiss target must be kept.',
        );
    }

    public function testDateTimeOptions(): void
    {
        $this->mockWebApplication();

        self::assertSameHtml(
            <<<HTML
            <div id="w0" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-header">
            <span class="fg-2">2 hours ago</span>
            </div>
            <div class="toast-body">
            body
            </div>
            </div>
            HTML,
            self::render(
                [
                    'closeButton' => false,
                    'dateTime' => time() - 7200,
                    'dateTimeOptions' => ['class' => 'fg-2', 'tag' => 'span'],
                ],
            ),
            'Time must be relative and use the configured tag.',
        );
    }

    public function testProtectedMethodsAreExtensionPoints(): void
    {
        $this->mockWebApplication();

        ob_start();

        $toast = new class extends Toast {
            protected function renderHeader(): string
            {
                return '<!-- header -->' . parent::renderHeader();
            }

            protected function toastOptions(): array
            {
                return [...parent::toastOptions(), 'data-extended' => 'true'];
            }
        };

        echo 'body';
        echo $toast->run();

        self::assertSameHtml(
            <<<HTML
            <div id="w0" class="toast" role="alert" aria-live="assertive" aria-atomic="true" data-extended="true">
            <!-- header --><div class="toast-header">
            <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="toast-body">
            body
            </div>
            </div>
            HTML,
            (string) ob_get_clean(),
            'Overridden renderers must be used.',
        );
    }

    public function testRender(): void
    {
        $this->mockWebApplication();

        self::assertSameHtml(
            <<<HTML
            <div id="w0" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-header">
            <strong class="me-auto">Bootstrap &amp; co</strong>
            <small>2 hours ago</small>
            <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="toast-body">
            body<b>Hello</b>
            </div>
            </div>
            HTML,
            self::render(['body' => '<b>Hello</b>', 'dateTime' => time() - 7200, 'title' => 'Bootstrap & co']),
            'Header and body must follow the documented markup.',
        );
        self::assertSame(
            self::IMPORT . 'bootstrap.Toast.getOrCreateInstance(document.getElementById("w0"), {}); });',
            self::registeredJs(self::webView(), 'w0-plugin'),
            'Plugin must always be initialized.',
        );
    }

    public function testRenderStringableContent(): void
    {
        $this->mockWebApplication();

        self::assertSame(
            self::render(['body' => '<b>B</b>', 'options' => ['id' => 't'], 'title' => 'T & t']),
            self::render(
                [
                    'body' => new StringableValue('<b>B</b>'),
                    'options' => ['id' => 't'],
                    'title' => new StringableValue('T & t'),
                ],
            ),
            'Stringable values must render like their `string`.',
        );
    }

    public function testRenderWithoutHeader(): void
    {
        $this->mockWebApplication();

        self::assertSameHtml(
            <<<HTML
            <div id="w0" class="my-toast toast toast-instant" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="my-body toast-body">
            body
            </div>
            </div>
            HTML,
            self::render(
                [
                    'bodyOptions' => ['class' => 'my-body'],
                    'closeButton' => false,
                    'instant' => true,
                    'options' => ['class' => 'my-toast'],
                ],
            ),
            'Header must be omitted.',
        );
    }

    public function testTitleOptions(): void
    {
        $this->mockWebApplication(['language' => 'es']);

        self::assertSameHtml(
            <<<HTML
            <div id="w0" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-header">
            <h6 class="fs-sm me-auto"><b>Title</b></h6>
            <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Cerrar"></button>
            </div>
            <div class="toast-body">
            body
            </div>
            </div>
            HTML,
            self::render(
                ['encodeTitle' => false, 'title' => '<b>Title</b>', 'titleOptions' => ['class' => 'fs-sm', 'tag' => 'h6']],
            ),
            'Title must use the configured tag and raw content.',
        );
    }

    /**
     * @param array<string, mixed> $config
     */
    private static function render(array $config): string
    {
        ob_start();

        Toast::begin($config);

        echo 'body';

        Toast::end();

        return (string) ob_get_clean();
    }
}
