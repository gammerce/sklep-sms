<?php
namespace App\Support;

/**
 * Turns a Steam persona name into a username-safe base.
 */
final class SteamUsername
{
    /**
     * Keep unicode letters, numbers, "_" and "-"; collapse everything else
     * (spaces, emoji, punctuation) into a single "_". Returns "" when nothing
     * usable is left. The result satisfies UsernameRule (htmlspecialchars is a
     * no-op for these characters).
     */
    public static function sanitize(string $value): string
    {
        $value = preg_replace("/[^\p{L}\p{N}_-]+/u", "_", $value) ?? "";

        return trim($value, "_-");
    }
}
