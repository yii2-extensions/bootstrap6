<?php

declare(strict_types=1);

namespace yii2\extensions\bootstrap6;

use Stringable;
use UIAwesome\Html\Helper\Enum;
use UnitEnum;
use yii\base\InvalidArgumentException;
use yii\helpers\Json;
use yii\web\{JsExpression, View};
use yii2\extensions\bootstrap6\asset\BootstrapPluginAsset;
use yii2\extensions\bootstrap6\exception\Message;

use function array_walk_recursive;
use function implode;
use function in_array;

/**
 * Provides Bootstrap plugin registration, client options, and client events for widgets.
 *
 * The using class must declare a public `$options` array; the trait fills `$options['id']` when it is not set.
 * Plugins initialize through a dynamic `import()` of the same module URL that {@see BootstrapPluginAsset} registers,
 * so the browser evaluates the bundle once and no global is required.
 */
trait BootstrapWidgetTrait
{
    /**
     * Plugin classes exported by `dist/js/bootstrap.bundle.js`.
     */
    private const array PLUGINS = [
        'Alert',
        'Button',
        'Carousel',
        'Chips',
        'Collapse',
        'Combobox',
        'Datepicker',
        'Dialog',
        'Drawer',
        'Menu',
        'NavOverflow',
        'OtpInput',
        'Popover',
        'Range',
        'ScrollSpy',
        'Strength',
        'Tab',
        'Toast',
        'Toggler',
        'Tooltip',
    ];

    /**
     * Event handlers attached to the widget element, as event name => JavaScript handler source.
     *
     * For example, `['shown.bs.toast' => 'function (event) { ... }']`.
     *
     * @var array<string, JsExpression|string>
     */
    public array $clientEvents = [];
    /**
     * Options passed to the plugin constructor, or `false` to skip the JavaScript initialization.
     *
     * Enum cases at any depth are encoded as their value (backed) or name (pure), and {@see Stringable} values as their
     * `string`; {@see JsExpression} values are emitted as raw JavaScript.
     *
     * @var array<string, mixed>|false
     */
    public array|false $clientOptions = [];

    /**
     * Initializes the widget and assigns an `id` to {@see $options} when it is not set.
     *
     * @throws \yii\base\InvalidConfigException if an input widget has neither a model attribute nor a name.
     */
    public function init(): void
    {
        parent::init();

        $this->options['id'] ??= $this->getId();
    }

    /**
     * Returns the JavaScript that initializes a plugin with its normalized constructor options.
     *
     * @param string $name Plugin class exported by the bundle.
     * @param string $element JavaScript expression selecting the plugin element.
     * @param string $options JSON-encoded constructor options.
     *
     * @return string JavaScript that obtains or creates the plugin instance.
     */
    protected function pluginInitialization(string $name, string $element, string $options): string
    {
        return "bootstrap.{$name}.getOrCreateInstance({$element}, {$options});";
    }

    /**
     * Registers the JavaScript event handlers listed in {@see $clientEvents} at {@see View::POS_END}.
     */
    protected function registerClientEvents(): void
    {
        if ($this->clientEvents === []) {
            return;
        }

        $id = $this->elementJs();
        $js = [];

        foreach ($this->clientEvents as $event => $handler) {
            $js[] = "{$id}.addEventListener(" . Json::htmlEncode($event) . ", {$handler});";
        }

        $this->getView()->registerJs(implode("\n", $js), View::POS_END, "{$this->widgetId()}-events");
    }

    /**
     * Registers the plugin asset, the plugin initialization when needed, and the {@see $clientEvents}.
     *
     * The initialization is emitted when {@see $clientOptions} is a non-empty array, or when `$always` is `true` and
     * {@see $clientOptions} is not `false`.
     *
     * @param string $name Plugin class exported by the bundle, for example `Toast`.
     * @param bool $always Whether to initialize the plugin even when {@see $clientOptions} is empty.
     *
     * @throws InvalidArgumentException if the bundle does not export the plugin.
     */
    protected function registerPlugin(string $name, bool $always = false): void
    {
        if (!in_array($name, self::PLUGINS, true)) {
            throw new InvalidArgumentException(
                Message::UNKNOWN_PLUGIN->getMessage($name),
            );
        }

        $view = $this->getView();

        $bundle = BootstrapPluginAsset::register($view);

        $clientOptions = $this->clientOptions;

        if ($clientOptions !== false && ($always || $clientOptions !== [])) {
            array_walk_recursive(
                $clientOptions,
                static function (mixed &$value): void {
                    if (
                        $value instanceof UnitEnum
                        || ($value instanceof Stringable && !$value instanceof JsExpression)
                    ) {
                        $value = Enum::normalizeValue($value);
                    }
                },
            );

            $options = $clientOptions === [] ? '{}' : Json::htmlEncode($clientOptions);

            $view->registerJs(
                $bundle->importJs(
                    $view,
                    $this->pluginInitialization($name, $this->elementJs(), $options),
                ),
                View::POS_END,
                "{$this->widgetId()}-plugin",
            );
        }

        $this->registerClientEvents();
    }

    /**
     * Returns the JavaScript expression selecting the widget element by its JSON-encoded `id`.
     *
     * @return string JavaScript expression selecting the widget element.
     */
    private function elementJs(): string
    {
        return 'document.getElementById(' . Json::htmlEncode($this->widgetId()) . ')';
    }

    /**
     * Returns the widget element `id`.
     *
     * @return string Widget element identifier.
     */
    private function widgetId(): string
    {
        /** @var string */
        return $this->options['id'];
    }
}
