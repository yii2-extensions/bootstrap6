<?php

declare(strict_types=1);

namespace yii2\extensions\bootstrap6;

use Stringable;
use Yii;
use yii\base\InvalidConfigException;
use yii2\extensions\bootstrap6\asset\BootstrapAsset;
use yii2\extensions\bootstrap6\item\BreadcrumbLink;

use function implode;
use function is_array;
use function is_string;
use function strtr;

/**
 * Renders a Bootstrap breadcrumb trail with explicit `.breadcrumb-divider` elements between the items.
 *
 * Items without a `url` (or with `active` set to `true`) are rendered with {@see $activeItemTemplate} and an active
 * `.breadcrumb-link`.
 *
 * @see https://getbootstrap.com/docs/6.0/components/breadcrumb/
 */
class Breadcrumbs extends Widget
{
    /**
     * Template of the active item; `{link}` is replaced by the rendered link.
     */
    public string $activeItemTemplate = "<li class=\"breadcrumb-item\" aria-current=\"page\">{link}</li>\n";
    /**
     * Content of every divider element; it is not HTML-encoded. An empty `string` renders the default chevron.
     */
    public string $divider = '';
    /**
     * HTML attributes of every divider tag.
     *
     * @var array<string, mixed>
     */
    public array $dividerOptions = ['class' => 'breadcrumb-divider'];
    /**
     * Whether to HTML-encode the link labels; an item `encode` key overrides it.
     */
    public bool $encodeLabels = true;
    /**
     * First item of the trail, `[]` for a link to {@see \yii\web\Application::$homeUrl} labeled `Home`, or `false` to
     * omit it. See {@see $links} for the format.
     *
     * @var array<array-key, mixed>|BreadcrumbLink|false
     */
    public array|BreadcrumbLink|false $homeLink = [];
    /**
     * Template of the inactive items; `{link}` is replaced by the rendered link.
     */
    public string $itemTemplate = "<li class=\"breadcrumb-item\">{link}</li>\n";
    /**
     * Items of the trail; the widget renders nothing when empty.
     *
     * Each item is a {@see BreadcrumbLink}, a label `string` (active item), a `url => label` pair, or an array
     * converted through {@see BreadcrumbLink::fromArray()} with the keys `label` (required, `string` or
     * {@see Stringable}), `url`, `active`, `encode`, `template`, and any other key rendered as an attribute of the link
     * tag.
     *
     * @var array<array-key, array<array-key, mixed>|BreadcrumbLink|string|Stringable>
     */
    public array $links = [];
    /**
     * HTML attributes of the `<nav>` container tag.
     *
     * @var array<string, mixed>
     */
    public array $navOptions = ['aria-label' => 'breadcrumb'];
    /**
     * Tag name of the list element.
     */
    public string $tag = 'ol';

    /**
     * Renders the breadcrumb trail, or an empty `string` when there are no links.
     *
     * @throws InvalidConfigException if an item has no `label`.
     *
     * @return string Rendered Breadcrumbs markup.
     */
    public function run(): string
    {
        if ($this->links === []) {
            return '';
        }

        BootstrapAsset::register($this->getView());

        $items = [];

        if ($this->homeLink !== false) {
            $items[] = $this->renderItem(
                match (true) {
                    $this->homeLink instanceof BreadcrumbLink => $this->homeLink,
                    $this->homeLink === [] => BreadcrumbLink::to(Yii::t('yii', 'Home'), Yii::$app->homeUrl),
                    default => BreadcrumbLink::fromArray($this->homeLink),
                },
            );
        }

        foreach ($this->links as $url => $link) {
            $items[] = $this->renderItem(
                match (true) {
                    $link instanceof BreadcrumbLink => $link,
                    is_array($link) => BreadcrumbLink::fromArray($link),
                    default => BreadcrumbLink::fromArray(['label' => $link, 'url' => is_string($url) ? $url : null]),
                },
            );
        }

        $options = $this->options;

        Html::addCssClass($options, 'breadcrumb');

        $divider = Html::tag('li', $this->divider, $this->dividerOptions) . "\n";

        return Html::tag(
            'nav',
            Html::tag($this->tag, implode($divider, $items), $options),
            $this->navOptions,
        );
    }

    /**
     * Renders an item of the trail.
     *
     * @param BreadcrumbLink $link Breadcrumb link or active trail item to render.
     *
     * @return string Generated HTML.
     */
    protected function renderItem(BreadcrumbLink $link): string
    {
        $options = $link->options;
        $active = $link->active ?? $link->url === null;

        Html::addCssClass($options, 'breadcrumb-link');

        if ($active) {
            Html::addCssClass($options, 'active');
        }

        return strtr(
            $link->template ?? ($active ? $this->activeItemTemplate : $this->itemTemplate),
            [
                '{link}' => Html::a(
                    ($link->encode ?? $this->encodeLabels) ? Html::encode($link->label) : $link->label,
                    $link->url,
                    $options,
                ),
            ],
        );
    }
}
