<?php
namespace Tests\Feature\Http\Api\Admin;

use App\Repositories\PaymentPlatformRepository;
use App\Verification\Cssetti;
use Tests\Psr4\TestCases\HttpTestCase;

class PaymentPlatformCollectionTest extends HttpTestCase
{
    /** @var PaymentPlatformRepository */
    private $paymentPlatformRepository;

    protected function setUp()
    {
        parent::setUp();

        $this->paymentPlatformRepository = $this->app->make(PaymentPlatformRepository::class);
    }

    /** @test */
    public function creates_payment_platform()
    {
        // given
        $name = "My Example";
        $moduleId = Cssetti::MODULE_ID;

        // when
        $response = $this->post("/api/admin/payment_platforms", [
            "name" => $name,
            "module" => $moduleId,
            "data" => [
                "account_id" => "example"
            ],
        ]);

        // then
        $this->assertSame(200, $response->getStatusCode());
        $json = $this->decodeJsonResponse($response);
        $this->assertSame("ok", $json["return_id"]);
        $paymentPlatform = $this->paymentPlatformRepository->get($json["data"]["id"]);
        $this->assertNotNull($paymentPlatform);
        $this->assertSame($name, $paymentPlatform->getName());
        $this->assertSame($moduleId, $paymentPlatform->getModule());
        $this->assertSame("example", $paymentPlatform->getData()["account_id"]);
    }

    /** @test */
    public function fails_when_invalid_data()
    {
        // given
        $name = "My Example";
        $moduleId = Cssetti::MODULE_ID;

        // when
        $response = $this->post("/api/admin/payment_platforms", [
            "name" => $name,
            "module" => $moduleId,
            "data" => [],
        ]);

        // then
        $this->assertSame(200, $response->getStatusCode());
        $json = $this->decodeJsonResponse($response);
        $this->assertSame("warnings", $json["return_id"]);
    }
}
