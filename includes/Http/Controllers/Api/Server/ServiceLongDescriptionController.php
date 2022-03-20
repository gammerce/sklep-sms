<?php
namespace App\Http\Controllers\Api\Server;

use App\Http\Responses\HtmlResponse;
use App\Managers\ServiceModuleManager;
use App\Managers\WebsiteHeader;
use App\Routing\UrlGenerator;
use App\Theme\Template;
use App\Translation\TranslationManager;
use App\View\Html\Script;
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
            $output = new Script("window.open(\"$safeLink\", \"\", \"height=720,width=1280\");");

            return new HtmlResponse($output);
        }

        $body = "";
        $pageTitle = $lang->t("description") . ": ";

        $serviceModule = $serviceModuleManager->get($serviceId);
        if ($serviceModule) {
            $body = $serviceModule->descriptionLongGet();
            $pageTitle .= $serviceModule->service->getNameI18n();
        }

        $customStyles = $template->render("shop/styles/general");
        $header = $template->render("shop/layout/header", [
            "currentPageId" => "service_long_description",
            "customStyles" => $customStyles,
            "langJsPath" => $url->versioned("lang.js", ["language" => $lang->getCurrentLanguage()]),
            "footer" => "",
            "pageTitle" => $pageTitle,
            "scripts" => $websiteHeader->getScripts(),
        ]);

        $output = $template->render(
            "shop/pages/service_long_description",
            compact("header", "body")
        );

        return new HtmlResponse($output);
    }
}
