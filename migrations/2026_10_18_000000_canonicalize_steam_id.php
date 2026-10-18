<?php

use App\Install\Migration;
use App\Support\SteamIDConverter;

/**
 * Canonicalises ss_users.steam_id to SteamID64 (the single stored form).
 *
 * - Legacy STEAM_0:Y:Z / STEAM_1:Y:Z of the same account collapse to the same
 *   SteamID64; if two accounts hold the same account's ID (the old prefix
 *   ambiguity), the verified one wins and the other is unlinked (logged).
 * - Legacy double-encoded IDs of the form STEAM_x:y:<SteamID64> (a pasted
 *   SteamID64 wrapped in Steam2 by the old code) are repaired to the embedded
 *   SteamID64 and logged.
 * - Anything that does not yield a valid individual SteamID64 is unlinked and
 *   logged rather than left in a form the app can no longer match or display.
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
            $canonical = $this->resolve($uid, $raw, $converter);

            if ($canonical === null) {
                $this->unlink($uid);
                $this->fileLogger->install(
                    "Steam ID canonicalization: invalid value unlinked for uid={$uid}: {$raw}"
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

            // Clear every member first, then write the winner: avoids transient
            // UNIQUE(steam_id) collisions when a loser already holds the canonical
            // value.
            foreach ($entries as $entry) {
                $this->setSteamId($entry["uid"], null);
            }

            $winner = array_shift($entries);
            $this->setSteamId($winner["uid"], $canonical);

            foreach ($entries as $loser) {
                $this->unlink($loser["uid"]);
                $this->fileLogger->install(
                    "Steam ID collision canonicalized: uid={$loser["uid"]} ({$loser["raw"]}) unlinked, kept uid={$winner["uid"]} ({$canonical})"
                );
            }
        }
    }

    private function resolve(int $uid, string $raw, SteamIDConverter $converter): ?string
    {
        // Repair legacy double-encoding: STEAM_x:y:<SteamID64>. A real Steam2 Z is
        // at most 10 digits, so a 17-digit Z is always an embedded SteamID64.
        if (preg_match("/^STEAM_[0-5]:[01]:(\d{17})$/", $raw, $matches)) {
            if (!$converter->isValidSteamID64($matches[1])) {
                return null;
            }

            $this->fileLogger->install(
                "Steam ID canonicalization: repaired double-encoded uid={$uid} {$raw} -> {$matches[1]}"
            );

            return $matches[1];
        }

        return $converter->tryToSteamID64($raw);
    }

    private function unlink(int $uid): void
    {
        $this->db
            ->statement(
                "UPDATE `ss_users` SET `steam_id` = NULL, `steam_verified` = 0 WHERE `uid` = ?"
            )
            ->bindAndExecute([$uid]);
    }

    private function setSteamId(int $uid, ?string $value): void
    {
        $this->db
            ->statement("UPDATE `ss_users` SET `steam_id` = ? WHERE `uid` = ?")
            ->bindAndExecute([$value, $uid]);
    }
}
