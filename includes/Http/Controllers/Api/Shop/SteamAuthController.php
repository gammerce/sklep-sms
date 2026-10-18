<?php
namespace App\Http\Controllers\Api\Shop;

use App\Loggers\DatabaseLogger;
use App\Repositories\UserRepository;
use App\Routing\UrlGenerator;
use App\Support\SteamOpenId;
use App\Support\SteamProfile;
use App\Support\SteamUsername;
use App\System\Auth;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * "Sign in through Steam" (OpenID 2.0).
 *
 * Security model:
 *  - Only a Steam ID proven via OpenID (`steam_verified = 1`) is used to
 *    authenticate web login. A manually typed (unverified) Steam ID never logs
 *    anyone in and is never auto-merged.
 *  - A verified Steam ID cannot be changed without first unlinking (which
 *    clears both `steam_id` and `steam_verified`).
 *  - Connecting via OpenID reclaims a Steam ID held by an unverified claim on
 *    another account; a conflicting verified link is refused.
 *
 * Steam IDs are handled in canonical form (SteamID64); the repository converts
 * to/from the legacy display form at its boundary.
 */
class SteamAuthController
{
    public function login(Request $request, SteamOpenId $steamOpenId, UrlGenerator $url): Response
    {
        return $this->start($request, $steamOpenId, $url, "login", null);
    }

    public function link(
        Request $request,
        SteamOpenId $steamOpenId,
        UrlGenerator $url,
        Auth $auth
    ): Response {
        return $this->start($request, $steamOpenId, $url, "link", $auth->user()->getId());
    }

    public function callback(
        Request $request,
        SteamOpenId $steamOpenId,
        Auth $auth,
        SteamProfile $steamProfile,
        UserRepository $userRepository,
        UrlGenerator $url,
        DatabaseLogger $logger
    ): Response {
        $session = $request->getSession();
        $expectedState = $session->get("steam_state");
        $intent = $session->get("steam_intent") ?: "login";
        $linkUserId = $session->get("steam_link_uid");

        $session->remove("steam_state");
        $session->remove("steam_intent");
        $session->remove("steam_link_uid");

        $state = (string) $request->query->get("state", "");
        $failurePath = $intent === "link" ? "/page/profile" : "/page/login";

        if (!$expectedState || !hash_equals((string) $expectedState, $state)) {
            return $this->fail($request, $url, "steam_auth_error", $failurePath);
        }

        $expectedReturnTo = $url->to("/api/steam/callback", ["state" => $state]);
        $steamId64 = $steamOpenId->verify($request, $expectedReturnTo);

        if ($steamId64 === null) {
            return $this->fail($request, $url, "steam_auth_error", $failurePath);
        }

        if ($intent === "link") {
            return $this->linkAccount(
                $request,
                $auth,
                $userRepository,
                $url,
                $logger,
                $steamId64,
                $linkUserId
            );
        }

        return $this->loginOrRegister(
            $request,
            $auth,
            $steamProfile,
            $userRepository,
            $url,
            $logger,
            $steamId64
        );
    }

    public function disconnect(
        Request $request,
        Auth $auth,
        UserRepository $userRepository,
        UrlGenerator $url,
        DatabaseLogger $logger
    ): Response {
        $user = $auth->user();

        if (!$user->isSteamVerified()) {
            return $this->fail($request, $url, "steam_not_connected", "/page/profile");
        }

        // Without a password, unlinking would permanently lock the user out.
        if (!$user->canUnlinkSteam()) {
            return $this->fail($request, $url, "steam_unlink_set_password", "/page/profile");
        }

        $userRepository->clearSteamId($user->getId());
        $user->setSteamId(null);
        $user->setSteamVerified(false);
        $logger->logWithUser($user, "log_steam_unlinked", $user->getUsername());

        return new RedirectResponse($url->to("/page/profile"));
    }

    private function start(
        Request $request,
        SteamOpenId $steamOpenId,
        UrlGenerator $url,
        string $intent,
        ?int $userId
    ): Response {
        $state = get_random_string(32);
        $session = $request->getSession();
        $session->set("steam_state", $state);
        $session->set("steam_intent", $intent);
        $session->set("steam_link_uid", $userId);

        $returnTo = $url->to("/api/steam/callback", ["state" => $state]);
        $realm = rtrim($url->getShopUrl(), "/") . "/";

        return new RedirectResponse($steamOpenId->buildAuthUrl($returnTo, $realm));
    }

