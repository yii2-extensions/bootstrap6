<?php

declare(strict_types=1);

namespace yii2\extensions\bootstrap6\tests;

use Closure;
use PHPUnit\Framework\Attributes\{DataProviderExternal, Group};
use yii\base\InvalidConfigException;
use yii2\extensions\bootstrap6\asset\BootstrapPluginAsset;
use yii2\extensions\bootstrap6\Dialog;
use yii2\extensions\bootstrap6\enum\{Breakpoint, DialogSize};
use yii2\extensions\bootstrap6\exception\Message;
use yii2\extensions\bootstrap6\tests\provider\DialogProvider;
use yii2\extensions\bootstrap6\tests\support\stub\StringableValue;
use yii2\extensions\bootstrap6\tests\support\TestCase;

use function ob_get_clean;
use function ob_get_level;
use function ob_get_status;
use function ob_start;

/**
 * Unit tests for {@see Dialog} markup, toggle button, data attributes, and plugin registration.
 *
 * {@see DialogProvider} for test case data providers.
 */
#[Group('widget')]
final class DialogTest extends TestCase
{
    public function testAriaAttributesFromOptionsAreKept(): void
    {
        $this->mockWebApplication();

        self::assertSameHtml(
            <<<HTML
            <dialog id="w0" class="dialog" aria-label="Custom">
            <div class="dialog-header">
            <h1 id="w0-label" class="dialog-title">Title</h1>
            </div>
            <div class="dialog-body">
            body
            </div>
            </dialog>
            HTML,
            self::render(['closeButton' => false, 'options' => ['aria' => ['label' => 'Custom']], 'title' => 'Title']),
            'Configured `aria-label` must replace `aria-labelledby`.',
        );
        self::assertSameHtml(
            <<<HTML
            <dialog id="w1" class="dialog" aria-labelledby="other">
            <div class="dialog-header">
            <h1 id="w1-label" class="dialog-title">Title</h1>
            </div>
            <div class="dialog-body">
            body
            </div>
            </dialog>
            HTML,
            self::render(
                ['closeButton' => false, 'options' => ['aria' => ['labelledby' => 'other']], 'title' => 'Title'],
            ),
            'Configured `aria-labelledby` must be kept.',
        );
    }

    public function testBeginBuffersContentUntilEnd(): void
    {
        $this->mockWebApplication();

        ob_start();

        $level = ob_get_level();

        Dialog::begin([]);

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

        Dialog::end();

        self::assertSame(
            $level,
            ob_get_level(),
            'Buffer level must be restored.',
        );
        self::assertSameHtml(
            <<<HTML
            <dialog id="w0" class="dialog">
            <div class="dialog-header">
            <button type="button" class="btn-close" data-bs-dismiss="dialog" aria-label="Close"></button>
            </div>
            <div class="dialog-body">
            body
            </div>
            </dialog>
            HTML,
            (string) ob_get_clean(),
            'Captured content must be wrapped by the markup.',
        );
    }

    public function testBodyAndFooterOptions(): void
    {
        $this->mockWebApplication();

        self::assertSameHtml(
            <<<HTML
            <dialog id="w0" class="dialog">
            <div class="custom-body dialog-body">
            body
            </div>
            <div class="custom-footer dialog-footer">
            <b>Footer</b>
            </div>
            </dialog>
            HTML,
            self::render(
                [
                    'bodyOptions' => ['class' => 'custom-body'],
                    'closeButton' => false,
                    'footer' => '<b>Footer</b>',
                    'footerOptions' => ['class' => 'custom-footer'],
                ],
            ),
            'Body and footer must carry their options; footer must not be encoded.',
        );
    }

