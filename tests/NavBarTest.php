<?php

declare(strict_types=1);

namespace yii2\extensions\bootstrap6\tests;

use Closure;
use PHPUnit\Framework\Attributes\Group;
use yii\base\InvalidConfigException;
use yii2\extensions\bootstrap6\asset\{BootstrapAsset, BootstrapPluginAsset};
use yii2\extensions\bootstrap6\enum\{Breakpoint, DrawerPlacement};
use yii2\extensions\bootstrap6\exception\Message;
use yii2\extensions\bootstrap6\NavBar;
use yii2\extensions\bootstrap6\tests\support\stub\StringableValue;
use yii2\extensions\bootstrap6\tests\support\TestCase;

use function ob_get_clean;
use function ob_get_level;
use function ob_get_status;
use function ob_start;

/**
 * Unit tests for {@see NavBar} brand, toggler, drawer, container, and expand breakpoint rendering.
 */
#[Group('widget')]
final class NavBarTest extends TestCase
{
    public function testBeginBuffersContentUntilEnd(): void
    {
        $this->mockWebApplication();

        ob_start();

        $level = ob_get_level();

        NavBar::begin(['drawerOptions' => false, 'expand' => false, 'renderInnerContainer' => false]);

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

        NavBar::end();

        self::assertSame(
            $level,
            ob_get_level(),
            'Buffer level must be restored.',
        );
        self::assertSameHtml(
            <<<HTML
            <nav id="w0" class="navbar">
            body</nav>
            HTML,
            (string) ob_get_clean(),
            'Captured content must be wrapped by the markup.',
        );
    }

    public function testRenderBrandImageAndLabel(): void
    {
        $this->mockWebApplication();

        self::assertSameHtml(
            <<<HTML
            <nav id="w0" class="navbar">
            <a class="logo navbar-brand" href="/index.php"><img src="/logo.svg" width="30" alt="Logo"> &lt;Brand&gt;</a>
            </nav>
            HTML,
            self::render(
                [
                    'brandImage' => '/logo.svg',
                    'brandImageOptions' => ['alt' => 'Logo', 'width' => 30],
                    'brandLabel' => '<Brand>',
                    'brandOptions' => ['class' => 'logo'],
                    'drawerOptions' => false,
                    'expand' => false,
                    'renderInnerContainer' => false,
                ],
            ),
            'Brand must link the image and the encoded label to the home URL.',
        );
    }

    public function testRenderBrandWithoutLink(): void
    {
        $this->mockWebApplication();

        self::assertSameHtml(
            <<<HTML
            <nav id="w0" class="navbar">
            <span class="navbar-brand"><b>Brand</b></span>
            </nav>
            <nav id="w1" class="navbar">
            <a class="navbar-brand" href="/index.php?r=site%2Findex"><img src="/logo.svg" alt=""></a>
            </nav>
            HTML,
            self::render(
                [
                    'brandLabel' => '<b>Brand</b>',
                    'brandUrl' => null,
                    'drawerOptions' => false,
                    'encodeBrandLabel' => false,
                    'expand' => false,
                    'renderInnerContainer' => false,
                ],
            )
            . "\n"
            . self::render(
                [
                    'brandImage' => '/logo.svg',
                    'brandUrl' => ['/site/index'],
                    'drawerOptions' => false,
                    'expand' => false,
                    'renderInnerContainer' => false,
                ],
            ),
            'Brand must render a `<span>` without URL and resolve array routes.',
        );
    }

    public function testRenderDefaults(): void
    {
        $this->mockWebApplication();

        self::assertSameHtml(
            <<<HTML
            <nav id="w0" class="navbar lg:navbar-expand">
            <div class="container-fluid">
            <button type="button" class="btn-icon navbar-toggler" data-bs-toggle="drawer" data-bs-target="#w0-drawer" aria-controls="w0-drawer" aria-expanded="false" aria-label="Toggle navigation"><span class="navbar-toggler-icon" aria-hidden="true"></span></button>
            <dialog id="w0-drawer" class="drawer drawer-end" tabindex="-1" aria-labelledby="w0-drawer-label">
            <div class="drawer-header">
            <h5 id="w0-drawer-label" class="drawer-title">Menu</h5>
            <button type="button" class="btn-close" data-bs-dismiss="drawer" aria-label="Close"></button>
            </div>
            <div class="drawer-body">
            <ul class="navbar-nav"></ul></div>
            </dialog>
            </div>
            </nav>
            HTML,
            self::render([], '<ul class="navbar-nav"></ul>'),
            'Content must be rendered inside the drawer body.',
        );
        self::assertArrayHasKey(
            BootstrapPluginAsset::class,
            self::webView()->assetBundles,
            'Drawer toggler needs the plugin bundle.',
        );
        self::assertSame(
            [],
            self::webView()->js,
            'Data API must not need an inline init.',
        );
    }

