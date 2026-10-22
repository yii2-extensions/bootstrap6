<?php

declare(strict_types=1);

namespace yii2\extensions\bootstrap6\asset;

use yii\base\InvalidConfigException;
use yii\helpers\Json;
use yii\web\View;
use yii2\extensions\bootstrap6\exception\Message;

use function is_string;
use function str_replace;

/**
 * Delivers the Bootstrap 6 JavaScript bundle as an ES module, with Floating UI and Vanilla Calendar Pro inlined.
 *
 * The bundle exposes no global; scripts access the plugins through `import()` of {@see moduleUrl()}, or through
 * `window.bootstrap` when {@see $exposeGlobal} is enabled.
 *
 * @see https://getbootstrap.com/docs/6.0/getting-started/javascript/
 */
final class BootstrapPluginAsset extends AbstractNpmAsset
{
    /**
     * Whether to assign the module namespace to `window.bootstrap` for scripts written against the global.
     */
    public bool $exposeGlobal = false;

    /**
     * Returns the {@see BootstrapAsset} dependency, so the stylesheet is registered with the bundle.
     *
     * @return list<class-string<\yii\web\AssetBundle>> Asset bundle classes required by the JavaScript bundle.
     */
    public static function dependencies(): array
    {
        return [BootstrapAsset::class];
    }

    /**
     * Returns JavaScript that imports the bundle module and runs the given code with the module namespace bound to
     * `bootstrap`.
     *
     * The module URL is a JSON string literal safe inside a `<script>` element, with forward slashes kept readable.
     *
     * @param View $view View the bundle is registered with.
     * @param string $code JavaScript statements that use the `bootstrap` namespace.
     *
     * @throws InvalidConfigException if the bundle does not list a script.
     *
     * @return string JavaScript in the form `import("url").then((bootstrap) => { code });`.
     */
    public function importJs(View $view, string $code): string
    {
        $url = str_replace('\\/', '/', Json::htmlEncode($this->moduleUrl($view)));

        return "import({$url}).then((bootstrap) => { {$code} });";
    }

    /**
     * Initializes the bundle and forces `type="module"` on its script tag, keeping the other configured options.
     *
     * @throws InvalidConfigException if the installed package version cannot be resolved in CDN mode.
     */
    public function init(): void
    {
        parent::init();

        $this->jsOptions['type'] = 'module';
    }

    /**
     * Returns the bundle script URL exactly as the view registers it, so `import()` evaluates the same module.
     *
     * @param View $view View the bundle is registered with.
     *
     * @return string Script URL, including the timestamp query when the asset manager appends one.
     *
     * @throws InvalidConfigException if the bundle does not list a script.
     */
    public function moduleUrl(View $view): string
    {
        $script = $this->js[0] ?? null;

        if (!is_string($script)) {
            throw new InvalidConfigException(Message::MODULE_SCRIPT_UNAVAILABLE->getMessage(self::class));
        }

        return $view->getAssetManager()->getAssetUrl($this, $script);
    }

    /**
     * Registers the asset files and optionally exposes the module as `window.bootstrap`.
     *
     * @param View $view View that renders the asset files.
     *
     * @throws InvalidConfigException if global exposure is enabled and the bundle does not list a script.
     */
    public function registerAssetFiles($view): void
    {
        parent::registerAssetFiles($view);

        if ($this->exposeGlobal) {
            $view->registerJs(
                $this->importJs($view, 'window.bootstrap = bootstrap;'),
                View::POS_END,
                'bootstrap6-global',
            );
        }
    }

    /**
     * Returns the `dist/js` directory of the package.
     *
     * @return string Script directory relative to the npm package root.
     */
    protected function directory(): string
    {
        return 'dist/js';
    }

    /**
     * Returns the `bootstrap` npm package.
     *
     * @return string Bootstrap npm package name.
     */
    protected function package(): string
    {
        return 'bootstrap';
    }

    /**
     * Returns the Bootstrap JavaScript bundle.
     *
     * @return list<string> Script paths relative to {@see directory()}.
     */
    protected function scripts(): array
    {
        return ['bootstrap.bundle.js'];
    }
}