    public function testClientOptionsInitializePlugin(): void
    {
        $this->mockWebApplication();

        self::render(['clientEvents' => ['shown.bs.dialog' => 'f'], 'clientOptions' => ['keyboard' => false]]);

        self::assertSame(
            <<<JS
            import("/assets/js/bootstrap.bundle.js").then((bootstrap) => { bootstrap.Dialog.getOrCreateInstance(document.getElementById("w0"), {"keyboard":false}); });
            JS,
            self::registeredJs(self::webView(), 'w0-plugin'),
            'Plugin must be initialized with the options.',
        );
        self::assertSame(
            'document.getElementById("w0").addEventListener("shown.bs.dialog", f);',
            self::registeredJs(self::webView(), 'w0-events'),
            'Event handler must be attached.',
        );
    }

    public function testCloseButtonOptions(): void
    {
        $this->mockWebApplication();

        self::assertSameHtml(
            <<<HTML
            <dialog id="w0" class="dialog">
            <div class="custom-header dialog-header">
            <button type="button" class="btn-close me-2" data-foo="bar" data-bs-dismiss="dialog" aria-label="Close"></button>
            </div>
            <div class="dialog-body">
            body
            </div>
            </dialog>
            HTML,
            self::render(
                [
                    'closeButton' => ['class' => 'me-2', 'data' => ['foo' => 'bar']],
                    'headerOptions' => ['class' => 'custom-header'],
                ],
            ),
            'Close button must merge its options with the dismiss attribute.',
        );
        self::assertSameHtml(
            <<<HTML
            <dialog id="w1" class="dialog">
            <div class="dialog-header">
            <button type="button" class="btn-close" data-bs-dismiss="other" aria-label="Close"></button>
            </div>
            <div class="dialog-body">
            body
            </div>
            </dialog>
            HTML,
            self::render(['closeButton' => ['data' => ['bs-dismiss' => 'other']]]),
            'Configured dismiss target must be kept.',
        );
    }

    public function testDataAttributesFromOptionsAreMerged(): void
    {
        $this->mockWebApplication();

        self::assertSameHtml(
            <<<HTML
            <dialog id="custom" class="my-dialog dialog" data-foo="bar" data-bs-keyboard="false">
            <div class="dialog-body">
            body
            </div>
            </dialog>
            HTML,
            self::render(
                [
                    'closeButton' => false,
                    'keyboard' => false,
                    'options' => ['class' => 'my-dialog', 'data' => ['foo' => 'bar'], 'id' => 'custom'],
                ],
            ),
            'Configured data attributes must be merged.',
        );
    }

    public function testDialogModifiers(): void
    {
        $this->mockWebApplication();

        self::assertSameHtml(
            <<<HTML
            <dialog id="w0" class="dialog dialog-lg md-down:dialog-fullscreen dialog-scrollable dialog-slide-up dialog-instant" data-bs-backdrop="static" data-bs-keyboard="false" data-bs-modal="false">
            <div class="dialog-body">
            body
            </div>
            </dialog>
            HTML,
            self::render(
                [
                    'backdrop' => 'static',
                    'closeButton' => false,
                    'fullscreenBelow' => Breakpoint::MD,
                    'instant' => true,
                    'keyboard' => false,
                    'nonModal' => true,
                    'scrollable' => true,
                    'size' => DialogSize::LG,
                    'slide' => 'up',
                ],
            ),
            'Every modifier must be rendered.',
        );
        self::assertSameHtml(
            <<<HTML
            <dialog id="w1" class="dialog dialog-sm 2xl-down:dialog-fullscreen dialog-slide-down" data-bs-backdrop="false">
            <div class="dialog-body">
            body
            </div>
            </dialog>
            HTML,
            self::render(
                [
                    'backdrop' => false,
                    'closeButton' => false,
                    'fullscreenBelow' => '2xl',
                    'size' => 'dialog-sm',
                    'slide' => 'down',
                ],
            ),
            'String values must be rendered verbatim.',
        );
    }

    public function testInvalidBackdropLeavesNoOpenBuffer(): void
    {
        $this->mockWebApplication();

        $level = ob_get_level();
        $caught = false;

        try {
            Dialog::begin(['backdrop' => 'none']);
        } catch (InvalidConfigException) {
            $caught = true;
        }

        self::assertTrue(
            $caught,
            'Invalid backdrop must be rejected.',
        );
        self::assertSame(
            $level,
            ob_get_level(),
            'Buffer level must be unchanged.',
        );
    }

