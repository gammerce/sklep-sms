<?php
namespace Tests\Feature\Http;

use App\Models\Purchase;
use App\Repositories\BoughtServiceRepository;
use App\Repositories\PaymentPlatformRepository;
use App\Services\ExtraFlags\ExtraFlagType;
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
        $tariff = 2;
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
        $this->factory->price([
            'service_id' => $serviceId,
            'tariff' => $tariff,
            'server_id' => $server->getId(),
        ]);

        $sign = md5(implode("#", [$type, $authData, $smsCode, $settings->get("random_key")]));

        $this->mockGoSetti();

        // when
        $response = $this->post(
            '/api/server/purchase',
            [
                'service' => $serviceId,
                'payment_platform' => $paymentPlatform->getId(),
                'server' => $server->getId(),
                'type' => $type,
                'auth_data' => $authData,
                'password' => $password,
                'sms_code' => $smsCode,
                'method' => Purchase::METHOD_SMS,
                'tariff' => $tariff,
                'ip' => "192.0.2.1",
                'sign' => $sign,
            ],
            [
                'key' => md5($settings->get("random_key")),
            ]
        );

        // then
        $this->assertSame(200, $response->getStatusCode());
        $this->assertRegExp(
            "#<return_value>purchased</return_value><text>Usługa została prawidłowo zakupiona\.</text><positive>1</positive><bsid>\d+</bsid>#",
            $response->getContent()
        );

        preg_match("#<bsid>(\d+)</bsid>#", $response->getContent(), $matches);
        $boughtServiceId = intval($matches[1]);
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
