<?php
namespace App\Http\Middlewares;

use App\System\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

interface MiddlewareContract
{
    /**
     * @param Request $request
     * @param Application $app
     * @param $args
     * @return Response|null
     */
    public function handle(Request $request, Application $app, $args);
}
