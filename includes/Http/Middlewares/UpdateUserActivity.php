<?php
namespace App\Http\Middlewares;

use App\Repositories\UserRepository;
use App\System\Auth;
use Closure;
use Symfony\Component\HttpFoundation\Request;

class UpdateUserActivity implements MiddlewareContract
{
    /** @var Auth */
    private $auth;

    /** @var UserRepository */
    private $userRepository;

    public function __construct(Auth $auth, UserRepository $userRepository)
    {
        $this->auth = $auth;
        $this->userRepository = $userRepository;
    }

    public function handle(Request $request, $args, Closure $next)
    {
        $user = $this->auth->user();
        $user->setLastIp(get_ip($request));

        if ($user->exists()) {
            $this->userRepository->touch($user);
        }

        return $next($request);
    }
}
