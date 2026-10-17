<?php
namespace App\Support;

use App\Requesting\Requester;

/**
 * Optional Steam Web API integration. Only used when STEAM_API_KEY is provided
 * by the shop owner in the environment; without it sign in works OpenID-only.
 */
class SteamProfile
{
    private const SUMMARY_ENDPOINT =
        "https://api.steampowered.com/ISteamUser/GetPlayerSummaries/v2/";

    private Requester $requester;

    public function __construct(Requester $requester)
    {
        $this->requester = $requester;
    }

    /**
     * @return string|null Steam persona (display) name, or null when unavailable.
     */
    public function getPersonaName(string $steamId64): ?string
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

        $data = $response->json() ?: [];
        $players = array_dot_get($data, "response.players", []);
        $name = is_array($players) ? array_get($players, 0)["personaname"] ?? null : null;

        return is_string($name) && strlen($name) ? $name : null;
    }
}
