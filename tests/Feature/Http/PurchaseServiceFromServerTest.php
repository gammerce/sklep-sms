<?php
namespace Tests\Feature\Http;

use App\Exceptions\LicenseRequestException;
use App\Models\Purchase;
use App\Repositories\BoughtServiceRepository;
use App\Repositories\PaymentPlatformRepository;
use App\Services\ExtraFlags\ExtraFlagType;
use App\System\License;
use App\System\Settings;
use App\Verification\PaymentModules\Gosetti;
use Tests\Psr4\Concerns\GosettiConcern;
use Tests\Psr4\Concerns\PaymentModuleFactoryConcern;
use Tests\Psr4\TestCases\HttpTestCase;

/**
 * @deprecated
 */
class PurchaseServiceFromServerTest extends HttpTestCase
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
    public function player_can_purchase_service()
    {
        // given
        /** @var BoughtServiceRepository $boughtServiceRepository */
        $boughtServiceRepository = $this->app->make(BoughtServiceRepository::class);

        /** @var PaymentPlatformRepository $paymentPlatformRepository */
        $paymentPlatformRepository = $this->app->make(PaymentPlatformRepository::class);

        $serviceId = 'vip';
        $tariff = 2;
        $type = ExtraFlagType::TYPE_NICK;
        $authData = 'test';
        $password = 'test123';
        $smsCode = 'ABCD12EF';
        $method = 'sms';
        $uid = 0;
        $platform = 'engine_amxx';

        $paymentPlatform = $paymentPlatformRepository->create('test', Gosetti::MODULE_ID);
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

        /** @var Settings $settings */
        $settings = $this->app->make(Settings::class);

        $query = [
            'key' => md5($settings->get('random_key')),
            'action' => 'purchase_service',
            'service' => $serviceId,
            'payment_platform' => $paymentPlatform->getId(),
            'server' => $server->getId(),
            'type' => $type,
            'auth_data' => $authData,
            'password' => $password,
            'sms_code' => $smsCode,
            'method' => $method,
            'tariff' => $tariff,
            'uid' => $uid,
            'platform' => $platform,
        ];

        $this->mockGoSetti();

        // when
        $response = $this->get('/servers_stuff.php', $query);

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

    /** @test */
    public function player_cannot_make_a_purchase_if_license_is_invalid()
    {
        // given
        $license = $this->app->make(License::class);
        $license->shouldReceive('isValid')->andReturn(false);
        $license->shouldReceive('getLoadingException')->andReturn(new LicenseRequestException());

        // when
        $response = $this->get('/servers_stuff.php');

        // then
        $this->assertSame(200, $response->getStatusCode());
        $json = json_decode($response->getContent(), true);
        $this->assertEquals($json, [
            "message" => "Coś poszło nie tak podczas łączenia się z serwerem weryfikacyjnym.",
        ]);
    }

    private function mockGoSetti()
    {
        $this->makeVerifySmsSuccessful(Gosetti::class);
        $this->mockGoSettiGetData();
    }
}
