<?php
namespace Tests\Feature\Http\Api\Server;

use App\Models\Price;
use App\Models\Server;
use App\Payment\General\PaymentMethod;
use App\Repositories\BoughtServiceRepository;
use App\Repositories\PaymentPlatformRepository;
use App\Server\ServerType;
use App\ServiceModules\ExtraFlags\ExtraFlagType;
use App\Verification\PaymentModules\Gosetti;
use Tests\Psr4\Concerns\GosettiConcern;
use Tests\Psr4\Concerns\PaymentModuleFactoryConcern;
use Tests\Psr4\TestCases\HttpTestCase;

class PurchaseResourceSmsTest extends HttpTestCase
{
    use GosettiConcern;
    use PaymentModuleFactoryConcern;

    /** @var Server */
    private $server;

    /** @var Price */
    private $price;

    /** @var string */
    private $serviceId = "vip";

    protected function setUp(): void
    {
        parent::setUp();
        $this->mockPaymentModuleFactory();
        $this->makeVerifySmsSuccessful(Gosetti::class);
        $this->mockGoSettiGetData();

        /** @var PaymentPlatformRepository $paymentPlatformRepository */
        $paymentPlatformRepository = $this->app->make(PaymentPlatformRepository::class);
        $paymentPlatform = $paymentPlatformRepository->create("test", Gosetti::MODULE_ID);

        $this->server = $this->factory->server([
            "sms_platform_id" => $paymentPlatform->getId(),
        ]);
        $this->factory->serverService([
            "server_id" => $this->server->getId(),
            "service_id" => $this->serviceId,
        ]);
        $this->price = $this->factory->price([
            "service_id" => $this->serviceId,
            "server_id" => $this->server->getId(),
        ]);
    }

    /** @test */
    public function purchase_using_sms()
    {
        // given
        /** @var BoughtServiceRepository $boughtServiceRepository */
        $boughtServiceRepository = $this->app->make(BoughtServiceRepository::class);

        $authData = "test";
        $password = "test123";
        $smsCode = "ABCD12EF";
        $type = ExtraFlagType::TYPE_NICK;

        $sign = md5(implode("#", [$type, $authData, $smsCode, $this->server->getToken()]));

        // when
        $response = $this->post(
            "/api/server/purchase",
            [
                "service_id" => $this->serviceId,
                "type" => $type,
                "auth_data" => $authData,
                "password" => $password,
                "sms_code" => $smsCode,
                "method" => PaymentMethod::SMS(),
                "price_id" => $this->price->getId(),
                "ip" => "192.0.2.1",
                "sign" => $sign,
            ],
            [
                "token" => $this->server->getToken(),
            ],
            [
                "User-Agent" => ServerType::AMXMODX,
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
    }

    /** @test */
    public function purchase_using_sms_accept_application_assoc()
    {
        // given
        $authData = "test";
        $password = "test123";
        $smsCode = "ABCD12EF";
        $type = ExtraFlagType::TYPE_NICK;

        $sign = md5(implode("#", [$type, $authData, $smsCode, $this->server->getToken()]));

        // when
        $response = $this->post(
            "/api/server/purchase",
            [
                "service_id" => $this->serviceId,
                "type" => $type,
                "auth_data" => $authData,
                "password" => $password,
                "sms_code" => $smsCode,
                "method" => PaymentMethod::SMS(),
                "price_id" => $this->price->getId(),
                "ip" => "192.0.2.1",
                "sign" => $sign,
            ],
            [
                "token" => $this->server->getToken(),
            ],
            [
                "Accept" => "application/assoc",
                "User-Agent" => ServerType::AMXMODX,
            ]
        );

        // then
        $this->assertSame(200, $response->getStatusCode());
        $data = implode("\n", [
            "status:purchased",
            "text:Usługa została prawidłowo zakupiona.",
            "bsid:\d+",
        ]);
        $this->assertMatchesRegularExpression("#^$data$#", $response->getContent());
    }

    /** @test */
    public function fails_with_invalid_data_passed()
    {
        // given
        $authData = "a";
        $smsCode = "ABCD12EF";
        $type = ExtraFlagType::TYPE_NICK;

        $sign = md5(implode("#", [$type, $authData, $smsCode, $this->server->getToken()]));

        // when
        $response = $this->post(
            "/api/server/purchase",
            [
                "service_id" => $this->serviceId,
                "type" => $type,
                "auth_data" => $authData,
                "password" => "1",
                "sms_code" => $smsCode,
                "method" => PaymentMethod::SMS(),
                "price_id" => $this->price->getId(),
                "sign" => $sign,
            ],
            [
                "token" => $this->server->getToken(),
            ],
            [
                "User-Agent" => ServerType::AMXMODX,
            ]
        );

        // then
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(
            "<return_value>warnings</return_value><text>auth_data: Pole musi się składać z co najmniej 2 znaków.</text><positive>0</positive><warnings><strong>auth_data</strong><br />Pole musi się składać z co najmniej 2 znaków.<br /><strong>password</strong><br />Pole musi się składać z co najmniej 6 znaków.<br /></warnings>",
            $response->getContent()
        );
    }
}
