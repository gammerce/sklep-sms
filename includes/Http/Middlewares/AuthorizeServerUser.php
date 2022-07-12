<?php
namespace App\Http\Middlewares;

use App\Repositories\UserRepository;
use App\System\Auth;
use Closure;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthorizeServerUser implements MiddlewareContract
{
    private UserRepository $userRepository;
    private Auth $auth;

    public function __construct(UserRepository $userRepository, Auth $auth)
    {
        $this->userRepository = $userRepository;
        $this->auth = $auth;
    }

    public function handle(Request $request, $args, Closure $next): Response
    {
        $this->authorizeUser($request);
        return $next($request);
    }

    private function authorizeUser(Request $request): void
    {
        $steamId = $this->getAuthorizationValue($request);
        $ip = $request->get("ip");

        if (!$steamId) {
            return;
        }

        $user = $this->userRepository->findBySteamId($steamId);
        if (!$user) {
            return;
        }

        $this->auth->setUser($user);

        if ($ip) {
            $user->setLastIp($ip);
            $this->userRepository->touch($user);
        }
    }

    private function getAuthorizationValue(Request $request): ?string
    {
        $authorization = $request->headers->get("Authorization");
        if (!$authorization) {
            return null;
        }

        if (0 === stripos($authorization, "bearer ")) {
            return substr($authorization, 7);
        }

        return $authorization;
    }
}
