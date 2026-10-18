<?php

declare(strict_types=1);

namespace yii2\extensions\bootstrap6\tests;

use PHPUnit\Framework\Attributes\{Group, TestWith};
use UIAwesome\Html\Svg\{Path, Svg};
use Yii;
use yii\base\InvalidConfigException;
use yii2\extensions\bootstrap6\asset\BootstrapPluginAsset;
use yii2\extensions\bootstrap6\{Combobox, IconSet};
use yii2\extensions\bootstrap6\enum\Placement;
use yii2\extensions\bootstrap6\exception\Message;
use yii2\extensions\bootstrap6\tests\support\stub\{StringableValue, User};
use yii2\extensions\bootstrap6\tests\support\TestCase;

/**
 * Unit tests for {@see Combobox} toggle and menu rendering, selection state, data attributes, and plugin registration.
 */
#[Group('widget')]
final class ComboboxTest extends TestCase
{
    private const string CARET = <<<HTML
    <svg class="combobox-caret" width="10" height="16" viewBox="0 0 10 16" fill="none" xmlns="http://www.w3.org/2000/svg">
    <path d="M0.46967 5.46967C0.762563 5.17678 1.23744 5.17678 1.53033 5.46967L5 8.93934L8.46967 5.46967C8.76256 5.17678 9.23744 5.17678 9.53033 5.46967C9.82322 5.76256 9.82322 6.23744 9.53033 6.53033L5.53033 10.5303C5.23744 10.8232 4.76256 10.8232 4.46967 10.5303L0.46967 6.53033C0.176777 6.23744 0.176777 5.76256 0.46967 5.46967Z" fill="currentcolor">
    </svg>
    HTML;
    private const string CHECK = <<<HTML
    <svg class="menu-item-check" width="16" height="16" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewBox="0 0 16 16">
    <path d="m2 7 4 5 8-8">
    </svg>
    HTML;
    private const string IMPORT = 'import("/assets/js/bootstrap.bundle.js").then((bootstrap) => { ';

    /**
     * @param array<string, mixed>|false $clientOptions Plugin options, or `false` for data-API initialization.
     */
    #[TestWith([[]])]
    #[TestWith([false])]
    #[TestWith([['name' => null]])]
    public function testRegisterInputAttributes(array|false $clientOptions): void
    {
        $this->mockWebApplication();

        Combobox::widget(
            [
                'name' => 'option',
                'items' => ['1' => 'One'],
                'clientOptions' => $clientOptions,
                'options' => ['id' => 'choice'],
            ],
        );

        $code = <<<JS
        const toggle = document.getElementById("choice");
        const sync = () => {
            const input = bootstrap.Combobox.getInstance(toggle)?._hiddenInput;
            if (!input) {
                return;
            }
            input.disabled = toggle.disabled;
            const form = toggle.getAttribute('form');
            if (form === null) {
                input.removeAttribute('form');
            } else {
                input.setAttribute('form', form);
            }
        };
        let parent = toggle.parentElement;
        const observe = () => {
            observer.observe(toggle, {attributes: true, attributeFilter: ['disabled', 'form']});
            if (parent) {
                observer.observe(parent, {childList: true});
            }
        };
        const observer = new MutationObserver(() => {
            if (toggle.parentElement !== parent) {
                observer.disconnect();
                parent = toggle.parentElement;
                if (!parent) {
                    return;
                }
                observe();
            }
            sync();
        });
        observe();
        sync();
        JS;

        self::assertSame(
            self::IMPORT . $code . ' });',
            self::registeredJs(self::webView(), 'choice-input'),
            'Input synchronization must release removed toggles and follow the current instance and parent.',
        );

        if ($clientOptions === false) {
            self::assertNull(
                self::registeredJs(self::webView(), 'choice-plugin'),
                'Synchronizing the submitted control must not force explicit plugin initialization.',
            );
        }
    }

    public function testRegisterPluginAssetWithDefaultInitialization(): void
    {
        $this->mockWebApplication();

        Combobox::widget(['name' => 'option', 'items' => ['1' => 'One']]);

        self::assertArrayHasKey(
            BootstrapPluginAsset::class,
            self::webView()->assetBundles,
            'Plugin bundle must be registered for the data API.',
        );
        self::assertSame(
            self::IMPORT . 'bootstrap.Combobox.getOrCreateInstance(document.getElementById("w0"), {}); });',
            self::registeredJs(self::webView(), 'w0-plugin'),
            'Default initialization must also support fragments inserted after page load.',
        );
    }

