<?php


use App\Models\Server;
use App\Requesting\Requester;
use App\Version;

class PageAdminUpdateServers extends PageAdmin
{
    const PAGE_ID = 'update_servers';
    protected $privilage = 'update';

    /** @var Requester */
    private $requester;

    /** @var Version */
    private $version;

    public function __construct(Requester $requester, Version $version)
    {
        parent::__construct();

        $this->requester = $requester;
        $this->heart->page_title = $this->title = $this->lang->translate('update_servers');
        $this->version = $version;
    }

    protected function content($get, $post)
    {
        $newestAmxxVersion = $this->version->getNewestAmxmodx();
        $newestSmVersion = $this->version->getNewestSourcemod();

        $versionBricks = "";
        foreach ($this->heart->get_servers() as $server) {
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

            $versionBricks .= $this->template->render(
                "admin/update_version_block",
                [
                    'name' => htmlspecialchars($server['name']),
                    'currentVersion' => $server['version'],
                    'newestVersion' => $newestVersion,
                    'link' => $link,
                ]
            );
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

    /**
     * @param array $server
     * @param string $newestAmxxVersion
     * @param string $newestSmVersion
     * @return bool
     */
    private function isServerNewest($server, $newestAmxxVersion, $newestSmVersion)
    {
        if ($server['type'] === Server::TYPE_AMXMODX && $server['version'] !== $newestAmxxVersion) {
            return false;
        }

        if ($server['type'] === Server::TYPE_SOURCEMOD && $server['version'] !== $newestSmVersion) {
            return false;
        }

        return true;
    }
}