    public function testRenderDrawerOptions(): void
    {
        $this->mockWebApplication();

        self::assertSameHtml(
            <<<HTML
            <header id="w0" class="bg-1 navbar md:navbar-expand">
            <div class="container">
            <a class="navbar-brand" href="/index.php">Brand</a>
            <button type="button" class="toggler btn-icon navbar-toggler" data-bs-toggle="drawer" data-bs-target="#side" aria-controls="side" aria-expanded="false" aria-label="Open">=</button>
            <dialog id="side" class="drawer-translucent drawer drawer-start" tabindex="0" aria-labelledby="side-label">
            <div class="drawer-header">
            <h5 id="side-label" class="drawer-title">&lt;Main&gt;</h5>
            <button type="button" class="btn-close" data-bs-dismiss="drawer" aria-label="Close"></button>
            </div>
            <div class="mb-2 drawer-body">
            </div>
            </dialog>
            </div>
            </header>
            HTML,
            self::render(
                [
                    'brandLabel' => 'Brand',
                    'drawerOptions' => [
                        'bodyOptions' => ['class' => 'mb-2'],
                        'options' => ['class' => 'drawer-translucent', 'id' => 'side', 'tabindex' => '0'],
                        'placement' => DrawerPlacement::START,
                        'title' => '<Main>',
                    ],
                    'expand' => Breakpoint::MD,
                    'innerContainerOptions' => ['class' => 'container'],
                    'options' => ['class' => 'bg-1', 'tag' => 'header'],
                    'screenReaderToggleText' => 'Open',
                    'togglerContent' => '=',
                    'togglerOptions' => ['class' => 'toggler'],
                ],
            ),
            'Drawer, toggler, and container options must be applied.',
        );
    }

    public function testRenderExpandValues(): void
    {
        $this->mockWebApplication();

        self::assertSameHtml(
            <<<HTML
            <nav id="w0" class="navbar navbar-expand">
            </nav>
            <nav id="w1" class="navbar sm:navbar-expand">
            </nav>
            <nav id="w2" class="navbar 2xl:navbar-expand">
            </nav>
            HTML,
            self::render(['drawerOptions' => false, 'expand' => null, 'renderInnerContainer' => false])
            . "\n"
            . self::render(['drawerOptions' => false, 'expand' => 'sm', 'renderInnerContainer' => false])
            . "\n"
            . self::render(['drawerOptions' => false, 'expand' => Breakpoint::XXL, 'renderInnerContainer' => false]),
            'Expand class must follow the breakpoint.',
        );
    }

    public function testRenderProtectedMethodsAreOverridable(): void
    {
        $this->mockWebApplication();

        ob_start();

        $navBar = new class (['expand' => false, 'renderInnerContainer' => false]) extends NavBar {
            protected function renderBrand(): string
            {
                return '<!--brand-->' . parent::renderBrand();
            }

            protected function renderDrawerBegin(): string
            {
                return '<!--drawer-->';
            }

            protected function renderToggler(): string
            {
                return '<!--toggler-->';
            }
        };

        $html = ob_get_clean() . $navBar->run();

        self::assertSameHtml(
            <<<HTML
            <nav id="w0" class="navbar">
            <!--brand-->
            <!--toggler-->
            <!--drawer-->
            </div>
            </dialog>
            </nav>
            HTML,
            $html,
            'Subclass overrides must take effect.',
        );
    }

    public function testRenderStringableContent(): void
    {
        $this->mockWebApplication();

        self::assertSame(
            self::render([
                'brandLabel' => 'Brand & co',
                'options' => ['id' => 'n'],
                'screenReaderToggleText' => 'Open',
                'togglerContent' => '<i></i>',
            ]),
            self::render([
                'brandLabel' => new StringableValue('Brand & co'),
                'options' => ['id' => 'n'],
                'screenReaderToggleText' => new StringableValue('Open'),
                'togglerContent' => new StringableValue('<i></i>'),
            ]),
            'Stringable values must render like their `string`.',
        );
    }

