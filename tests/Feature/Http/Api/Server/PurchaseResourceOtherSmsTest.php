<?php
namespace Tests\Feature\Http;

use App\Models\Purchase;
use App\Models\Server;
use App\Repositories\BoughtServiceRepository;
use App\Repositories\PaymentPlatformRepository;
use App\ServiceModules\Other\OtherServiceModule;
use App\System\Settings;
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

    protected function setUp()
    {
        parent::setUp();
        $this->mockRequester();
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

        /** @var Settings $settings */
        $settings = $this->app->make(Settings::class);

        $service = $this->factory->service([
            "id" => "monety",
            "module" => OtherServiceModule::MODULE_ID,
        ]);
        $paymentPlatform = $this->paymentPlatformRepository->create("test", Gosetti::MODULE_ID);
        $server = $this->factory->server();
        $this->factory->serverService([
            'server_id' => $server->getId(),
            'service_id' => $service->getId(),
        ]);
        $price = $this->factory->price([
            'service_id' => $service->getId(),
            'server_id' => $server->getId(),
        ]);

        $authData = 'test';
        $smsCode = 'ABCD12EF';

        $sign = md5(implode("#", ["0", $authData, $smsCode, $settings->get("random_key")]));

        // when
        $response = $this->post(
            '/api/server/purchase',
            [
                'service_id' => $service->getId(),
                'payment_platform_id' => $paymentPlatform->getId(),
                'server_id' => $server->getId(),
                'type' => "0",
                'auth_data' => $authData,
                'password' => "",
                'sms_code' => $smsCode,
                'method' => Purchase::METHOD_SMS,
                'price_id' => $price->getId(),
                'ip' => "192.0.2.1",
                'sign' => $sign,
            ],
            [
                'key' => md5($settings->get("random_key")),
            ],
            [
                'User-Agent' => Server::TYPE_AMXMODX,
            ]
        );

        // then
        $this->assertSame(200, $response->getStatusCode());
        $this->assertRegExp(
            "#<return_value>purchased</return_value><text>Usługa została prawidłowo zakupiona\.</text><positive>1</positive><bsid>\d+</bsid>#",
            $response->getContent()
        );

        preg_match("#<bsid>(\d+)</bsid>#", $response->getContent(), $matches);
        $boughtServiceId = $matches[1];
        $boughtService = $boughtServiceRepository->get($boughtServiceId);
        $this->assertNotNull($boughtService);
        $this->assertEquals(Purchase::METHOD_SMS, $boughtService->getMethod());
        $this->assertEquals("monety", $boughtService->getServiceId());
    }
}
