<?php
namespace App\Middlewares;

use App\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;

class SetUserSession implements MiddlewareContract
{
    public function handle(Request $request, Application $app)
    {
        /** @var Session $session */
        $session = $app->make(Session::class);
        $session->setName("user");
        $session->start();

        return null;
    }
}
