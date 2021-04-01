<?php
namespace App\Http\Middlewares;

use App\Repositories\UserRepository;
use App\System\Auth;
use Closure;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class UpdateUserActivity implements MiddlewareContract
{
    private Auth $auth;
    private UserRepository $userRepository;

    public function __construct(Auth $auth, UserRepository $userRepository)
    {
        $this->auth = $auth;
        $this->userRepository = $userRepository;
    }

    public function handle(Request $request, $args, Closure $next): Response
    {
        $user = $this->auth->user();
        $user->setLastIp(get_ip($request));

        if ($user->exists()) {
            $this->userRepository->touch($user);
        }

        return $next($request);
    }
}
