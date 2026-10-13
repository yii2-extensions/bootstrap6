<?php

declare(strict_types=1);

namespace yii2\extensions\bootstrap6\tests;

use PHPUnit\Framework\Attributes\{DataProviderExternal, Group};
use yii\base\InvalidConfigException;
use yii\data\Pagination;
use yii\helpers\ReplaceArrayValue;
use yii2\extensions\bootstrap6\asset\BootstrapAsset;
use yii2\extensions\bootstrap6\enum\{Size, Theme};
use yii2\extensions\bootstrap6\exception\Message;
use yii2\extensions\bootstrap6\LinkPager;
use yii2\extensions\bootstrap6\tests\provider\LinkPagerProvider;
use yii2\extensions\bootstrap6\tests\support\TestCase;

/**
 * Unit tests for {@see LinkPager} page buttons, labels, states, and link tag registration.
 *
 * {@see LinkPagerProvider} for test case data providers.
 */
#[Group('widget')]
final class LinkPagerTest extends TestCase
{
    private const string NEXT = '<span aria-hidden="true">&raquo;</span>';
    private const string PREV = '<span aria-hidden="true">&laquo;</span>';

    public function testDisableCurrentPageButton(): void
    {
        $pagination = $this->getPagination(5);

        self::assertStringContainsString(
            <<<HTML
            <li class="page-item active"><a class="page-link" href="/?r=test&amp;page=6" data-page="5" aria-current="page">6</a></li>
            HTML,
            LinkPager::widget(['disableCurrentPageButton' => false, 'pagination' => $pagination]),
            'Current page must be active and enabled.',
        );
        self::assertStringContainsString(
            <<<HTML
            <li class="page-item active disabled"><a class="page-link" href="/?r=test&amp;page=6" data-page="5" aria-current="page" tabindex="-1">6</a></li>
            HTML,
            LinkPager::widget(['disableCurrentPageButton' => true, 'pagination' => $pagination]),
            'Current page must be active and disabled.',
        );
    }

    public function testDisabledPageElementOptions(): void
    {
        self::assertStringContainsString(
            '<li class="page-item prev disabled"><a class="page-link foo-bar"',
            LinkPager::widget(
                ['disabledListItemSubTagOptions' => ['class' => ['foo-bar']], 'pagination' => $this->getPagination(0)],
            ),
            'Disabled link options must be merged.',
        );
    }

    public function testFirstLastPageLabels(): void
    {
        $pagination = $this->getPagination(5);

        $output = LinkPager::widget(['firstPageLabel' => true, 'lastPageLabel' => true, 'pagination' => $pagination]);

        self::assertStringContainsString(
            '<li class="page-item first"><a class="page-link" href="/?r=test&amp;page=1" data-page="0">1</a></li>',
            $output,
            '`true` first label must use the page number.',
        );
        self::assertStringContainsString(
            '<li class="page-item last"><a class="page-link" href="/?r=test&amp;page=25" data-page="24">25</a></li>',
            $output,
            '`true` last label must use the page count.',
        );

        $output = LinkPager::widget(
            ['firstPageLabel' => 'First', 'lastPageLabel' => 'Last', 'pagination' => $pagination],
        );

        self::assertStringContainsString(
            '<li class="page-item first"><a class="page-link" href="/?r=test&amp;page=1" data-page="0">First</a></li>',
            $output,
            'First label must be rendered.',
        );
        self::assertStringContainsString(
            '<li class="page-item last"><a class="page-link" href="/?r=test&amp;page=25" data-page="24">Last</a></li>',
            $output,
            'Last label must be rendered.',
        );

        $output = LinkPager::widget(['firstPageLabel' => false, 'lastPageLabel' => false, 'pagination' => $pagination]);

        self::assertStringNotContainsString(
            'page-item first',
            $output,
            'First button must be omitted.',
        );
        self::assertStringNotContainsString(
            'page-item last',
            $output,
            'Last button must be omitted.',
        );
    }

