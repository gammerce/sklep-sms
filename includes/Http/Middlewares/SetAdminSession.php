<?php
namespace App\Http\Middlewares;

use App\System\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;

class SetAdminSession implements MiddlewareContract
{
    public function handle(Request $request, Application $app, $args = null)
    {
        /** @var Session $session */
        $session = $app->make(Session::class);
        $session->setName("admin");
        $session->start();

        $app->setAdminSession();

        return null;
    }
}
