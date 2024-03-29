<?php
namespace App\View\Blocks;

use App\Managers\ServerManager;
use App\Models\Server;
use App\Routing\UrlGenerator;
use App\Server\ServerListService;
use App\Theme\Template;
use Symfony\Component\HttpFoundation\Request;

class BlockServersButtons extends Block
{
    const BLOCK_ID = "servers_buttons";

    private Template $template;
    private UrlGenerator $url;
    private ServerListService $serverListService;

    public function __construct(
        Template $template,
        UrlGenerator $url,
        ServerListService $serverListService
    ) {
        $this->template = $template;
        $this->url = $url;
        $this->serverListService = $serverListService;
    }

    public function getContentClass(): string
    {
        return "servers-buttons";
    }

    public function getContent(Request $request, array $params): string
    {
        $servers = collect($this->serverListService->listActive())
            ->map(
                fn(Server $server) => $this->template->render(
                    "shop/components/navbar/navigation_item",
                    [
                        "text" => $server->getName(),
                        "link" => $this->url->to("/page/server", [
                            "id" => $server->getId(),
                        ]),
                    ]
                )
            )
            ->join();

        if (!$servers) {
            return "";
        }

        return $this->template->render("shop/layout/servers_buttons", compact("servers"));
    }
}
