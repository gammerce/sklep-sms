<?php
namespace Tests\Feature\Http\Api\Admin;

use App\Models\PaymentPlatform;
use App\Repositories\PaymentPlatformRepository;
use App\Repositories\SettingsRepository;
use App\System\Settings;
use Tests\Psr4\TestCases\HttpTestCase;

class PaymentPlatformResourceTest extends HttpTestCase
{
    /** @var PaymentPlatformRepository */
    private $paymentPlatformRepository;

    /** @var PaymentPlatform */
    private $paymentPlatform;

    protected function setUp()
    {
        parent::setUp();

        $this->paymentPlatform = $this->factory->paymentPlatform();
        $this->paymentPlatformRepository = $this->app->make(PaymentPlatformRepository::class);
    }

    /** @test */
    public function updates_payment_platform()
    {
        // given
        $this->actingAs($this->factory->admin());
        $name = "My Example";

        // when
        $response = $this->put("/api/admin/payment_platforms/{$this->paymentPlatform->getId()}", [
            "name" => $name,
            "data" => [
                "account_id" => "example",
            ],
        ]);

        // then
        $this->assertSame(200, $response->getStatusCode());
        $json = $this->decodeJsonResponse($response);
        $this->assertSame("ok", $json["return_id"]);
        $freshPaymentPlatform = $this->paymentPlatformRepository->get(
            $this->paymentPlatform->getId()
        );
        $this->assertNotNull($freshPaymentPlatform);
        $this->assertSame($name, $freshPaymentPlatform->getName());
        $this->assertSame("example", $freshPaymentPlatform->getData()["account_id"]);
    }

    /** @test */
    public function deletes_payment_platform()
    {
        // given
        $this->actingAs($this->factory->admin());

        // when
        $response = $this->delete("/api/admin/payment_platforms/{$this->paymentPlatform->getId()}");

        // then
        $this->assertSame(200, $response->getStatusCode());
        $json = $this->decodeJsonResponse($response);
        $this->assertSame("ok", $json["return_id"]);
        $freshPaymentPlatform = $this->paymentPlatformRepository->get(
            $this->paymentPlatform->getId()
        );
        $this->assertNull($freshPaymentPlatform);
    }

    /** @test */
    public function cannot_delete_if_used_in_settings()
    {
        // given
        $this->actingAs($this->factory->admin());

        /** @var SettingsRepository $settingsRepository */
        $settingsRepository = $this->app->make(SettingsRepository::class);
        $settingsRepository->update([
            "sms_platform" => $this->paymentPlatform->getId(),
        ]);

        // when
        $response = $this->delete("/api/admin/payment_platforms/{$this->paymentPlatform->getId()}");

        // then
        $this->assertSame(200, $response->getStatusCode());
        $json = $this->decodeJsonResponse($response);
        $this->assertSame("error", $json["return_id"]);
        $freshPaymentPlatform = $this->paymentPlatformRepository->get(
            $this->paymentPlatform->getId()
        );
        $this->assertNotNull($freshPaymentPlatform);
    }

    /** @test */
    public function cannot_delete_if_server_uses_it()
    {
        // given
        $this->actingAs($this->factory->admin());

        $this->factory->server([
            "sms_platform_id" => $this->paymentPlatform->getId(),
        ]);

        // when
        $response = $this->delete("/api/admin/payment_platforms/{$this->paymentPlatform->getId()}");

        // then
        $this->assertSame(200, $response->getStatusCode());
        $json = $this->decodeJsonResponse($response);
        $this->assertSame("error", $json["return_id"]);
        $freshPaymentPlatform = $this->paymentPlatformRepository->get(
            $this->paymentPlatform->getId()
        );
        $this->assertNotNull($freshPaymentPlatform);
    }
}
