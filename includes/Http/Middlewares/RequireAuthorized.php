<?php
namespace App\Http\Middlewares;

use App\Exceptions\UnauthorizedException;
use App\System\Auth;
use Closure;
use Symfony\Component\HttpFoundation\Request;

class RequireAuthorized implements MiddlewareContract
{
    /** @var Auth */
    private $auth;

    public function __construct(Auth $auth)
    {
        $this->auth = $auth;
    }

    public function handle(Request $request, $privilege, Closure $next)
    {
        if (
            !$this->auth->check() ||
            ($privilege && !get_privileges($privilege, $this->auth->user()))
        ) {
            throw new UnauthorizedException();
        }

        return $next($request);
    }
}
