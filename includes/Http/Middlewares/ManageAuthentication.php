<?php
namespace App\Http\Middlewares;

use App\System\Application;
use App\System\Auth;
use Symfony\Component\HttpFoundation\Request;

class ManageAuthentication implements MiddlewareContract
{
    public function handle(Request $request, Application $app, $args = null)
    {
        /** @var Auth $auth */
        $auth = $app->make(Auth::class);
        $session = $request->getSession();

        // Pozyskujemy dane uzytkownika, jeżeli jeszcze ich nie ma
        if (!$auth->check() && $session->has('uid')) {
            $auth->loginUserUsingId($session->get('uid'));
        }

        return null;
    }
}
