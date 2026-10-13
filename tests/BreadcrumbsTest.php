<?php

declare(strict_types=1);

namespace yii2\extensions\bootstrap6\tests;

use PHPUnit\Framework\Attributes\Group;
use yii\base\InvalidConfigException;
use yii2\extensions\bootstrap6\asset\BootstrapAsset;
use yii2\extensions\bootstrap6\Breadcrumbs;
use yii2\extensions\bootstrap6\exception\Message;
use yii2\extensions\bootstrap6\item\BreadcrumbLink;
use yii2\extensions\bootstrap6\tests\support\stub\StringableValue;
use yii2\extensions\bootstrap6\tests\support\TestCase;

/**
 * Unit tests for {@see Breadcrumbs} items, dividers, home link, and templates.
 */
#[Group('widget')]
final class BreadcrumbsTest extends TestCase
{
    public function testActiveItemWithUrlAndCustomTemplate(): void
    {
        $this->mockWebApplication();

        self::assertSameHtml(
            <<<HTML
            <nav aria-label="breadcrumb"><ol id="w0" class="breadcrumb"><li class="breadcrumb-item" aria-current="page"><a class="breadcrumb-link active" href="#">Data</a></li>
            <li class="breadcrumb-divider"></li>
            <span><a class="breadcrumb-link">Custom</a></span></ol></nav>
            HTML,
            Breadcrumbs::widget(
                [
                    'homeLink' => false,
                    'links' => [
                        ['active' => true, 'label' => 'Data', 'url' => '#'],
                        ['active' => false, 'label' => 'Custom', 'template' => '<span>{link}</span>', 'url' => 1],
                    ],
                ],
            ),
            'Explicit `active` and `template` keys must be honored.',
        );
    }

    public function testDefaultHomeLinkAndKeyedLinks(): void
    {
        $this->mockWebApplication(['homeUrl' => '/home']);

        self::assertSameHtml(
            <<<HTML
            <nav aria-label="breadcrumb"><ol id="w0" class="breadcrumb"><li class="breadcrumb-item"><a class="breadcrumb-link" href="/home">Home</a></li>
            <li class="breadcrumb-divider"></li>
            <li class="breadcrumb-item"><a class="breadcrumb-link" href="/library">Library</a></li>
            <li class="breadcrumb-divider"></li>
            <li class="breadcrumb-item" aria-current="page"><a class="breadcrumb-link active">Data &amp; more</a></li>
            </ol></nav>
            HTML,
            Breadcrumbs::widget(['links' => ['/library' => 'Library', 'Data & more']]),
            'Default home link and `url => label` pairs must be rendered.',
        );
    }

    public function testDividerContentAndOptions(): void
    {
        $this->mockWebApplication();

        self::assertSameHtml(
            <<<HTML
            <nav class="trail"><ul id="crumbs" class="small breadcrumb"><li class="breadcrumb-item"><a class="breadcrumb-link" href="#"><b>Home</b></a></li>
            <li class="breadcrumb-divider fg-3">/</li>
            <li class="breadcrumb-item" aria-current="page"><a class="breadcrumb-link active">&lt;i&gt;Data&lt;/i&gt;</a></li>
            </ul></nav>
            HTML,
            Breadcrumbs::widget(
                [
                    'divider' => '/',
                    'dividerOptions' => ['class' => 'breadcrumb-divider fg-3'],
                    'encodeLabels' => false,
                    'homeLink' => ['label' => '<b>Home</b>', 'url' => '#'],
                    'links' => [['encode' => true, 'label' => '<i>Data</i>']],
                    'navOptions' => ['class' => 'trail'],
                    'options' => ['class' => 'small', 'id' => 'crumbs'],
                    'tag' => 'ul',
                ],
            ),
            'Divider content, tags, and encoding overrides must be rendered.',
        );
    }

