<?php
namespace Tests\Unit\Support;

use App\Support\SteamUsername;
use Tests\Psr4\TestCases\UnitTestCase;

class SteamUsernameTest extends UnitTestCase
{
    /** @test */
    public function sanitizes_persona_names()
    {
        // emoji/space runs collapse to a separator and are trimmed
        $this->assertSame("SeeK", SteamUsername::sanitize("♋ SeeK"));
        $this->assertSame("John_Doe", SteamUsername::sanitize("  John   Doe  "));

        // punctuation becomes "_"
        $this->assertSame("O_Brien", SteamUsername::sanitize("O'Brien"));
        $this->assertSame("a_b_c", SteamUsername::sanitize("a<b>&c"));

        // unicode letters, numbers, "_" and "-" are kept
        $this->assertSame("Łukasz", SteamUsername::sanitize("Łukasz"));
        $this->assertSame("a-b_c123", SteamUsername::sanitize("a-b_c123"));

        // nothing usable left
        $this->assertSame("", SteamUsername::sanitize("♋♋♋"));
        $this->assertSame("", SteamUsername::sanitize("   "));
    }
}
