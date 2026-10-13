<?php

declare(strict_types=1);

namespace yii2\extensions\bootstrap6\tests\asset;

use PHPUnit\Framework\Attributes\Group;
use Yii;
use yii\base\InvalidConfigException;
use yii\web\View;
use yii2\extensions\bootstrap6\asset\{
    AbstractNpmAsset,
    BootstrapAsset,
    BootstrapIconAsset,
    BootstrapPluginAsset,
};
use yii2\extensions\bootstrap6\exception\Message;
use yii2\extensions\bootstrap6\tests\support\stub\{ExtensionlessNpmAsset, PlainAsset};
use yii2\extensions\bootstrap6\tests\support\TestCase;

use function dirname;
use function file_put_contents;
use function is_dir;
use function mkdir;
use function ob_end_clean;
use function ob_start;
use function str_replace;
use function strlen;
use function substr;

/**
 * Unit tests for {@see AbstractNpmAsset} delivery modes and the Bootstrap asset bundles.
 */
#[Group('asset')]
final class AssetBundleTest extends TestCase
{
    private const string CDN_BASE = 'https://cdn.jsdelivr.net/npm/bootstrap@6.0.0-alpha.1';

    public function testApplicationConfigurationWinsOverRegisterWith(): void
    {
        $this->mockWebApplication(
            ['components' => ['assetManager' => ['bundles' => [BootstrapPluginAsset::class => ['cdn' => true]]]]],
        );

        $bundle = BootstrapPluginAsset::registerWith($this->view());

        self::assertTrue(
            $bundle->cdn,
            'Application bundle configuration must take precedence.',
        );
    }

    public function testBundlesKeepDefaultModeWhenAssetManagerBundlesAreDisabled(): void
    {
        $this->mockWebApplication(['components' => ['assetManager' => ['bundles' => false]]]);

        $bundle = BootstrapPluginAsset::registerWith($this->view(), true);

        self::assertFalse(
            $bundle->cdn,
            'Disabled bundle configuration must not be seeded.',
        );
    }

    public function testCdnBundleLinksInstalledVersion(): void
    {
        $this->mockWebApplication();

        $bundle = new BootstrapPluginAsset(['cdn' => true, 'minified' => true]);

        self::assertNull(
            $bundle->sourcePath,
            'CDN mode must not publish local files.',
        );
        self::assertSame(
            [self::CDN_BASE . '/dist/js/bootstrap.bundle.min.js'],
            $bundle->js,
            'Script URL must pin the installed version.',
        );
        self::assertSame(
            [],
            $bundle->css,
            'Plugin bundle ships no stylesheet.',
        );
    }

    public function testCdnBundleWithoutDirectoryLinksPackageRoot(): void
    {
        $this->mockWebApplication();

        $bundle = new ExtensionlessNpmAsset(['cdn' => true]);

        self::assertSame(
            [self::CDN_BASE . '/dist/js/bootstrap.bundle.js', self::CDN_BASE . '/LICENSE'],
            $bundle->js,
            'Files must be joined to the package root with a single slash.',
        );
    }

    public function testCdnUrlTrailingSlashIsTrimmed(): void
    {
        $this->mockWebApplication();

        $bundle = new BootstrapAsset(['cdn' => true, 'cdnUrl' => 'https://unpkg.com/']);

        self::assertSame(
            ['https://unpkg.com/bootstrap@6.0.0-alpha.1/dist/css/bootstrap.css'],
            $bundle->css,
            'Custom CDN must be joined with a single slash.',
        );
    }

    public function testDependenciesAreExposed(): void
    {
        $this->mockWebApplication();

        $bundle = new BootstrapPluginAsset();

        self::assertSame(
            [BootstrapAsset::class],
            $bundle->depends,
            'Plugin bundle must depend on the stylesheet bundle.',
        );
        self::assertSame(
            [BootstrapAsset::class],
            BootstrapPluginAsset::dependencies(),
            'Plugin dependencies must be exposed.',
        );
        self::assertSame(
            [],
            BootstrapAsset::dependencies(),
            'Stylesheet bundle must not depend on other bundles.',
        );
    }

