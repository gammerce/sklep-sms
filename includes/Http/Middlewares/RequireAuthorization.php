<?php
namespace App\Http\Middlewares;

use App\Exceptions\UnauthorizedException;
use App\System\Application;
use App\System\Auth;
use Symfony\Component\HttpFoundation\Request;

class RequireAuthorization implements MiddlewareContract
{
    public function handle(Request $request, Application $app, $privilege = null)
    {
        /** @var Auth $auth */
        $auth = app()->make(Auth::class);

        if (!$auth->check() || ($privilege && !get_privileges($privilege, $auth->user()))) {
            throw new UnauthorizedException();
        }

        return null;
    }
}
