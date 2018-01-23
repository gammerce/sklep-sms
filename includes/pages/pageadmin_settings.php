<?php

use App\TranslationManager;

class PageAdminSettings extends PageAdmin
{
    const PAGE_ID = 'settings';
    protected $privilage = 'manage_settings';

    public function __construct()
    {
        parent::__construct();

        $this->heart->page_title = $this->title = $this->lang->translate('settings');
    }

    protected function content($get, $post)
    {
        global $db, $settings;

        /** @var TranslationManager $translationManager */
        $translationManager = app()->make(TranslationManager::class);
        $lang = $translationManager->user();
        $langShop = $translationManager->shop();

        // Pobranie listy serwisów transakcyjnych
        $result = $db->query(
            "SELECT id, name, sms, transfer " .
            "FROM `" . TABLE_PREFIX . "transaction_services`"
        );
        $sms_services = $transfer_services = "";
        while ($row = $db->fetch_array_assoc($result)) {
            if ($row['sms']) {
                $sms_services .= create_dom_element("option", $row['name'], [
                    'value'    => $row['id'],
                    'selected' => $row['id'] == $settings['sms_service'] ? "selected" : "",
                ]);
            }
            if ($row['transfer']) {
                $transfer_services .= create_dom_element("option", $row['name'], [
                    'value'    => $row['id'],
                    'selected' => $row['id'] == $settings['transfer_service'] ? "selected" : "",
                ]);
            }
        }
        $cron[$settings['cron_each_visit'] ? "yes" : "no"] = "selected";
        $user_edit_service[$settings['user_edit_service'] ? "yes" : "no"] = "selected";

        // Pobieranie listy dostępnych szablonów
        $dirlist = scandir(SCRIPT_ROOT . "themes");
        $themes_list = "";
        foreach ($dirlist as $dir_name) {
            if ($dir_name[0] != '.' && is_dir(SCRIPT_ROOT . "themes/" . $dir_name)) {
                $themes_list .= create_dom_element("option", $dir_name, [
                    'value'    => $dir_name,
                    'selected' => $dir_name == $settings['theme'] ? "selected" : "",
                ]);
            }
        }

        // Pobieranie listy dostępnych języków
        $dirlist = scandir(SCRIPT_ROOT . "includes/languages");
        $languages_list = "";
        foreach ($dirlist as $dir_name) {
            if ($dir_name[0] != '.' && is_dir(SCRIPT_ROOT . "includes/languages/{$dir_name}")) {
                $languages_list .= create_dom_element("option", $lang->translate('language_' . $dir_name), [
                    'value'    => $dir_name,
                    'selected' => $dir_name == $langShop->getCurrentLanguage() ? "selected" : "",
                ]);
            }
        }

        // Pobranie wyglądu strony
        return eval($this->template->render("admin/settings"));
    }
}