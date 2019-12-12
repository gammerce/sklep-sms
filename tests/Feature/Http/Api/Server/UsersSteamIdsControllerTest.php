<?php
namespace Tests\Feature\Http\Api\Server;

use Tests\Psr4\TestCases\IndexTestCase;

class UsersSteamIdsControllerTest extends IndexTestCase
{
    /** @test */
    public function list_when_no_users()
    {
        // when
        $response = $this->get('/api/server/users/steam-ids');

        // then
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(";", $response->getContent());
    }

    /** @test */
    public function lists_users_steam_ids()
    {
        // given
        $this->factory->user([
            "steam_id" => "STEAM_1",
        ]);
        $this->factory->user([
            "steam_id" => "STEAM_12",
        ]);
        $this->factory->user();
        $this->factory->user([
            "steam_id" => "STEAM_2",
        ]);

        // when
        $response = $this->get('/api/server/users/steam-ids');

        // then
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals("STEAM_1;STEAM_12;STEAM_2;", $response->getContent());
    }
}
