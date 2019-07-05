<?php
namespace App\Middlewares;

use App\Application;
use Symfony\Component\HttpFoundation\Request;

class SetAdminSession implements MiddlewareContract
{
    public function handle(Request $request, Application $app)
    {
        session_name('admin');
        session_start();

        $app->setAdminSession();

        return null;
    }
}
