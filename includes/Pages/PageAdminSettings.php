<?php
namespace App\Pages;

use Admin\Table\Option;
use Admin\Table\Select;
use App\TranslationManager;

class PageAdminSettings extends PageAdmin
{
    const PAGE_ID = 'settings';
    protected $privilege = 'manage_settings';

    public function __construct()
    {
        parent::__construct();

        $this->heart->pageTitle = $this->title = $this->lang->translate('settings');
    }

    protected function content($get, $post)
    {
        /** @var TranslationManager $translationManager */
        $translationManager = $this->app->make(TranslationManager::class);
        $lang = $this->lang;
        $langShop = $translationManager->shop();

        // Pobranie listy serwisów transakcyjnych
        $result = $this->db->query(
            "SELECT id, name, sms, transfer " . "FROM `" . TABLE_PREFIX . "transaction_services`"
        );
        $sms_services = $transfer_services = "";
        while ($row = $this->db->fetchArrayAssoc($result)) {
            if ($row['sms']) {
                $sms_services .= create_dom_element("option", $row['name'], [
                    'value' => $row['id'],
                    'selected' => $row['id'] == $this->settings['sms_service'] ? "selected" : "",
                ]);
            }
            if ($row['transfer']) {
                $transfer_services .= create_dom_element("option", $row['name'], [
                    'value' => $row['id'],
                    'selected' =>
                        $row['id'] == $this->settings['transfer_service'] ? "selected" : "",
                ]);
            }
        }

        $cronSelect = $this->createCronSelect();
        $userEditServiceSelect = $this->createUserEditServiceSelect();

        // Pobieranie listy dostępnych szablonów
        $dirlist = scandir($this->app->path('themes'));
        $themes_list = "";
        foreach ($dirlist as $dir_name) {
            if ($dir_name[0] != '.' && is_dir($this->app->path("themes/$dir_name"))) {
                $themes_list .= create_dom_element("option", $dir_name, [
                    'value' => $dir_name,
                    'selected' => $dir_name == $this->settings['theme'] ? "selected" : "",
                ]);
            }
        }

        // Pobieranie listy dostępnych języków
        $dirlist = scandir($this->app->path('includes/languages'));
        $languages_list = "";
        foreach ($dirlist as $dir_name) {
            if ($dir_name[0] != '.' && is_dir($this->app->path("includes/languages/{$dir_name}"))) {
                $languages_list .= create_dom_element(
                    "option",
                    $lang->translate('language_' . $dir_name),
                    [
                        'value' => $dir_name,
                        'selected' =>
                            $dir_name == $langShop->getCurrentLanguage() ? "selected" : "",
                    ]
                );
            }
        }

        // Pobranie wyglądu strony
        return $this->template->render(
            "admin/settings",
            compact(
                "userEditServiceSelect",
                "sms_services",
                "transfer_services",
                "languages_list",
                "themes_list",
                "cronSelect"
            ) + ["title" => $this->title]
        );
    }

    protected function createUserEditServiceSelect()
    {
        $yesOption = new Option($this->lang->translate("yes"));
        $yesOption->setParam("value", "1");
        if ($this->settings["user_edit_service"]) {
            $yesOption->setParam("selected", "selected");
        }

        $noOption = new Option($this->lang->translate("no"));
        $noOption->setParam("value", "0");
        if (!$this->settings["user_edit_service"]) {
            $noOption->setParam("selected", "selected");
        }

        $userEditServiceSelect = new Select();
        $userEditServiceSelect->setParam("name", "user_edit_service");
        $userEditServiceSelect->addContent($yesOption);
        $userEditServiceSelect->addContent($noOption);

        return $userEditServiceSelect;
    }

    protected function createCronSelect()
    {
        $yesOption = new Option($this->lang->translate("yes"));
        $yesOption->setParam("value", "1");
        if ($this->settings["cron_each_visit"]) {
            $yesOption->setParam("selected", "selected");
        }

        $noOption = new Option($this->lang->translate("no"));
        $noOption->setParam("value", "0");
        if (!$this->settings["cron_each_visit"]) {
            $noOption->setParam("selected", "selected");
        }

        $cronSelect = new Select();
        $cronSelect->setParam("name", "cron");
        $cronSelect->addContent($yesOption);
        $cronSelect->addContent($noOption);

        return $cronSelect;
    }
}
