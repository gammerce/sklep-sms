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
        /** @var TranslationManager $translationManager */
        $translationManager = $this->app->make(TranslationManager::class);
        $lang = $translationManager->user();
        $langShop = $translationManager->shop();

        // Pobranie listy serwisów transakcyjnych
        $result = $this->db->query(
            "SELECT id, name, sms, transfer " . "FROM `" . TABLE_PREFIX . "transaction_services`"
        );
        $sms_services = $transfer_services = "";
        while ($row = $this->db->fetch_array_assoc($result)) {
            if ($row['sms']) {
                $sms_services .= create_dom_element("option", $row['name'], [
                    'value' => $row['id'],
                    'selected' => $row['id'] == $this->settings['sms_service'] ? "selected" : ""
                ]);
            }
            if ($row['transfer']) {
                $transfer_services .= create_dom_element("option", $row['name'], [
                    'value' => $row['id'],
                    'selected' =>
                        $row['id'] == $this->settings['transfer_service'] ? "selected" : ""
                ]);
            }
        }
        $cron[$this->settings['cron_each_visit'] ? "yes" : "no"] = "selected";
        $cron[$this->settings['cron_each_visit'] ? "no" : "yes"] = "";
        $user_edit_service[$this->settings['user_edit_service'] ? "yes" : "no"] = "selected";
        $user_edit_service[$this->settings['user_edit_service'] ? "no" : "yes"] = "";

        // Pobieranie listy dostępnych szablonów
        $dirlist = scandir($this->app->path('themes'));
        $themes_list = "";
        foreach ($dirlist as $dir_name) {
            if ($dir_name[0] != '.' && is_dir($this->app->path("themes/$dir_name"))) {
                $themes_list .= create_dom_element("option", $dir_name, [
                    'value' => $dir_name,
                    'selected' => $dir_name == $this->settings['theme'] ? "selected" : ""
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
                        'selected' => $dir_name == $langShop->getCurrentLanguage() ? "selected" : ""
                    ]
                );
            }
        }

        // Pobranie wyglądu strony
        return $this->template->render(
            "admin/settings",
            compact(
                'user_edit_service',
                'sms_services',
                'transfer_services',
                'languages_list',
                'themes_list',
                'cron'
            ) + ['title' => $this->title]
        );
    }
}
