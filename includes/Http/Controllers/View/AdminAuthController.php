<?php
namespace App\Http\Controllers\View;

use App\Managers\UserManager;
use App\Managers\WebsiteHeader;
use App\Routing\UrlGenerator;
use App\Services\IntendedUrlService;
use App\Support\Template;
use App\System\Auth;
use App\Translation\TranslationManager;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminAuthController
{
    public function get(
        Request $request,
        Template $template,
        WebsiteHeader $websiteHeader,
        TranslationManager $translationManager,
        UrlGenerator $url
    ) {
        $session = $request->getSession();
        $lang = $translationManager->user();

        $warning = "";
        if ($session->has("info")) {
            if ($session->get("info") == "wrong_data") {
                $text = $lang->t("wrong_login_data");
                $warning = $template->render("admin/login_warning", compact("text"));
            }
            $session->remove("info");
        }

        $header = $template->render("admin/header", [
            "currentPageId" => "login",
            "pageTitle" => "Login",
            "scripts" => $websiteHeader->getScripts(),
            "styles" => $websiteHeader->getStyles(),
        ]);

        $action = $url->to("/admin/login", $request->query->all());

        return new Response(
            $template->render("admin/login", compact("header", "warning", "action"))
        );
    }

    public function post(
        Request $request,
        Auth $auth,
        UrlGenerator $url,
        UserManager $userManager,
        IntendedUrlService $intendedUrlService
    ) {
        if ($request->request->get("action") === "logout") {
            $auth->logout($request);
            return new RedirectResponse($url->to("/admin/login"));
        }

        // Let's try to login to ACP
        if ($request->request->has("username") && $request->request->has("password")) {
            $user = $userManager->getByLogin(
                $request->request->get("username"),
                $request->request->get("password")
            );

            $auth->loginAdmin($request, $user);

            if ($user && $intendedUrlService->exists($request)) {
                return $intendedUrlService->redirect($request);
            }
        }

        return new RedirectResponse($url->to("/admin"));
    }
}
