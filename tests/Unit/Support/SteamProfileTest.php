<?php
namespace Tests\Unit\Support;

use App\Requesting\Response as RequestingResponse;
use App\Support\SteamProfile;
use Mockery;
use Tests\Psr4\TestCases\UnitTestCase;

class SteamProfileTest extends UnitTestCase
{
    private SteamProfile $steamProfile;

    protected function setUp(): void
    {
        parent::setUp();
        $this->steamProfile = new SteamProfile($this->requesterMock);
    }

    protected function tearDown(): void
    {
        putenv("STEAM_API_KEY");
        parent::tearDown();
    }

    /** @test */
    public function returns_null_without_an_api_key()
    {
        putenv("STEAM_API_KEY");

        $this->assertNull($this->steamProfile->getPersonaName("76561198000000000"));
        $this->assertNull($this->steamProfile->getRealName("76561198000000000"));
    }

    /** @test */
    public function reads_persona_and_real_name_with_a_single_request()
    {
        putenv("STEAM_API_KEY=testkey");

        $this->requesterMock
            ->shouldReceive("get")
            ->once() // both getters share one lookup
            ->withArgs([
                "https://api.steampowered.com/ISteamUser/GetPlayerSummaries/v2/",
                Mockery::type("array"),
            ])
            ->andReturn(
                new RequestingResponse(
                    200,
                    json_encode([
                        "response" => [
                            "players" => [
                                ["personaname" => "♋ SeeK", "realname" => "Jan Kowalski"],
                            ],
                        ],
                    ])
                )
            );

        $this->assertSame("♋ SeeK", $this->steamProfile->getPersonaName("76561198000000000"));
        $this->assertSame("Jan Kowalski", $this->steamProfile->getRealName("76561198000000000"));
    }
}
