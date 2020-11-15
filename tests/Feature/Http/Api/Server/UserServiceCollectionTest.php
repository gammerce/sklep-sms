<?php
namespace Tests\Feature\Http\Api\Server;

use App\Models\Server;
use App\ServiceModules\ExtraFlags\ExtraFlagType;
use Tests\Psr4\TestCases\HttpTestCase;

class UserServiceCollectionTest extends HttpTestCase
{
    /** @var Server */
    private $server;

    protected function setUp()
    {
        parent::setUp();
        $this->server = $this->factory->server();
    }

    /** @test */
    public function get_user_services()
    {
        // given
        $userServiceNick = $this->factory->extraFlagUserService([
            "server_id" => $this->server->getId(),
            "type" => ExtraFlagType::TYPE_NICK,
            "auth_data" => "example",
        ]);
        $userServiceIp = $this->factory->extraFlagUserService([
            "server_id" => $this->server->getId(),
            "service_id" => "vippro",
            "type" => ExtraFlagType::TYPE_IP,
            "auth_data" => "192.0.2.1",
        ]);
        $userServiceSteamId = $this->factory->extraFlagUserService([
            "server_id" => $this->server->getId(),
            "type" => ExtraFlagType::TYPE_SID,
            "auth_data" => "STEAM_1:0:22309350",
        ]);
        $this->factory->extraFlagUserService([
            "server_id" => $this->server->getId(),
            "service_id" => "vippro",
            "type" => ExtraFlagType::TYPE_SID,
            "auth_data" => "STEAM_1:0:22309351",
        ]);

        // when
        $response = $this->get(
            "/api/server/user_services",
            [
                "token" => $this->server->getToken(),
                "server_id" => $this->server->getId(),
                "nick" => "example",
                "ip" => "192.0.2.1",
                "steam_id" => "STEAM_1:0:22309350",
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
            "server_id" => $this->server->getId(),
            "service_id" => "vippro",
            "type" => ExtraFlagType::TYPE_SID,
            "auth_data" => "STEAM_1:0:22309351",
        ]);

        // when
        $response = $this->get(
            "/api/server/user_services",
            [
                "token" => $this->server->getToken(),
                "server_id" => $this->server->getId(),
                "nick" => "example",
                "ip" => "192.0.2.1",
                "steam_id" => "STEAM_1:0:22309350",
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
            "server_id" => $this->server->getId(),
            "service_id" => "vippro",
            "type" => ExtraFlagType::TYPE_NICK,
            "auth_data" => $userName,
        ]);

        // when
        $response = $this->get(
            "/api/server/user_services",
            [
                "token" => $this->server->getToken(),
                "server_id" => $this->server->getId(),
                "nick" => $userName,
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
