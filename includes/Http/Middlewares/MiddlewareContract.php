<?php
namespace App\Http\Middlewares;

use Closure;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

interface MiddlewareContract
{
    /**
     * @param Request $request
     * @param $args
     * @param Closure $next
     * @return Response|null
     */
    public function handle(Request $request, $args, Closure $next);
}
