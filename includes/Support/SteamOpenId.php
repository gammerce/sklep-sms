<?php
namespace App\Support;

use App\Requesting\Requester;
use Symfony\Component\HttpFoundation\Request;

/**
 * Minimal Steam OpenID 2.0 ("Sign in through Steam") client.
 *
 * Steam OpenID does not require an API key. The claimed SteamID is only trusted
 * after the assertion is verified server side with Steam (check_authentication).
 */
class SteamOpenId
{
    public const OPENID_ENDPOINT = "https://steamcommunity.com/openid/login";

    private const IDENTITY_SELECT = "http://specs.openid.net/auth/2.0/identifier_select";
    private const NAMESPACE_2_0 = "http://specs.openid.net/auth/2.0";

    private const CLAIMED_ID_REGEX = "#^https://steamcommunity\.com/openid/id/(\d{17})$#";

    private Requester $requester;

    public function __construct(Requester $requester)
    {
        $this->requester = $requester;
    }

    /**
     * URL the browser should be redirected to in order to start the flow.
     *
     * @param string $returnTo Absolute URL Steam will redirect the user back to.
     * @param string $realm    Absolute realm URL (usually the shop root).
     */
    public function buildAuthUrl(string $returnTo, string $realm): string
    {
        $query = http_build_query([
            "openid.ns" => self::NAMESPACE_2_0,
            "openid.mode" => "checkid_setup",
            "openid.return_to" => $returnTo,
            "openid.realm" => $realm,
            "openid.identity" => self::IDENTITY_SELECT,
            "openid.claimed_id" => self::IDENTITY_SELECT,
        ]);

        return self::OPENID_ENDPOINT . "?" . $query;
    }

    /**
     * Verifies the assertion returned to the callback.
     *
     * @return string|null SteamID64 (17 digits) when the assertion is valid, otherwise null.
     */
    public function verify(Request $request, string $expectedReturnTo): ?string
    {
        $params = $this->openIdParams($request);

        if (array_get($params, "openid.mode") !== "id_res") {
            return null;
        }

        // The returned return_to must be the one we issued, otherwise the assertion
        // may have been produced for a different flow/site.
        if (array_get($params, "openid.return_to") !== $expectedReturnTo) {
            return null;
        }

        if (!$this->checkAuthentication($params)) {
            return null;
        }

        return $this->extractSteamId64(array_get($params, "openid.claimed_id", ""));
    }

    /**
     * @return array<string,string>
     */
    private function openIdParams(Request $request): array
    {
        $params = [];

        // PHP converts dots in $_GET keys to underscores ("openid.mode" becomes
        // "openid_mode"), so the query bag can't be used here. The raw query
        // string is intact and is parsed manually instead.
        $rawQuery = (string) $request->server->get("QUERY_STRING", "");

        if (strlen($rawQuery)) {
            foreach (explode("&", $rawQuery) as $pair) {
                if ($pair === "") {
                    continue;
                }

                [$key, $value] = array_pad(explode("=", $pair, 2), 2, "");
                $key = urldecode($key);

                if (str_starts_with($key, "openid.")) {
                    $params[$key] = urldecode($value);
                }
            }
        }

        // Fallback for programmatically built requests (e.g. tests) where only
        // the query bag is populated.
        foreach ($request->query->all() as $key => $value) {
            if (is_string($value) && str_starts_with((string) $key, "openid.")) {
                $params[$key] ??= $value;
            }
        }

        return $params;
    }

    /**
     * @param array<string,string> $params
     */
    private function checkAuthentication(array $params): bool
    {
        $params["openid.mode"] = "check_authentication";

        $response = $this->requester->post(self::OPENID_ENDPOINT, http_build_query($params), [
            "Content-Type" => "application/x-www-form-urlencoded",
        ]);

        if (!$response || !$response->is2xx()) {
            return false;
        }

        return (bool) preg_match("/^is_valid:true\r?$/mi", $response->getBody());
    }

    public function extractSteamId64(string $claimedId): ?string
    {
        if (!preg_match(self::CLAIMED_ID_REGEX, $claimedId, $matches)) {
            return null;
        }

        return (new SteamIDConverter())->isValidSteamID64($matches[1]) ? $matches[1] : null;
    }
}
