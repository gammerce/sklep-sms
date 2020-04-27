<?php
namespace App\View\Pages;

use App\Models\Server;
use App\Requesting\Requester;
use App\Support\Version;
use Symfony\Component\HttpFoundation\Request;

class PageAdminUpdateServers extends PageAdmin
{
    const PAGE_ID = "update_servers";
    protected $privilege = "update";

    /** @var Requester */
    private $requester;

    /** @var Version */
    private $version;

    public function __construct(Requester $requester, Version $version)
    {
        parent::__construct();

        $this->requester = $requester;
        $this->version = $version;
    }

    public function getTitle(Request $request)
    {
        return $this->lang->t("update_servers");
    }

    protected function content(array $query, array $body)
    {
        $newestAmxxVersion = $this->version->getNewestAmxmodx();
        $newestSmVersion = $this->version->getNewestSourcemod();

        $versionBricks = "";
        foreach ($this->heart->getServers() as $server) {
            if ($server->getType() === Server::TYPE_AMXMODX) {
                $newestVersion = $newestAmxxVersion;
                $link = "https://github.com/gammerce/plugin-amxmodx/releases/tag/{$newestAmxxVersion}";
            } elseif ($server->getType() === Server::TYPE_SOURCEMOD) {
                $newestVersion = $newestSmVersion;
                $link = "https://github.com/gammerce/plugin-sourcemod/releases/tag/{$newestSmVersion}";
            } else {
                continue;
            }

            if ($server->getVersion() === $newestVersion) {
                continue;
            }

            $versionBricks .= $this->template->render("admin/update_version_block", [
                "name" => $server->getName(),
                "currentVersion" => $server->getVersion(),
                "newestVersion" => $newestVersion,
                "link" => $link,
            ]);
        }

        if (!strlen($versionBricks)) {
            return $this->template->render("admin/no_update");
        }

        $pageTitle = $this->template->render("admin/page_title", [
            "buttons" => "",
            "title" => $this->title,
        ]);

        return $this->template->render(
            "admin/update_server",
            compact("pageTitle", "versionBricks")
        );
    }
}
