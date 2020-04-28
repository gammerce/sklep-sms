<?php
namespace App\Http\Middlewares;

use App\System\Auth;
use Closure;
use Symfony\Component\HttpFoundation\Request;

class AuthorizeUser implements MiddlewareContract
{
    /** @var Auth */
    private $auth;

    public function __construct(Auth $auth)
    {
        $this->auth = $auth;
    }

    public function handle(Request $request, $args, Closure $next)
    {
        $session = $request->getSession();

        if (!$this->auth->check() && $session->has("uid")) {
            $this->auth->loginUserUsingId($session->get("uid"));
        }

        return $next($request);
    }
}
