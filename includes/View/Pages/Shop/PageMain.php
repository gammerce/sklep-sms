<?php
namespace App\View\Pages\Shop;

use App\Managers\ServerManager;
use App\Managers\ServiceManager;
use App\Models\Server;
use App\Models\Service;
use App\Routing\UrlGenerator;
use App\Server\ServerListService;
use App\Service\ServiceListService;
use App\Service\UserServiceAccessService;
use App\System\Auth;
use App\Theme\Template;
use App\Translation\TranslationManager;
use App\View\Pages\Page;
use Symfony\Component\HttpFoundation\Request;

class PageMain extends Page
{
    const PAGE_ID = "home";
    const SERVICE_LIMIT = 5;
    const SERVER_LIMIT = 5;

    private Auth $auth;
    private ServerListService $serverListService;
    private ServiceListService $serviceListService;
    private UserServiceAccessService $userServiceAccessService;
    private UrlGenerator $url;
    private ServerManager $serverManager;
    private ServiceManager $serviceManager;

    public function __construct(
        Template $template,
        TranslationManager $translationManager,
        Auth $auth,
        ServerManager $serverManager,
        ServerListService $serverListService,
        ServiceListService $serviceListService,
        ServiceManager $serviceManager,
        UserServiceAccessService $userServiceAccessService,
        UrlGenerator $url
    ) {
        parent::__construct($template, $translationManager);
        $this->auth = $auth;
        $this->serverListService = $serverListService;
        $this->serviceListService = $serviceListService;
        $this->userServiceAccessService = $userServiceAccessService;
        $this->url = $url;
        $this->serviceManager = $serviceManager;
        $this->serverManager = $serverManager;
    }

    public function getTitle(Request $request = null): string
    {
        return $this->lang->t("main_page");
    }

    public function getContent(Request $request)
    {
        $servicesSection = $this->getServicesSection();
        $serversSection = $this->getServersSection();

        return $this->template->render("shop/pages/home", [
            "servicesSection" => $servicesSection,
            "serversSection" => $serversSection,
            "signUpSectionClass" => $this->auth->check() ? "is-hidden" : "",
        ]);
    }

    private function getServicesSection(): string
    {
        $services = collect($this->serviceListService->getWebSupportedForUser($this->auth->user()))
            ->limit($this::SERVICE_LIMIT)
            ->map(
                fn(Service $service) => $this->template->render(
                    "shop/components/home/entity_tile",
                    [
                        "link" => $this->url->to("/page/purchase", [
                            "service" => $service->getId(),
                        ]),
                        "name" => $service->getNameI18n(),
                    ]
                )
            )
            ->join();

        if (!$services) {
            return "";
        }

        return $this->template->render("shop/components/home/entity_section", [
            "entities" => $services,
            "icon" => "fa-shopping-cart",
            "moreUrl" => $this->url->to("/page/services"),
            "title" => $this->lang->t("available_services"),
        ]);
    }

    private function getServersSection(): string
    {
        $servers = collect($this->serverListService->listActive())
            ->limit($this::SERVER_LIMIT)
            ->map(
                fn(Server $server) => $this->template->render("shop/components/home/entity_tile", [
                    "link" => $this->url->to("/page/server", [
                        "id" => $server->getId(),
                    ]),
                    "name" => $server->getName(),
                ])
            )
            ->join();

        if (!$servers) {
            return "";
        }

        return $this->template->render("shop/components/home/entity_section", [
            "entities" => $servers,
            "icon" => "fa-server",
            "moreUrl" => $this->url->to("/page/servers"),
            "title" => $this->lang->t("available_servers"),
        ]);
    }
}