    public function testRegisterPluginWithClientOptions(): void
    {
        $this->mockWebApplication();

        Combobox::widget(
            [
                'name' => 'option',
                'items' => ['1' => 'One'],
                'clientOptions' => ['searchNormalize' => true],
                'clientEvents' => ['change.bs.combobox' => 'onChange'],
            ],
        );

        $import = self::IMPORT;

        self::assertSame(
            <<<JS
            {$import}bootstrap.Combobox.getOrCreateInstance(document.getElementById("w0"), {"searchNormalize":true}); });
            JS,
            self::registeredJs(self::webView(), 'w0-plugin'),
            'Constructor options must initialize the toggle.',
        );
        self::assertSame(
            'document.getElementById("w0").addEventListener("change.bs.combobox", onChange);',
            self::registeredJs(self::webView(), 'w0-events'),
            'Events must be attached to the toggle.',
        );
    }

    public function testRenderContainerIcons(): void
    {
        $this->mockWebApplication();

        Yii::$container->set(
            IconSet::class,
            [
                'comboboxCaret' => Svg::tag()->class('my-caret')->html(Path::tag()->d('m0 0')),
                'menuItemCheck' => Svg::tag()->class('my-check')->html(Path::tag()->d('m1 1')),
            ],
        );

        self::assertSameHtml(
            <<<HTML
            <button type="button" id="w0" class="form-control combobox-toggle" data-bs-toggle="combobox" data-bs-name="option"><span class="combobox-value"></span>
            <svg class="my-caret combobox-caret">
            <path d="m0 0">
            </svg></button>
            <div class="menu">
            <button type="button" class="menu-item" data-bs-value="1">One
            <svg class="my-check menu-item-check">
            <path d="m1 1">
            </svg></button>
            </div>
            HTML,
            Combobox::widget(['name' => 'option', 'items' => ['1' => 'One'], 'check' => true]),
            'Container icons must replace the defaults and receive the structural classes.',
        );
    }

    public function testRenderIconsString(): void
    {
        $this->mockWebApplication();

        self::assertSameHtml(
            <<<HTML
            <button type="button" id="w0" class="form-control combobox-toggle" data-bs-toggle="combobox" data-bs-name="option"><span class="combobox-value"></span>
            <i class="bi bi-chevron-down"></i></button>
            <div class="menu">
            <button type="button" class="menu-item" data-bs-value="1">One
            <i class="bi bi-check"></i></button>
            </div>
            HTML,
            Combobox::widget(
                [
                    'name' => 'option',
                    'items' => ['1' => 'One'],
                    'caretIcon' => '<i class="bi bi-chevron-down"></i>',
                    'check' => true,
                    'checkIcon' => '<i class="bi bi-check"></i>',
                ],
            ),
            'Markup must be rendered verbatim, without the structural classes.',
        );
    }

    public function testRenderIconsSvg(): void
    {
        $this->mockWebApplication();

        Yii::$container->set(
            IconSet::class,
            ['comboboxCaret' => '<i>container caret</i>', 'menuItemCheck' => '<i>container check</i>'],
        );

        self::assertSameHtml(
            <<<HTML
            <button type="button" id="w0" class="form-control combobox-toggle" data-bs-toggle="combobox" data-bs-name="option"><span class="combobox-value"></span>
            <svg class="my-caret combobox-caret">
            <path d="m0 0">
            </svg></button>
            <div class="menu">
            <button type="button" class="menu-item" data-bs-value="1">One
            <svg class="my-check menu-item-check">
            <path d="m1 1">
            </svg></button>
            </div>
            HTML,
            Combobox::widget(
                [
                    'name' => 'option',
                    'items' => ['1' => 'One'],
                    'caretIcon' => Svg::tag()->class('my-caret')->html(Path::tag()->d('m0 0')),
                    'check' => true,
                    'checkIcon' => Svg::tag()->class('my-check')->html(Path::tag()->d('m1 1')),
                ],
            ),
            'Instance icons must take precedence and receive the structural classes.',
        );
    }

    public function testRenderMethodsAreOverridable(): void
    {
        $this->mockWebApplication();

        $combobox = new class (['name' => 'option', 'items' => ['1' => 'One']]) extends Combobox {
            protected function renderItem(int|string $value, string $label, array $selected): string
            {
                return '<item>' . parent::renderItem($value, $label, $selected) . '</item>';
            }

            protected function renderMenu(array $selected): string
            {
                return '<menu>' . parent::renderMenu($selected) . '</menu>';
            }

            protected function renderToggle(array $selected): string
            {
                return '<toggle>' . parent::renderToggle($selected) . '</toggle>';
            }
        };

        $html = $combobox->run();

        self::assertStringStartsWith(
            '<toggle><button',
            $html,
            'Toggle must be overridable.',
        );
        self::assertStringContainsString(
            '<menu><div class="menu">',
            $html,
            'Menu must be overridable.',
        );
        self::assertStringContainsString(
            '<item><button type="button" class="menu-item"',
            $html,
            'Item must be overridable.',
        );
    }

    public function testRenderModelWithoutAttributeUsesName(): void
    {
        $this->mockWebApplication();

        $user = new User();

        $user->username = '1';

        self::assertStringContainsString(
            'data-bs-name="option"><span class="combobox-value"></span>',
            Combobox::widget(['model' => $user, 'name' => 'option', 'items' => ['1' => 'One']]),
            'Name mode must ignore the model value.',
        );
    }

    public function testRenderMultipleWithCheckAndArrayValue(): void
    {
        $this->mockWebApplication();

        $caret = self::CARET;
        $check = self::CHECK;

        self::assertSameHtml(
            <<<HTML
            <button type="button" id="w0" class="form-control combobox-toggle" data-bs-toggle="combobox" data-bs-name="skills" data-bs-multiple="true"><span class="combobox-value">CSS</span>
            {$caret}</button>
            <div class="menu">
            <button type="button" class="menu-item" data-bs-value="1">HTML
            {$check}</button>
            <button type="button" class="menu-item selected" data-bs-value="2" aria-selected="true">CSS
            {$check}</button>
            <button type="button" class="menu-item selected" data-bs-value="js" aria-selected="true">JavaScript
            {$check}</button>
            </div>
            HTML,
            Combobox::widget(
                [
                    'name' => 'skills',
                    'value' => [2, 'js', ['nested']],
                    'items' => [1 => 'HTML', 2 => 'CSS', 'js' => 'JavaScript'],
                    'multiple' => true,
                    'check' => true,
                ],
            ),
            'Every selected value must be marked and the first one shown.',
        );
    }

    public function testRenderPlacementEnum(): void
    {
        $this->mockWebApplication();

        self::assertStringContainsString(
            'data-bs-name="option" data-bs-placement="top-end">',
            Combobox::widget(['name' => 'option', 'items' => ['1' => 'One'], 'placement' => Placement::TOP_END]),
            'Placement enum must be emitted by value.',
        );
    }

    public function testRenderPlacementString(): void
    {
        $this->mockWebApplication();

        self::assertStringContainsString(
            'data-bs-name="option" data-bs-placement="bottom-end">',
            Combobox::widget(['name' => 'option', 'items' => ['1' => 'One'], 'placement' => 'bottom-end']),
            'Placement string must be emitted as is.',
        );
    }

    public function testRenderResponsivePlacement(): void
    {
        $this->mockWebApplication();

        self::assertStringContainsString(
            'data-bs-placement="bottom-start md:top-end"',
            Combobox::widget(['name' => 'c', 'items' => ['1' => 'One'], 'placement' => 'bottom-start md:top-end']),
            'Breakpoint-prefixed placement must be accepted.',
        );
    }

    public function testRenderSearch(): void
    {
        $this->mockWebApplication();

        $caret = self::CARET;

        self::assertSameHtml(
            <<<HTML
            <button type="button" id="w0" class="form-control combobox-toggle" data-bs-toggle="combobox" data-bs-name="country" data-bs-search="true"><span class="combobox-value"></span>
            {$caret}</button>
            <div class="menu">
            <div class="combobox-search"><input type="text" class="form-control combobox-search-input" placeholder="Find" autocomplete="off" aria-label="Find"></div>
            <button type="button" class="menu-item" data-bs-value="us">United States</button>
            <div class="combobox-no-results d-none">Nothing &amp; more</div>
            </div>
            HTML,
            Combobox::widget(
                [
                    'name' => 'country',
                    'items' => ['us' => 'United States'],
                    'search' => true,
                    'searchPlaceholder' => 'Find',
                    'noResultsText' => 'Nothing & more',
                ],
            ),
            'Search field and no-results message must wrap the items.',
        );
    }

    public function testRenderSearchDefaultTexts(): void
    {
        $this->mockWebApplication();

        $html = Combobox::widget(['name' => 'country', 'items' => ['us' => 'United States'], 'search' => true]);

        self::assertStringContainsString(
            'placeholder="Search..." autocomplete="off" aria-label="Search..."',
            $html,
            'Default search placeholder must be used.',
        );
        self::assertStringContainsString(
            '<div class="combobox-no-results d-none">No results found</div>',
            $html,
            'Default no-results text must be used.',
        );
    }

    public function testRenderStringableContent(): void
    {
        $this->mockWebApplication();

        self::assertSame(
            Combobox::widget([
                'items' => ['1' => 'One'],
                'name' => 'c',
                'noResultsText' => 'None & more',
                'options' => ['id' => 'c'],
                'search' => true,
                'searchPlaceholder' => 'Find "x"',
            ]),
            Combobox::widget([
                'items' => ['1' => 'One'],
                'name' => 'c',
                'noResultsText' => new StringableValue('None & more'),
                'options' => ['id' => 'c'],
                'search' => true,
                'searchPlaceholder' => new StringableValue('Find "x"'),
            ]),
            'Stringable values must render like their `string`.',
        );
    }

    public function testRenderWithModel(): void
    {
        $this->mockWebApplication();

        $user = new User();

        $user->username = 'editor';
        $caret = self::CARET;

        self::assertSameHtml(
            <<<HTML
            <button type="button" id="user-username" class="form-control-sm form-control combobox-toggle" data-bs-toggle="combobox" data-bs-name="User[username]" data-bs-placeholder="Choose a role &amp; more"><span class="combobox-value">Editor &lt;b&gt;</span>
            {$caret}</button>
            <div class="custom menu">
            <button type="button" class="menu-item" data-bs-value="admin">Admin</button>
            <button type="button" class="menu-item selected" data-bs-value="editor" aria-selected="true">Editor &lt;b&gt;</button>
            </div>
            HTML,
            Combobox::widget(
                [
                    'model' => $user,
                    'attribute' => 'username',
                    'items' => ['admin' => 'Admin', 'editor' => 'Editor <b>'],
                    'placeholder' => 'Choose a role & more',
                    'options' => ['class' => 'form-control-sm'],
                    'menuOptions' => ['class' => 'custom'],
                ],
            ),
            'Model value must preselect the matching item.',
        );
    }

    public function testRenderWithoutEncoding(): void
    {
        $this->mockWebApplication();

        $caret = self::CARET;

        self::assertSameHtml(
            <<<HTML
            <button type="button" id="w0" class="form-control combobox-toggle" data-bs-toggle="combobox" data-bs-name="option" data-bs-placeholder="&lt;i&gt;Pick&lt;/i&gt;"><span class="combobox-value"><i>Pick</i></span>
            {$caret}</button>
            <div class="menu">
            <button type="button" class="menu-item" data-bs-value="1"><b>One</b></button>
            </div>
            HTML,
            Combobox::widget(
                [
                    'name' => 'option',
                    'items' => ['1' => '<b>One</b>'],
                    'placeholder' => '<i>Pick</i>',
                    'encodeLabels' => false,
                ],
            ),
            'Labels and placeholder must be rendered raw.',
        );
    }

    public function testThrowInvalidConfigExceptionForInvalidPlacement(): void
    {
        $this->mockWebApplication();

        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage(
            Message::INVALID_VALUE->getMessage(
                'middle',
                'placement',
                'bottom, bottom-end, bottom-start, end, end-end, end-start, left, left-end, left-start, right, '
                . 'right-end, right-start, start, start-end, start-start, top, top-end, top-start',
            ),
        );

        new Combobox(['items' => ['1' => 'One'], 'name' => 'c', 'placement' => 'middle']);
    }

    public function testThrowInvalidConfigExceptionWhenItemsAreEmpty(): void
    {
        $this->mockWebApplication();

        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage(
            Message::INVALID_ITEMS->getMessage(),
        );

        Combobox::widget(['name' => 'option']);
    }
}
