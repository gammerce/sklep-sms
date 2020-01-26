<?php
namespace App\Http\Middlewares;

use App\Repositories\UserRepository;
use App\System\Application;
use App\System\Auth;
use Symfony\Component\HttpFoundation\Request;

class UpdateUserActivity implements MiddlewareContract
{
    public function handle(Request $request, Application $app, $args = null)
    {
        /** @var Auth $auth */
        $auth = $app->make(Auth::class);

        /** @var UserRepository $userRepository */
        $userRepository = $app->make(UserRepository::class);

        $user = $auth->user();
        $user->setLastIp(get_ip($request));

        if ($user->exists()) {
            $userRepository->touch($user);
        }

        return null;
    }
}
