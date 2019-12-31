<?php
namespace Tests\Feature\Http\Api\Admin;

use App\Models\PaymentPlatform;
use App\Repositories\PaymentPlatformRepository;
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
        $name = "My Example";

        // when
        $response = $this->put("/api/admin/payment_platforms/{$this->paymentPlatform->getId()}", [
            "name" => $name,
            "data" => [
                "account_id" => "example"
            ],
        ]);

        // then
        $this->assertSame(200, $response->getStatusCode());
        $json = $this->decodeJsonResponse($response);
        $this->assertSame("ok", $json["return_id"]);
        $freshPaymentPlatform = $this->paymentPlatformRepository->get($this->paymentPlatform->getId());
        $this->assertNotNull($freshPaymentPlatform);
        $this->assertSame($name, $freshPaymentPlatform->getName());
        $this->assertSame("example", $freshPaymentPlatform->getData()["account_id"]);
    }

    /** @test */
    public function cannot_update_when_invalid_data_given()
    {
        // given
        $name = "My Example";

        // when
        $response = $this->post("/api/admin/payment_platforms/{$this->paymentPlatform->getId()}", [
            "name" => $name,
            "data" => [],
        ]);

        // then
        $this->assertSame(200, $response->getStatusCode());
        $json = $this->decodeJsonResponse($response);
        $this->assertSame("warnings", $json["return_id"]);
    }

    // TODO Test delete
}
