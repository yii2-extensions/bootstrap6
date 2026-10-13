<?php

declare(strict_types=1);

namespace yii2\extensions\bootstrap6;

use Yii;
use yii\base\InvalidConfigException;
use yii\data\Pagination;
use yii\helpers\ArrayHelper;
use yii2\extensions\bootstrap6\asset\BootstrapAsset;
use yii2\extensions\bootstrap6\enum\{Size, Theme};
use yii2\extensions\bootstrap6\exception\Message;

use function implode;
use function intdiv;
use function is_string;
use function max;
use function min;

/**
 * Renders Bootstrap pagination links for a {@see Pagination} object.
 *
 * To use it in every `GridView`/`ListView`, register it in the DI container as the definition of
 * {@see \yii\widgets\LinkPager}.
 *
 * @see https://getbootstrap.com/docs/6.0/components/pagination/
 */
class LinkPager extends Widget
{
    /**
     * CSS class of the active (current) page item.
     */
    public string $activePageCssClass = 'active';
    /**
     * Whether to render the current page button as disabled.
     */
    public bool $disableCurrentPageButton = false;
    /**
     * HTML attributes merged into the links of disabled page items.
     *
     * @var array<string, mixed>
     */
    public array $disabledListItemSubTagOptions = [];
    /**
     * CSS class of the disabled page items.
     */
    public string $disabledPageCssClass = 'disabled';
    /**
     * CSS class of the "first" page item.
     */
    public string $firstPageCssClass = 'first';
    /**
     * Label of the "first" page button (not HTML-encoded), `true` for the page number, or `false` to omit it.
     */
    public bool|string $firstPageLabel = false;
    /**
     * Whether to render nothing when there is only one page.
     */
    public bool $hideOnSinglePage = true;
    /**
     * CSS class of the "last" page item.
     */
    public string $lastPageCssClass = 'last';
    /**
     * Label of the "last" page button (not HTML-encoded), `true` for the page number, or `false` to omit it.
     */
    public bool|string $lastPageLabel = false;
    /**
     * HTML attributes of every page item; the `tag` option sets the tag name (default `li`).
     *
     * @var array<string, mixed>
     */
    public array $linkContainerOptions = ['class' => ['page-item']];
    /**
     * HTML attributes of every page link.
     *
     * @var array<string, mixed>
     */
    public array $linkOptions = ['class' => ['page-link']];
    /**
     * HTML attributes of the list tag; the `tag` option sets the tag name (default `ul`).
     *
     * @var array<string, mixed>
     */
    public array $listOptions = ['class' => ['pagination']];
    /**
     * Maximum number of internal page buttons.
     */
    public int $maxButtonCount = 10;
    /**
     * CSS class of the "next" page item.
     */
    public string $nextPageCssClass = 'next';
    /**
     * Label of the "next" page button (not HTML-encoded), or `false` to omit it.
     */
    public false|string $nextPageLabel = '<span aria-hidden="true">&raquo;</span>';
    /**
     * HTML attributes of the container tag; the `tag` option sets the tag name (default `nav`).
     *
     * @var array<string, mixed>
     */
    public array $options = [];
    /**
     * CSS class of the internal page items.
     */
    public string $pageCssClass = 'page-item';
    /**
     * Pagination that provides the page count, the current page, and the page URLs.
     */
    public Pagination|null $pagination = null;
    /**
     * CSS class of the "previous" page item.
     */
    public string $prevPageCssClass = 'prev';
    /**
     * Label of the "previous" page button (not HTML-encoded), or `false` to omit it.
     */
    public false|string $prevPageLabel = '<span aria-hidden="true">&laquo;</span>';
    /**
     * Whether to register `<link>` tags for the first, previous, next, and last pages in the view head.
     */
    public bool $registerLinkTags = false;
    /**
     * Size modifier rendered as the `pagination-{value}` class (`sm` or `lg`), or `null` for the default size.
     */
    public Size|string|null $size = null;
    /**
     * Theme color rendered as the `theme-{value}` class on the list, or `null` for the default color.
     */
    public Theme|string|null $theme = null;

    /**
     * Initializes the widget and validates {@see $size} and {@see $theme}.
     *
     * @throws InvalidConfigException if {@see $size} or {@see $theme} is not a value of its enum.
     */
    public function init(): void
    {
        parent::init();

        Html::enumValue(Size::class, $this->size, 'size');
        Html::enumValue(Theme::class, $this->theme, 'theme');
    }

    /**
     * @throws InvalidConfigException if {@see $pagination} is not set.
     */
    public function run(): string
    {
        $pagination = $this->getPagination();

        BootstrapAsset::register($this->getView());

        if ($this->registerLinkTags) {
            $this->registerLinkTags($pagination);
        }

        $options = $this->options;

        $tag = ArrayHelper::remove($options, 'tag', 'nav');

        return Html::tag(
            is_string($tag) ? $tag : 'nav',
            $this->renderPageButtons($pagination),
            $options,
        );
    }

