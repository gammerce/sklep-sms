<?php
namespace App\Middlewares;

use App\Application;
use App\Auth;
use Symfony\Component\HttpFoundation\Request;

class ManageAuthentication implements MiddlewareContract
{
    public function handle(Request $request, Application $app)
    {
        /** @var Auth $auth */
        $auth = $app->make(Auth::class);

        // Pozyskujemy dane uzytkownika, jeżeli jeszcze ich nie ma
        if (!$auth->check() && isset($_SESSION['uid'])) {
            $auth->loginUserUsingId($_SESSION['uid']);
        }

        return null;
    }
}
