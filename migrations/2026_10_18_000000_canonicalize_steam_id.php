<?php

use App\Install\Migration;
use App\Support\SteamIDConverter;

/**
 * Canonicalises ss_users.steam_id to SteamID64 (the single stored form).
 *
 * Legacy STEAM_0:Y:Z / STEAM_1:Y:Z of the same account collapse to the same
 * SteamID64; if two accounts hold the same account's ID (the old prefix
 * ambiguity), the verified one wins and the other is unlinked (logged).
 */
class CanonicalizeSteamId extends Migration
{
    public function up()
    {
        $converter = new SteamIDConverter();

        $rows = $this->db
            ->query(
                "SELECT `uid`, `steam_id`, `steam_verified` FROM `ss_users` WHERE `steam_id` IS NOT NULL AND `steam_id` <> ''"
            )
            ->fetchAll();

        $byCanonical = [];

        foreach ($rows as $row) {
            $uid = (int) $row["uid"];
            $raw = trim((string) $row["steam_id"]);

            try {
                $canonical = $converter->toSteamID64($raw);
            } catch (UnexpectedValueException $e) {
                $this->fileLogger->install(
                    "Steam ID canonicalization: invalid value kept for uid={$uid}: {$raw}"
                );
                continue;
            }

            $byCanonical[$canonical][] = [
                "uid" => $uid,
                "verified" => (int) $row["steam_verified"],
                "raw" => $raw,
            ];
        }

        foreach ($byCanonical as $canonical => $entries) {
            // Verified first, then lowest uid.
            usort($entries, function (array $a, array $b) {
                if ($a["verified"] !== $b["verified"]) {
                    return $b["verified"] <=> $a["verified"];
                }
                return $a["uid"] <=> $b["uid"];
            });

            $winner = array_shift($entries);
            $this->setSteamId($winner["uid"], $canonical);

            foreach ($entries as $loser) {
                $this->setSteamId($loser["uid"], null);
                $this->db
                    ->statement("UPDATE `ss_users` SET `steam_verified` = 0 WHERE `uid` = ?")
                    ->bindAndExecute([$loser["uid"]]);
                $this->fileLogger->install(
                    "Steam ID collision canonicalized: uid={$loser["uid"]} ({$loser["raw"]}) unlinked, kept uid={$winner["uid"]} ({$canonical})"
                );
            }
        }
    }

    private function setSteamId(int $uid, ?string $value): void
    {
        $this->db
            ->statement("UPDATE `ss_users` SET `steam_id` = ? WHERE `uid` = ?")
            ->bindAndExecute([$value, $uid]);
    }
}
