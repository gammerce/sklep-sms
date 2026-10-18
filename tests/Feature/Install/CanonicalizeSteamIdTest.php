<?php
namespace Tests\Feature\Install;

use App\Support\Database;
use App\Support\SteamIDConverter;
use Tests\Psr4\TestCases\HttpTestCase;

class CanonicalizeSteamIdTest extends HttpTestCase
{
    private Database $db;

    protected function setUp(): void
    {
        parent::setUp();
        $this->db = $this->app->make(Database::class);

        require_once __DIR__ .
            "/../../../migrations/2026_10_18_000000_canonicalize_steam_id.php";
    }

    /** @test */
    public function canonicalises_repairs_and_unlinks_values()
    {
        // given
        $plain = $this->addUser("m_plain", "STEAM_1:0:22309350", 0);
        $doubleEncoded = $this->addUser("m_double", "STEAM_0:0:76561198099614424", 0);
        $garbage = $this->addUser("m_garbage", "not-a-steam-id", 0);

        // when
        $this->app->make("CanonicalizeSteamId")->up();

        // then
        $expectedPlain = (new SteamIDConverter())->toSteamID64("STEAM_1:0:22309350");
        $this->assertSame($expectedPlain, $this->steamIdOf($plain));

        // a SteamID64 wrapped in Steam2 by the old code is repaired
        $this->assertSame("76561198099614424", $this->steamIdOf($doubleEncoded));

        // unusable values are unlinked instead of kept
        $this->assertNull($this->steamIdOf($garbage));
    }

    /** @test */
    public function verified_account_wins_a_collision()
    {
        // given: the same account held by two rows with different universe prefixes
        $verified = $this->addUser("m_verified", "STEAM_0:1:55555555", 1);
        $unverified = $this->addUser("m_unverified", "STEAM_1:1:55555555", 0);

        // when
        $this->app->make("CanonicalizeSteamId")->up();

        // then
        $canonical = (new SteamIDConverter())->toSteamID64("STEAM_0:1:55555555");
        $this->assertSame($canonical, $this->steamIdOf($verified));
        $this->assertNull($this->steamIdOf($unverified));
    }

    private function addUser(string $username, ?string $steamId, int $verified): int
    {
        $this->db
            ->statement(
                "INSERT INTO `ss_users` (`username`, `password`, `salt`, `email`, `forename`, `surname`, `regip`, `groups`, `wallet`, `steam_id`, `steam_verified`, `billing_address`, `regdate`) VALUES (?, '', '', ?, '', '', '127.0.0.1', '1', 0, ?, ?, '{}', NOW())"
            )
            ->bindAndExecute([$username, "{$username}@example.com", $steamId, $verified]);

        return $this->db->lastId();
    }

    private function steamIdOf(int $uid): ?string
    {
        $statement = $this->db->statement("SELECT `steam_id` FROM `ss_users` WHERE `uid` = ?");
        $statement->bindAndExecute([$uid]);
        $value = $statement->fetchColumn();

        return $value === false || $value === null ? null : (string) $value;
    }
}
