<?php
namespace App\Middlewares;

use App\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;

class SetUserSession implements MiddlewareContract
{
    public function handle(Request $request, Application $app)
    {
        $session = new Session();
        $session->setName("user");
        $session->start();
        $request->setSession($session);

        return null;
    }
}
