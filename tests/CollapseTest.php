<?php

declare(strict_types=1);

namespace yii2\extensions\bootstrap6\tests;

use Closure;
use PHPUnit\Framework\Attributes\Group;
use yii2\extensions\bootstrap6\asset\BootstrapPluginAsset;
use yii2\extensions\bootstrap6\Collapse;
use yii2\extensions\bootstrap6\tests\support\TestCase;

use function ob_get_clean;
use function ob_get_level;
use function ob_get_status;
use function ob_start;

/**
 * Unit tests for {@see Collapse} markup, toggle button, and plugin registration.
 */
#[Group('widget')]
final class CollapseTest extends TestCase
{
    public function testBeginBuffersContentUntilEnd(): void
    {
        $this->mockWebApplication();

        ob_start();

        $level = ob_get_level();

        Collapse::begin([]);

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

        Collapse::end();

        self::assertSame(
            $level,
            ob_get_level(),
            'Buffer level must be restored.',
        );
        self::assertSameHtml(
            <<<HTML
            <div id="w0" class="collapse">
            body
            </div>
            HTML,
            (string) ob_get_clean(),
            'Captured content must be wrapped by the markup.',
        );
    }

    public function testClientOptionsInitializePlugin(): void
    {
        $this->mockWebApplication();

        self::render(['clientOptions' => ['parent' => '#accordion']]);

        self::assertSame(
            <<<JS
            import("/assets/js/bootstrap.bundle.js").then((bootstrap) => { bootstrap.Collapse.getOrCreateInstance(document.getElementById("w0"), {"parent":"#accordion"}); });
            JS,
            self::registeredJs(self::webView(), 'w0-plugin'),
            'Plugin must be initialized with the options.',
        );
    }

    public function testCollapseModifiers(): void
    {
        $this->mockWebApplication();

        self::assertSameHtml(
            <<<HTML
            <section id="w0" class="my-collapse collapse collapse-horizontal show" data-foo="bar" data-bs-parent="#group">
            body
            </section>
            HTML,
            self::render(
                [
                    'horizontal' => true,
                    'options' => ['class' => 'my-collapse', 'data' => ['foo' => 'bar']],
                    'parent' => '#group',
                    'show' => true,
                    'tagName' => 'section',
                ],
            ),
            'Every modifier must be rendered.',
        );
    }

