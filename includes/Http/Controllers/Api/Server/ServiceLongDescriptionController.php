<?php
namespace App\Http\Controllers\Api\Server;

use App\Html\UnescapedSimpleText;
use App\Http\Responses\HtmlResponse;
use App\Routes\UrlGenerator;
use App\System\CurrentPage;
use App\System\Heart;
use App\System\License;
use App\System\Template;
use App\Translation\TranslationManager;
use Symfony\Component\HttpFoundation\Request;

class ServiceLongDescriptionController
{
    public function get(
        $serviceId,
        Request $request,
        Template $template,
        Heart $heart,
        CurrentPage $currentPage,
        TranslationManager $translationManager,
        UrlGenerator $url,
        License $license
    ) {
        $lang = $translationManager->user();

        if ($request->query->get("popup")) {
            $link = $url->to("/api/server/services/{$serviceId}/long_description");
            $safeLink = str_replace('"', '\"', $link);
            $output = create_dom_element(
                "script",
                new UnescapedSimpleText(
                    'window.open("' . $safeLink . '", "", "height=720,width=1280");'
                ),
                [
                    'type' => "text/javascript",
                ]
            );

            return new HtmlResponse($output);
        }

        $body = "";
        $heart->pageTitle = $lang->t('description') . ": ";

        $serviceModule = $heart->getServiceModule($serviceId);
        if ($serviceModule) {
            $body = $serviceModule->descriptionLongGet();
            $heart->pageTitle .= $serviceModule->service->getName();
        }

        $heart->styleAdd($url->versioned("build/css/static/extra_stuff/long_desc.css"));
        $header = $template->render("header", compact('currentPage', 'heart', 'license'));

        $output = create_dom_element("html", [
            create_dom_element("head", new UnescapedSimpleText($header)),
            create_dom_element("body", new UnescapedSimpleText($body)),
        ]);

        return new HtmlResponse($output);
    }

    /**
     * @deprecated
     */
    public function oldGet(
        Request $request,
        Template $template,
        Heart $heart,
        CurrentPage $currentPage,
        TranslationManager $translationManager,
        UrlGenerator $url,
        License $license
    ) {
        return $this->get(
            $request->query->get("service"),
            $request,
            $template,
            $heart,
            $currentPage,
            $translationManager,
            $url,
            $license
        );
    }
}
