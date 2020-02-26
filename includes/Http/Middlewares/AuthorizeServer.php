<?php
namespace App\Http\Middlewares;

use App\Repositories\ServerRepository;
use App\Repositories\UserRepository;
use App\System\Auth;
use App\System\ServerAuth;
use App\System\Settings;
use Closure;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthorizeServer implements MiddlewareContract
{
    /** @var Settings */
    private $settings;

    /** @var UserRepository */
    private $userRepository;

    /** @var ServerRepository */
    private $serverRepository;

    /** @var Auth */
    private $auth;

    /** @var ServerAuth */
    private $serverAuth;

    public function __construct(
        Settings $settings,
        UserRepository $userRepository,
        ServerRepository $serverRepository,
        Auth $auth,
        ServerAuth $serverAuth
    ) {
        $this->settings = $settings;
        $this->userRepository = $userRepository;
        $this->serverRepository = $serverRepository;
        $this->auth = $auth;
        $this->serverAuth = $serverAuth;
    }

    public function handle(Request $request, $args, Closure $next)
    {
        $key = $request->query->get("key");
        $token = $request->query->get("token");
        $steamId = $request->headers->get("Authorization");

        if ($token) {
            $server = $this->serverRepository->findByToken($token);

            if (!$server) {
                return new Response("Server unauthorized", 400);
            }

            $this->serverAuth->setServer($server);
        }
        // TODO Remove authorization by key
        elseif ($key !== md5($this->settings->getSecret())) {
            return new Response("Server unauthorized", 400);
        }

        $user = $this->userRepository->findBySteamId($steamId);
        if ($user) {
            $this->auth->setUser($user);
        }

        return $next($request);
    }
}
