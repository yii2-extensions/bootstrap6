<?php

declare(strict_types=1);

namespace yii2\extensions\bootstrap6\tests;

use PHPUnit\Framework\Attributes\Group;
use yii\base\Model;
use yii2\extensions\bootstrap6\asset\BootstrapPluginAsset;
use yii2\extensions\bootstrap6\Chips;
use yii2\extensions\bootstrap6\tests\support\stub\User;
use yii2\extensions\bootstrap6\tests\support\TestCase;

/**
 * Unit tests for {@see Chips} container, chip and input rendering, data attributes, and value synchronization.
 */
#[Group('widget')]
final class ChipsTest extends TestCase
{
    private const string IMPORT = 'import("/assets/js/bootstrap.bundle.js").then((bootstrap) => { ';

    public function testRegisterPluginAssetWithoutInitialization(): void
    {
        $this->mockWebApplication();

        Chips::widget(['name' => 'tags']);

        self::assertArrayHasKey(
            BootstrapPluginAsset::class,
            self::webView()->assetBundles,
            'Plugin bundle must be registered for the data API.',
        );
        self::assertNull(
            self::registeredJs(self::webView(), 'w0-chips-plugin'),
            'Data API must not need an inline init.',
        );
    }

    public function testRegisterPluginOnContainer(): void
    {
        $this->mockWebApplication();

        Chips::widget(
            [
                'name' => 'tags',
                'clientOptions' => ['dismissIcon' => 'x'],
                'clientEvents' => ['add.bs.chips' => 'onAdd'],
            ],
        );

        $import = self::IMPORT;

        self::assertSame(
            <<<JS
            {$import}bootstrap.Chips.getOrCreateInstance(document.getElementById("w0-chips"), {"dismissIcon":"x"}); });
            JS,
            self::registeredJs(self::webView(), 'w0-chips-plugin'),
            'Plugin must target the container.',
        );
        self::assertSame(
            'document.getElementById("w0-chips").addEventListener("add.bs.chips", onAdd);',
            self::registeredJs(self::webView(), 'w0-chips-events'),
            'Events must be attached to the container.',
        );
    }

    public function testRegisterSyncScript(): void
    {
        $this->mockWebApplication();

        Chips::widget(['name' => 'tags', 'separator' => ';']);

        self::assertSame(
            <<<JS
            document.getElementById("w0-chips").addEventListener("change.bs.chips", (event) => {
                document.getElementById("w0-value").value = event.values.join(";");
            });
            JS,
            self::registeredJs(self::webView(), 'w0-chips-sync'),
            'Hidden input must follow the chip values.',
        );
    }

    public function testRenderHiddenInputCopiesDisabledAndForm(): void
    {
        $this->mockWebApplication();

        self::assertSameHtml(
            <<<HTML
            <div id="w0-chips" class="chip-input" data-bs-chips>
            <span class="chip">a</span>
            <input type="text" id="w0" class="form-ghost" form="f1" disabled>
            <input type="hidden" id="w0-value" name="tags" value="a" form="f1" disabled>
            </div>
            HTML,
            Chips::widget(['name' => 'tags', 'options' => ['disabled' => true, 'form' => 'f1'], 'value' => 'a']),
            'Named hidden input must be disabled and bound to the form.',
        );

        $user = new User();

        $user->username = 'a';

        self::assertSameHtml(
            <<<HTML
            <div id="user-username-chips" class="chip-input" data-bs-chips>
            <span class="chip">a</span>
            <input type="text" id="user-username" class="form-ghost" disabled>
            <input type="hidden" id="user-username-value" name="User[username]" value="a" disabled>
            </div>
            HTML,
            Chips::widget(['model' => $user, 'attribute' => 'username', 'options' => ['disabled' => true]]),
            'Model hidden input must be disabled.',
        );
        self::assertSameHtml(
            <<<HTML
            <div id="w1-chips" class="chip-input" data-bs-chips>
            <span class="chip">a</span>
            <input type="text" id="w1" class="form-ghost">
            <input type="hidden" id="w1-value" name="tags" value="a">
            </div>
            HTML,
            Chips::widget(['name' => 'tags', 'options' => ['disabled' => false], 'value' => 'a']),
            'Enabled field must leave the hidden input submittable.',
        );
    }

    public function testRenderHiddenInputIsOverridable(): void
    {
        $this->mockWebApplication();

        $chips = new class (['name' => 'tags', 'value' => 'a']) extends Chips {
            protected function renderHiddenInput(string $id, string $value): string
            {
                return '<hidden>' . parent::renderHiddenInput($id, $value) . '</hidden>';
            }
        };

        self::assertStringContainsString(
            '<hidden><input type="hidden" id="w0-value" name="tags" value="a"></hidden>',
            $chips->run(),
            'Subclass must be able to decorate the hidden input.',
        );
    }

    public function testRenderModelWithoutAttributeUsesName(): void
    {
        $this->mockWebApplication();

        $user = new User();
        $user->username = 'model';

        self::assertSameHtml(
            <<<HTML
            <div id="w0-chips" class="chip-input" data-bs-chips>
            <span class="chip">value</span>
            <input type="text" id="w0" class="form-ghost">
            <input type="hidden" id="w0-value" name="tags" value="value">
            </div>
            HTML,
            Chips::widget(['model' => $user, 'name' => 'tags', 'value' => 'value']),
            'Name mode must ignore the model value.',
        );
    }

    public function testRenderRestoresInputOptions(): void
    {
        $this->mockWebApplication();

        $chips = new Chips(['name' => 'tags', 'options' => ['class' => 'custom']]);
        $chips->run();

        self::assertSame(
            ['class' => 'custom', 'id' => 'w0'],
            $chips->options,
            'Input options must be restored after the plugin registration.',
        );
    }

    public function testRenderWithAllOptions(): void
    {
        $this->mockWebApplication();

        self::assertSameHtml(
            <<<HTML
            <div id="tags" class="theme-primary chip-input" data-bs-chips data-bs-separator=";" data-bs-allow-duplicates="true" data-bs-max-chips="5" data-bs-dismissible="false" data-bs-create-on-blur="true">
            <span class="chip">a, b</span>
            <span class="chip">c</span>
            <input type="text" id="w0" class="form-ghost" placeholder="Add tag">
            <input type="hidden" id="w0-value" name="tags" value="a, b;c">
            </div>
            HTML,
            Chips::widget(
                [
                    'name' => 'tags',
                    'value' => ' a, b ;; c ',
                    'separator' => ';',
                    'allowDuplicates' => true,
                    'maxChips' => 5,
                    'dismissible' => false,
                    'createOnBlur' => true,
                    'placeholder' => 'Add tag',
                    'containerOptions' => ['id' => 'tags', 'class' => 'theme-primary'],
                ],
            ),
            'Every option must be emitted as its data attribute.',
        );
    }

    public function testRenderWithDisabledSeparator(): void
    {
        $this->mockWebApplication();

        self::assertSameHtml(
            <<<HTML
            <div id="w0-chips" class="chip-input" data-bs-chips data-bs-separator="" data-bs-allow-duplicates="false" data-bs-dismissible="true" data-bs-create-on-blur="false">
            <span class="chip">a</span>
            <span class="chip">b</span>
            <input type="text" id="w0" class="form-ghost">
            <input type="hidden" id="w0-value" name="tags" value="a,b">
            </div>
            HTML,
            Chips::widget(
                [
                    'name' => 'tags',
                    'value' => 'a,b',
                    'separator' => '',
                    'allowDuplicates' => false,
                    'dismissible' => true,
                    'createOnBlur' => false,
                ],
            ),
            'Empty separator must disable creation and join values with a comma.',
        );
    }

    public function testRenderWithIntegerValue(): void
    {
        $this->mockWebApplication();

        self::assertStringContainsString(
            '<span class="chip">5</span>',
            Chips::widget(['name' => 'tags', 'value' => 5]),
            'Scalar value must render a single chip.',
        );
    }

    public function testRenderWithModelArrayValue(): void
    {
        $this->mockWebApplication();

        $model = new class extends Model {
            /**
             * @var array<mixed>
             */
            public array $tags = ['JavaScript', '<b>', 5, ['nested'], ''];

            public function formName(): string
            {
                return 'Post';
            }
        };

        self::assertSameHtml(
            <<<HTML
            <div id="tags-input-chips" class="chip-input" data-bs-chips>
            <span class="chip">JavaScript</span>
            <span class="chip">&lt;b&gt;</span>
            <span class="chip">5</span>
            <input type="text" id="tags-input" class="form-ghost" placeholder="Custom">
            <input type="hidden" id="tags-input-value" name="Post[tags]" value="JavaScript,&lt;b&gt;,5">
            </div>
            HTML,
            Chips::widget(
                [
                    'model' => $model,
                    'attribute' => 'tags',
                    'options' => ['id' => 'tags-input', 'placeholder' => 'Custom'],
                    'placeholder' => 'Ignored',
                ],
            ),
            'Array value must render one chip per scalar item.',
        );
    }

    public function testRenderWithModelStringValue(): void
    {
        $this->mockWebApplication();

        $user = new User();

        $user->username = 'React,<i>Vue</i>';

        self::assertSameHtml(
            <<<HTML
            <div id="user-username-chips" class="chip-input" data-bs-chips>
            <span class="chip">React</span>
            <span class="chip"><i>Vue</i></span>
            <input type="text" id="user-username" class="form-ghost">
            <input type="hidden" id="user-username-value" name="User[username]" value="React,&lt;i&gt;Vue&lt;/i&gt;">
            </div>
            HTML,
            Chips::widget(['model' => $user, 'attribute' => 'username', 'encodeLabels' => false]),
            'Model string must be split into raw chips.',
        );
    }

    public function testRenderWithoutValue(): void
    {
        $this->mockWebApplication();

        self::assertSameHtml(
            <<<HTML
            <div id="w0-chips" class="chip-input" data-bs-chips>
            <input type="text" id="w0" class="form-ghost">
            <input type="hidden" id="w0-value" name="tags" value="">
            </div>
            HTML,
            Chips::widget(['name' => 'tags']),
            'Missing value must render the empty state.',
        );
    }
}
