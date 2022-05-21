<?php
namespace App\Http\Middlewares;

use Closure;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class JsonBody implements MiddlewareContract
{
    public function handle(Request $request, $args, Closure $next): Response
    {
        if (str_starts_with($request->headers->get("Content-Type", ""), "application/json")) {
            $data = json_decode($request->getContent(), true);
            $request->request->replace(is_array($data) ? $data : []);
        }

        return $next($request);
    }
}
