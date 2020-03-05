<?php
namespace Tests\Feature\Http\Api\Admin;

use App\System\Settings;
use App\Verification\PaymentModules\Microsms;
use App\Verification\PaymentModules\SimPay;
use App\Verification\PaymentModules\TPay;
use Tests\Psr4\TestCases\HttpTestCase;

class SettingsControllerTest extends HttpTestCase
{
    /** @test */
    public function updates_settings()
    {
        // given
        /** @var Settings $settings */
        $settings = $this->app->make(Settings::class);

        $this->actingAs($this->factory->admin());

        $directBillingPaymentPlatform = $this->factory->paymentPlatform([
            'module' => SimPay::MODULE_ID,
        ]);
        $smsPaymentPlatform = $this->factory->paymentPlatform([
            'module' => Microsms::MODULE_ID,
        ]);
        $transferPaymentPlatform = $this->factory->paymentPlatform([
            'module' => TPay::MODULE_ID,
        ]);

        // when
        $response = $this->put("/api/admin/settings", [
            'cron' => 1,
            'delete_logs' => 1,
            'direct_billing_platform' => $directBillingPaymentPlatform->getId(),
            'language' => 'polish',
            'license_token' => "abc123",
            'row_limit' => 20,
            'shop_url' => "https://example.com",
            'sms_platform' => $smsPaymentPlatform->getId(),
            'theme' => 'default',
            'transfer_platform' => $transferPaymentPlatform->getId(),
            'user_edit_service' => 1,
            'vat' => 1.23,
        ]);

        // then
        $this->assertSame(200, $response->getStatusCode());
        $json = $this->decodeJsonResponse($response);
        $this->assertSame("ok", $json["return_id"]);
        $settings->load();
        $this->assertSame("https://example.com", $settings->getShopUrl());
        $this->assertSame("abc123", $settings->getLicenseToken());
        $this->assertSame(1.23, $settings->getVat());
        $this->assertSame("20", $settings["row_limit"]);
        $this->assertSame("1", $settings["delete_logs"]);
        $this->assertSame("1", $settings["cron_each_visit"]);
        $this->assertSame("1", $settings["user_edit_service"]);
        $this->assertSame("default", $settings->getTheme());
        $this->assertSame("polish", $settings->getLanguage());
        $this->assertSame($smsPaymentPlatform->getId(), $settings->getSmsPlatformId());
        $this->assertSame(
            $directBillingPaymentPlatform->getId(),
            $settings->getDirectBillingPlatformId()
        );
        $this->assertSame($transferPaymentPlatform->getId(), $settings->getTransferPlatformId());
    }
}