    public function testFirstLastPagesDisabledAtBoundaries(): void
    {
        $next = self::NEXT;

        self::assertStringContainsString(
            <<<HTML
            <li class="page-item first disabled"><a class="page-link" href="/?r=test&amp;page=1" data-page="0" tabindex="-1">1</a></li>
            HTML,
            LinkPager::widget(['firstPageLabel' => true, 'pagination' => $this->getPagination(0)]),
            'First button must be disabled on the first page.',
        );

        $output = LinkPager::widget(['lastPageLabel' => true, 'pagination' => $this->getPagination(24)]);

        self::assertStringContainsString(
            <<<HTML
            <li class="page-item next disabled"><a class="page-link" href="/?r=test&amp;page=25" data-page="24" aria-label="Next" tabindex="-1">{$next}</a></li>
            HTML,
            $output,
            'Next button must be disabled on the last page.',
        );
        self::assertStringContainsString(
            <<<HTML
            <li class="page-item last disabled"><a class="page-link" href="/?r=test&amp;page=25" data-page="24" tabindex="-1">25</a></li>
            HTML,
            $output,
            'Last button must be disabled on the last page.',
        );
        self::assertStringContainsString(
            '<a class="page-link" href="/?r=test&amp;page=16" data-page="15">16</a>',
            $output,
            'Range must end at the last page.',
        );
        self::assertStringNotContainsString(
            'data-page="14">15</a>',
            $output,
            'Range must hold ten buttons.',
        );
    }

    public function testHideOnSinglePage(): void
    {
        $next = self::NEXT;
        $prev = self::PREV;

        $pagination = new Pagination(['route' => 'test', 'totalCount' => 5]);

        self::assertSame(
            '<nav id="w0"></nav>',
            LinkPager::widget(['pagination' => $pagination]),
            'Single page must render an empty container.',
        );
        self::assertSameHtml(
            <<<HTML
            <nav id="w1"><ul class="pagination"><li class="page-item prev disabled"><a class="page-link" href="/?r=test&amp;page=1" data-page="0" aria-label="Previous" tabindex="-1">{$prev}</a></li>
            <li class="page-item active"><a class="page-link" href="/?r=test&amp;page=1" data-page="0" aria-current="page">1</a></li>
            <li class="page-item next disabled"><a class="page-link" href="/?r=test&amp;page=1" data-page="0" aria-label="Next" tabindex="-1">{$next}</a></li></ul></nav>
            HTML,
            LinkPager::widget(['hideOnSinglePage' => false, 'pagination' => $pagination]),
            'Single page must be rendered when not hidden.',
        );
    }

    public function testLinkWrapOptions(): void
    {
        $output = LinkPager::widget(
            [
                'linkContainerOptions' => ['class' => 'my-class', 'tag' => 'div'],
                'pagination' => $this->getPagination(1),
            ],
        );

        self::assertStringContainsString(
            '<div class="my-class page-item"><a class="page-link" href="/?r=test&amp;page=3" data-page="2">3</a></div>',
            $output,
            'Item tag and class must be configurable.',
        );
        self::assertStringContainsString(
            <<<HTML
            <div class="my-class page-item active"><a class="page-link" href="/?r=test&amp;page=2" data-page="1" aria-current="page">2</a></div>
            HTML,
            $output,
            'Active item must keep the custom tag.',
        );
        self::assertStringContainsString(
            '<li class="page-item"><a class="page-link" href="/?r=test&amp;page=2" data-page="1">2</a></li>',
            LinkPager::widget(['linkContainerOptions' => ['tag' => null], 'pagination' => $this->getPagination(5)]),
            'Non-string item tag must fall back to `li`.',
        );
    }

