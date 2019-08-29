<?php
namespace App\Middlewares;

use App\Application;
use App\Auth;
use App\CurrentPage;
use Symfony\Component\HttpFoundation\Request;

class ManageAdminAuthentication implements MiddlewareContract
{
    public function handle(Request $request, Application $app, $args = null)
    {
        /** @var Auth $auth */
        $auth = $app->make(Auth::class);
        $session = $request->getSession();

        // Logowanie się do panelu admina
        if (isset($_POST['username']) && isset($_POST['password'])) {
            $auth->loginAdminUsingCredentials($_POST['username'], $_POST['password']);
        } elseif (isset($_POST['action']) && $_POST['action'] == "logout") {
            $auth->logoutAdmin();
        }

        // Pozyskujemy dane gracza, jeżeli jeszcze ich nie ma
        if (!$auth->check() && $session->has("uid")) {
            $auth->loginUserUsingId($session->get("uid"));
        }

        // Jeżeli próbujemy wejść do PA i nie jesteśmy zalogowani, to zmień stronę
        if (!$auth->check() || !get_privilages("acp")) {
            /** @var CurrentPage $currentPage */
            $currentPage = $app->make(CurrentPage::class);
            $currentPage->setPid("login");

            // Jeżeli jest zalogowany, ale w międzyczasie odebrano mu dostęp do PA
            if ($auth->check()) {
                $session->set("info", "no_privilages");
            }
        }

        return null;
    }
}
