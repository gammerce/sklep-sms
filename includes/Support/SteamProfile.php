<?php
namespace App\Support;

use App\Requesting\Requester;

/**
 * Optional Steam Web API integration. Only used when STEAM_API_KEY is provided
 * by the shop owner in the environment; without it sign in works OpenID-only.
 */
class SteamProfile
{
    private const SUMMARY_ENDPOINT = "https://api.steampowered.com/ISteamUser/GetPlayerSummaries/v2/";

    private Requester $requester;

    /** @var array<string, array|null> */
    private array $players = [];

    public function __construct(Requester $requester)
    {
        $this->requester = $requester;
    }

    /**
     * @return string|null Steam persona (display) name.
     */
    public function getPersonaName(string $steamId64): ?string
    {
        return $this->stringValue($this->getPlayer($steamId64), "personaname");
    }

    /**
     * @return string|null Public real name, when the player set one.
     */
    public function getRealName(string $steamId64): ?string
    {
        return $this->stringValue($this->getPlayer($steamId64), "realname");
    }

    /**
     * One HTTP request per Steam ID per request lifecycle (both getters share it).
     *
     * @return array|null
     */
    private function getPlayer(string $steamId64): ?array
    {
        if (array_key_exists($steamId64, $this->players)) {
            return $this->players[$steamId64];
        }

        $this->players[$steamId64] = $this->fetchPlayer($steamId64);

        return $this->players[$steamId64];
    }

    private function fetchPlayer(string $steamId64): ?array
    {
        $apiKey = (string) getenv("STEAM_API_KEY");

        if (!strlen($apiKey)) {
            return null;
        }

        $response = $this->requester->get(self::SUMMARY_ENDPOINT, [
            "key" => $apiKey,
            "steamids" => $steamId64,
        ]);

        if (!$response || !$response->isOk()) {
            return null;
        }

        $players = array_dot_get($response->json() ?: [], "response.players", []);
        $player = is_array($players) ? array_get($players, 0) : null;

        return is_array($player) ? $player : null;
    }

    private function stringValue(?array $player, string $key): ?string
    {
        $value = $player[$key] ?? null;

        return is_string($value) && strlen($value) ? $value : null;
    }
}