    public function testListOptionsSizeAndTheme(): void
    {
        self::assertStringStartsWith(
            '<nav id="w0"><ol class="pagination pagination-lg theme-accent">',
            LinkPager::widget(
                [
                    'listOptions' => ['class' => ['pagination'], 'tag' => 'ol'],
                    'pagination' => $this->getPagination(1),
                    'size' => Size::LG,
                    'theme' => Theme::ACCENT,
                ],
            ),
            'Enum size and theme must be added to the list.',
        );
        self::assertStringStartsWith(
            '<nav id="w1"><ul class="pagination pagination-sm theme-danger">',
            LinkPager::widget(
                [
                    'listOptions' => ['class' => 'pagination', 'tag' => null],
                    'pagination' => $this->getPagination(1),
                    'size' => 'sm',
                    'theme' => 'danger',
                ],
            ),
            'String size and theme must be added and non-string tag must fall back to `ul`.',
        );
    }

    public function testOptionsWithTagOption(): void
    {
        $output = LinkPager::widget(['options' => ['tag' => 'div'], 'pagination' => $this->getPagination(5)]);

        self::assertStringStartsWith(
            '<div id="w0">',
            $output,
            'Container must start with the custom tag.',
        );
        self::assertStringEndsWith(
            '</div>',
            $output,
            'Container must end with the custom tag.',
        );
        self::assertStringStartsWith(
            '<nav id="w1">',
            LinkPager::widget(['options' => ['tag' => null], 'pagination' => $this->getPagination(5)]),
            'Non-string tag must fall back to `nav`.',
        );
    }

    public function testOverrideDisabledPageElementOptions(): void
    {
        self::assertStringContainsString(
            '<li class="page-item prev disabled"><a class="foo-bar"',
            LinkPager::widget(
                [
                    'disabledListItemSubTagOptions' => ['class' => new ReplaceArrayValue(['foo-bar'])],
                    'pagination' => $this->getPagination(0),
                ],
            ),
            'Replace value must override the link class.',
        );
    }

    public function testPreviousButtonTargetsPreviousPage(): void
    {
        self::assertStringContainsString(
            <<<HTML
            <li class="page-item prev"><a class="page-link" href="/?r=test&amp;page=5" data-page="4" aria-label="Previous">
            HTML,
            LinkPager::widget(['pagination' => $this->getPagination(5)]),
            'Previous button must target the previous page.',
        );
    }

    public function testPrevNextLabelsCanBeOmitted(): void
    {
        $output = LinkPager::widget(
            [
                'maxButtonCount' => 3,
                'nextPageLabel' => false,
                'pagination' => $this->getPagination(5),
                'prevPageLabel' => false,
            ],
        );

        self::assertSameHtml(
            <<<HTML
            <nav id="w0"><ul class="pagination"><li class="page-item"><a class="page-link" href="/?r=test&amp;page=5" data-page="4">5</a></li>
            <li class="page-item active"><a class="page-link" href="/?r=test&amp;page=6" data-page="5" aria-current="page">6</a></li>
            <li class="page-item"><a class="page-link" href="/?r=test&amp;page=7" data-page="6">7</a></li></ul></nav>
            HTML,
            $output,
            'Only internal pages centered on the current page must be rendered.',
        );
    }

    public function testRangeIsClampedAtBothEnds(): void
    {
        self::assertStringNotContainsString(
            'data-page="-1"',
            LinkPager::widget(['maxButtonCount' => 3, 'pagination' => $this->getPagination(0)]),
            'Range must not start before the first page.',
        );

        $output = LinkPager::widget(['nextPageLabel' => false, 'pagination' => $this->getPagination(21)]);

        self::assertStringContainsString(
            '<li class="page-item"><a class="page-link" href="/?r=test&amp;page=16" data-page="15">16</a></li>',
            $output,
            'Range must be shifted back to hold ten buttons.',
        );
        self::assertStringNotContainsString(
            'data-page="25"',
            $output,
            'Range must not exceed the last page.',
        );
    }

    public function testRegisterLinkTags(): void
    {
        LinkPager::widget(['pagination' => $this->getPagination(5), 'registerLinkTags' => true]);

        self::assertSame(
            [
                'self' => '<link href="/?r=test&amp;page=6" rel="self">',
                'first' => '<link href="/?r=test&amp;page=1" rel="first">',
                'last' => '<link href="/?r=test&amp;page=25" rel="last">',
                'prev' => '<link href="/?r=test&amp;page=5" rel="prev">',
                'next' => '<link href="/?r=test&amp;page=7" rel="next">',
            ],
            self::webView()->linkTags,
            'Relational links must be registered by `rel`.',
        );
    }

