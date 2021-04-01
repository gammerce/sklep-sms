<?php
namespace App\Http\Middlewares;

use App\Exceptions\UnauthorizedException;
use App\System\Auth;
use Closure;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireAuthorized implements MiddlewareContract
{
    private Auth $auth;

    public function __construct(Auth $auth)
    {
        $this->auth = $auth;
    }

    public function handle(Request $request, $args, Closure $next): Response
    {
        if (!$this->auth->check() || ($args && $this->auth->user()->cannot($args))) {
            throw new UnauthorizedException();
        }

        return $next($request);
    }
}