    public function testExposeGlobalAssignsModuleNamespaceToWindow(): void
    {
        $this->mockWebApplication(
            [
                'components' => [
                    'assetManager' => ['bundles' => [BootstrapPluginAsset::class => ['exposeGlobal' => true]]],
                ],
            ],
        );

        $view = $this->view();

        BootstrapPluginAsset::register($view);

        self::endBody($view);

        self::assertSame(
            <<<JS
            import("/assets/js/bootstrap.bundle.js").then((bootstrap) => { window.bootstrap = bootstrap; });
            JS,
            self::registeredJs($view, 'bootstrap6-global'),
            'Global assignment must import the registered module.',
        );
    }

    public function testExposeGlobalIsDisabledByDefault(): void
    {
        $this->mockWebApplication();

        $view = $this->view();

        BootstrapPluginAsset::register($view);

        self::endBody($view);

        self::assertSame(
            [],
            $view->js,
            'No inline script must be registered.',
        );
    }

    public function testIconAssetPublishesStylesheetAndFonts(): void
    {
        $this->mockWebApplication();

        $bundle = BootstrapIconAsset::register($this->view());

        self::assertStringEndsWith(
            '/node_modules/bootstrap-icons/font',
            str_replace('\\', '/', (string) $bundle->sourcePath),
            'Source must be the `font` directory.',
        );
        self::assertSame(
            ['bootstrap-icons.css'],
            $bundle->css,
            'Readable stylesheet expected.',
        );
        self::assertSame(
            ['bootstrap-icons.css', 'bootstrap-icons.css.map', 'fonts/*'],
            $bundle->publishOptions['only'] ?? null,
            'Stylesheet, source map, and fonts must be published.',
        );
        self::assertFileExists(
            "{$bundle->basePath}/fonts/bootstrap-icons.woff2",
            'Font files must be published.',
        );
        self::assertFileExists(
            "{$bundle->basePath}/bootstrap-icons.css",
            'Stylesheet must be published.',
        );
    }

    public function testIndependentDirectoriesArePublishedWhenPluginBundleIsRegisteredFirst(): void
    {
        $this->mockWebApplication();

        $view = $this->view();
        $assetManager = $view->getAssetManager();

        $plugin = BootstrapPluginAsset::register($view);
        $core = BootstrapAsset::register($view);

        self::endBody($view);

        $cssUrl = $assetManager->getAssetUrl($core, 'bootstrap.css');
        $jsUrl = $plugin->moduleUrl($view);

        self::assertNotSame(
            $core->basePath,
            $plugin->basePath,
            'Each bundle must publish its own directory.',
        );
        self::assertFileExists(
            "{$core->basePath}/bootstrap.css",
            'Stylesheet must be published after the script bundle.',
        );
        self::assertFileExists(
            "{$plugin->basePath}/bootstrap.bundle.js",
            'Script must be published.',
        );
        self::assertSame(
            ['/assets/css/bootstrap.css', '/assets/js/bootstrap.bundle.js'],
            [$cssUrl, $jsUrl],
            'URLs must point to the published directories.',
        );
        self::assertArrayHasKey(
            $cssUrl,
            $view->cssFiles,
            'Stylesheet URL must be registered.',
        );
        self::assertArrayHasKey(
            $jsUrl,
            self::registeredJsFiles($view),
            'Script URL must be registered.',
        );
        self::assertFileExists(
            $assetManager->basePath . substr($cssUrl, strlen($assetManager->baseUrl)),
            'Registered stylesheet URL must resolve to a file.',
        );
        self::assertFileExists(
            $assetManager->basePath . substr($jsUrl, strlen($assetManager->baseUrl)),
            'Registered script URL must resolve to a file.',
        );
    }

    public function testJsOptionsForceModuleType(): void
    {
        $this->mockWebApplication(
            [
                'components' => [
                    'assetManager' => [
                        'bundles' => [
                            BootstrapPluginAsset::class => [
                                'jsOptions' => ['defer' => true, 'type' => 'text/javascript'],
                            ],
                        ],
                    ],
                ],
            ],
        );

        $view = $this->view();

        $bundle = BootstrapPluginAsset::register($view);

        self::endBody($view);

        self::assertTrue(
            $bundle->jsOptions['defer'] ?? null,
            'Configured options must be kept.',
        );
        self::assertSame(
            'module',
            $bundle->jsOptions['type'] ?? null,
            'Type must be forced to `module`.',
        );
        self::assertSame(
            '<script type="module" src="/assets/js/bootstrap.bundle.js" defer></script>',
            self::registeredJsFiles($view)['/assets/js/bootstrap.bundle.js'] ?? null,
            'Script tag must load an ES module.',
        );
    }

