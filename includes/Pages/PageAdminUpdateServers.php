<?php
namespace App\Pages;

use App\Models\Server;
use App\Requesting\Requester;
use App\Version;

class PageAdminUpdateServers extends PageAdmin
{
    const PAGE_ID = 'update_servers';
    protected $privilege = 'update';

    /** @var Requester */
    private $requester;

    /** @var Version */
    private $version;

    public function __construct(Requester $requester, Version $version)
    {
        parent::__construct();

        $this->requester = $requester;
        $this->heart->pageTitle = $this->title = $this->lang->translate('update_servers');
        $this->version = $version;
    }

    protected function content(array $query, array $body)
    {
        $newestAmxxVersion = $this->version->getNewestAmxmodx();
        $newestSmVersion = $this->version->getNewestSourcemod();

        $versionBricks = "";
        foreach ($this->heart->getServers() as $server) {
            if ($server['type'] === Server::TYPE_AMXMODX) {
                $newestVersion = $newestAmxxVersion;
                $link = "https://github.com/gammerce/plugin-amxmodx/releases/tag/{$newestAmxxVersion}";
            } elseif ($server['type'] === Server::TYPE_SOURCEMOD) {
                $newestVersion = $newestSmVersion;
                $link = "https://github.com/gammerce/plugin-sourcemod/releases/tag/{$newestSmVersion}";
            } else {
                continue;
            }

            if ($server['version'] === $newestVersion) {
                continue;
            }

            $versionBricks .= $this->template->render("admin/update_version_block", [
                'name' => htmlspecialchars($server['name']),
                'currentVersion' => $server['version'],
                'newestVersion' => $newestVersion,
                'link' => $link,
            ]);
        }

        if (!strlen($versionBricks)) {
            $output = $this->template->render("admin/no_update");

            return $output;
        }

        return $this->template->render(
            "admin/update_server",
            compact('versionBricks') + ['title' => $this->title]
        );
    }
}
