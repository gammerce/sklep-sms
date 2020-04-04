<?php
namespace App\Http\Controllers\View;

use App\Routing\UrlGenerator;
use App\Support\Template;
use App\System\Heart;
use App\Translation\TranslationManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminLoginController
{
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
            'pageTitle'     => $heart->pageTitle,
            'scripts'       => $heart->getScripts(),
            'styles'        => $heart->getStyles(),
        ]);

        $action = $url->to("/admin", $request->query->all());

        return new Response(
            $template->render("admin/login", compact('header', 'warning', 'action'))
        );
    }
}