    public function testLocalBundlePublishesDistFiles(): void
    {
        $this->mockWebApplication();

        $bundle = BootstrapAsset::registerWith($this->view());

        self::assertFalse(
            $bundle->cdn,
            'Local delivery is the default.',
        );
        self::assertSame(
            ['bootstrap.css'],
            $bundle->css,
            'Debug mode must serve readable stylesheets.',
        );
        self::assertSame(
            ['bootstrap.css', 'bootstrap.css.map'],
            $bundle->publishOptions['only'] ?? null,
            'Only the file and its source map.',
        );
        self::assertStringEndsWith(
            '/node_modules/bootstrap/dist/css',
            str_replace('\\', '/', (string) $bundle->sourcePath),
            'Source must be the `dist/css` directory.',
        );
        self::assertFileExists(
            "{$bundle->basePath}/bootstrap.css",
            'Stylesheet must be published.',
        );
        self::assertFileExists(
            "{$bundle->basePath}/bootstrap.css.map",
            'Source map must be published.',
        );
        self::assertFileDoesNotExist(
            "{$bundle->basePath}/bootstrap.min.css",
            'Unselected files must not be published.',
        );
    }

    public function testMinifiedDefaultsToDebugMode(): void
    {
        $this->mockWebApplication();

        $bundle = new BootstrapAsset();

        self::assertFalse(
            $bundle->minified,
            'Debug mode must disable minification.',
        );
    }

    public function testMinifiedInsertsInfixBeforeLastExtension(): void
    {
        $this->mockWebApplication();

        $bundle = new ExtensionlessNpmAsset(['minified' => true]);

        self::assertSame(
            ['dist/js/bootstrap.bundle.min.js', 'LICENSE'],
            $bundle->js,
            'Infix must precede the last extension only; extensionless names stay unchanged.',
        );
        self::assertSame(
            ['dist/js/bootstrap.bundle.min.js', 'LICENSE', 'dist/js/bootstrap.bundle.min.js.map', 'LICENSE.map'],
            $bundle->publishOptions['only'] ?? null,
            'Every file must be published with its source map.',
        );
        self::assertStringEndsWith(
            '/node_modules/bootstrap',
            str_replace('\\', '/', (string) $bundle->sourcePath),
            'Empty directory must publish the package root.',
        );
    }

    public function testMinifiedSelectsMinFiles(): void
    {
        $this->mockWebApplication();

        $bundle = BootstrapPluginAsset::register($this->view());
        $minified = new BootstrapPluginAsset(['minified' => true]);

        self::assertSame(
            ['bootstrap.bundle.js'],
            $bundle->js,
            'Readable script expected by default.',
        );
        self::assertSame(
            ['bootstrap.bundle.min.js'],
            $minified->js,
            'Minified script expected.',
        );
        self::assertSame(
            ['bootstrap.bundle.min.js', 'bootstrap.bundle.min.js.map'],
            $minified->publishOptions['only'] ?? null,
            'Only the minified file and its source map.',
        );
    }

    public function testModuleUrlMatchesRegisteredCdnScript(): void
    {
        $this->mockWebApplication(['components' => ['assetManager' => ['appendTimestamp' => true]]]);

        $view = $this->view();

        $bundle = BootstrapPluginAsset::registerWith($view, true);

        self::endBody($view);

        self::assertSame(
            self::CDN_BASE . '/dist/js/bootstrap.bundle.js',
            $bundle->moduleUrl($view),
            'Absolute URL must be returned unchanged.',
        );
        self::assertArrayHasKey(
            $bundle->moduleUrl($view),
            self::registeredJsFiles($view),
            'URL must match the registered script.',
        );
    }

    public function testModuleUrlMatchesRegisteredLocalScript(): void
    {
        $this->mockWebApplication();

        $view = $this->view();

        $bundle = BootstrapPluginAsset::register($view);

        self::endBody($view);

        self::assertSame(
            '/assets/js/bootstrap.bundle.js',
            $bundle->moduleUrl($view),
            'URL must point to the published script.',
        );
        self::assertArrayHasKey(
            $bundle->moduleUrl($view),
            self::registeredJsFiles($view),
            'URL must match the registered script.',
        );
    }

