<?php
namespace App\Http\Middlewares;

use Closure;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

interface MiddlewareContract
{
    public function handle(Request $request, $args, Closure $next): Response;
}
