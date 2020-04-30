<?php
namespace App\View\Pages\Shop;

use App\Models\Service;
use App\Routing\UrlGenerator;
use App\Services\ServiceListService;
use App\Support\Template;
use App\System\Auth;
use App\Translation\TranslationManager;
use App\View\Pages\Page;
use Symfony\Component\HttpFoundation\Request;

class PageServices extends Page
{
    const PAGE_ID = "services";

    /** @var Auth */
    private $auth;

    /** @var UrlGenerator */
    private $url;

    /** @var ServiceListService */
    private $serviceListService;

    public function __construct(
        Template $template,
        TranslationManager $translationManager,
        Auth $auth,
        UrlGenerator $url,
        ServiceListService $serviceListService
    ) {
        parent::__construct($template, $translationManager);
        $this->auth = $auth;
        $this->url = $url;
        $this->serviceListService = $serviceListService;
    }

    public function getTitle(Request $request)
    {
        return $this->lang->t("services");
    }

    public function getContent(Request $request)
    {
        $cards = collect($this->serviceListService->getWebSupportedForUser($this->auth->user()))
            ->map(function (Service $service) {
                return $this->template->render("shop/components/services/service_card", [
                    "link" => $this->url->to("/page/purchase", ["service" => $service->getId()]),
                    "description" => $service->getDescription(),
                    "name" => $service->getName(),
                ]);
            })
            ->join();

        return $this->template->render("shop/pages/services", compact("cards"));
    }
}
