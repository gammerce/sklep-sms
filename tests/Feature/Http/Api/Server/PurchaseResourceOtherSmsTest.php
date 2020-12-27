<?php
namespace Tests\Feature\Http\Api\Server;

use App\Payment\General\PaymentMethod;
use App\Repositories\BoughtServiceRepository;
use App\Repositories\PaymentPlatformRepository;
use App\Server\Platform;
use App\ServiceModules\Other\OtherServiceModule;
use App\Verification\PaymentModules\Gosetti;
use Tests\Psr4\Concerns\GosettiConcern;
use Tests\Psr4\Concerns\PaymentModuleFactoryConcern;
use Tests\Psr4\TestCases\HttpTestCase;

class PurchaseResourceOtherSmsTest extends HttpTestCase
{
    use GosettiConcern;
    use PaymentModuleFactoryConcern;

    /** @var PaymentPlatformRepository */
    private $paymentPlatformRepository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mockPaymentModuleFactory();
        $this->makeVerifySmsSuccessful(Gosetti::class);
        $this->mockGoSettiGetData();
        $this->paymentPlatformRepository = $this->app->make(PaymentPlatformRepository::class);
    }

    /** @test */
    public function purchase_newly_created_service()
    {
        // given
        /** @var BoughtServiceRepository $boughtServiceRepository */
        $boughtServiceRepository = $this->app->make(BoughtServiceRepository::class);

        $service = $this->factory->service([
            "id" => "monety",
            "module" => OtherServiceModule::MODULE_ID,
        ]);
        $paymentPlatform = $this->paymentPlatformRepository->create("test", Gosetti::MODULE_ID);
        $server = $this->factory->server([
            "sms_platform_id" => $paymentPlatform->getId(),
        ]);
        $this->factory->serverService([
            "server_id" => $server->getId(),
            "service_id" => $service->getId(),
        ]);
        $price = $this->factory->price([
            "service_id" => $service->getId(),
            "server_id" => $server->getId(),
        ]);

        $authData = "test";
        $smsCode = "ABCD12EF";

        $sign = md5(implode("#", ["0", $authData, $smsCode, $server->getToken()]));

        // when
        $response = $this->post(
            "/api/server/purchase",
            [
                "service_id" => $service->getId(),
                "type" => "0",
                "auth_data" => $authData,
                "password" => "",
                "sms_code" => $smsCode,
                "method" => PaymentMethod::SMS(),
                "price_id" => $price->getId(),
                "ip" => "192.0.2.1",
                "sign" => $sign,
            ],
            [
                "token" => $server->getToken(),
            ],
            [
                "User-Agent" => Platform::AMXMODX,
            ]
        );

        // then
        $this->assertSame(200, $response->getStatusCode());
        $this->assertMatchesRegularExpression(
            "#<return_value>purchased</return_value><text>Usługa została prawidłowo zakupiona\.</text><positive>1</positive><bsid>\d+</bsid>#",
            $response->getContent()
        );

        preg_match("#<bsid>(\d+)</bsid>#", $response->getContent(), $matches);
        $boughtServiceId = $matches[1];
        $boughtService = $boughtServiceRepository->get($boughtServiceId);
        $this->assertNotNull($boughtService);
        $this->assertSameEnum(PaymentMethod::SMS(), $boughtService->getMethod());
        $this->assertEquals("monety", $boughtService->getServiceId());
    }
}
