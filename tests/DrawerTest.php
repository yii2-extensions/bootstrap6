<?php

declare(strict_types=1);

namespace yii2\extensions\bootstrap6\tests;

use Closure;
use PHPUnit\Framework\Attributes\{DataProviderExternal, Group};
use yii\base\InvalidConfigException;
use yii2\extensions\bootstrap6\asset\BootstrapPluginAsset;
use yii2\extensions\bootstrap6\Drawer;
use yii2\extensions\bootstrap6\enum\{Breakpoint, DrawerPlacement};
use yii2\extensions\bootstrap6\exception\Message;
use yii2\extensions\bootstrap6\tests\provider\DrawerProvider;
use yii2\extensions\bootstrap6\tests\support\stub\StringableValue;
use yii2\extensions\bootstrap6\tests\support\TestCase;

use function ob_get_clean;
use function ob_get_level;
use function ob_get_status;
use function ob_start;

/**
 * Unit tests for {@see Drawer} markup, toggle button, data attributes, and plugin registration.
 *
 * {@see DrawerProvider} for test case data providers.
 */
#[Group('widget')]
final class DrawerTest extends TestCase
{
    public function testAriaAttributesFromOptionsAreKept(): void
    {
        $this->mockWebApplication();

        self::assertSameHtml(
            <<<HTML
            <dialog id="w0" class="drawer drawer-start" aria-label="Custom" tabindex="-1">
            <div class="drawer-header">
            <h5 id="w0-label" class="drawer-title">Title</h5>
            </div>
            <div class="drawer-body">
            body
            </div>
            </dialog>
            HTML,
            self::render(['closeButton' => false, 'options' => ['aria' => ['label' => 'Custom']], 'title' => 'Title']),
            'Configured `aria-label` must replace `aria-labelledby`.',
        );
        self::assertSameHtml(
            <<<HTML
            <dialog id="w1" class="drawer drawer-start" aria-labelledby="other" tabindex="-1">
            <div class="drawer-header">
            <h5 id="w1-label" class="drawer-title">Title</h5>
            </div>
            <div class="drawer-body">
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

        Drawer::begin([]);

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

        Drawer::end();

        self::assertSame(
            $level,
            ob_get_level(),
            'Buffer level must be restored.',
        );
        self::assertSameHtml(
            <<<HTML
            <dialog id="w0" class="drawer drawer-start" tabindex="-1">
            <div class="drawer-header">
            <button type="button" class="btn-close" data-bs-dismiss="drawer" aria-label="Close"></button>
            </div>
            <div class="drawer-body">
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
            <dialog id="w0" class="drawer drawer-start" tabindex="-1">
            <div class="custom-body drawer-body">
            body
            </div>
            <div class="custom-footer drawer-footer">
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

        self::render(['clientOptions' => ['scroll' => true]]);

        self::assertSame(
            <<<JS
            import("/assets/js/bootstrap.bundle.js").then((bootstrap) => { bootstrap.Drawer.getOrCreateInstance(document.getElementById("w0"), {"scroll":true}); });
            JS,
            self::registeredJs(self::webView(), 'w0-plugin'),
            'Plugin must be initialized with the options.',
        );
    }

    public function testCloseButtonOptions(): void
    {
        $this->mockWebApplication();

        self::assertSameHtml(
            <<<HTML
            <dialog id="w0" class="drawer drawer-start" tabindex="-1">
            <div class="custom-header drawer-header">
            <button type="button" class="btn-close me-2" data-foo="bar" data-bs-dismiss="drawer" aria-label="Close"></button>
            </div>
            <div class="drawer-body">
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
            <dialog id="w1" class="drawer drawer-start" tabindex="-1">
            <div class="drawer-header">
            <button type="button" class="btn-close" data-bs-dismiss="other" aria-label="Close"></button>
            </div>
            <div class="drawer-body">
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
            <dialog id="custom" class="my-drawer drawer drawer-start" data-foo="bar" data-bs-keyboard="false" tabindex="0">
            <div class="drawer-body">
            body
            </div>
            </dialog>
            HTML,
            self::render(
                [
                    'closeButton' => false,
                    'keyboard' => false,
                    'options' => ['class' => 'my-drawer', 'data' => ['foo' => 'bar'], 'id' => 'custom', 'tabindex' => '0'],
                ],
            ),
            'Configured attributes must be kept.',
        );
    }

    public function testDrawerModifiers(): void
    {
        $this->mockWebApplication();

        self::assertSameHtml(
            <<<HTML
            <dialog id="w0" class="md:drawer drawer-end drawer-sheet" tabindex="-1" data-bs-backdrop="static" data-bs-scroll="true">
            <div class="drawer-body">
            body
            </div>
            </dialog>
            HTML,
            self::render(
                [
                    'backdrop' => 'static',
                    'closeButton' => false,
                    'placement' => DrawerPlacement::END,
                    'responsive' => Breakpoint::MD,
                    'scroll' => true,
                    'sheet' => true,
                ],
            ),
            'Every modifier must be rendered.',
        );
        self::assertSameHtml(
            <<<HTML
            <dialog id="w1" class="lg:drawer drawer-fullscreen" tabindex="-1" data-bs-backdrop="false">
            <div class="drawer-body">
            body
            </div>
            </dialog>
            HTML,
            self::render(
                [
                    'backdrop' => false,
                    'closeButton' => false,
                    'fullscreen' => true,
                    'placement' => 'drawer-bottom',
                    'responsive' => 'lg',
                ],
            ),
            'Fullscreen must replace the placement class.',
        );
        self::assertSameHtml(
            <<<HTML
            <dialog id="w2" class="drawer drawer-top" tabindex="-1">
            <div class="drawer-body">
            body
            </div>
            </dialog>
            HTML,
            self::render(['closeButton' => false, 'placement' => 'drawer-top']),
            'String placement must be rendered verbatim.',
        );
        self::assertSameHtml(
            <<<HTML
            <dialog id="w3" class="drawer" tabindex="-1">
            <div class="drawer-body">
            body
            </div>
            </dialog>
            HTML,
            self::render(['closeButton' => false, 'placement' => null]),
            '`null` placement must omit the placement class.',
        );
    }

    public function testInvalidBackdropLeavesNoOpenBuffer(): void
    {
        $this->mockWebApplication();

        $level = ob_get_level();
        $caught = false;

        try {
            Drawer::begin(['backdrop' => 'none']);
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

        $drawer = new class (['closeButton' => false, 'footer' => 'Footer']) extends Drawer {
            protected function drawerOptions(): array
            {
                return [...parent::drawerOptions(), 'data-extended' => 'true'];
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
        echo $drawer->run();

        self::assertSameHtml(
            <<<HTML
            <!-- toggle -->
            <dialog id="w0" class="drawer drawer-start" tabindex="-1" data-extended="true">
            <!-- header -->
            <div class="drawer-body">
            body
            </div><!-- footer -->
            <div class="drawer-footer">
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
            <dialog id="w0" class="drawer drawer-start" tabindex="-1">
            <div class="drawer-header">
            <button type="button" class="btn-close" data-bs-dismiss="drawer" aria-label="Close"></button>
            </div>
            <div class="drawer-body">
            body
            </div>
            </dialog>
            HTML,
            self::render([]),
            'Default drawer must render a header with the close button only.',
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
            self::render(['footer' => '<b>F</b>', 'options' => ['id' => 'd'], 'title' => 'T & t']),
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
            <dialog id="w0" class="drawer drawer-start" tabindex="-1">
            <div class="drawer-body">
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
            <dialog id="w0" class="drawer drawer-start" tabindex="-1" aria-labelledby="w0-label">
            <div class="drawer-header">
            <h5 id="w0-label" class="drawer-title">Drawer &lt;title&gt;</h5>
            <button type="button" class="btn-close" data-bs-dismiss="drawer" aria-label="Close"></button>
            </div>
            <div class="drawer-body">
            body
            </div>
            <div class="drawer-footer">
            Footer
            </div>
            </dialog>
            HTML,
            self::render(['footer' => 'Footer', 'title' => 'Drawer <title>']),
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

        Drawer::begin(['backdrop' => 'none']);
    }

    /**
     * @param array<string, mixed> $config
     */
    #[DataProviderExternal(DrawerProvider::class, 'invalidEnumValueCases')]
    public function testThrowInvalidConfigExceptionForInvalidEnumValue(array $config, string $message): void
    {
        $this->mockWebApplication();

        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage(
            $message,
        );

        new Drawer([...$config]);
    }

    public function testTitleOptions(): void
    {
        $this->mockWebApplication();

        self::assertSameHtml(
            <<<HTML
            <dialog id="w0" class="drawer drawer-start" tabindex="-1" aria-labelledby="custom-title">
            <div class="drawer-header">
            <h2 id="custom-title" class="fs-lg drawer-title"><b>Title</b></h2>
            </div>
            <div class="drawer-body">
            body
            </div>
            </dialog>
            HTML,
            self::render(
                [
                    'closeButton' => false,
                    'encodeTitle' => false,
                    'title' => '<b>Title</b>',
                    'titleOptions' => ['class' => 'fs-lg', 'id' => 'custom-title', 'tag' => 'h2'],
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
            <button type="button" class="btn-solid theme-primary" data-bs-toggle="drawer" data-bs-target="#w0" aria-controls="w0">Show</button>
            <dialog id="w0" class="drawer drawer-start" tabindex="-1">
            <div class="drawer-body">
            body
            </div>
            </dialog>
            HTML,
            self::render(['closeButton' => false, 'toggleButton' => []]),
            'Default toggle button must target the drawer.',
        );
    }

    public function testToggleButtonOptions(): void
    {
        $this->mockWebApplication();

        self::assertSameHtml(
            <<<HTML
            <button type="submit" class="btn-outline" aria-controls="other" data-bs-target="#other" data-foo="bar" data-bs-toggle="drawer">Open &amp; go</button>
            <dialog id="w0" class="drawer drawer-start" tabindex="-1">
            <div class="drawer-body">
            body
            </div>
            </dialog>
            HTML,
            self::render(
                [
                    'closeButton' => false,
                    'toggleButton' => [
                        'aria' => ['controls' => 'other'],
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
            <a class="btn-solid theme-primary" href="#w1" role="button" data-bs-toggle="drawer" aria-controls="w1"><i>Open</i></a>
            <dialog id="w1" class="drawer drawer-start" tabindex="-1">
            <div class="drawer-body">
            body
            </div>
            </dialog>
            HTML,
            self::render(
                [
                    'closeButton' => false,
                    'toggleButton' => [
                        'encode' => false,
                        'href' => '#w1',
                        'label' => '<i>Open</i>',
                        'role' => 'button',
                        'tag' => 'a',
                    ],
                ],
            ),
            'Link must rely on `href` and render a raw label without `type`.',
        );
        self::assertSameHtml(
            <<<HTML
            <span class="btn-solid theme-primary" data-bs-toggle="drawer" data-bs-target="#w2" aria-controls="w2"></span>
            <dialog id="w2" class="drawer drawer-start" tabindex="-1">
            <div class="drawer-body">
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
            <button type="button" class="btn-solid theme-primary" title="Open" data-bs-toggle="drawer" data-bs-target="#w0" aria-controls="w0">Show</button>
            <dialog id="w0" class="drawer drawer-start" tabindex="-1">
            <div class="drawer-body">
            body
            </div>
            </dialog>
            HTML,
            self::render(['closeButton' => false], static fn(Drawer $drawer): Drawer => $drawer->addTogglerAttribute('title', 'Open')),
            'Attribute must enable the default toggle button.',
        );
        self::assertSameHtml(
            <<<HTML
            <button type="button" class="btn-outline" data-bs-toggle="drawer" data-bs-target="#w1" aria-controls="w1">Show</button>
            <dialog id="w1" class="drawer drawer-start" tabindex="-1">
            <div class="drawer-body">
            body
            </div>
            </dialog>
            HTML,
            self::render(['closeButton' => false], static fn(Drawer $drawer): Drawer => $drawer->addTogglerClass('btn-outline')),
            'Class must enable the toggle button and replace the default classes.',
        );
        self::assertSameHtml(
            <<<HTML
            <button type="button" class="btn-solid theme-primary" style="margin: 0" data-bs-toggle="drawer" data-bs-target="#w2" aria-controls="w2">Show</button>
            <dialog id="w2" class="drawer drawer-start" tabindex="-1">
            <div class="drawer-body">
            body
            </div>
            </dialog>
            HTML,
            self::render(['closeButton' => false], static fn(Drawer $drawer): Drawer => $drawer->addTogglerCssStyle('margin: 0')),
            'Style must enable the default toggle button.',
        );
    }

    public function testTogglerMethodsExtendConfiguredToggleButton(): void
    {
        $this->mockWebApplication();

        self::assertSameHtml(
            <<<HTML
            <button type="button" class="btn-outline btn-lg" title="Open panel" style="color: blue; margin: 0;" data-bs-toggle="drawer" data-bs-target="#w0" aria-controls="w0">Open</button>
            <dialog id="w0" class="drawer drawer-start" tabindex="-1">
            <div class="drawer-body">
            body
            </div>
            </dialog>
            HTML,
            self::render(
                [
                    'closeButton' => false, 'toggleButton' => ['class' => 'btn-outline', 'label' => 'Open', 'style' => 'color: red;'],
                ],
                static fn(Drawer $drawer): Drawer => $drawer
                    ->addTogglerClass('btn-lg', null)
                    ->addTogglerCssStyle('color: blue; margin: 0;')
                    ->addTogglerAttribute('title', 'Open panel'),
            ),
            'Configured class and style must be extended and the conflicting property replaced.',
        );
        self::assertSameHtml(
            <<<HTML
            <button type="button" class="btn-outline theme-secondary" data-bs-toggle="drawer" data-bs-target="#w1" aria-controls="w1">Open</button>
            <dialog id="w1" class="drawer drawer-start" tabindex="-1">
            <div class="drawer-body">
            body
            </div>
            </dialog>
            HTML,
            self::render(
                ['closeButton' => false, 'toggleButton' => ['label' => 'Open']],
                static fn(Drawer $drawer): Drawer => $drawer->addTogglerClass('btn-outline', 'theme-secondary'),
            ),
            'Added classes must replace the default classes.',
        );
    }

    public function testTogglerOptionsHookIsExtensionPoint(): void
    {
        $this->mockWebApplication();

        ob_start();

        $drawer = new class (['closeButton' => false]) extends Drawer {
            protected function &togglerOptions(): array
            {
                $options = &parent::togglerOptions();

                $options['data-extended'] = 'true';

                return $options;
            }
        };

        $drawer->addTogglerClass('btn-outline');

        echo 'body';
        echo $drawer->run();

        self::assertSameHtml(
            <<<HTML
            <button type="button" class="btn-outline" data-extended="true" data-bs-toggle="drawer" data-bs-target="#w0" aria-controls="w0">Show</button>
            <dialog id="w0" class="drawer drawer-start" tabindex="-1">
            <div class="drawer-body">
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
            <button type="button" class="btn-solid theme-primary" data-bs-toggle="drawer" data-bs-target="#w0" aria-controls="w0">Mostrar</button>
            <dialog id="w0" class="drawer drawer-start" tabindex="-1">
            <div class="drawer-header">
            <button type="button" class="btn-close" data-bs-dismiss="drawer" aria-label="Cerrar"></button>
            </div>
            <div class="drawer-body">
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
     * @param (Closure(Drawer): mixed)|null $chain Fluent calls applied to the value returned by `begin()`.
     */
    private static function render(array $config, Closure|null $chain = null): string
    {
        ob_start();

        $drawer = Drawer::begin($config);

        if ($chain !== null) {
            $chain($drawer);
        }

        echo 'body';

        Drawer::end();

        return (string) ob_get_clean();
    }
}
