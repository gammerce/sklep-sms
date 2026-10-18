<?php

namespace Tests\Unit\Support;

use App\Requesting\Response as RequestingResponse;
use App\Support\SteamOpenId;
use Symfony\Component\HttpFoundation\Request;
use Tests\Psr4\TestCases\UnitTestCase;

class SteamOpenIdTest extends UnitTestCase
{
    private const STEAM_ID_64 = "76561197990444178";
    private const RETURN_TO = "http://localhost/api/steam/callback?state=abc";

    private SteamOpenId $steamOpenId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->steamOpenId = new SteamOpenId($this->requesterMock);
    }

    /** @test */
    public function builds_auth_url()
    {
        $url = $this->steamOpenId->buildAuthUrl(self::RETURN_TO, "http://localhost/");

        $this->assertStringStartsWith(SteamOpenId::OPENID_ENDPOINT . "?", $url);
        $this->assertStringContainsString("openid.mode=checkid_setup", $url);
        $this->assertStringContainsString("openid.return_to=" . urlencode(self::RETURN_TO), $url);
        $this->assertStringContainsString("identifier_select", $url);
    }

    /** @test */
    public function verifies_valid_assertion()
    {
        // given
        $this->requesterMock
            ->shouldReceive("post")
            ->andReturn(
                new RequestingResponse(200, "ns:http://specs.openid.net/auth/2.0\nis_valid:true\n")
            );

        // when
        $steamId64 = $this->steamOpenId->verify($this->requestWithAssertion(), self::RETURN_TO);

        // then
        $this->assertSame(self::STEAM_ID_64, $steamId64);
    }

    /**
     * PHP mangles dots in $_GET keys ("openid.mode" -> "openid_mode"), so in
     * production the params must be read from the raw query string. Regression
     * guard for the "Could not sign in through Steam" bug on localhost/dev.
     *
     * @test
     */
    public function verifies_assertion_from_raw_query_string()
    {
        // given
        $this->requesterMock
            ->shouldReceive("post")
            ->once()
            ->andReturn(new RequestingResponse(200, "is_valid:true\n"));

        $claimedId = "https://steamcommunity.com/openid/id/" . self::STEAM_ID_64;
        $rawQuery = http_build_query([
            "state" => "abc",
            "openid.ns" => "http://specs.openid.net/auth/2.0",
            "openid.mode" => "id_res",
            "openid.return_to" => self::RETURN_TO,
            "openid.claimed_id" => $claimedId,
            "openid.identity" => $claimedId,
        ]);

        // emulate production: only the raw QUERY_STRING is reliable, the query bag
        // would hold "openid_mode" etc.
        $request = new Request([], [], [], [], [], ["QUERY_STRING" => $rawQuery]);

        // when
        $steamId64 = $this->steamOpenId->verify($request, self::RETURN_TO);

        // then
        $this->assertSame(self::STEAM_ID_64, $steamId64);
    }

    /** @test */
    public function rejects_failed_authentication()
    {
        // given
        $this->requesterMock
            ->shouldReceive("post")
            ->andReturn(new RequestingResponse(200, "is_valid:false\n"));

        // when
        $steamId64 = $this->steamOpenId->verify($this->requestWithAssertion(), self::RETURN_TO);

        // then
        $this->assertNull($steamId64);
    }

    /** @test */
    public function rejects_mismatched_return_to()
    {
        // given
        $request = $this->requestWithAssertion();

        // when
        $steamId64 = $this->steamOpenId->verify($request, "http://evil.example/api/steam/callback");

        // then
        $this->assertNull($steamId64);
    }

    /** @test */
    public function rejects_non_id_res_mode()
    {
        // given
        $request = new Request([
            "openid.mode" => "cancel",
            "openid.return_to" => self::RETURN_TO,
            "openid.claimed_id" => "https://steamcommunity.com/openid/id/" . self::STEAM_ID_64,
        ]);

        // when
        $steamId64 = $this->steamOpenId->verify($request, self::RETURN_TO);

        // then
        $this->assertNull($steamId64);
    }

    /** @test */
    public function rejects_out_of_range_claimed_id()
    {
        // given a well-formed 17-digit id outside the individual-account range
        $this->requesterMock
            ->shouldReceive("post")
            ->andReturn(new RequestingResponse(200, "is_valid:true\n"));

        $claimedId = "https://steamcommunity.com/openid/id/76561206550200319";
        $request = new Request([
            "openid.ns" => "http://specs.openid.net/auth/2.0",
            "openid.mode" => "id_res",
            "openid.return_to" => self::RETURN_TO,
            "openid.claimed_id" => $claimedId,
            "openid.identity" => $claimedId,
        ]);

        // when / then
        $this->assertNull($this->steamOpenId->verify($request, self::RETURN_TO));
    }

    private function requestWithAssertion(): Request
    {
        $claimedId = "https://steamcommunity.com/openid/id/" . self::STEAM_ID_64;

        return new Request([
            "openid.ns" => "http://specs.openid.net/auth/2.0",
            "openid.mode" => "id_res",
            "openid.return_to" => self::RETURN_TO,
            "openid.claimed_id" => $claimedId,
            "openid.identity" => $claimedId,
        ]);
    }
}
