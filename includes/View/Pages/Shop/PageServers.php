<?php
namespace App\View\Pages\Shop;

use App\Models\Server;
use App\Routing\UrlGenerator;
use App\Server\ServerListService;
use App\Theme\Template;
use App\Translation\TranslationManager;
use App\View\Pages\Page;
use Symfony\Component\HttpFoundation\Request;

class PageServers extends Page
{
    const PAGE_ID = "servers";

    private UrlGenerator $url;
    private ServerListService $serverListService;

    public function __construct(
        Template $template,
        TranslationManager $translationManager,
        UrlGenerator $url,
        ServerListService $serverListService
    ) {
        parent::__construct($template, $translationManager);
        $this->url = $url;
        $this->serverListService = $serverListService;
    }

    public function getTitle(Request $request = null): string
    {
        return $this->lang->t("servers");
    }

    public function getContent(Request $request)
    {
        $cards = collect($this->serverListService->listActive())
            ->map(
                fn(Server $server) => $this->template->render(
                    "shop/components/entity/entity_card",
                    [
                        "link" => $this->url->to("/page/server", [
                            "id" => $server->getId(),
                        ]),
                        "name" => $server->getName(),
                        "description" => $this->template->render(
                            "shop/components/servers/description",
                            [
                                "address" => $server->getAddress(),
                                "platform" =>
                                    $server->getType() ?: strtolower($this->lang->t("none")),
                            ]
                        ),
                    ]
                )
            )
            ->join();

        return $this->template->render("shop/pages/servers", compact("cards"));
    }
}
