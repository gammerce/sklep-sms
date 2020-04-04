<?php
namespace App\Http\Controllers\View;

use App\Routing\UrlGenerator;
use App\System\Auth;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

class AdminAuthController
{
    const URL_INTENDED_KEY = "url.intended";

    public function action(Request $request, Auth $auth, UrlGenerator $url)
    {
        $session = $request->getSession();

        if ($request->request->get('action') === "logout") {
            $auth->logoutAdmin();
            return new RedirectResponse($url->to("/admin/login"));
        }

        // Let's try to login to ACP
        if ($request->request->has('username') && $request->request->has('password')) {
            $user = $auth->loginAdminUsingCredentials(
                $request->request->get('username'),
                $request->request->get('password')
            );

            if ($user->exists() && $session->has(static::URL_INTENDED_KEY)) {
                $intendedUrl = $session->get(static::URL_INTENDED_KEY);
                $session->remove(static::URL_INTENDED_KEY);
                return new RedirectResponse($intendedUrl);
            }
        }

        return new RedirectResponse($url->to("/admin"));
    }
}