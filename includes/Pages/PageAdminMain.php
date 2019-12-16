<?php
namespace App\Pages;

use App\Html\UnescapedSimpleText;
use App\Models\Server;
use App\Requesting\Requester;
use App\System\License;
use App\System\Version;

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

    protected function content(array $query, array $body)
    {
        //
        // Ogloszenia

        $notes = [];

        // Info o braku licki
        if (!$this->license->isValid()) {
            $settingsUrl = $this->url->to("/admin/settings");
            $notes[] = $this->addNote(
                $this->lang->sprintf($this->lang->translate('license_error'), $settingsUrl),
                "is-danger"
            );
        }

        $expireSeconds = strtotime($this->license->getExpires()) - time();
        if (
            !$this->license->isForever() &&
            $expireSeconds >= 0 &&
            $expireSeconds < self::EXPIRE_THRESHOLD
        ) {
            $notes[] = $this->addNote(
                $this->lang->sprintf(
                    $this->lang->translate('license_soon_expire'),
                    secondsToTime(strtotime($this->license->getExpires()) - time())
                ),
                "is-danger"
            );
        }

        $newestVersion = $this->version->getNewestWeb();
        $newestAmxxVersion = $this->version->getNewestAmxmodx();
        $newestSmVersion = $this->version->getNewestSourcemod();

        if ($this->app->version() !== $newestVersion) {
            $updateWebLink = $this->url->to("/admin/update_web");

            $notes[] = $this->addNote(
                $this->lang->sprintf(
                    $this->lang->translate('update_available'),
                    htmlspecialchars($newestVersion),
                    $updateWebLink
                ),
                "is-success"
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

            $notes[] = $this->addNote(
                $this->lang->sprintf(
                    $this->lang->translate('update_available_servers'),
                    $serversCount,
                    $this->heart->getServersAmount(),
                    $updateServersLink
                ),
                "is-success"
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
                $this->db->getColumn("SELECT COUNT(*) FROM `" . TABLE_PREFIX . "users`", "COUNT(*)")
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

        $notes = implode("", $notes);
        return $this->template->render("admin/home", compact('notes', 'bricks'));
    }

    /**
     * @param Server $server
     * @param string $newestAmxxVersion
     * @param string $newestSmVersion
     * @return bool
     */
    private function isServerNewest(Server $server, $newestAmxxVersion, $newestSmVersion)
    {
        if (
            $server->getType() === Server::TYPE_AMXMODX &&
            $server->getVersion() !== $newestAmxxVersion
        ) {
            return false;
        }

        if (
            $server->getType() === Server::TYPE_SOURCEMOD &&
            $server->getVersion() !== $newestSmVersion
        ) {
            return false;
        }

        return true;
    }

    private function addNote($text, $class)
    {
        return create_dom_element("div", new UnescapedSimpleText($text), [
            'class' => "notification " . $class,
        ]);
    }
}