    public function testRegistersCssAssetWithoutLinkTags(): void
    {
        LinkPager::widget(['pagination' => $this->getPagination(5)]);

        self::assertArrayHasKey(
            BootstrapAsset::class,
            self::webView()->assetBundles,
            'CSS bundle must be registered.',
        );
        self::assertSame(
            [],
            self::webView()->linkTags,
            'Link tags must not be registered by default.',
        );
    }

    public function testRenderHelpersAreExtensionPoints(): void
    {
        $next = self::NEXT;
        $prev = self::PREV;

        $pager = new class (['pagination' => $this->getPagination(1), 'registerLinkTags' => true]) extends LinkPager {
            protected function getPageRange(Pagination $pagination): array
            {
                [$begin] = parent::getPageRange($pagination);

                return [$begin, $begin];
            }

            protected function registerLinkTags(Pagination $pagination): void
            {
                parent::registerLinkTags($pagination);

                $this->getView()->registerLinkTag(['rel' => 'custom', 'href' => '/'], 'custom');
            }

            protected function renderPageButton(
                string $label,
                int $page,
                string $class,
                bool $disabled,
                bool $active,
                array $linkOptions = [],
            ): string {
                return '[' . parent::renderPageButton($label, $page, $class, $disabled, $active, $linkOptions) . ']';
            }

            protected function renderPageButtons(Pagination $pagination): string
            {
                return '{' . parent::renderPageButtons($pagination) . '}';
            }
        };

        self::assertSameHtml(
            <<<HTML
            <nav id="w0">{<ul class="pagination">[<li class="page-item prev"><a class="page-link" href="/?r=test&amp;page=1" data-page="0" aria-label="Previous">{$prev}</a></li>]
            [<li class="page-item"><a class="page-link" href="/?r=test&amp;page=1" data-page="0">1</a></li>]
            [<li class="page-item next"><a class="page-link" href="/?r=test&amp;page=3" data-page="2" aria-label="Next">{$next}</a></li>]</ul>}</nav>
            HTML,
            $pager->run(),
            'Overridden helpers must decorate the parent output.',
        );
        self::assertArrayHasKey(
            'custom',
            self::webView()->linkTags,
            'Link tag helper must be overridable.',
        );
    }

    public function testRendersTwoPages(): void
    {
        self::assertStringContainsString(
            '<a class="page-link" href="/?r=test&amp;page=2" data-page="1">2</a>',
            LinkPager::widget(['pagination' => new Pagination(['route' => 'test', 'totalCount' => 40])]),
            'Two pages must not be hidden.',
        );
    }

    public function testShouldTriggerInitEvent(): void
    {
        $initTriggered = false;

        LinkPager::widget(
            [
                'on init' => static function () use (&$initTriggered): void {
                    $initTriggered = true;
                },
                'pagination' => $this->getPagination(1),
            ],
        );

        self::assertTrue(
            $initTriggered,
            'Init event must be triggered.',
        );
    }

    /**
     * @param array<string, mixed> $config
     */
    #[DataProviderExternal(LinkPagerProvider::class, 'invalidEnumValueCases')]
    public function testThrowInvalidConfigExceptionForInvalidEnumValue(array $config, string $message): void
    {
        $this->mockWebApplication();

        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage(
            $message,
        );

        new LinkPager([...$config]);
    }

    public function testThrowInvalidConfigExceptionWhenPaginationIsMissing(): void
    {
        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage(
            Message::PROPERTY_REQUIRED->getMessage('pagination'),
        );

        LinkPager::widget();
    }