    private function loginOrRegister(
        Request $request,
        Auth $auth,
        SteamProfile $steamProfile,
        UserRepository $userRepository,
        UrlGenerator $url,
        DatabaseLogger $logger,
        string $steamId64
    ): Response {
        $user = $userRepository->findBySteamId($steamId64);

        if ($user) {
            // A Steam ID that was only typed in (not proven) must never log in,
            // otherwise anyone could pre-claim a public Steam ID and hijack logins.
            if (!$user->isSteamVerified()) {
                return $this->fail($request, $url, "steam_account_exists", "/page/login");
            }

            $auth->loginUser($request, $user);
            return new RedirectResponse($url->to("/"));
        }

        [$forename, $surname] = $this->splitRealName($steamProfile->getRealName($steamId64));

        $account = $userRepository->createSteamUser(
            $this->generateUsername(
                $steamProfile->getPersonaName($steamId64),
                $userRepository,
                $steamId64
            ),
            $steamId64,
            steam_placeholder_email(),
            get_ip($request),
            $forename,
            $surname
        );

        // A concurrent request may have created an unverified claim for this
        // Steam ID first; never log into an unverified account.
        if (!$account->isSteamVerified()) {
            return $this->fail($request, $url, "steam_account_exists", "/page/login");
        }

        $auth->loginUser($request, $account);
        $logger->log(
            "log_steam_account_created",
            $account->getId(),
            $account->getUsername(),
            $account->getRegIp()
        );

        return new RedirectResponse($url->to("/page/profile"));
    }

    private function linkAccount(
        Request $request,
        Auth $auth,
        UserRepository $userRepository,
        UrlGenerator $url,
        DatabaseLogger $logger,
        string $steamId64,
        ?int $linkUserId
    ): Response {
        if (!$auth->check()) {
            return $this->fail($request, $url, "steam_auth_error", "/page/login");
        }

        $user = $auth->user();

        if ($linkUserId && $user->getId() !== $linkUserId) {
            return $this->fail($request, $url, "steam_auth_error", "/page/profile");
        }

        if ($user->isSteamVerified()) {
            // Already linked: re-linking the same account is a no-op, a different
            // one requires unlinking first (verified link is otherwise immutable).
            if (to_canonical_steam_id($user->getSteamId()) === $steamId64) {
                return new RedirectResponse($url->to("/page/profile"));
            }

            return $this->fail($request, $url, "steam_already_linked", "/page/profile");
        }

        $existing = $userRepository->findBySteamId($steamId64);
        if ($existing && $existing->getId() !== $user->getId()) {
            if ($existing->isSteamVerified()) {
                return $this->fail($request, $url, "steam_id_occupied", "/page/profile");
            }

            // A proven (OpenID) owner takes the Steam ID over from a manually
            // typed, unverified claim.
            $userRepository->clearSteamId($existing->getId());
            $logger->log("log_steam_overridden", $existing->getId(), $steamId64, $user->getId());
        }

        $userRepository->linkSteamId($user->getId(), $steamId64);
        $user->setSteamId(to_legacy_steam_id($steamId64));
        $user->setSteamVerified(true);
        $logger->logWithUser($user, "log_steam_linked", $user->getUsername());

        return new RedirectResponse($url->to("/page/profile"));
    }

    private function generateUsername(
        ?string $persona,
        UserRepository $userRepository,
        string $steamId64
    ): string {
        $base = $persona ? SteamUsername::sanitize($persona) : "";

        if (strlen($base) < 2) {
            $base = "steam_{$steamId64}";
        }

        // Keep room for the "_N" suffix inside the 64-char username column.
        $base = mb_substr($base, 0, 55);

        $username = $base;
        $i = 1;
        while ($userRepository->findByUsername($username)) {
            $username = mb_substr($base . "_" . $i, 0, 64);
            $i++;
        }

        return $username;
    }

    /**
     * Splits a public real name into forename/surname (column limits: 32/64).
     *
     * @return array{0: string, 1: string}
     */
    private function splitRealName(?string $realName): array
    {
        $realName = trim((string) $realName);

        if (!strlen($realName)) {
            return ["", ""];
        }

        $parts = preg_split("/\s+/", $realName, 2) ?: [];

        return [mb_substr($parts[0] ?? "", 0, 32), mb_substr($parts[1] ?? "", 0, 64)];
    }

    private function fail(
        Request $request,
        UrlGenerator $url,
        string $errorKey,
        string $path
    ): Response {
        $request->getSession()->set("steam_error", $errorKey);

        return new RedirectResponse($url->to($path));
    }
}
