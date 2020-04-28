<?php
namespace App\View\Pages\Admin;

use App\Models\Server;
use App\Requesting\Requester;
use App\Support\Template;
use App\Support\Version;
use App\System\Heart;
use App\Translation\TranslationManager;
use Symfony\Component\HttpFoundation\Request;

class PageAdminUpdateServers extends PageAdmin
{
    const PAGE_ID = "update_servers";

    /** @var Requester */
    private $requester;

    /** @var Version */
    private $version;

    /** @var Heart */
    private $heart;

    public function __construct(
        Template $template,
        TranslationManager $translationManager,
        Requester $requester,
        Version $version,
        Heart $heart
    ) {
        parent::__construct($template, $translationManager);

        $this->requester = $requester;
        $this->version = $version;
        $this->heart = $heart;
    }

    public function getPrivilege()
    {
        return "update";
    }

    public function getTitle(Request $request)
    {
        return $this->lang->t("update_servers");
    }

    public function getContent(Request $request)
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
            "title" => $this->getTitle($request),
        ]);

        return $this->template->render(
            "admin/update_server",
            compact("pageTitle", "versionBricks")
        );
    }
}
