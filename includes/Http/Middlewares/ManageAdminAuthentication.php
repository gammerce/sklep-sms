<?php
namespace App\Http\Middlewares;

use App\Routing\UrlGenerator;
use App\System\Auth;
use Closure;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

class ManageAdminAuthentication implements MiddlewareContract
{
    /** @var Auth */
    private $auth;

    /** @var UrlGenerator */
    private $url;

    public function __construct(Auth $auth, UrlGenerator $urlGenerator)
    {
        $this->auth = $auth;
        $this->url = $urlGenerator;
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
            // TODO Redirect after login
            $session->set("url.intended", $request->getRequestUri());
            return new RedirectResponse($this->url->to("/admin/login"));
        }

        return $next($request);
    }
}