    public function testWithNoButtons(): void
    {
        $next = self::NEXT;
        $prev = self::PREV;

        self::assertSameHtml(
            <<<HTML
            <nav id="w0"><ul class="pagination"><li class="page-item prev disabled"><a class="page-link" href="/?r=test&amp;page=1" data-page="0" aria-label="Previous" tabindex="-1">{$prev}</a></li>
            <li class="page-item next"><a class="page-link" href="/?r=test&amp;page=2" data-page="1" aria-label="Next">{$next}</a></li></ul></nav>
            HTML,
            LinkPager::widget(['maxButtonCount' => 0, 'pagination' => $this->getPagination(0)]),
            'Only previous and next buttons must be rendered on the first page.',
        );
        self::assertSameHtml(
            <<<HTML
            <nav id="w1"><ul class="pagination"><li class="page-item prev"><a class="page-link" href="/?r=test&amp;page=1" data-page="0" aria-label="Previous">{$prev}</a></li>
            <li class="page-item next"><a class="page-link" href="/?r=test&amp;page=3" data-page="2" aria-label="Next">{$next}</a></li></ul></nav>
            HTML,
            LinkPager::widget(['maxButtonCount' => 0, 'pagination' => $this->getPagination(1)]),
            'Only previous and next buttons must be rendered on the second page.',
        );
    }

    public function testWithOneButton(): void
    {
        $prev = self::PREV;

        self::assertStringContainsString(
            <<<HTML
            <li class="page-item prev"><a class="page-link" href="/?r=test&amp;page=1" data-page="0" aria-label="Previous">{$prev}</a></li>
            <li class="page-item active"><a class="page-link" href="/?r=test&amp;page=2" data-page="1" aria-current="page">2</a></li>
            <li class="page-item next">
            HTML,
            LinkPager::widget(['maxButtonCount' => 1, 'pagination' => $this->getPagination(1)]),
            'Only the current page must be rendered.',
        );
    }

    public function testWithThreeButtonsCentersCurrentPage(): void
    {
        $output = LinkPager::widget(['maxButtonCount' => 3, 'pagination' => $this->getPagination(1)]);

        self::assertStringContainsString(
            'data-page="0">1</a>',
            $output,
            'Previous page must be rendered.',
        );
        self::assertStringContainsString(
            'data-page="2">3</a>',
            $output,
            'Next page must be rendered.',
        );
        self::assertStringNotContainsString(
            'data-page="3">4</a>',
            $output,
            'Range must hold three buttons.',
        );
    }

    public function testWithTwoButtons(): void
    {
        $next = self::NEXT;
        $prev = self::PREV;

        self::assertSameHtml(
            <<<HTML
            <nav id="w0"><ul class="pagination"><li class="page-item prev disabled"><a class="page-link" href="/?r=test&amp;page=1" data-page="0" aria-label="Previous" tabindex="-1">{$prev}</a></li>
            <li class="page-item active"><a class="page-link" href="/?r=test&amp;page=1" data-page="0" aria-current="page">1</a></li>
            <li class="page-item"><a class="page-link" href="/?r=test&amp;page=2" data-page="1">2</a></li>
            <li class="page-item next"><a class="page-link" href="/?r=test&amp;page=2" data-page="1" aria-label="Next">{$next}</a></li></ul></nav>
            HTML,
            LinkPager::widget(['maxButtonCount' => 2, 'pagination' => $this->getPagination(0)]),
            'Two buttons must start at the current page.',
        );
        self::assertStringContainsString(
            <<<HTML
            <li class="page-item active"><a class="page-link" href="/?r=test&amp;page=2" data-page="1" aria-current="page">2</a></li>
            <li class="page-item"><a class="page-link" href="/?r=test&amp;page=3" data-page="2">3</a></li>
            HTML,
            LinkPager::widget(['maxButtonCount' => 2, 'pagination' => $this->getPagination(1)]),
            'Two buttons must not include the previous page.',
        );
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockWebApplication(['components' => ['urlManager' => ['scriptUrl' => '/']]]);
    }

    private function getPagination(int $page): Pagination
    {
        $pagination = new Pagination(['route' => 'test', 'totalCount' => 500]);

        $pagination->setPage($page);

        return $pagination;
    }
}
