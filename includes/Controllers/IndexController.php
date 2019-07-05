<?php
namespace App\Controllers;

use App\CurrentPage;
use App\Heart;
use App\License;
use App\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class IndexController
{
    public function action(
        $pageId,
        Request $request,
        Heart $heart,
        License $license,
        CurrentPage $currentPage,
        Template $template
    ) {
        $currentPage->setPid($pageId);
        return $this->oldAction($request, $heart, $license, $currentPage, $template);
    }

    public function oldAction(
        Request $request,
        Heart $heart,
        License $license,
        CurrentPage $currentPage,
        Template $template
    ) {
        if (!$heart->page_exists($currentPage->getPid())) {
            $currentPage->setPid('home');
        }

        // Pobranie miejsca logowania
        $logged_info = get_content("logged_info", $request);

        // Pobranie portfela
        $wallet = get_content("wallet", $request);

        // Pobranie zawartości
        $content = get_content("content", $request);

        // Pobranie przycisków usług
        $services_buttons = get_content("services_buttons", $request);

        // Pobranie przycisków użytkownika
        $user_buttons = get_content("user_buttons", $request);

        // Pobranie headera
        $header = $template->render("header", compact('heart', 'license'));

        // Pobranie ostatecznego szablonu
        $output = $template->render(
            "index",
            compact(
                "header",
                "heart",
                "logged_info",
                "wallet",
                "services_buttons",
                "content",
                "user_buttons"
            )
        );

        return new Response($output);
    }
}
