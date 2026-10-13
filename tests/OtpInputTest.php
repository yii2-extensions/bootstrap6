<?php

declare(strict_types=1);

namespace yii2\extensions\bootstrap6\tests;

use PHPUnit\Framework\Attributes\{DataProviderExternal, Group};
use yii\base\InvalidConfigException;
use yii2\extensions\bootstrap6\asset\BootstrapPluginAsset;
use yii2\extensions\bootstrap6\exception\Message;
use yii2\extensions\bootstrap6\OtpInput;
use yii2\extensions\bootstrap6\tests\provider\OtpInputProvider;
use yii2\extensions\bootstrap6\tests\support\stub\User;
use yii2\extensions\bootstrap6\tests\support\TestCase;

/**
 * Unit tests for {@see OtpInput} container and input rendering, data attributes, and plugin registration.
 *
 * {@see OtpInputProvider} for test case data providers.
 */
#[Group('widget')]
final class OtpInputTest extends TestCase
{
    private const string IMPORT = 'import("/assets/js/bootstrap.bundle.js").then((bootstrap) => { ';

    public function testRegisterPluginAssetWithoutInitialization(): void
    {
        $this->mockWebApplication();

        OtpInput::widget(['name' => 'code']);

        self::assertArrayHasKey(
            BootstrapPluginAsset::class,
            self::webView()->assetBundles,
            'Plugin bundle must be registered for the data API.',
        );
        self::assertNull(
            self::registeredJs(self::webView(), 'w0-otp-plugin'),
            'Data API must not need an inline init.',
        );
    }

    public function testRegisterPluginOnContainer(): void
    {
        $this->mockWebApplication();

        OtpInput::widget(
            [
                'name' => 'code',
                'clientOptions' => ['length' => 4],
                'clientEvents' => ['complete.bs.otpInput' => 'onComplete'],
            ],
        );

        $import = self::IMPORT;

        self::assertSame(
            <<<JS
            {$import}bootstrap.OtpInput.getOrCreateInstance(document.getElementById("w0-otp"), {"length":4}); });
            JS,
            self::registeredJs(self::webView(), 'w0-otp-plugin'),
            'Plugin must target the container.',
        );
        self::assertSame(
            'document.getElementById("w0-otp").addEventListener("complete.bs.otpInput", onComplete);',
            self::registeredJs(self::webView(), 'w0-otp-events'),
            'Events must be attached to the container.',
        );
    }

    public function testRenderRestoresInputOptions(): void
    {
        $this->mockWebApplication();

        $otp = new OtpInput(['name' => 'code', 'options' => ['aria-label' => 'Code']]);
        $otp->run();

        self::assertSame(
            ['aria-label' => 'Code', 'id' => 'w0'],
            $otp->options,
            'Input options must be restored after the plugin registration.',
        );
    }

    public function testRenderWithAllOptions(): void
    {
        $this->mockWebApplication();

        self::assertSameHtml(
            <<<HTML
            <div id="otp" class="otp-connected otp" data-bs-otp data-bs-type="alphanumeric" data-bs-mask="true" data-bs-groups='[3,3]' data-bs-separator="-">
            <input type="text" id="w0" class="otp-input" name="code" value="123" maxlength="6" aria-label="Verification code">
            </div>
            HTML,
            OtpInput::widget(
                [
                    'name' => 'code',
                    'value' => '123',
                    'type' => 'alphanumeric',
                    'mask' => true,
                    'groups' => [3, 3],
                    'separator' => '-',
                    'options' => ['aria-label' => 'Verification code'],
                    'containerOptions' => ['id' => 'otp', 'class' => 'otp-connected'],
                ],
            ),
            'Every option must be emitted as its data attribute.',
        );
    }

    public function testRenderWithModel(): void
    {
        $this->mockWebApplication();

        $user = new User();
        $user->password = '1234';

        self::assertSameHtml(
            <<<HTML
            <div id="user-password-otp" class="otp" data-bs-otp data-bs-mask="false">
            <input type="text" id="user-password" class="otp-input" name="User[password]" value="1234" maxlength="4">
            </div>
            HTML,
            OtpInput::widget(['model' => $user, 'attribute' => 'password', 'length' => 4, 'mask' => false]),
            'Model attribute must bind the input.',
        );
    }

    #[DataProviderExternal(OtpInputProvider::class, 'typeCases')]
    public function testRenderWithType(string $type): void
    {
        $this->mockWebApplication();

        self::assertStringContainsString(
            "data-bs-type=\"{$type}\"",
            OtpInput::widget(['name' => 'code', 'type' => $type]),
            'Allowed type must be emitted.',
        );
    }

    public function testThrowInvalidConfigExceptionForInvalidType(): void
    {
        $this->mockWebApplication();

        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage(
            Message::INVALID_VALUE->getMessage('hex', 'type', 'numeric, alphanumeric, alpha'),
        );

        OtpInput::widget(['name' => 'code', 'type' => 'hex']);
    }
}
