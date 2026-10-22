<?php

declare(strict_types=1);

namespace yii2\extensions\bootstrap6;

use Stringable;
use yii\base\InvalidConfigException;
use yii2\extensions\bootstrap6\enum\Placement;

use function is_array;
use function ob_get_clean;
use function ob_start;
use function strtolower;

/**
 * Renders an element that wraps the trigger content and shows a Bootstrap tooltip on hover and focus.
 *
 * Content captured between {@see begin()} and {@see end()} is the trigger. Bootstrap initializes tooltips on first
 * interaction, so the plugin is initialized explicitly only when {@see $clientOptions} is not empty.
 *
 * @see https://getbootstrap.com/docs/6.0/components/tooltip/
 */
class Tooltip extends Widget
{
    /**
     * Classes added to the floating element (`data-bs-custom-class`), or `null`.
     */
    public string|null $customClass = null;
    /**
     * Whether the floating element renders its text as HTML (`data-bs-html="true"`).
     */
    public bool $html = false;
    /**
     * Position of the floating element (`data-bs-placement`), for example {@see Placement::BOTTOM} or the responsive
     * value `top md:right`, or `null` for the plugin default.
     */
    public Placement|string|null $placement = null;
    /**
     * Tag name of the wrapping element.
     */
    public string $tagName = 'span';
    /**
     * Title of the floating element (`data-bs-title`), or `null`.
     */
    public string|Stringable|null $title = null;
    /**
     * Space-separated triggers such as `click`, `hover`, `focus`, or `manual` (`data-bs-trigger`), or `null`.
     */
    public string|null $trigger = null;

    /**
     * Initializes the widget, validates {@see $placement}, and starts capturing the trigger content.
     *
     * @throws InvalidConfigException if {@see $placement} is not a {@see Placement} value, optionally
     * breakpoint-prefixed.
     */
    public function init(): void
    {
        parent::init();

        Html::enumValue(Placement::class, $this->placement, 'placement', true);

        ob_start();
    }

    /**
     * Returns the wrapping element with the captured trigger content and its `data-bs-*` attributes, and
     * registers the plugin named by {@see pluginName()}.
     *
     * @throws InvalidConfigException if {@see $placement} is not a {@see Placement} value, optionally
     * breakpoint-prefixed.
     *
     * @return string Rendered Tooltip markup.
     */
    public function run(): string
    {
        $content = ob_get_clean();

        $this->registerPlugin($this->pluginName());

        $options = $this->options;
        $data = is_array($options['data'] ?? null) ? $options['data'] : [];

        $options['data'] = [...$data, ...$this->dataAttributes()];

        return Html::beginTag($this->tagName, $options) . $content . Html::endTag($this->tagName);
    }

    /**
     * Returns the `data-bs-*` attributes of the wrapping element, without the `data-` prefix.
     *
     * @throws InvalidConfigException if {@see $placement} is not a {@see Placement} value, optionally
     * breakpoint-prefixed.
     *
     * @return array<string, string|Stringable> Plugin attributes without the `data-` prefix.
     */
    protected function dataAttributes(): array
    {
        $data = ['bs-toggle' => strtolower($this->pluginName())];

        if ($this->title !== null) {
            $data['bs-title'] = $this->title;
        }

        $placement = Html::enumValue(Placement::class, $this->placement, 'placement', true);

        if ($placement !== null) {
            $data['bs-placement'] = $placement;
        }

        if ($this->trigger !== null) {
            $data['bs-trigger'] = $this->trigger;
        }

        if ($this->html) {
            $data['bs-html'] = 'true';
        }

        if ($this->customClass !== null) {
            $data['bs-custom-class'] = $this->customClass;
        }

        return $data;
    }

    /**
     * Returns the plugin class exported by the bundle.
     *
     * @return string Tooltip plugin class name.
     */
    protected function pluginName(): string
    {
        return 'Tooltip';
    }
}
