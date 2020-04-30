<?php
namespace App\Http\Middlewares;

use Closure;
use Symfony\Component\HttpFoundation\Request;

class StartAdminSession implements MiddlewareContract
{
    public function handle(Request $request, $args, Closure $next)
    {
        $session = $request->getSession();
        $session->setName("admin");
        $session->start();

        return $next($request);
    }
}
