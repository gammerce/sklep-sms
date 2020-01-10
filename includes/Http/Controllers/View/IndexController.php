<?php
namespace App\Http\Controllers\View;

use App\Routing\UrlGenerator;
use App\View\CurrentPage;
use App\System\Heart;
use App\System\License;
use App\System\Template;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class IndexController
{
    public function action(
        $pageId = null,
        Request $request,
        Heart $heart,
        License $license,
        CurrentPage $currentPage,
        Template $template
    ) {
        $currentPage->setPid($pageId);

        if (!$heart->pageExists($currentPage->getPid())) {
            $currentPage->setPid('home');
        }

        // Pobranie miejsca logowania
        $loggedInfo = get_content("logged_info", $request);

        // Pobranie portfela
        $wallet = get_content("wallet", $request);

        // Pobranie zawartości
        $content = get_content("content", $request);

        // Pobranie przycisków usług
        $servicesButtons = get_content("services_buttons", $request);

        // Pobranie przycisków użytkownika
        $userButtons = get_content("user_buttons", $request);

        // Pobranie headera
        $header = $template->render("header", compact('currentPage', 'heart', 'license'));

        // Pobranie ostatecznego szablonu
        $output = $template->render(
            "index",
            compact(
                "header",
                "heart",
                "loggedInfo",
                "wallet",
                "servicesButtons",
                "content",
                "userButtons"
            )
        );

        return new Response($output);
    }

    /**
     * @deprecated
     */
    public function oldGet(Request $request, UrlGenerator $url)
    {
        $path = "/";

        $query = $request->query->all();

        if (array_key_exists("pid", $query)) {
            $path .= "/page/{$query["pid"]}";
            unset($query["pid"]);
        }

        return new RedirectResponse($url->to($path, $query), 301);
    }
}
