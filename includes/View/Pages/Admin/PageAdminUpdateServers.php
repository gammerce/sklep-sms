<?php
namespace App\View\Pages\Admin;

use App\Managers\ServerManager;
use App\Requesting\Requester;
use App\Server\Platform;
use App\Support\Template;
use App\Support\Version;
use App\Translation\TranslationManager;
use App\User\Permission;
use Symfony\Component\HttpFoundation\Request;

class PageAdminUpdateServers extends PageAdmin
{
    const PAGE_ID = "update_servers";

    /** @var Requester */
    private $requester;

    /** @var Version */
    private $version;

    /** @var ServerManager */
    private $serverManager;

    public function __construct(
        Template $template,
        TranslationManager $translationManager,
        Requester $requester,
        Version $version,
        ServerManager $serverManager
    ) {
        parent::__construct($template, $translationManager);

        $this->requester = $requester;
        $this->version = $version;
        $this->serverManager = $serverManager;
    }

    public function getPrivilege()
    {
        return Permission::UPDATE();
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
        foreach ($this->serverManager->getServers() as $server) {
            if (Platform::AMXMODX()->equals($server->getType())) {
                $newestVersion = $newestAmxxVersion;
                $link = "https://github.com/gammerce/plugin-amxmodx/releases/tag/{$newestAmxxVersion}";
            } elseif (Platform::SOURCEMOD()->equals($server->getType())) {
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