    /**
     * Returns the first and last internal page indexes to render.
     *
     * @return array{int, int}
     */
    protected function getPageRange(Pagination $pagination): array
    {
        $currentPage = $pagination->getPage();
        $pageCount = $pagination->getPageCount();
        $beginPage = max(0, $currentPage - ($this->maxButtonCount > 2 ? intdiv($this->maxButtonCount, 2) : 0));
        $endPage = $beginPage + $this->maxButtonCount - 1;

        if ($endPage >= $pageCount) {
            $endPage = $pageCount - 1;
            $beginPage = max(0, $endPage - $this->maxButtonCount + 1);
        }

        return [$beginPage, $endPage];
    }

    /**
     * Registers the relational link tags returned by {@see Pagination::getLinks()}.
     */
    protected function registerLinkTags(Pagination $pagination): void
    {
        $view = $this->getView();

        foreach ($pagination->getLinks() as $rel => $href) {
            $view->registerLinkTag(['rel' => $rel, 'href' => $href], (string) $rel);
        }
    }

    /**
     * Renders a page item.
     *
     * @param string $label Link content; it is not HTML-encoded.
     * @param int $page Zero-based page index.
     * @param string $class CSS class of the item, or an empty `string` for {@see $pageCssClass}.
     * @param bool $disabled Whether the item is disabled.
     * @param bool $active Whether the item is the current page.
     * @param array<string, mixed> $linkOptions Extra HTML attributes of the link.
     *
     * @return string Generated HTML.
     */
    protected function renderPageButton(
        string $label,
        int $page,
        string $class,
        bool $disabled,
        bool $active,
        array $linkOptions = [],
    ): string {
        $options = $this->linkContainerOptions;
        $tag = ArrayHelper::remove($options, 'tag', 'li');
        $linkOptions = ArrayHelper::merge($this->linkOptions, ['data-page' => $page], $linkOptions);

        Html::addCssClass($options, $class === '' ? $this->pageCssClass : $class);

        if ($active) {
            Html::addCssClass($options, $this->activePageCssClass);

            $linkOptions['aria-current'] = 'page';
        }

        if ($disabled) {
            Html::addCssClass($options, $this->disabledPageCssClass);

            $linkOptions = ArrayHelper::merge($linkOptions, $this->disabledListItemSubTagOptions);
            $linkOptions['tabindex'] = '-1';
        }

        return Html::tag(
            is_string($tag) ? $tag : 'li',
            Html::a($label, $this->getPagination()->createUrl($page), $linkOptions),
            $options,
        );
    }

    /**
     * Renders the list of page items, or an empty `string` when a single page is hidden.
     */
    protected function renderPageButtons(Pagination $pagination): string
    {
        $pageCount = $pagination->getPageCount();

        if ($pageCount < 2 && $this->hideOnSinglePage) {
            return '';
        }

        $currentPage = $pagination->getPage();
        $lastPage = $pageCount - 1;
        $buttons = [];

        $firstPageLabel = $this->firstPageLabel === true ? '1' : $this->firstPageLabel;

        if ($firstPageLabel !== false) {
            $buttons[] = $this->renderPageButton(
                $firstPageLabel,
                0,
                $this->firstPageCssClass,
                $currentPage <= 0,
                false,
            );
        }

        if ($this->prevPageLabel !== false) {
            $buttons[] = $this->renderPageButton(
                $this->prevPageLabel,
                max(0, $currentPage - 1),
                $this->prevPageCssClass,
                $currentPage <= 0,
                false,
                ['aria-label' => Yii::t(Bootstrap::TRANSLATION_CATEGORY, 'Previous')],
            );
        }

        [$beginPage, $endPage] = $this->getPageRange($pagination);

        for ($i = $beginPage; $i <= $endPage; ++$i) {
            $buttons[] = $this->renderPageButton(
                (string) ($i + 1),
                $i,
                '',
                $this->disableCurrentPageButton && $i === $currentPage,
                $i === $currentPage,
            );
        }

        if ($this->nextPageLabel !== false) {
            $buttons[] = $this->renderPageButton(
                $this->nextPageLabel,
                min($lastPage, $currentPage + 1),
                $this->nextPageCssClass,
                $currentPage >= $lastPage,
                false,
                ['aria-label' => Yii::t(Bootstrap::TRANSLATION_CATEGORY, 'Next')],
            );
        }

        $lastPageLabel = $this->lastPageLabel === true ? (string) $pageCount : $this->lastPageLabel;

        if ($lastPageLabel !== false) {
            $buttons[] = $this->renderPageButton(
                $lastPageLabel,
                $lastPage,
                $this->lastPageCssClass,
                $currentPage >= $lastPage,
                false,
            );
        }

        $options = $this->listOptions;
        $tag = ArrayHelper::remove($options, 'tag', 'ul');
        $size = Html::enumValue(Size::class, $this->size, 'size');

        if ($size !== null) {
            Html::addCssClass($options, "pagination-{$size}");
        }

        Html::theme($options, $this->theme);

        return Html::tag(
            is_string($tag) ? $tag : 'ul',
            implode("\n", $buttons),
            $options,
        );
    }

    /**
     * Returns the configured pagination.
     *
     * @throws InvalidConfigException if {@see $pagination} is not set.
     */
    private function getPagination(): Pagination
    {
        return $this->pagination ?? throw new InvalidConfigException(
            Message::PROPERTY_REQUIRED->getMessage('pagination'),
        );
    }
}
