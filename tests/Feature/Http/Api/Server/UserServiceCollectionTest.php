<?php
namespace Tests\Feature\Http\Api\Server;

use App\Models\Server;
use App\ServiceModules\ExtraFlags\ExtraFlagType;
use Tests\Psr4\TestCases\HttpTestCase;

class UserServiceCollectionTest extends HttpTestCase
{
    private Server $server;

    protected function setUp(): void
    {
        parent::setUp();
        $this->server = $this->factory->server();
    }

    /** @test */
    public function get_user_services()
    {
        // given
        $userServiceNick = $this->factory->extraFlagUserService([
            "auth_data" => "example",
            "server_id" => $this->server->getId(),
            "type" => ExtraFlagType::TYPE_NICK,
        ]);
        $userServiceIp = $this->factory->extraFlagUserService([
            "auth_data" => "192.0.2.1",
            "server_id" => $this->server->getId(),
            "service_id" => "vippro",
            "type" => ExtraFlagType::TYPE_IP,
        ]);
        $userServiceSteamId = $this->factory->extraFlagUserService([
            "auth_data" => "STEAM_1:0:22309350",
            "server_id" => $this->server->getId(),
            "type" => ExtraFlagType::TYPE_SID,
        ]);
        $this->factory->extraFlagUserService([
            "auth_data" => "STEAM_1:0:22309351",
            "server_id" => $this->server->getId(),
            "service_id" => "vippro",
            "type" => ExtraFlagType::TYPE_SID,
        ]);

        // when
        $response = $this->get(
            "/api/server/user_services",
            [
                "ip" => "192.0.2.1",
                "nick" => "example",
                "steam_id" => "STEAM_1:0:22309350",
                "token" => $this->server->getToken(),
            ],
            [
                "Accept" => "application/json",
            ]
        );

        // then
        $this->assertSame(200, $response->getStatusCode());
        $json = $this->decodeJsonResponse($response);
        $this->assertEquals(
            [
                [
                    "s" => "VIP",
                    "e" => as_expiration_datetime_string($userServiceSteamId->getExpire()),
                ],
                [
                    "s" => "VIP PRO",
                    "e" => as_expiration_datetime_string($userServiceIp->getExpire()),
                ],
                [
                    "s" => "VIP",
                    "e" => as_expiration_datetime_string($userServiceNick->getExpire()),
                ],
            ],
            $json
        );
    }

    /** @test */
    public function no_user_services()
    {
        // given
        $this->factory->extraFlagUserService([
            "auth_data" => "STEAM_1:0:22309351",
            "server_id" => $this->server->getId(),
            "service_id" => "vippro",
            "type" => ExtraFlagType::TYPE_SID,
        ]);

        // when
        $response = $this->get(
            "/api/server/user_services",
            [
                "ip" => "192.0.2.1",
                "nick" => "example",
                "steam_id" => "STEAM_1:0:22309350",
                "token" => $this->server->getToken(),
            ],
            [
                "Accept" => "application/json",
            ]
        );

        // then
        $this->assertSame(200, $response->getStatusCode());
        $json = $this->decodeJsonResponse($response);
        $this->assertEquals([], $json);
    }

    /** @test */
    public function works_with_special_characters()
    {
        // given
        $userName = "❀zażółć gęślą jaźń ㋛ヅ❤♫";

        $userServiceNick = $this->factory->extraFlagUserService([
            "auth_data" => $userName,
            "server_id" => $this->server->getId(),
            "service_id" => "vippro",
            "type" => ExtraFlagType::TYPE_NICK,
        ]);

        // when
        $response = $this->get(
            "/api/server/user_services",
            [
                "nick" => $userName,
                "token" => $this->server->getToken(),
            ],
            [
                "Accept" => "application/json",
            ]
        );

        // then
        $this->assertSame(200, $response->getStatusCode());
        $json = $this->decodeJsonResponse($response);
        $this->assertEquals(
            [
                [
                    "s" => "VIP PRO",
                    "e" => as_expiration_datetime_string($userServiceNick->getExpire()),
                ],
            ],
            $json
        );
    }
}
