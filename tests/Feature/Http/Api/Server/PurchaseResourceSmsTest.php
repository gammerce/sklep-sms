<?php
namespace Tests\Feature\Http;

use App\Models\PaymentPlatform;
use App\Models\Price;
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

    /** @var PaymentPlatform */
    private $paymentPlatform;

    /** @var Server */
    private $server;

    /** @var Price */
    private $price;

    /** @var string */
    private $serviceId = 'vip';

    protected function setUp()
    {
        parent::setUp();
        $this->mockRequester();
        $this->mockPaymentModuleFactory();
        $this->makeVerifySmsSuccessful(Gosetti::class);
        $this->mockGoSettiGetData();

        /** @var PaymentPlatformRepository $paymentPlatformRepository */
        $paymentPlatformRepository = $this->app->make(PaymentPlatformRepository::class);

        $this->paymentPlatform = $paymentPlatformRepository->create("test", Gosetti::MODULE_ID);

        $this->server = $this->factory->server();
        $this->factory->serverService([
            'server_id' => $this->server->getId(),
            'service_id' => $this->serviceId,
        ]);
        $this->price = $this->factory->price([
            'service_id' => $this->serviceId,
            'server_id' => $this->server->getId(),
        ]);
    }

    /** @test */
    public function purchase_using_sms()
    {
        // given
        /** @var BoughtServiceRepository $boughtServiceRepository */
        $boughtServiceRepository = $this->app->make(BoughtServiceRepository::class);

        /** @var Settings $settings */
        $settings = $this->app->make(Settings::class);

        $authData = 'test';
        $password = 'test123';
        $smsCode = 'ABCD12EF';
        $type = ExtraFlagType::TYPE_NICK;

        $sign = md5(implode("#", [$type, $authData, $smsCode, $settings->get("random_key")]));

        // when
        $response = $this->post(
            '/api/server/purchase',
            [
                'service_id' => $this->serviceId,
                'payment_platform_id' => $this->paymentPlatform->getId(),
                'server_id' => $this->server->getId(),
                'type' => $type,
                'auth_data' => $authData,
                'password' => $password,
                'sms_code' => $smsCode,
                'method' => Purchase::METHOD_SMS,
                'price_id' => $this->price->getId(),
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
    }

    /** @test */
    public function purchase_using_sms_accept_application_assoc()
    {
        // given
        /** @var Settings $settings */
        $settings = $this->app->make(Settings::class);

        $authData = 'test';
        $password = 'test123';
        $smsCode = 'ABCD12EF';
        $type = ExtraFlagType::TYPE_NICK;

        $sign = md5(implode("#", [$type, $authData, $smsCode, $settings->get("random_key")]));

        // when
        $response = $this->post(
            '/api/server/purchase',
            [
                'service_id' => $this->serviceId,
                'payment_platform_id' => $this->paymentPlatform->getId(),
                'server_id' => $this->server->getId(),
                'type' => $type,
                'auth_data' => $authData,
                'password' => $password,
                'sms_code' => $smsCode,
                'method' => Purchase::METHOD_SMS,
                'price_id' => $this->price->getId(),
                'ip' => "192.0.2.1",
                'sign' => $sign,
            ],
            [
                'key' => md5($settings->get("random_key")),
            ],
            [
                'Accept' => 'application/assoc',
                'User-Agent' => Server::TYPE_AMXMODX,
            ]
        );

        // then
        $this->assertSame(200, $response->getStatusCode());
        $data = implode("\n", [
            'status:purchased',
            'text:Usługa została prawidłowo zakupiona.',
            'positive:1',
            'bsid:\d+',
        ]);
        $this->assertRegExp("#^$data$#", $response->getContent());
    }

    /** @test */
    public function fails_with_invalid_data_passes()
    {
        // given
        /** @var Settings $settings */
        $settings = $this->app->make(Settings::class);

        $authData = 'a';
        $smsCode = 'ABCD12EF';
        $type = ExtraFlagType::TYPE_NICK;

        $sign = md5(implode("#", [$type, $authData, $smsCode, $settings->get("random_key")]));

        // when
        $response = $this->post(
            '/api/server/purchase',
            [
                'service_id' => $this->serviceId,
                'payment_platform_id' => $this->paymentPlatform->getId(),
                'server_id' => $this->server->getId(),
                'type' => $type,
                'auth_data' => $authData,
                'password' => '1',
                'sms_code' => $smsCode,
                'method' => Purchase::METHOD_SMS,
                'price_id' => $this->price->getId(),
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
        $this->assertSame(
            "<return_value>warnings</return_value><text>Nie wszystkie pola formularza zostały prawidłowo wypełnione.</text><positive>0</positive><warnings><strong>auth_data</strong><br />Pole musi się składać z co najmniej 2 znaków.<br /><strong>password</strong><br />Pole musi się składać z co najmniej 6 znaków.<br /></warnings>",
            $response->getContent()
        );
    }
}
