<?php
namespace App\Http\Middlewares;

use App\System\Auth;
use Closure;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthorizeUser implements MiddlewareContract
{
    private Auth $auth;

    public function __construct(Auth $auth)
    {
        $this->auth = $auth;
    }

    public function handle(Request $request, $args, Closure $next): Response
    {
        $session = $request->getSession();

        if (!$this->auth->check() && $session->has("uid")) {
            $this->auth->setUserById($session->get("uid"));
        }

        return $next($request);
    }
}
