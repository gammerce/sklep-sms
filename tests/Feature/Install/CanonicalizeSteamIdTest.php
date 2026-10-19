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

        require_once __DIR__ . "/../../../migrations/2026_10_18_000000_canonicalize_steam_id.php";
    }

    /** @test */
    public function canonicalises_repairs_and_unlinks_values()
    {
        // given
        $plain = $this->addUser("m_plain", "STEAM_1:0:22309350", 0);
        $doubleEncoded = $this->addUser("m_double", "STEAM_0:0:76561198099614424", 0);
        $doubleEncodedHigh = $this->addUser("m_double_high", "STEAM_0:1:76561202255233023", 0);
        $garbage = $this->addUser("m_garbage", "not-a-steam-id", 0);

        // when
        $this->app->make("CanonicalizeSteamId")->up();

        // then
        $expectedPlain = (new SteamIDConverter())->toSteamID64("STEAM_1:0:22309350");
        $this->assertSame($expectedPlain, $this->steamIdOf($plain));

        // a SteamID64 wrapped in Steam2 by the old code is repaired
        $this->assertSame("76561198099614424", $this->steamIdOf($doubleEncoded));
        $this->assertSame("76561202255233023", $this->steamIdOf($doubleEncodedHigh));

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

    /** @test */
    public function handles_a_loser_that_already_holds_the_canonical_value()
    {
        // given the winner holds the legacy form and the loser the canonical one
        $canonical = (new SteamIDConverter())->toSteamID64("STEAM_0:1:55555555");
        $winner = $this->addUser("m_w", "STEAM_1:1:55555555", 1);
        $loser = $this->addUser("m_l", $canonical, 0);

        // when
        $this->app->make("CanonicalizeSteamId")->up();

        // then (no UNIQUE violation: the group is cleared before the winner is set)
        $this->assertSame($canonical, $this->steamIdOf($winner));
        $this->assertNull($this->steamIdOf($loser));
    }

    /** @test */
    public function is_idempotent()
    {
        // given
        $user = $this->addUser("m_plain", "STEAM_1:0:22309350", 0);
        $migration = $this->app->make("CanonicalizeSteamId");

        // when
        $migration->up();
        $first = $this->steamIdOf($user);
        $migration->up();

        // then
        $this->assertSame($first, $this->steamIdOf($user));
    }

    /** @test */
    public function accepts_steam3_and_rejects_out_of_range()
    {
        // given
        $steam3 = $this->addUser("m_s3", "[U:1:20340372]", 0);
        $outOfRange = $this->addUser("m_bad", "00000000000000000", 0);

        // when
        $this->app->make("CanonicalizeSteamId")->up();

        // then
        $this->assertSame(
            (new SteamIDConverter())->toSteamID64("[U:1:20340372]"),
            $this->steamIdOf($steam3)
        );
        $this->assertNull($this->steamIdOf($outOfRange));
    }

    /** @test */
    public function steam_id_has_a_unique_index()
    {
        $statement = $this->db->query("SHOW INDEX FROM `ss_users` WHERE Key_name = 'steam_id'");
        $this->assertGreaterThan(0, count($statement->fetchAll()));
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
