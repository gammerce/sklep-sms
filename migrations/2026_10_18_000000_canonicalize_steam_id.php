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
    private const STEAMID64_BASE = 76561197960265728; // universe=1, type=1, instance=1, accountID=0
    private const STEAMID64_MAX = self::STEAMID64_BASE + 2 * 4294967295 + 1;

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
                $this->setSteamId($uid, null);
                $this->db
                    ->statement("UPDATE `ss_users` SET `steam_verified` = 0 WHERE `uid` = ?")
                    ->bindAndExecute([$uid]);
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

    private function resolve(int $uid, string $raw, SteamIDConverter $converter): ?string
    {
        // Repair legacy double-encoding: STEAM_x:y:<SteamID64>.
        if (preg_match("/^STEAM_[0-5]:[01]:(7656119[0-9]{10})$/", $raw, $matches)) {
            if (!$this->isValidSteamId64($matches[1])) {
                return null;
            }

            $this->fileLogger->install(
                "Steam ID canonicalization: repaired double-encoded uid={$uid} {$raw} -> {$matches[1]}"
            );

            return $matches[1];
        }

        try {
            $canonical = $converter->toSteamID64($raw);
        } catch (UnexpectedValueException $e) {
            return null;
        }

        return $this->isValidSteamId64($canonical) ? $canonical : null;
    }

    private function isValidSteamId64(string $value): bool
    {
        if (!preg_match("/^[0-9]{17}$/", $value)) {
            return false;
        }

        $number = (int) $value;

        return $number >= self::STEAMID64_BASE && $number <= self::STEAMID64_MAX;
    }

    private function setSteamId(int $uid, ?string $value): void
    {
        $this->db
            ->statement("UPDATE `ss_users` SET `steam_id` = ? WHERE `uid` = ?")
            ->bindAndExecute([$value, $uid]);
    }
}
