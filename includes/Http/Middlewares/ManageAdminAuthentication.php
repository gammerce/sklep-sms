<?php
namespace App\Http\Middlewares;

use App\Routing\UrlGenerator;
use App\System\Auth;
use Closure;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

class ManageAdminAuthentication implements MiddlewareContract
{
    const URL_INTENDED_KEY = "url.intended";

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

        if ($request->request->get('action') === "logout") {
            $this->auth->logoutAdmin();
            return new RedirectResponse($this->url->to("/admin/login"));
        }

        // Let's try to login to ACP
        if ($request->request->has('username') && $request->request->has('password')) {
            $user = $this->auth->loginAdminUsingCredentials(
                $request->request->get('username'),
                $request->request->get('password')
            );

            if ($user->exists() && $session->has(static::URL_INTENDED_KEY)) {
                $intendedUrl = $session->get(static::URL_INTENDED_KEY);
                $session->remove(static::URL_INTENDED_KEY);
                return new RedirectResponse($intendedUrl);
            }
        }

        if (!$this->auth->check() && $session->has("uid")) {
            $this->auth->loginUserUsingId($session->get("uid"));
        }

        if (!get_privileges("acp")) {
            $session->set(static::URL_INTENDED_KEY, $request->getRequestUri());
            return new RedirectResponse($this->url->to("/admin/login"));
        }

        return $next($request);
    }
}
