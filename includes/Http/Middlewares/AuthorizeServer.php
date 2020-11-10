<?php
namespace App\Http\Middlewares;

use App\Repositories\ServerRepository;
use App\Repositories\UserRepository;
use App\System\Auth;
use App\System\ServerAuth;
use Closure;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthorizeServer implements MiddlewareContract
{
    /** @var UserRepository */
    private $userRepository;

    /** @var ServerRepository */
    private $serverRepository;

    /** @var Auth */
    private $auth;

    /** @var ServerAuth */
    private $serverAuth;

    public function __construct(
        UserRepository $userRepository,
        ServerRepository $serverRepository,
        Auth $auth,
        ServerAuth $serverAuth
    ) {
        $this->userRepository = $userRepository;
        $this->serverRepository = $serverRepository;
        $this->auth = $auth;
        $this->serverAuth = $serverAuth;
    }

    public function handle(Request $request, $args, Closure $next)
    {
        $token = $request->query->get("token");
        $steamId = $request->headers->get("Authorization");
        $ip = $request->get("ip");

        $server = $this->serverRepository->findByToken($token);

        if (!$server) {
            return new Response("Server unauthorized", Response::HTTP_BAD_REQUEST);
        }

        $this->serverAuth->setServer($server);

        if ($steamId) {
            $user = $this->userRepository->findBySteamId($steamId);
            if ($user) {
                // TODO Write test that verifies that IP is updated in a DB
                $user->setLastIp($ip);
                $this->userRepository->touch($user);
                $this->auth->setUser($user);
            }
        }

        return $next($request);
    }
}
