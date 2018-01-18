<?php

abstract class PageAdmin extends Page implements I_BeLoggedMust
{
    protected $privilage = "acp";

    public function get_content($get, $post)
    {
        global $heart, $settings;

        if (!get_privilages($this->privilage)) {
            global $lang;

            return $lang->translate('no_privilages');
        }

        // Dodajemy wszystkie skrypty
        $path = "jscripts/admin/pages/" . $this::PAGE_ID . "/";
        if (strlen($this::PAGE_ID) && file_exists(SCRIPT_ROOT . $path)) {
            foreach (scandir(SCRIPT_ROOT . $path) as $file) {
                if (ends_at($file, ".js")) {
                    $heart->script_add($settings['shop_url_slash'] . $path . $file . "?version=" . VERSION);
                }
            }
        }

        // Dodajemy wszystkie css
        $path = "styles/admin/pages/" . $this::PAGE_ID . "/";
        if (strlen($this::PAGE_ID) && file_exists(SCRIPT_ROOT . $path)) {
            foreach (scandir(SCRIPT_ROOT . $path) as $file) {
                if (ends_at($file, ".css")) {
                    $heart->style_add($settings['shop_url_slash'] . $path . $file . "?version=" . VERSION);
                }
            }
        }

        // Globalne jsy cssy konkretnych modułów usług
        if (in_array($this::PAGE_ID, ["service_codes", "services", "user_service"])) {
            foreach ($heart->get_services_modules() as $module_info) {
                $path = "styles/services/" . $module_info['id'] . ".css";
                if (file_exists(SCRIPT_ROOT . $path)) {
                    $heart->style_add($settings['shop_url_slash'] . $path . "?version=" . VERSION);
                }

                $path = "jscripts/services/" . $module_info['id'] . ".js";
                if (file_exists(SCRIPT_ROOT . $path)) {
                    $heart->script_add($settings['shop_url_slash'] . $path . "?version=" . VERSION);
                }
            }
        }

        return $this->content($get, $post);
    }
}