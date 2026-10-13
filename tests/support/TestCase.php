<?php

declare(strict_types=1);

namespace yii2\extensions\bootstrap6\tests\support;

use Closure;
use PHPForge\Support\DirectoryCleaner;
use ReflectionClass;
use Yii;
use yii\base\{InvalidConfigException, Widget};
use yii\di\Container;
use yii\helpers\ArrayHelper;
use yii\web\{Application, View};
use yii2\extensions\bootstrap6\{BaseHtml, Bootstrap};
use yii2\extensions\bootstrap6\item\{
    AccordionItem,
    BreadcrumbLink,
    CarouselItem,
    MenuItem,
    NavItem,
    ProgressBar,
    StepItem,
    TabItem,
};

use function array_map;
use function basename;
use function dirname;
use function get_object_vars;
use function getmypid;
use function is_array;
use function is_dir;
use function is_string;
use function mkdir;
use function str_replace;

/**
 * Base class for the extension test suite.
 *
 * Provides a web application factory with an asset manager publishing into `runtime`, the extension bootstrap, the
 * reset of the widget and helper `id` counters, and cleanup of published assets after each test.
 */
abstract class TestCase extends \PHPUnit\Framework\TestCase
{
    protected const string RUNTIME_PATH = __DIR__ . '/../../runtime/tests';

    /**
     * Runtime directory of the current process, so parallel test processes never clean each other's files.
     */
    private static string|null $runtimePath = null;

    /**
     * Asserts that two HTML strings are identical after normalizing line endings.
     */
    protected static function assertSameHtml(string $expected, string $actual, string $message): void
    {
        self::assertSame(
            str_replace("\r\n", "\n", $expected),
            str_replace("\r\n", "\n", $actual),
            $message,
        );
    }

    /**
     * Asserts that `$with` returns a copy of `$item` where only `$property` holds `$value`, leaving `$item` untouched.
     *
     * @template T of AccordionItem|BreadcrumbLink|CarouselItem|MenuItem|NavItem|ProgressBar|StepItem|TabItem
     *
     * @param T $item Item to copy.
     * @param Closure(T): T $with Copy operation under test.
     * @param string $property Name of the property that changes.
     * @param mixed $value Expected value of the property in the copy.
     */
    protected static function assertWithCopy(object $item, Closure $with, string $property, mixed $value): void
    {
        $before = self::exportItem($item);
        $copy = $with($item);

        self::assertNotSame($item, $copy, 'A new instance must be returned.');
        self::assertSame(
            [...$before, $property => self::exportValue($value)],
            self::exportItem($copy),
            'Only the given property must change.',
        );
        self::assertSame($before, self::exportItem($item), 'Original must stay untouched.');
    }

    /**
     * Returns the class and public properties of an item value object, with nested value objects exported as well.
     *
     * @return array<string, mixed>
     */
    protected static function exportItem(
        AccordionItem|BreadcrumbLink|CarouselItem|MenuItem|NavItem|ProgressBar|StepItem|TabItem $item,
    ): array {
        $export = ['class' => $item::class];

        foreach (get_object_vars($item) as $name => $value) {
            $export[$name] = self::exportValue($value);
        }

        return $export;
    }

    /**
     * Returns a property value with the item value objects it holds, at any depth, exported by {@see exportItem()}.
     */
    protected static function exportValue(mixed $value): mixed
    {
        return match (true) {
            is_array($value) => array_map(self::exportValue(...), $value),
            $value instanceof AccordionItem,
            $value instanceof BreadcrumbLink,
            $value instanceof CarouselItem,
            $value instanceof MenuItem,
            $value instanceof NavItem,
            $value instanceof ProgressBar,
            $value instanceof StepItem,
            $value instanceof TabItem => self::exportItem($value),
            default => $value,
        };
    }

    /**
     * Creates a web application with the extension bootstrap and an isolated asset manager.
     *
     * The `@npm` alias is pinned to the package `node_modules` directory because Yii 2.0.x resets it to `vendor/npm`
     * when `vendorPath` is set.
     *
     * @param array<string, mixed> $config Configuration merged over the defaults.
     *
     * @throws InvalidConfigException if the configuration is invalid.
     */
    protected function mockWebApplication(array $config = []): Application
    {
        $assetPath = self::runtimePath() . '/assets';

        if (!is_dir($assetPath)) {
            mkdir($assetPath, 0o777, true);
        }

        return new Application(
            ArrayHelper::merge(
                [
                    'id' => 'test-app',
                    'aliases' => ['@npm' => dirname(__DIR__, 2) . '/node_modules'],
                    'basePath' => dirname(__DIR__, 2),
                    'bootstrap' => [Bootstrap::class],
                    'components' => [
                        'assetManager' => [
                            'basePath' => $assetPath,
                            'baseUrl' => '/assets',
                            'hashCallback' => basename(...),
                        ],
                        'request' => [
                            'cookieValidationKey' => 'test-cookie-validation-key',
                            'scriptFile' => __DIR__ . '/index.php',
                            'scriptUrl' => '/index.php',
                        ],
                    ],
                    'vendorPath' => dirname(__DIR__, 2) . '/vendor',
                ],
                $config,
            ),
        );
    }

    /**
     * Returns the inline script registered at {@see View::POS_END} under the given key, or `null` when absent.
     */
    protected static function registeredJs(View $view, string $key): string|null
    {
        $block = $view->js[View::POS_END] ?? null;
        $script = is_array($block) ? ($block[$key] ?? null) : null;

        return is_string($script) ? $script : null;
    }

    /**
     * Returns the script tags registered at {@see View::POS_END}, indexed by URL.
     *
     * @return array<mixed>
     */
    protected static function registeredJsFiles(View $view): array
    {
        $files = $view->jsFiles[View::POS_END] ?? null;

        return is_array($files) ? $files : [];
    }

    /**
     * Returns the runtime directory of the current process.
     */
    protected static function runtimePath(): string
    {
        return self::$runtimePath ??= self::RUNTIME_PATH . '/' . getmypid();
    }

    protected function setUp(): void
    {
        parent::setUp();

        (new ReflectionClass(Widget::class))->setStaticPropertyValue('counter', 0);

        BaseHtml::$counter = 0;
    }

    protected function tearDown(): void
    {
        (new ReflectionClass(Yii::class))->setStaticPropertyValue('app', null);

        Yii::$container = new Container();

        if (is_dir(self::runtimePath())) {
            DirectoryCleaner::clean(self::runtimePath());
        }

        parent::tearDown();
    }

    /**
     * Creates a view bound to the current application.
     */
    protected function view(): View
    {
        return new View();
    }

    /**
     * Returns the web view of the current application.
     */
    protected static function webView(): View
    {
        $view = Yii::$app->getView();

        self::assertInstanceOf(View::class, $view, 'Application view must be a web view.');

        return $view;
    }
}
