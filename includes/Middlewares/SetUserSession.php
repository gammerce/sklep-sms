<?php
namespace App\Middlewares;

use App\Application;
use Symfony\Component\HttpFoundation\Request;

class SetUserSession implements MiddlewareContract
{
    public function handle(Request $request, Application $app)
    {
        session_name('user');
        session_start();

        return null;
    }
}
