<?php
namespace App\View\Pages\Shop;

use App\Exceptions\EntityNotFoundException;
use App\Models\Server;
use App\Models\Service;
use App\Repositories\ServerRepository;
use App\Routing\UrlGenerator;
use App\Service\ServiceListService;
use App\Theme\Template;
use App\System\Auth;
use App\Translation\TranslationManager;
use App\View\Pages\Page;
use Symfony\Component\HttpFoundation\Request;

class PageServer extends Page
{
    const PAGE_ID = "server";

    private Auth $auth;
    private UrlGenerator $url;
    private ServiceListService $serviceListService;
    private ServerRepository $serverRepository;

    public function __construct(
        Template $template,
        TranslationManager $translationManager,
        Auth $auth,
        UrlGenerator $url,
        ServiceListService $serviceListService,
        ServerRepository $serverRepository
    ) {
        parent::__construct($template, $translationManager);
        $this->auth = $auth;
        $this->url = $url;
        $this->serviceListService = $serviceListService;
        $this->serverRepository = $serverRepository;
    }

    public function getTitle(Request $request = null): string
    {
        return $this->lang->t("server");
    }

    public function getContent(Request $request)
    {
        $server = $this->getServer($request);
        $cards = collect(
            $this->serviceListService->getWebSupportedForUserAndServer($this->auth->user(), $server)
        )
            ->map(
                fn(Service $service) => $this->template->render(
                    "shop/components/services/service_card",
                    [
                        "link" => $this->url->to("/page/purchase", [
                            "service" => $service->getId(),
                            "server" => $server->getId(),
                        ]),
                        "description" => $service->getDescriptionI18n(),
                        "name" => $service->getNameI18n(),
                    ]
                )
            )
            ->join();

        return $this->template->render("shop/pages/server", [
            "cards" => $cards,
            "name" => $server->getName(),
            "address" => $server->getAddress(),
        ]);
    }

    private function getServer(Request $request): Server
    {
        $serverId = $request->query->get("server");
        $server = $this->serverRepository->get($serverId);
        if ($server) {
            return $server;
        }

        throw new EntityNotFoundException();
    }
}