    public function testModuleUrlMatchesRegisteredScriptWithTimestamp(): void
    {
        $this->mockWebApplication(['components' => ['assetManager' => ['appendTimestamp' => true]]]);

        $view = $this->view();

        $bundle = BootstrapPluginAsset::register($view);

        $url = $bundle->moduleUrl($view);

        self::endBody($view);

        self::assertMatchesRegularExpression(
            '#^/assets/js/bootstrap\.bundle\.js\?v=\d+$#',
            $url,
            'Timestamp query must be appended.',
        );
        self::assertArrayHasKey(
            $url,
            self::registeredJsFiles($view),
            'URL must match the registered script.',
        );
    }

    public function testPublishOptionsOnlyIsNotOverridden(): void
    {
        $this->mockWebApplication();

        $bundle = new BootstrapAsset(['publishOptions' => ['only' => ['dist/*']]]);

        self::assertSame(
            ['dist/*'],
            $bundle->publishOptions['only'] ?? null,
            'Configured patterns must be kept.',
        );
    }

    public function testRegisterWithSeedsDependenciesAndKeepsFirstMode(): void
    {
        $this->mockWebApplication();

        $view = $this->view();

        $plugin = BootstrapPluginAsset::registerWith($view, true);

        $core = $view->assetBundles[BootstrapAsset::class] ?? null;

        $again = BootstrapPluginAsset::registerWith($view, false);

        self::assertInstanceOf(
            BootstrapAsset::class,
            $core,
            'Dependency must be registered.',
        );
        self::assertTrue(
            $plugin->cdn,
            'Requested mode must apply to the bundle.',
        );
        self::assertTrue(
            $core->cdn,
            'Requested mode must propagate to npm dependencies.',
        );
        self::assertSame(
            $plugin,
            $again,
            'A registered bundle must keep its mode.',
        );
    }

    public function testRegisterWithSkipsNonNpmDependencies(): void
    {
        $this->mockWebApplication();

        $view = $this->view();

        ExtensionlessNpmAsset::registerWith($view, true);

        self::assertInstanceOf(
            PlainAsset::class,
            $view->assetBundles[PlainAsset::class] ?? null,
            'Non-npm dependencies must be registered without seeded configuration.',
        );
    }

    public function testThrowInvalidConfigExceptionWhenModuleScriptIsMissing(): void
    {
        $this->mockWebApplication();

        $bundle = new BootstrapPluginAsset();

        $bundle->js = [];

        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage(
            Message::MODULE_SCRIPT_UNAVAILABLE->getMessage(BootstrapPluginAsset::class),
        );

        $bundle->moduleUrl($this->view());
    }

    public function testThrowInvalidConfigExceptionWhenPackageManifestIsMissing(): void
    {
        $this->mockWebApplication();

        Yii::setAlias('@npm', self::runtimePath() . '/missing');

        try {
            $this->expectException(InvalidConfigException::class);
            $this->expectExceptionMessage(
                Message::PACKAGE_VERSION_UNAVAILABLE->getMessage(
                    'bootstrap-icons',
                    self::runtimePath() . '/missing/bootstrap-icons/package.json',
                ),
            );

            new BootstrapIconAsset(['cdn' => true]);
        } finally {
            Yii::setAlias('@npm', dirname(__DIR__, 2) . '/node_modules');
        }
    }

    public function testThrowInvalidConfigExceptionWhenPackageVersionIsMissing(): void
    {
        $this->mockWebApplication();

        $path = self::runtimePath() . '/npm/bootstrap';

        if (!is_dir($path)) {
            mkdir($path, 0o777, true);
        }

        file_put_contents("{$path}/package.json", '{"name":"bootstrap","version":""}');

        Yii::setAlias('@npm', self::runtimePath() . '/npm');

        try {
            $this->expectException(InvalidConfigException::class);
            $this->expectExceptionMessage(
                Message::PACKAGE_VERSION_UNAVAILABLE->getMessage('bootstrap', "{$path}/package.json"),
            );

            new BootstrapAsset(['cdn' => true]);
        } finally {
            Yii::setAlias('@npm', dirname(__DIR__, 2) . '/node_modules');
        }
    }

    /**
     * Ends the page body, which registers the files of every registered bundle.
     */
    private static function endBody(View $view): void
    {
        ob_start();

        $view->endBody();

        ob_end_clean();
    }
}