    public function testEmptyLinksRenderNothing(): void
    {
        $this->mockWebApplication();

        self::assertSame(
            '',
            Breadcrumbs::widget(),
            'No links must render nothing.',
        );
        self::assertArrayNotHasKey(
            BootstrapAsset::class,
            self::webView()->assetBundles,
            'Empty trail must not register assets.',
        );
    }

    public function testRender(): void
    {
        $this->mockWebApplication();

        self::assertSameHtml(
            <<<HTML
            <nav aria-label="breadcrumb"><ol id="w0" class="breadcrumb"><li class="breadcrumb-item"><a class="breadcrumb-link" href="#">Home</a></li>
            <li class="breadcrumb-divider"></li>
            <li class="breadcrumb-item"><a class="breadcrumb-link" href="#">Library</a></li>
            <li class="breadcrumb-divider"></li>
            <li class="breadcrumb-item" aria-current="page"><a class="breadcrumb-link active">Data</a></li>
            </ol></nav>
            HTML,
            Breadcrumbs::widget(
                [
                    'homeLink' => ['label' => 'Home', 'url' => '#'],
                    'links' => [['label' => 'Library', 'url' => '#'], ['label' => 'Data']],
                ],
            ),
            'Items must be separated by divider elements.',
        );
        self::assertArrayHasKey(
            BootstrapAsset::class,
            self::webView()->assetBundles,
            'CSS bundle must be registered.',
        );
    }

    public function testRenderItemIsExtensionPoint(): void
    {
        $this->mockWebApplication();

        $breadcrumbs = new class (['homeLink' => false, 'links' => ['Data']]) extends Breadcrumbs {
            protected function renderItem(BreadcrumbLink $link): string
            {
                return '[' . parent::renderItem($link) . ']';
            }
        };

        self::assertStringContainsString(
            '[<li class="breadcrumb-item" aria-current="page"><a class="breadcrumb-link active">Data</a></li>' . "\n]",
            $breadcrumbs->run(),
            'Overridden helper must decorate the parent output.',
        )
        ;
    }

    public function testRenderMixedLinks(): void
    {
        $this->mockWebApplication(['homeUrl' => '/home']);

        self::assertSameHtml(
            <<<HTML
            <nav aria-label="breadcrumb"><ol id="w0" class="breadcrumb"><li class="breadcrumb-item"><a class="breadcrumb-link" href="/home">Home</a></li>
            <li class="breadcrumb-divider"></li>
            <li class="breadcrumb-item"><a class="breadcrumb-link" href="/library">Library</a></li>
            <li class="breadcrumb-divider"></li>
            <li class="breadcrumb-item"><a class="breadcrumb-link" href="/docs" title="D">Docs</a></li>
            <li class="breadcrumb-divider"></li>
            <li class="breadcrumb-item"><a class="breadcrumb-link" href="/api">API</a></li>
            <li class="breadcrumb-divider"></li>
            <li class="breadcrumb-item" aria-current="page"><a class="breadcrumb-link active">Data &amp; more</a></li>
            </ol></nav>
            HTML,
            Breadcrumbs::widget(
                [
                    'links' => [
                        '/library' => 'Library',
                        BreadcrumbLink::to('Docs', '/docs')->withOptions(['title' => 'D']),
                        ['label' => 'API', 'url' => '/api'],
                        'Data & more',
                    ],
                ],
            ),
            'Value objects, arrays, and shortcuts must render in order.',
        );
    }

    public function testRenderStringableItems(): void
    {
        $this->mockWebApplication();

        self::assertSame(
            Breadcrumbs::widget(['links' => [['label' => 'A & a', 'url' => '/a'], ['label' => 'B']], 'options' => ['id' => 'b']]),
            Breadcrumbs::widget([
                'links' => [
                    ['label' => new StringableValue('A & a'), 'url' => '/a'],
                    ['label' => new StringableValue('B')],
                ],
                'options' => ['id' => 'b'],
            ]),
            'Stringable values must render like their `string`.',
        );
    }

