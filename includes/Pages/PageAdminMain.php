<?php
namespace App\Pages;

use App\License;
use App\Models\Server;
use App\Requesting\Requester;
use App\Version;

class PageAdminMain extends PageAdmin
{
    const PAGE_ID = "home";
    const EXPIRE_THRESHOLD = 4 * 24 * 60 * 60;

    /** @var Version */
    protected $version;

    /** @var License */
    protected $license;

    /** @var Requester */
    protected $requester;

    public function __construct(Version $version, License $license, Requester $requester)
    {
        parent::__construct();

        $this->heart->pageTitle = $this->title = $this->lang->translate('main_page');
        $this->version = $version;
        $this->license = $license;
        $this->requester = $requester;
    }

    protected function content($get, $post)
    {
        //
        // Ogloszenia

        $notes = "";

        // Info o braku licki
        if (!$this->license->isValid()) {
            $settingsUrl = $this->url->to("/admin/settings");
            $this->add_note(
                $this->lang->sprintf($this->lang->translate('license_error'), $settingsUrl),
                "negative",
                $notes
            );
        }

        $expireSeconds = strtotime($this->license->getExpires()) - time();
        if (
            !$this->license->isForever() &&
            $expireSeconds >= 0 &&
            $expireSeconds < self::EXPIRE_THRESHOLD
        ) {
            $this->add_note(
                $this->lang->sprintf(
                    $this->lang->translate('license_soon_expire'),
                    secondsToTime(strtotime($this->license->getExpires()) - time())
                ),
                "negative",
                $notes
            );
        }

        $newestVersion = $this->version->getNewestWeb();
        $newestAmxxVersion = $this->version->getNewestAmxmodx();
        $newestSmVersion = $this->version->getNewestSourcemod();

        if ($this->app->version() !== $newestVersion) {
            $updateWebLink = $this->url->to("/admin/update_web");

            $this->add_note(
                $this->lang->sprintf(
                    $this->lang->translate('update_available'),
                    htmlspecialchars($newestVersion),
                    $updateWebLink
                ),
                "positive",
                $notes
            );
        }

        $serversCount = 0;
        foreach ($this->heart->getServers() as $server) {
            if (!$this->isServerNewest($server, $newestAmxxVersion, $newestSmVersion)) {
                $serversCount += 1;
            }
        }

        if ($serversCount) {
            $updateServersLink = $this->url->to("/admin/update_servers");

            $this->add_note(
                $this->lang->sprintf(
                    $this->lang->translate('update_available_servers'),
                    $serversCount,
                    $this->heart->getServersAmount(),
                    $updateServersLink
                ),
                "positive",
                $notes
            );
        }

        //
        // Cegielki informacyjne

        $bricks = "";

        // Info o serwerach
        $bricks .= create_brick(
            $this->lang->sprintf(
                $this->lang->translate('amount_of_servers'),
                $this->heart->getServersAmount()
            ),
            "brick_pa_main"
        );

        // Info o użytkownikach
        $bricks .= create_brick(
            $this->lang->sprintf(
                $this->lang->translate('amount_of_users'),
                $this->db->getColumn(
                    "SELECT COUNT(*) FROM `" . TABLE_PREFIX . "users`",
                    "COUNT(*)"
                )
            ),
            "brick_pa_main"
        );

        // Info o kupionych usługach
        $amount = $this->db->getColumn(
            "SELECT COUNT(*) " . "FROM ({$this->settings['transactions_query']}) AS t",
            "COUNT(*)"
        );
        $bricks .= create_brick(
            $this->lang->sprintf($this->lang->translate('amount_of_bought_services'), $amount),
            "brick_pa_main"
        );

        // Info o wysłanych smsach
        $amount = $this->db->getColumn(
            "SELECT COUNT(*) AS `amount` " .
                "FROM ({$this->settings['transactions_query']}) as t " .
                "WHERE t.payment = 'sms' AND t.free='0'",
            "amount"
        );
        $bricks .= create_brick(
            $this->lang->sprintf($this->lang->translate('amount_of_sent_smses'), $amount),
            "brick_pa_main"
        );

        return $this->template->render("admin/home", compact('notes', 'bricks'));
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

    private function add_note($text, $class, &$notes)
    {
        $notes .= create_dom_element("div", $text, [
            'class' => "note " . $class,
        ]);
    }
}
