<?php

namespace App\Support;

use UnexpectedValueException;

final class SteamIDConverter
{
    private const BASE_NUM = 76561197960265728; // "V" in the conversion algorithms
    private const REGEX_STEAMID64 = "/^[0-9]{17}$/";
    private const REGEX_STEAMID = "/^STEAM_[0-5]:[01]:\d{1,17}$/";
    private const REGEX_STEAMID3 = "/^\[U:1:[0-9]{1,17}\]$/";

    /**
     * Convert any format to SteamID64
     */
    public function toSteamID64(string $value): string
    {
        if ($this->isSteamID64($value)) {
            return $value;
        }

        $steamID = $this->toSteamID($value);
        $split = explode(":", $steamID);
        $v = $this::BASE_NUM;
        $z = (int) $split[2];
        $y = (int) $split[1];

        return (string) ($v + $z * 2 + $y);
    }

    /**
     * Convert any format to SteamID
     */
    public function toSteamID(string $value): string
    {
        if ($this->isSteamID($value)) {
            return $value;
        }

        if ($this->isSteamID3($value)) {
            $split = explode(":", $value);
            $last = (int) substr($split[2], 0, -1);
            $y = $last % 2;
            $z = floor($last / 2);

            return "STEAM_0:{$y}:{$z}";
        }

        if ($this->isSteamID64($value)) {
            $v = $this::BASE_NUM;
            $w = (int) $value;
            $y = $w % 2;
            $z = floor(($w - $y - $v) / 2);

            return "STEAM_0:{$y}:{$z}";
        }

        throw new UnexpectedValueException("Invalid value format");
    }

    /**
     * Convert any format to SteamID3
     */
    public function toSteamID3(string $value): string
    {
        if ($this->isSteamID3($value)) {
            return $value;
        }

        $steamID = $this->toSteamID($value);
        $split = explode(":", $steamID);
        $z = (int) $split[1] + (int) $split[2] * 2;

        return "[U:1:{$z}]";
    }

    public function isAnySteamID(string $value): bool
    {
        return $this->isSteamID($value) || $this->isSteamID3($value) || $this->isSteamID64($value);
    }

    public function isSteamID(string $value): bool
    {
        return preg_match($this::REGEX_STEAMID, $value);
    }

    public function isSteamID64(string $value): bool
    {
        return preg_match($this::REGEX_STEAMID64, $value);
    }

    public function isSteamID3(string $value): bool
    {
        return preg_match($this::REGEX_STEAMID3, $value);
    }

    public function profileURL(string $value): string
    {
        return "https://steamcommunity.com/profiles/{$this->toSteamID64($value)}";
    }
}