    public function testProtectedMethodsAreExtensionPoints(): void
    {
        $this->mockWebApplication();

        ob_start();

        $collapse = new class extends Collapse {
            protected function collapseOptions(): array
            {
                return [...parent::collapseOptions(), 'data-extended' => 'true'];
            }

            protected function renderToggleButton(): string
            {
                return '<!-- toggle button -->' . parent::renderToggleButton();
            }
        };

        echo 'body';
        echo $collapse->run();

        self::assertSameHtml(
            <<<HTML
            <!-- toggle button -->
            <div id="w0" class="collapse" data-extended="true">
            body
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
            <div id="w0" class="collapse">
            body
            </div>
            HTML,
            self::render([]),
            'Default collapse must render the collapsible element only.',
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

    public function testToggleButton(): void
    {
        $this->mockWebApplication();

        self::assertSameHtml(
            <<<HTML
            <button type="button" class="btn-solid theme-primary" data-bs-toggle="collapse" data-bs-target="#w0" aria-expanded="false" aria-controls="w0">Show</button>
            <div id="w0" class="collapse">
            body
            </div>
            HTML,
            self::render(['toggleButton' => []]),
            'Default toggle button must target the collapsed element.',
        );
        self::assertSameHtml(
            <<<HTML
            <button type="button" class="btn-solid theme-primary" data-bs-toggle="collapse" data-bs-target="#w1" aria-expanded="true" aria-controls="w1">Show</button>
            <div id="w1" class="collapse show">
            body
            </div>
            HTML,
            self::render(['show' => true, 'toggleButton' => []]),
            'Expanded state must be reflected.',
        );
    }

    public function testToggleButtonOptions(): void
    {
        $this->mockWebApplication();

        self::assertSameHtml(
            <<<HTML
            <button type="submit" class="btn-outline" aria-controls="other" aria-expanded="false" data-bs-target=".multi" data-foo="bar" data-bs-toggle="collapse">Open &amp; go</button>
            <div id="w0" class="collapse">
            body
            </div>
            HTML,
            self::render(
                [
                    'toggleButton' => [
                        'aria' => ['controls' => 'other'],
                        'class' => 'btn-outline',
                        'data' => ['bs-target' => '.multi', 'foo' => 'bar'],
                        'label' => 'Open & go',
                        'type' => 'submit',
                    ],
                ],
            ),
            'Configured attributes must be kept and the label encoded.',
        );
        self::assertSameHtml(
            <<<HTML
            <a class="btn-solid theme-primary" href="#w1" role="button" data-bs-toggle="collapse" aria-expanded="false" aria-controls="w1"><i>Open</i></a>
            <div id="w1" class="collapse">
            body
            </div>
            HTML,
            self::render(
                [
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
            <span class="btn-solid theme-primary" data-bs-toggle="collapse" data-bs-target="#w2" aria-expanded="false" aria-controls="w2"></span>
            <div id="w2" class="collapse">
            body
            </div>
            HTML,
            self::render(['toggleButton' => ['label' => null, 'tag' => 'span']]),
            'Non-string label must render empty content.',
        );
    }

    public function testTogglerMethodsEnableToggleButton(): void
    {
        $this->mockWebApplication();

        self::assertSameHtml(
            <<<HTML
            <button type="button" class="btn-solid theme-primary" title="Open" data-bs-toggle="collapse" data-bs-target="#w0" aria-expanded="false" aria-controls="w0">Show</button>
            <div id="w0" class="collapse">
            body
            </div>
            HTML,
            self::render([], static fn(Collapse $collapse): Collapse => $collapse->addTogglerAttribute('title', 'Open')),
            'Attribute must enable the default toggle button.',
        );
        self::assertSameHtml(
            <<<HTML
            <button type="button" class="btn-outline" data-bs-toggle="collapse" data-bs-target="#w1" aria-expanded="false" aria-controls="w1">Show</button>
            <div id="w1" class="collapse">
            body
            </div>
            HTML,
            self::render([], static fn(Collapse $collapse): Collapse => $collapse->addTogglerClass('btn-outline')),
            'Class must enable the toggle button and replace the default classes.',
        );
        self::assertSameHtml(
            <<<HTML
            <button type="button" class="btn-solid theme-primary" style="margin: 0" data-bs-toggle="collapse" data-bs-target="#w2" aria-expanded="false" aria-controls="w2">Show</button>
            <div id="w2" class="collapse">
            body
            </div>
            HTML,
            self::render([], static fn(Collapse $collapse): Collapse => $collapse->addTogglerCssStyle('margin: 0')),
            'Style must enable the default toggle button.',
        );
    }

    public function testTogglerMethodsExtendConfiguredToggleButton(): void
    {
        $this->mockWebApplication();

        self::assertSameHtml(
            <<<HTML
            <button type="button" class="btn-outline btn-lg" title="Open panel" style="color: blue; margin: 0;" data-bs-toggle="collapse" data-bs-target="#w0" aria-expanded="false" aria-controls="w0">Open</button>
            <div id="w0" class="collapse">
            body
            </div>
            HTML,
            self::render(
                [
                    'toggleButton' => ['class' => 'btn-outline', 'label' => 'Open', 'style' => 'color: red;'],
                ],
                static fn(Collapse $collapse): Collapse => $collapse
                    ->addTogglerClass('btn-lg', null)
                    ->addTogglerCssStyle('color: blue; margin: 0;')
                    ->addTogglerAttribute('title', 'Open panel'),
            ),
            'Configured class and style must be extended and the conflicting property replaced.',
        );
        self::assertSameHtml(
            <<<HTML
            <button type="button" class="btn-outline theme-secondary" data-bs-toggle="collapse" data-bs-target="#w1" aria-expanded="false" aria-controls="w1">Open</button>
            <div id="w1" class="collapse">
            body
            </div>
            HTML,
            self::render(
                ['toggleButton' => ['label' => 'Open']],
                static fn(Collapse $collapse): Collapse => $collapse->addTogglerClass('btn-outline', 'theme-secondary'),
            ),
            'Added classes must replace the default classes.',
        );
    }

    public function testTogglerOptionsHookIsExtensionPoint(): void
    {
        $this->mockWebApplication();

        ob_start();

        $collapse = new class extends Collapse {
            protected function &togglerOptions(): array
            {
                $options = &parent::togglerOptions();

                $options['data-extended'] = 'true';

                return $options;
            }
        };

        $collapse->addTogglerClass('btn-outline');

        echo 'body';
        echo $collapse->run();

        self::assertSameHtml(
            <<<HTML
            <button type="button" class="btn-outline" data-extended="true" data-bs-toggle="collapse" data-bs-target="#w0" aria-expanded="false" aria-controls="w0">Show</button>
            <div id="w0" class="collapse">
            body
            </div>
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
            <button type="button" class="btn-solid theme-primary" data-bs-toggle="collapse" data-bs-target="#w0" aria-expanded="false" aria-controls="w0">Mostrar</button>
            <div id="w0" class="collapse">
            body
            </div>
            HTML,
            self::render(['toggleButton' => []]),
            'Label must be translated.',
        );
    }

    /**
     * @param array<string, mixed> $config
     * @param (Closure(Collapse): mixed)|null $chain Fluent calls applied to the value returned by `begin()`.
     */
    private static function render(array $config, Closure|null $chain = null): string
    {
        ob_start();

        $collapse = Collapse::begin($config);

        if ($chain !== null) {
            $chain($collapse);
        }

        echo 'body';

        Collapse::end();

        return (string) ob_get_clean();
    }
}
