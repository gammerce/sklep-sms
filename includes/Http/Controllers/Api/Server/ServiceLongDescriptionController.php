<?php
namespace App\Http\Controllers\Api\Server;

use App\Http\Responses\HtmlResponse;
use App\Managers\ServiceModuleManager;
use App\Managers\WebsiteHeader;
use App\Routing\UrlGenerator;
use App\Support\Template;
use App\Translation\TranslationManager;
use App\View\Html\RawText;
use Symfony\Component\HttpFoundation\Request;

class ServiceLongDescriptionController
{
    public function get(
        $serviceId,
        Request $request,
        Template $template,
        ServiceModuleManager $serviceModuleManager,
        WebsiteHeader $websiteHeader,
        TranslationManager $translationManager,
        UrlGenerator $url
    ) {
        $lang = $translationManager->user();

        if ($request->query->get("popup")) {
            $link = $url->to("/api/server/services/{$serviceId}/long_description");
            $safeLink = str_replace('"', '\"', $link);
            $output = create_dom_element(
                "script",
                new RawText("window.open(\"$safeLink\", \"\", \"height=720,width=1280\");"),
                [
                    "type" => "text/javascript",
                ]
            );

            return new HtmlResponse($output);
        }

        $body = "";
        $pageTitle = $lang->t("description") . ": ";

        $serviceModule = $serviceModuleManager->get($serviceId);
        if ($serviceModule) {
            $body = $serviceModule->descriptionLongGet();
            $pageTitle .= $serviceModule->service->getName();
        }

        $header = $template->render("shop/layout/header", [
            "currentPageId" => "service_long_description",
            "footer" => "",
            "pageTitle" => $pageTitle,
            "scripts" => $websiteHeader->getScripts(),
            "styles" => $websiteHeader->getStyles(),
        ]);

        $output = $template->render(
            "shop/pages/service_long_description",
            compact("header", "body")
        );

        return new HtmlResponse($output);
    }
}
