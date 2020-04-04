<?php
namespace App\Http\Middlewares;

use App\System\Application;
use App\System\Auth;
use App\View\CurrentPage;
use Closure;
use Symfony\Component\HttpFoundation\Request;

class ManageAdminAuthentication implements MiddlewareContract
{
    /** @var Application */
    private $app;

    /** @var Auth */
    private $auth;

    public function __construct(Application $app, Auth $auth)
    {
        $this->app = $app;
        $this->auth = $auth;
    }

    public function handle(Request $request, $args, Closure $next)
    {
        $session = $request->getSession();

        // Logowanie się do panelu admina
        if ($request->request->has('username') && $request->request->has('password')) {
            $this->auth->loginAdminUsingCredentials(
                $request->request->get('username'),
                $request->request->get('password')
            );
        } elseif (
            $request->request->has('action') &&
            $request->request->get('action') == "logout"
        ) {
            $this->auth->logoutAdmin();
        }

        // Pozyskujemy dane gracza, jeżeli jeszcze ich nie ma
        if (!$this->auth->check() && $session->has("uid")) {
            $this->auth->loginUserUsingId($session->get("uid"));
        }

        // Jeżeli próbujemy wejść do PA i nie jesteśmy zalogowani, to zmień stronę
        if (!$this->auth->check() || !get_privileges("acp")) {
            /** @var CurrentPage $currentPage */
            $currentPage = $this->app->make(CurrentPage::class);
            $currentPage->setPid("login");
            // TODO Instead of changing pid, redirect to /login page

            // Jeżeli jest zalogowany, ale w międzyczasie odebrano mu dostęp do PA
            if ($this->auth->check()) {
                $session->set("info", "no_privileges");
            }
        }

        return $next($request);
    }
}
