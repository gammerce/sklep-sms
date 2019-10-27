<?php
namespace App\Middlewares;

use App\Application;
use App\Auth;
use Symfony\Component\HttpFoundation\Request;

class UpdateUserActivity implements MiddlewareContract
{
    public function handle(Request $request, Application $app, $args = null)
    {
        /** @var Auth $auth */
        $auth = $app->make(Auth::class);

        $user = $auth->user();
        $user->setLastIp(get_ip());
        $user->updateActivity();

        return null;
    }
}
