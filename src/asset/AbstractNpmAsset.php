<?php

declare(strict_types=1);

namespace yii2\extensions\bootstrap6\asset;

use Yii;
use yii\base\InvalidConfigException;
use yii\web\{AssetBundle, AssetManager, View};
use yii2\extensions\bootstrap6\exception\Message;

use function array_map;
use function file_get_contents;
use function is_array;
use function is_string;
use function is_subclass_of;
use function json_decode;
use function rtrim;
use function strrpos;
use function substr_replace;

/**
 * Provides local or CDN delivery for asset bundles backed by an npm package installed through Foxy.
 *
 * In local mode the bundle publishes {@see directory()} of the package from the `@npm` alias. In CDN mode it links
 * the same files from {@see $cdnUrl}, pinned to the installed version, so both delivery modes serve the same release.
 *
 * Each bundle publishes its own sub-directory because the asset manager publishes a source directory only once: two
 * bundles sharing one source path with different `publishOptions['only']` filters would leave the files of the second
 * bundle unpublished.
 */
abstract class AbstractNpmAsset extends AssetBundle
{
    /**
     * Whether to link the files from the CDN instead of publishing the local package.
     */
    public bool $cdn = false;
    /**
     * Base URL of the CDN serving npm packages as `{cdnUrl}/{package}@{version}/{directory}/{file}`.
     */
    public string $cdnUrl = 'https://cdn.jsdelivr.net/npm';
    /**
     * Whether to serve the minified files, or `null` to serve them outside debug mode.
     */
    public bool|null $minified = null;

    /**
     * Returns the npm package name that ships the files.
     *
     * @return string npm package name.
     */
    abstract protected function package(): string;

    /**
     * Returns the asset bundle classes this bundle depends on.
     *
     * @return list<class-string<AssetBundle>> Dependency bundle classes.
     */
    public static function dependencies(): array
    {
        return [];
    }

    /**
     * Initializes the bundle file lists for the configured delivery mode.
     *
     * @throws InvalidConfigException if the installed package version cannot be resolved in CDN mode.
     */
    public function init(): void
    {
        $this->depends = static::dependencies();

        $this->minified ??= !YII_DEBUG;

        $css = $this->files($this->styles());
        $js = $this->files($this->scripts());
        $directory = $this->directory();

        $suffix = $directory === '' ? '' : "/{$directory}";

        if ($this->cdn) {
            $baseUrl = rtrim($this->cdnUrl, '/') . "/{$this->package()}@{$this->packageVersion()}{$suffix}/";

            $this->css = array_map(static fn(string $file): string => $baseUrl . $file, $css);
            $this->js = array_map(static fn(string $file): string => $baseUrl . $file, $js);
        } else {
            $files = [...$css, ...$js];

            $this->sourcePath = "@npm/{$this->package()}{$suffix}";
            $this->css = $css;
            $this->js = $js;
            $this->publishOptions['only'] ??= [
                ...$files,
                ...array_map(static fn(string $file): string => "{$file}.map", $files),
                ...$this->publishPatterns(),
            ];
        }

        parent::init();
    }

    /**
     * Registers the bundle, and its npm-backed dependencies, with the given delivery mode.
     *
     * Bundles already configured in the asset manager, or already registered, keep their current mode.
     *
     * @param View $view View that renders the asset files.
     * @param bool $cdn Whether to link the files from the CDN.
     *
     * @return static Registered bundle instance.
     */
    public static function registerWith(View $view, bool $cdn = false): static
    {
        self::seed($view->getAssetManager(), static::class, $cdn);

        return static::register($view);
    }

    /**
     * Returns the package sub-directory the bundle publishes, without leading or trailing slashes.
     *
     * @return string Sub-directory relative to the package root, or an empty string to publish the whole package.
     */
    protected function directory(): string
    {
        return '';
    }

    /**
     * Returns additional publish patterns, relative to {@see directory()}, for files referenced by the stylesheets.
     *
     * @return list<string> Patterns appended to the default `publishOptions['only']` list.
     */
    protected function publishPatterns(): array
    {
        return [];
    }

    /**
     * Returns the script paths relative to {@see directory()}, without the `.min` infix.
     *
     * @return list<string> Script paths.
     */
    protected function scripts(): array
    {
        return [];
    }

    /**
     * Returns the stylesheet paths relative to {@see directory()}, without the `.min` infix.
     *
     * @return list<string> Stylesheet paths.
     */
    protected function styles(): array
    {
        return [];
    }

    /**
     * Resolves file paths, inserting the `.min` infix before the last extension when {@see $minified} is enabled.
     *
     * @param list<string> $files Paths without the `.min` infix.
     *
     * @return list<string> Paths relative to {@see directory()}.
     */
    private function files(array $files): array
    {
        if ($this->minified !== true) {
            return $files;
        }

        return array_map(
            static function (string $file): string {
                $extension = strrpos($file, '.');

                return $extension === false ? $file : substr_replace($file, '.min', $extension, 0);
            },
            $files,
        );
    }

    /**
     * Reads the installed package version from its `package.json`.
     *
     * @throws InvalidConfigException if the package manifest is missing or has no version.
     */
    private function packageVersion(): string
    {
        $path = Yii::getAlias('@npm/' . $this->package() . '/package.json');

        $manifest = @file_get_contents($path);
        $decoded = is_string($manifest) ? json_decode($manifest, true) : null;
        $version = is_array($decoded) ? ($decoded['version'] ?? null) : null;

        if (!is_string($version) || $version === '') {
            throw new InvalidConfigException(
                Message::PACKAGE_VERSION_UNAVAILABLE->getMessage($this->package(), $path),
            );
        }

        return $version;
    }

    /**
     * Stores the delivery mode of a bundle and its npm-backed dependencies before they are instantiated.
     *
     * @param class-string<self> $class Bundle class to seed.
     */
    private static function seed(AssetManager $assetManager, string $class, bool $cdn): void
    {
        $bundles = $assetManager->bundles;

        if ($bundles === false || isset($bundles[$class])) {
            return;
        }

        $bundles[$class] = ['cdn' => $cdn];
        $assetManager->bundles = $bundles;

        foreach ($class::dependencies() as $dependency) {
            if (is_subclass_of($dependency, self::class)) {
                self::seed($assetManager, $dependency, $cdn);
            }
        }
    }
}
