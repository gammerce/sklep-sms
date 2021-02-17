<?php
namespace App\View\Pages\Shop;

use App\Models\Service;
use App\Routing\UrlGenerator;
use App\Service\ServiceListService;
use App\Support\Template;
use App\System\Auth;
use App\Translation\TranslationManager;
use App\View\Pages\Page;
use Symfony\Component\HttpFoundation\Request;

class PageServices extends Page
{
    const PAGE_ID = "services";

    private Auth $auth;
    private UrlGenerator $url;
    private ServiceListService $serviceListService;

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

    public function getTitle(Request $request): string
    {
        return $this->lang->t("services");
    }

    public function getContent(Request $request)
    {
        $cards = collect($this->serviceListService->getWebSupportedForUser($this->auth->user()))
            ->map(
                fn(Service $service) => $this->template->render(
                    "shop/components/services/service_card",
                    [
                        "link" => $this->url->to("/page/purchase", [
                            "service" => $service->getId(),
                        ]),
                        "description" => $service->getDescriptionI18n(),
                        "name" => $service->getNameI18n(),
                    ]
                )
            )
            ->join();

        return $this->template->render("shop/pages/services", compact("cards"));
    }
}
