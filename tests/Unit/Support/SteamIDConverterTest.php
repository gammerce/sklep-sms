<?php

namespace Tests\Unit\Support;

use App\Support\SteamIDConverter;
use Tests\Psr4\TestCases\TestCase;
use Tests\Psr4\TestCases\UnitTestCase;
use UnexpectedValueException;

class SteamIDConverterTest extends UnitTestCase
{
    private SteamIDConverter $converter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->converter = new SteamIDConverter();
    }

    /**
     * @test
     * @dataProvider providerSteamID
     */
    public function transform_steam_id_to_other_formats(string $steamID)
    {
        // when
        $steamID3 = $this->converter->toSteamID3($steamID);
        $steamID64 = $this->converter->toSteamID64($steamID);

        // then
        $this->assertSame($steamID, $this->converter->toSteamID($steamID3));
        $this->assertSame($steamID, $this->converter->toSteamID($steamID64));
    }

    /**
     * @test
     * @dataProvider providerSteamID3
     */
    public function transform_steam_id_3_to_other_formats(string $steamID3)
    {
        // when
        $steamID = $this->converter->toSteamID($steamID3);
        $steamID64 = $this->converter->toSteamID64($steamID3);

        // then
        $this->assertSame($steamID3, $this->converter->toSteamID3($steamID));
        $this->assertSame($steamID3, $this->converter->toSteamID3($steamID64));
    }

    /**
     * @test
     * @dataProvider providerSteamID64
     */
    public function transform_steam_id_64_to_other_formats(string $steamID64)
    {
        // when
        $steamID = $this->converter->toSteamID($steamID64);
        $steamID3 = $this->converter->toSteamID3($steamID64);

        // then
        $this->assertSame($steamID64, $this->converter->toSteamID64($steamID));
        $this->assertSame($steamID64, $this->converter->toSteamID64($steamID3));
    }

    /** @test */
    public function fails_if_invalid_format_is_given()
    {
        // given
        $this->expectException(UnexpectedValueException::class);

        // when
        $this->converter->toSteamID("abcd");
    }

    public function providerSteamID(): array
    {
        return [
            ["STEAM_0:1:21984552"],
            ["STEAM_0:0:176934959"],
            ["STEAM_0:0:175494939"],
            ["STEAM_0:0:457292964"],
            ["STEAM_0:0:4017410"],
        ];
    }

    public function providerSteamID3(): array
    {
        return [
            ["[U:1:43969105]"],
            ["[U:1:353869918]"],
            ["[U:1:350989878]"],
            ["[U:1:914585928]"],
            ["[U:1:8034820]"],
        ];
    }

    public function providerSteamID64(): array
    {
        return [
            ["76561198004234833"],
            ["76561198314135646"],
            ["76561198311255606"],
            ["76561198874851656"],
            ["76561197968300548"],
        ];
    }
}
