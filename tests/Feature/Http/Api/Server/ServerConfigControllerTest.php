<?php
namespace Tests\Feature\Http\Api\Server;

use App\Models\PaymentPlatform;
use App\Models\Server;
use App\Server\Platform;
use App\ServiceModules\ExtraFlags\ExtraFlagType;
use App\ServiceModules\ExtraFlags\PlayerFlagService;
use App\System\Settings;
use App\Verification\PaymentModules\Gosetti;
use Tests\Psr4\Concerns\GosettiConcern;
use Tests\Psr4\TestCases\HttpTestCase;

class ServerConfigControllerTest extends HttpTestCase
{
    use GosettiConcern;

    private Settings $settings;
    private PaymentPlatform $paymentPlatform;
    private Server $server;

    protected function setUp(): void
    {
        parent::setUp();

        $this->settings = $this->app->make(Settings::class);

        $this->paymentPlatform = $this->factory->paymentPlatform([
            "module" => Gosetti::MODULE_ID,
        ]);
        $this->server = $this->factory->server([
            "sms_platform_id" => $this->paymentPlatform->getId(),
        ]);

        $this->mockGoSettiGetData();
    }

    /** @test */
    public function config_server_response()
    {
        // given
        $this->factory->serverService([
            "server_id" => $this->server->getId(),
            "service_id" => "vip",
        ]);

        $price = $this->factory->price([
            "service_id" => "vip",
            "sms_price" => 200,
            "quantity" => 10,
        ]);

        // when
        $response = $this->get(
            "/api/server/config",
            [
                "token" => $this->server->getToken(),
                "ip" => $this->server->getIp(),
                "port" => $this->server->getPort(),
                "version" => "3.10.1-rc.1242",
            ],
            [
                "User-Agent" => Platform::AMXMODX,
            ]
        );

        // then
        $data = [
            "id:{$this->server->getId()}",
            "license_token:abc123",
            "sms_platform_id:{$this->paymentPlatform->getId()}",
            "sms_text:abc123",
            "currency:PLN",
            "contact:",
            "vat:1.23",
            "sn.c:11",
            "sn.0:71480",
            "sn.1:72480",
            "sn.2:73480",
            "sn.3:74480",
            "sn.4:75480",
            "sn.5:76480",
            "sn.6:79480",
            "sn.7:91400",
            "sn.8:91900",
            "sn.9:92022",
            "sn.10:92521",
            "se.c:1",
            "se.0.i:vip",
            "se.0.n:VIP",
            "se.0.d:",
            "se.0.ta:dni",
            "se.0.f:t",
            "se.0.ty:7",
            "pr.c:1",
            "pr.0.i:{$price->getId()}",
            "pr.0.s:vip",
            "pr.0.p:200",
            "pr.0.q:10",
            "pr.0.d:0",
            "pf.c:0",
        ];
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(implode("\n", $data), $response->getContent());
    }

    /** @test */
    public function config_json_response()
    {
        // given
        // when
        $response = $this->get(
            "/api/server/config",
            [
                "token" => $this->server->getToken(),
                "ip" => $this->server->getIp(),
                "port" => $this->server->getPort(),
                "version" => "3.10.0",
            ],
            [
                "Accept" => "application/json",
                "User-Agent" => Platform::AMXMODX,
            ]
        );

        // then
        $this->assertSame(200, $response->getStatusCode());
        $json = $this->decodeJsonResponse($response);
        $this->assertSame($this->paymentPlatform->getId(), $json["sms_platform_id"]);
        $this->assertSame($this->settings->getVat(), $json["vat"]);
        $this->assertSame("abc123", $json["license_token"]);
    }

    /** @test */
    public function returns_402_if_invalid_version()
    {
        // when
        $response = $this->get(
            "/api/server/config",
            [
                "token" => $this->server->getToken(),
                "ip" => $this->server->getIp(),
                "port" => $this->server->getPort(),
                "version" => "3.9.0",
            ],
            [
                "User-Agent" => Platform::AMXMODX,
            ]
        );

        // then
        $this->assertSame(402, $response->getStatusCode());
    }

    /** @test */
    public function returns_400_if_invalid_token()
    {
        // when
        $response = $this->get(
            "/api/server/config",
            [
                "token" => "asd",
                "version" => "3.10.0",
            ],
            [
                "User-Agent" => Platform::AMXMODX,
            ]
        );

        // then
        $this->assertSame(400, $response->getStatusCode());
    }

    /** @test */
    public function with_player_flags()
    {
        // given
        /** @var PlayerFlagService $playerFlagService */
        $playerFlagService = $this->app->make(PlayerFlagService::class);

        $userService = $this->factory->extraFlagUserService([
            "server_id" => $this->server->getId(),
        ]);
        $playerFlagService->recalculatePlayerFlags(
            $this->server->getId(),
            $userService->getType(),
            $userService->getAuthData()
        );

        // when
        $response = $this->get(
            "/api/server/config",
            [
                "token" => $this->server->getToken(),
                "ip" => $this->server->getIp(),
                "port" => $this->server->getPort(),
                "version" => "3.10.0",
            ],
            [
                "Accept" => "application/json",
                "User-Agent" => Platform::AMXMODX,
            ]
        );

        // then
        $this->assertSame(200, $response->getStatusCode());
        $json = $this->decodeJsonResponse($response);
        $this->assertSame(
            [
                [
                    "t" => ExtraFlagType::TYPE_NICK,
                    "a" => "my_nickname",
                    "p" => "pokll12",
                    "f" => "t",
                ],
            ],
            $json["pf"]
        );
    }

    /** @test */
    public function do_not_include_service_with_group()
    {
        // given
        $this->factory->serverService([
            "server_id" => $this->server->getId(),
            "service_id" => "vip",
        ]);
        $this->factory->extraFlagService([
            "id" => "test",
            "groups" => [1],
        ]);
        $this->factory->serverService([
            "server_id" => $this->server->getId(),
            "service_id" => "test",
        ]);

        // when
        $response = $this->get(
            "/api/server/config",
            [
                "token" => $this->server->getToken(),
                "ip" => $this->server->getIp(),
                "port" => $this->server->getPort(),
                "version" => "3.10.0",
            ],
            [
                "Accept" => "application/json",
                "User-Agent" => Platform::AMXMODX,
            ]
        );

        // then
        $this->assertSame(200, $response->getStatusCode());
        $json = $this->decodeJsonResponse($response);
        $this->assertCount(1, $json["se"]);
    }
}
