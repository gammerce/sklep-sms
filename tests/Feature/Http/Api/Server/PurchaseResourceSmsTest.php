<?php
namespace Tests\Feature\Http;

use App\Models\Purchase;
use App\Models\Server;
use App\Repositories\BoughtServiceRepository;
use App\Repositories\PaymentPlatformRepository;
use App\ServiceModules\ExtraFlags\ExtraFlagType;
use App\System\Settings;
use App\Verification\PaymentModules\Gosetti;
use Tests\Psr4\Concerns\GosettiConcern;
use Tests\Psr4\Concerns\PaymentModuleFactoryConcern;
use Tests\Psr4\TestCases\HttpTestCase;

class PurchaseResourceSmsTest extends HttpTestCase
{
    use GosettiConcern;
    use PaymentModuleFactoryConcern;

    protected function setUp()
    {
        parent::setUp();
        $this->mockRequester();
        $this->mockPaymentModuleFactory();
    }

    /** @test */
    public function purchase_using_sms()
    {
        // given
        /** @var BoughtServiceRepository $boughtServiceRepository */
        $boughtServiceRepository = $this->app->make(BoughtServiceRepository::class);

        /** @var PaymentPlatformRepository $paymentPlatformRepository */
        $paymentPlatformRepository = $this->app->make(PaymentPlatformRepository::class);

        /** @var Settings $settings */
        $settings = $this->app->make(Settings::class);

        $serviceId = 'vip';
        $authData = 'test';
        $password = 'test123';
        $smsCode = 'ABCD12EF';
        $type = ExtraFlagType::TYPE_NICK;

        $paymentPlatform = $paymentPlatformRepository->create("test", Gosetti::MODULE_ID);
        $server = $this->factory->server();
        $this->factory->serverService([
            'server_id' => $server->getId(),
            'service_id' => $serviceId,
        ]);
        $price = $this->factory->price([
            'service_id' => $serviceId,
            'server_id' => $server->getId(),
        ]);

        $sign = md5(implode("#", [$type, $authData, $smsCode, $settings->get("random_key")]));

        $this->mockGoSetti();

        // when
        $response = $this->post(
            '/api/server/purchase',
            [
                'service_id' => $serviceId,
                'payment_platform_id' => (string) $paymentPlatform->getId(),
                'server_id' => (string) $server->getId(),
                'type' => (string) $type,
                'auth_data' => $authData,
                'password' => $password,
                'sms_code' => $smsCode,
                'method' => Purchase::METHOD_SMS,
                'price_id' => (string) $price->getId(),
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
        $boughtServiceId = (int) $matches[1];
        $boughtService = $boughtServiceRepository->get($boughtServiceId);
        $this->assertNotNull($boughtService);
        $this->assertEquals(Purchase::METHOD_SMS, $boughtService->getMethod());
    }

    private function mockGoSetti()
    {
        $this->makeVerifySmsSuccessful(Gosetti::class);
        $this->mockGoSettiGetData();
    }
}
