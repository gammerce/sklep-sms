<?php
namespace App\Http\Controllers\View;

use App\Routing\UrlGenerator;
use App\Support\Template;
use App\System\Auth;
use App\System\Heart;
use App\Translation\TranslationManager;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminAuthController
{
    const URL_INTENDED_KEY = "url.intended";

    public function get(
        Request $request,
        Heart $heart,
        Template $template,
        TranslationManager $translationManager,
        UrlGenerator $url
    ) {
        $session = $request->getSession();
        $lang = $translationManager->user();

        $heart->pageTitle = "Login";

        $warning = "";
        if ($session->has("info")) {
            if ($session->get("info") == "wrong_data") {
                $text = $lang->t('wrong_login_data');
                $warning = $template->render("admin/login_warning", compact('text'));
            }
            $session->remove("info");
        }

        $header = $template->render("admin/header", [
            'currentPageId' => "login",
            'pageTitle' => $heart->pageTitle,
            'scripts' => $heart->getScripts(),
            'styles' => $heart->getStyles(),
        ]);

        $action = $url->to("/admin/login", $request->query->all());

        return new Response(
            $template->render("admin/login", compact('header', 'warning', 'action'))
        );
    }

    public function post(Request $request, Auth $auth, UrlGenerator $url)
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