    public function testProtectedMethodsAreExtensionPoints(): void
    {
        $this->mockWebApplication();

        ob_start();

        $dialog = new class (['closeButton' => false, 'footer' => 'Footer']) extends Dialog {
            protected function dialogOptions(): array
            {
                return [...parent::dialogOptions(), 'data-extended' => 'true'];
            }

            protected function renderFooter(): string
            {
                return '<!-- footer -->' . parent::renderFooter();
            }

            protected function renderHeader(): string
            {
                return '<!-- header -->' . parent::renderHeader();
            }

            protected function renderToggleButton(): string
            {
                return '<!-- toggle -->' . parent::renderToggleButton();
            }
        };

        echo 'body';
        echo $dialog->run();

        self::assertSameHtml(
            <<<HTML
            <!-- toggle -->
            <dialog id="w0" class="dialog" data-extended="true">
            <!-- header -->
            <div class="dialog-body">
            body
            </div><!-- footer -->
            <div class="dialog-footer">
            Footer
            </div>
            </dialog>
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
            <dialog id="w0" class="dialog">
            <div class="dialog-header">
            <button type="button" class="btn-close" data-bs-dismiss="dialog" aria-label="Close"></button>
            </div>
            <div class="dialog-body">
            body
            </div>
            </dialog>
            HTML,
            self::render([]),
            'Default dialog must render a header with the close button only.',
        );
        self::assertNull(
            self::registeredJs(self::webView(), 'w0-plugin'),
            'Data API must be used without client options.',
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
            self::render(
                ['footer' => '<b>F</b>', 'options' => ['id' => 'd'], 'title' => 'T & t'],
            ),
            self::render(
                [
                    'footer' => new StringableValue('<b>F</b>'),
                    'options' => ['id' => 'd'],
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
            <dialog id="w0" class="dialog">
            <div class="dialog-body">
            body
            </div>
            </dialog>
            HTML,
            self::render(['closeButton' => false]),
            'Header must be omitted.',
        );
    }

    public function testRenderWithTitleAndFooter(): void
    {
        $this->mockWebApplication();

        self::assertSameHtml(
            <<<HTML
            <dialog id="w0" class="dialog" aria-labelledby="w0-label">
            <div class="dialog-header">
            <h1 id="w0-label" class="dialog-title">Dialog &lt;title&gt;</h1>
            <button type="button" class="btn-close" data-bs-dismiss="dialog" aria-label="Close"></button>
            </div>
            <div class="dialog-body">
            body
            </div>
            <div class="dialog-footer">
            Footer
            </div>
            </dialog>
            HTML,
            self::render(['footer' => 'Footer', 'title' => 'Dialog <title>']),
            'Title must be encoded and referenced by `aria-labelledby`.',
        );
    }

    public function testThrowInvalidConfigExceptionForInvalidBackdrop(): void
    {
        $this->mockWebApplication();

        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage(
            Message::INVALID_VALUE->getMessage('none', 'backdrop', 'true, false, static'),
        );

        Dialog::begin(['backdrop' => 'none']);
    }

    /**
     * @param array<string, mixed> $config
     */
    #[DataProviderExternal(DialogProvider::class, 'invalidEnumValueCases')]
    public function testThrowInvalidConfigExceptionForInvalidEnumValue(array $config, string $message): void
    {
        $this->mockWebApplication();

        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage(
            $message,
        );

        new Dialog([...$config]);
    }

    public function testThrowInvalidConfigExceptionForInvalidSlide(): void
    {
        $this->mockWebApplication();

        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage(
            Message::INVALID_VALUE->getMessage('left', 'slide', 'down, up'),
        );

        Dialog::begin(['slide' => 'left']);
    }

    public function testTitleOptions(): void
    {
        $this->mockWebApplication();

        self::assertSameHtml(
            <<<HTML
            <dialog id="w0" class="dialog" aria-labelledby="custom-title">
            <div class="dialog-header">
            <h5 id="custom-title" class="fs-lg dialog-title"><b>Title</b></h5>
            </div>
            <div class="dialog-body">
            body
            </div>
            </dialog>
            HTML,
            self::render(
                [
                    'closeButton' => false,
                    'encodeTitle' => false,
                    'title' => '<b>Title</b>',
                    'titleOptions' => ['class' => 'fs-lg', 'id' => 'custom-title', 'tag' => 'h5'],
                ],
            ),
            'Title must use the configured tag, `id`, and raw content.',
        );
    }

    public function testToggleButton(): void
    {
        $this->mockWebApplication();

        self::assertSameHtml(
            <<<HTML
            <button type="button" class="btn-solid theme-primary" data-bs-toggle="dialog" data-bs-target="#w0">Show</button>
            <dialog id="w0" class="dialog">
            <div class="dialog-body">
            body
            </div>
            </dialog>
            HTML,
            self::render(['closeButton' => false, 'toggleButton' => []]),
            'Default toggle button must target the dialog.',
        );
    }

    public function testToggleButtonOptions(): void
    {
        $this->mockWebApplication();

        self::assertSameHtml(
            <<<HTML
            <button type="submit" class="btn-outline" data-bs-target="#other" data-foo="bar" data-bs-toggle="dialog">Open &amp; go</button>
            <dialog id="w0" class="dialog">
            <div class="dialog-body">
            body
            </div>
            </dialog>
            HTML,
            self::render(
                [
                    'closeButton' => false,
                    'toggleButton' => [
                        'class' => 'btn-outline',
                        'data' => ['bs-target' => '#other', 'foo' => 'bar'],
                        'label' => 'Open & go',
                        'type' => 'submit',
                    ],
                ],
            ),
            'Configured attributes must be kept and the label encoded.',
        );
        self::assertSameHtml(
            <<<HTML
            <a class="btn-solid theme-primary" href="#w1" data-bs-toggle="dialog"><i>Open</i></a>
            <dialog id="w1" class="dialog">
            <div class="dialog-body">
            body
            </div>
            </dialog>
            HTML,
            self::render(
                [
                    'closeButton' => false,
                    'toggleButton' => ['encode' => false, 'href' => '#w1', 'label' => '<i>Open</i>', 'tag' => 'a'],
                ],
            ),
            'Link must rely on `href` and render a raw label without `type`.',
        );
        self::assertSameHtml(
            <<<HTML
            <span class="btn-solid theme-primary" data-bs-toggle="dialog" data-bs-target="#w2"></span>
            <dialog id="w2" class="dialog">
            <div class="dialog-body">
            body
            </div>
            </dialog>
            HTML,
            self::render(['closeButton' => false, 'toggleButton' => ['label' => null, 'tag' => 'span']]),
            'Non-string label must render empty content.',
        );
    }

    public function testTogglerMethodsEnableToggleButton(): void
    {
        $this->mockWebApplication();

        self::assertSameHtml(
            <<<HTML
            <button type="button" class="btn-solid theme-primary" title="Open" data-bs-toggle="dialog" data-bs-target="#w0">Show</button>
            <dialog id="w0" class="dialog">
            <div class="dialog-body">
            body
            </div>
            </dialog>
            HTML,
            self::render(['closeButton' => false], static fn(Dialog $dialog): Dialog => $dialog->addTogglerAttribute('title', 'Open')),
            'Attribute must enable the default toggle button.',
        );
        self::assertSameHtml(
            <<<HTML
            <button type="button" class="btn-outline" data-bs-toggle="dialog" data-bs-target="#w1">Show</button>
            <dialog id="w1" class="dialog">
            <div class="dialog-body">
            body
            </div>
            </dialog>
            HTML,
            self::render(['closeButton' => false], static fn(Dialog $dialog): Dialog => $dialog->addTogglerClass('btn-outline')),
            'Class must enable the toggle button and replace the default classes.',
        );
        self::assertSameHtml(
            <<<HTML
            <button type="button" class="btn-solid theme-primary" style="margin: 0" data-bs-toggle="dialog" data-bs-target="#w2">Show</button>
            <dialog id="w2" class="dialog">
            <div class="dialog-body">
            body
            </div>
            </dialog>
            HTML,
            self::render(['closeButton' => false], static fn(Dialog $dialog): Dialog => $dialog->addTogglerCssStyle('margin: 0')),
            'Style must enable the default toggle button.',
        );
    }

    public function testTogglerMethodsExtendConfiguredToggleButton(): void
    {
        $this->mockWebApplication();

        self::assertSameHtml(
            <<<HTML
            <button type="button" class="btn-outline btn-lg" title="Open panel" style="color: blue; margin: 0;" data-bs-toggle="dialog" data-bs-target="#w0">Open</button>
            <dialog id="w0" class="dialog">
            <div class="dialog-body">
            body
            </div>
            </dialog>
            HTML,
            self::render(
                [
                    'closeButton' => false, 'toggleButton' => ['class' => 'btn-outline', 'label' => 'Open', 'style' => 'color: red;'],
                ],
                static fn(Dialog $dialog): Dialog => $dialog
                    ->addTogglerClass('btn-lg', null)
                    ->addTogglerCssStyle('color: blue; margin: 0;')
                    ->addTogglerAttribute('title', 'Open panel'),
            ),
            'Configured class and style must be extended and the conflicting property replaced.',
        );
        self::assertSameHtml(
            <<<HTML
            <button type="button" class="btn-outline theme-secondary" data-bs-toggle="dialog" data-bs-target="#w1">Open</button>
            <dialog id="w1" class="dialog">
            <div class="dialog-body">
            body
            </div>
            </dialog>
            HTML,
            self::render(
                ['closeButton' => false, 'toggleButton' => ['label' => 'Open']],
                static fn(Dialog $dialog): Dialog => $dialog->addTogglerClass('btn-outline', 'theme-secondary'),
            ),
            'Added classes must replace the default classes.',
        );
    }

    public function testTogglerOptionsHookIsExtensionPoint(): void
    {
        $this->mockWebApplication();

        ob_start();

        $dialog = new class (['closeButton' => false]) extends Dialog {
            protected function &togglerOptions(): array
            {
                $options = &parent::togglerOptions();

                $options['data-extended'] = 'true';

                return $options;
            }
        };

        $dialog->addTogglerClass('btn-outline');

        echo 'body';
        echo $dialog->run();

        self::assertSameHtml(
            <<<HTML
            <button type="button" class="btn-outline" data-extended="true" data-bs-toggle="dialog" data-bs-target="#w0">Show</button>
            <dialog id="w0" class="dialog">
            <div class="dialog-body">
            body
            </div>
            </dialog>
            HTML,
            (string) ob_get_clean(),
            'Overridden hook must receive the fluent calls.',
        );
    }

    public function testTranslatesLabels(): void
    {
        $this->mockWebApplication(['language' => 'es']);

        self::assertSameHtml(
            <<<HTML
            <button type="button" class="btn-solid theme-primary" data-bs-toggle="dialog" data-bs-target="#w0">Mostrar</button>
            <dialog id="w0" class="dialog">
            <div class="dialog-header">
            <button type="button" class="btn-close" data-bs-dismiss="dialog" aria-label="Cerrar"></button>
            </div>
            <div class="dialog-body">
            body
            </div>
            </dialog>
            HTML,
            self::render(['toggleButton' => []]),
            'Labels must be translated.',
        );
    }

    /**
     * @param array<string, mixed> $config
     * @param (Closure(Dialog): mixed)|null $chain Fluent calls applied to the value returned by `begin()`.
     */
    private static function render(array $config, Closure|null $chain = null): string
    {
        ob_start();

        $dialog = Dialog::begin($config);

        if ($chain !== null) {
            $chain($dialog);
        }

        echo 'body';

        Dialog::end();

        return (string) ob_get_clean();
    }
}