    public function testRenderValueObjectLinks(): void
    {
        $this->mockWebApplication();

        self::assertSameHtml(
            <<<HTML
            <nav aria-label="breadcrumb"><ol id="w0" class="breadcrumb"><li class="breadcrumb-item"><a class="breadcrumb-link" href="#">Home</a></li>
            <li class="breadcrumb-divider"></li>
            <li class="breadcrumb-item"><a class="breadcrumb-link" href="#">Library</a></li>
            <li class="breadcrumb-divider"></li>
            <li class="breadcrumb-item" aria-current="page"><a class="breadcrumb-link active">Data</a></li>
            </ol></nav>
            HTML,
            Breadcrumbs::widget(
                [
                    'homeLink' => BreadcrumbLink::to('Home', '#'),
                    'links' => [BreadcrumbLink::to('Library', '#'), BreadcrumbLink::to('Data')],
                ],
            ),
            'Value objects must render like the array form.',
        );
    }

    public function testRenderValueObjectTemplateAndEncoding(): void
    {
        $this->mockWebApplication();

        self::assertSameHtml(
            <<<HTML
            <nav aria-label="breadcrumb"><ol id="w0" class="breadcrumb"><li class="breadcrumb-item" aria-current="page"><a class="breadcrumb-link active" href="#">Data</a></li>
            <li class="breadcrumb-divider"></li>
            <span><a class="breadcrumb-link">Custom</a></span></ol></nav>
            <nav class="trail"><ul id="crumbs" class="small breadcrumb"><li class="breadcrumb-item"><a class="breadcrumb-link" href="#"><b>Home</b></a></li>
            <li class="breadcrumb-divider fg-3">/</li>
            <li class="breadcrumb-item" aria-current="page"><a class="breadcrumb-link active">&lt;i&gt;Data&lt;/i&gt;</a></li>
            </ul></nav>
            HTML,
            Breadcrumbs::widget(
                [
                    'homeLink' => false,
                    'links' => [
                        BreadcrumbLink::to('Data', '#')->withActive(true),
                        BreadcrumbLink::to('Custom')->withActive(false)->withTemplate('<span>{link}</span>'),
                    ],
                ],
            )
            . "\n"
            . Breadcrumbs::widget(
                [
                    'divider' => '/',
                    'dividerOptions' => ['class' => 'breadcrumb-divider fg-3'],
                    'encodeLabels' => false,
                    'homeLink' => BreadcrumbLink::to('<b>Home</b>', '#'),
                    'links' => [BreadcrumbLink::to('<i>Data</i>')->withEncode(true)],
                    'navOptions' => ['class' => 'trail'],
                    'options' => ['class' => 'small', 'id' => 'crumbs'],
                    'tag' => 'ul',
                ],
            ),
            'Templates and encoding flags must render like the array form.',
        );
    }

    public function testRenderWithoutHomeLink(): void
    {
        $this->mockWebApplication();

        self::assertSameHtml(
            <<<HTML
            <nav aria-label="breadcrumb"><ol id="w0" class="breadcrumb"><li class="breadcrumb-item"><a class="breadcrumb-link" href="#" title="Lib">Library</a></li>
            <li class="breadcrumb-divider"></li>
            <li class="breadcrumb-item" aria-current="page"><a class="breadcrumb-link active">Data</a></li>
            </ol></nav>
            HTML,
            Breadcrumbs::widget(
                ['homeLink' => false, 'links' => [['label' => 'Library', 'title' => 'Lib', 'url' => '#'], 'Data']],
            ),
            'Home link must be omitted and extra keys rendered as attributes.',
        );
    }

    public function testThrowInvalidConfigExceptionWhenLabelIsMissing(): void
    {
        $this->mockWebApplication();

        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage(
            Message::ITEM_LABEL_REQUIRED->getMessage(),
        );

        Breadcrumbs::widget(['links' => [['url' => '#']]]);
    }
}
