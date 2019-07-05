<?php
namespace App\Middlewares;

use App\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;

class SetAdminSession implements MiddlewareContract
{
    public function handle(Request $request, Application $app)
    {
        $session = new Session();
        $session->setName("admin");
        $session->start();
        $request->setSession($session);

        $app->setAdminSession();

        return null;
    }
}
