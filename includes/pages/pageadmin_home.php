<?php

use App\License;
use App\Version;

class PageAdminMain extends PageAdmin
{
    const PAGE_ID = "home";

    /** @var Version */
    private $version;

    /** @var License */
    protected $license;

    public function __construct(Version $version)
    {
        parent::__construct();

        $this->heart->page_title = $this->title = $this->lang->translate('main_page');
        $this->version = $version;
        $this->license = app()->make(License::class);
    }

    protected function content($get, $post)
    {
        $settings = $this->settings;
        $lang = $this->lang;
        $license = $this->license;

        //
        // Ogloszenia

        $notes = "";

        // Info o braku licki
        if (!$this->license->isValid()) {
            $this->add_note($this->lang->translate('license_error'), "negative", $notes);
        }

        $expireSeconds = strtotime($this->license->getExpires()) - time();
        if (!$this->license->isForever() && $expireSeconds >= 0 && $expireSeconds < 4 * 24 * 60 * 60) {
            $this->add_note($this->lang->sprintf($this->lang->translate('license_soon_expire'),
                secondsToTime(strtotime($this->license->getExpires()) - time())), "negative", $notes);
        }

        // Sprawdzanie wersji skryptu
        $newestVersion = $this->version->getNewestWeb();
        if (VERSION != $newestVersion) {
            $this->add_note(
                $this->lang->sprintf($this->lang->translate('update_available'), htmlspecialchars($newestVersion)),
                "positive",
                $notes
            );
        }

        // Sprawdzanie wersji serwerów
        $amount = 0;
        $newest_versions = json_decode(
            trim(curl_get_contents("https://sklep-sms.pl/version.php?action=get_newest&type=engines")), true
        );
        foreach ($this->heart->get_servers() as $server) {
            $engine = "engine_{$server['type']}";
            if (strlen($newest_versions[$engine]) && $server['version'] != $newest_versions[$engine]) {
                $amount += 1;
            }
        }

        if ($amount) {
            $this->add_note($this->lang->sprintf($this->lang->translate('update_available_servers'), $amount,
                $this->heart->get_servers_amount(), htmlspecialchars($newestVersion)), "positive", $notes);
        }

        //
        // Cegielki informacyjne

        $bricks = "";

        // Info o serwerach
        $bricks .= create_brick($this->lang->sprintf($this->lang->translate('amount_of_servers'),
            $this->heart->get_servers_amount()),
            "brick_pa_main");

        // Info o użytkownikach
        $bricks .= create_brick($this->lang->sprintf($this->lang->translate('amount_of_users'),
            $this->db->get_column("SELECT COUNT(*) FROM `" . TABLE_PREFIX . "users`", "COUNT(*)")), "brick_pa_main");

        // Info o kupionych usługach
        $amount = $this->db->get_column(
            "SELECT COUNT(*) " .
            "FROM ({$this->settings['transactions_query']}) AS t",
            "COUNT(*)"
        );
        $bricks .= create_brick($this->lang->sprintf($this->lang->translate('amount_of_bought_services'), $amount),
            "brick_pa_main");

        // Info o wysłanych smsach
        $amount = $this->db->get_column(
            "SELECT COUNT(*) AS `amount` " .
            "FROM ({$this->settings['transactions_query']}) as t " .
            "WHERE t.payment = 'sms' AND t.free='0'",
            "amount"
        );
        $bricks .= create_brick($this->lang->sprintf($this->lang->translate('amount_of_sent_smses'), $amount),
            "brick_pa_main");

        // Pobranie wyglądu strony
        return eval($this->template->render("admin/home"));
    }

    private function add_note($text, $class, &$notes)
    {
        $notes .= create_dom_element("div", $text, [
            'class' => "note " . $class,
        ]);
    }
}