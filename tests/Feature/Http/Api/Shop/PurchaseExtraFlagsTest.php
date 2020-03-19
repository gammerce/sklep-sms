<?php
namespace Tests\Feature\Http\Api\Shop;

use App\Models\Purchase;
use App\ServiceModules\ExtraFlags\ExtraFlagType;
use App\Verification\PaymentModules\Zabijaka;
use Tests\Psr4\Concerns\PaymentModuleFactoryConcern;
use Tests\Psr4\TestCases\HttpTestCase;

class PurchaseExtraFlagsTest extends HttpTestCase
{
    use PaymentModuleFactoryConcern;

    protected function setUp()
    {
        parent::setUp();
        $this->mockPaymentModuleFactory();
    }

    /** @test */
    public function purchase_using_sms()
    {
        $this->makeVerifySmsSuccessful(Zabijaka::class);
        $this->actingAs($this->factory->user());
        $paymentPlatform = $this->factory->paymentPlatform([
            'module' => Zabijaka::MODULE_ID,
        ]);
        $server = $this->factory->server([
            'sms_platform_id' => $paymentPlatform->getId(),
        ]);
        $this->factory->serverService([
            'server_id' => $server->getId(),
            'service_id' => 'vippro',
        ]);
        $price = $this->factory->price([
            'service_id' => 'vippro',
            'server_id' => $server->getId(),
            'sms_price' => 500,
        ]);

        $validationResponse = $this->post('/api/purchases', [
            'service_id' => 'vippro',
            'method' => Purchase::METHOD_SMS,
            'sms_price' => 500,
            'type' => ExtraFlagType::TYPE_NICK,
            'auth_data' => "mama",
            'password' => "manq12a",
            'password_repeat' => "manq12a",
            'server_id' => $server->getId(),
            'price_id' => $price->getId(),
            'email' => 'a@a.pl',
        ]);
        $this->assertSame(200, $validationResponse->getStatusCode());
        $json = $this->decodeJsonResponse($validationResponse);

        $response = $this->post('/api/payment', [
            'method' => Purchase::METHOD_SMS,
            'sms_code' => 'abc123',
            'purchase_sign' => $json["sign"],
            'purchase_data' => $json["data"],
        ]);
        $this->assertSame(200, $response->getStatusCode());
        $json = $this->decodeJsonResponse($response);
        $this->assertSame("purchased", $json['return_id']);
    }
}
