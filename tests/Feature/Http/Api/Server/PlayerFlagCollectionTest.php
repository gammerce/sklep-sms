<?php
namespace Tests\Feature\Http\Api\Server;

use App\Models\Server;
use App\ServiceModules\ExtraFlags\ExtraFlagType;
use App\ServiceModules\ExtraFlags\PlayerFlagService;
use App\System\Settings;
use Tests\Psr4\TestCases\HttpTestCase;

class PlayerFlagCollectionTest extends HttpTestCase
{
    /** @var Settings */
    private $settings;

    /** @var Server */
    private $server;

    /** @var PlayerFlagService */
    private $playerFlagService;

    protected function setUp()
    {
        parent::setUp();

        $this->settings = $this->app->make(Settings::class);
        $this->server = $this->factory->server();
        $this->playerFlagService = $this->app->make(PlayerFlagService::class);
    }

    /** @test */
    public function get_user_services()
    {
        // given
        $userService = $this->factory->extraFlagUserService([
            'server_id' => $this->server->getId(),
            'service_id' => "vippro",
            'type' => ExtraFlagType::TYPE_SID,
            'auth_data' => 'STEAM_1:0:22309351',
            'password' => '',
        ]);
        $this->playerFlagService->recalculatePlayerFlags(
            $userService->getServerId(),
            $userService->getType(),
            $userService->getAuthData()
        );

        // when
        $response = $this->get(
            '/api/server/players_flags',
            [
                'token' => $this->server->getToken(),
            ],
            [
                'Accept' => 'application/json',
            ]
        );

        // then
        $this->assertSame(200, $response->getStatusCode());
        $json = $this->decodeJsonResponse($response);
        $this->assertEquals(
            [
                "pf" => [
                    [
                        "t" => ExtraFlagType::TYPE_SID,
                        "a" => "STEAM_1:0:22309351",
                        "p" => "",
                        "f" => "btx",
                    ],
                ],
            ],
            $json
        );
    }

    /** @test */
    public function no_user_services()
    {
        // given
        $anotherServer = $this->factory->server();
        $userService = $this->factory->extraFlagUserService([
            'server_id' => $anotherServer->getId(),
            'service_id' => "vippro",
            'type' => ExtraFlagType::TYPE_SID,
            'auth_data' => 'STEAM_1:0:22309351',
        ]);
        $this->playerFlagService->recalculatePlayerFlags(
            $userService->getServerId(),
            $userService->getType(),
            $userService->getAuthData()
        );

        // when
        $response = $this->get(
            '/api/server/players_flags',
            [
                'token' => $this->server->getToken(),
            ],
            [
                'Accept' => 'application/json',
            ]
        );

        // then
        $this->assertSame(200, $response->getStatusCode());
        $json = $this->decodeJsonResponse($response);
        $this->assertEquals(
            [
                "pf" => [],
            ],
            $json
        );
    }
}
