<?php
namespace App\Http\Middlewares;

use App\Exceptions\UnauthorizedException;
use App\System\Auth;
use Closure;
use Symfony\Component\HttpFoundation\Request;

class RequireAuthorized implements MiddlewareContract
{
    private Auth $auth;

    public function __construct(Auth $auth)
    {
        $this->auth = $auth;
    }

    public function handle(Request $request, $permission, Closure $next)
    {
        if (!$this->auth->check() || ($permission && $this->auth->user()->cannot($permission))) {
            throw new UnauthorizedException();
        }

        return $next($request);
    }
}