    public function testRenderWithoutDrawer(): void
    {
        $this->mockWebApplication();

        self::assertSameHtml(
            <<<HTML
            <nav id="w0" class="navbar lg:navbar-expand">
            <div class="container-fluid">
            <span class="navbar-text">Text</span></div>
            </nav>
            HTML,
            self::render(['drawerOptions' => false], '<span class="navbar-text">Text</span>'),
            'Content must be rendered without toggler and drawer.',
        );
        self::assertArrayHasKey(
            BootstrapAsset::class,
            self::webView()->assetBundles,
            'CSS bundle must be registered.',
        );
        self::assertArrayNotHasKey(
            BootstrapPluginAsset::class,
            self::webView()->assetBundles,
            'Plugin bundle must not be registered.',
        );
    }

    public function testThrowInvalidConfigExceptionForInvalidDrawerPlacement(): void
    {
        $this->mockWebApplication();

        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage(
            Message::INVALID_VALUE->getMessage(
                'left',
                'placement',
                'drawer-bottom, drawer-end, drawer-start, drawer-top',
            ),
        );

        NavBar::widget(['drawerOptions' => ['placement' => 'left']]);
    }

    public function testThrowInvalidConfigExceptionForInvalidExpand(): void
    {
        $this->mockWebApplication();

        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage(
            Message::INVALID_VALUE->getMessage('xxl', 'expand', 'lg, md, sm, xl, 2xl'),
        );

        new NavBar(['expand' => 'xxl']);
    }

    public function testTogglerMethodsExtendTogglerOptions(): void
    {
        $this->mockWebApplication();

        self::assertSameHtml(
            <<<HTML
            <nav id="w0" class="navbar lg:navbar-expand">
            <button type="button" class="my-toggler ms-auto btn-icon navbar-toggler" title="Menu" style="color: blue; margin: 0;" data-bs-toggle="drawer" data-bs-target="#w0-drawer" aria-controls="w0-drawer" aria-expanded="false" aria-label="Toggle navigation"><span class="navbar-toggler-icon" aria-hidden="true"></span></button>
            <dialog id="w0-drawer" class="drawer drawer-end" tabindex="-1" aria-labelledby="w0-drawer-label">
            <div class="drawer-header">
            <h5 id="w0-drawer-label" class="drawer-title">Menu</h5>
            <button type="button" class="btn-close" data-bs-dismiss="drawer" aria-label="Close"></button>
            </div>
            <div class="drawer-body">
            </div>
            </dialog>
            </nav>
            HTML,
            self::render(
                [
                    'renderInnerContainer' => false,
                    'togglerOptions' => ['class' => 'my-toggler', 'style' => 'color: red;'],
                ],
                '',
                static fn(NavBar $navBar): NavBar => $navBar
                    ->addTogglerClass('ms-auto', null)
                    ->addTogglerCssStyle('color: blue; margin: 0;')
                    ->addTogglerAttribute('title', 'Menu'),
            ),
            'Fluent toggler attributes must be merged before the fixed classes.',
        );
    }

    public function testTogglerOptionsHookIsExtensionPoint(): void
    {
        $this->mockWebApplication();

        ob_start();

        $navBar = new class (['renderInnerContainer' => false]) extends NavBar {
            protected function &togglerOptions(): array
            {
                $options = &parent::togglerOptions();

                $options['data-extended'] = 'true';

                return $options;
            }
        };

        $navBar->addTogglerClass('btn-outline');

        echo 'body';
        echo $navBar->run();

        self::assertSameHtml(
            <<<HTML
            <nav id="w0" class="navbar lg:navbar-expand">
            <button type="button" class="btn-outline btn-icon navbar-toggler" data-extended="true" data-bs-toggle="drawer" data-bs-target="#w0-drawer" aria-controls="w0-drawer" aria-expanded="false" aria-label="Toggle navigation"><span class="navbar-toggler-icon" aria-hidden="true"></span></button>
            <dialog id="w0-drawer" class="drawer drawer-end" tabindex="-1" aria-labelledby="w0-drawer-label">
            <div class="drawer-header">
            <h5 id="w0-drawer-label" class="drawer-title">Menu</h5>
            <button type="button" class="btn-close" data-bs-dismiss="drawer" aria-label="Close"></button>
            </div>
            <div class="drawer-body">
            body</div>
            </dialog>
            </nav>
            HTML,
            (string) ob_get_clean(),
            'Overridden hook must receive the fluent calls.',
        );
    }

    /**
     * @param array<string, mixed> $config
     * @param (Closure(NavBar): mixed)|null $chain Fluent calls applied to the value returned by `begin()`.
     */
    private static function render(array $config, string $content = '', Closure|null $chain = null): string
    {
        ob_start();

        $navBar = NavBar::begin($config);

        if ($chain !== null) {
            $chain($navBar);
        }

        echo $content;

        NavBar::end();

        return (string) ob_get_clean();
    }
}
