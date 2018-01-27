<?php

abstract class PageAdmin extends Page implements I_BeLoggedMust
{
    protected $privilage = 'acp';

    public function get_content($get, $post)
    {
        if (!get_privilages($this->privilage)) {
            return $this->lang->translate('no_privilages');
        }

        // Dodajemy wszystkie skrypty
        $path = "jscripts/admin/pages/" . $this::PAGE_ID . "/";
        if (strlen($this::PAGE_ID) && file_exists(SCRIPT_ROOT . $path)) {
            foreach (scandir(SCRIPT_ROOT . $path) as $file) {
                if (ends_at($file, ".js")) {
                    $this->heart->script_add($this->settings['shop_url_slash'] . $path . $file . "?version=" . VERSION);
                }
            }
        }

        // Dodajemy wszystkie css
        $path = "styles/admin/pages/" . $this::PAGE_ID . "/";
        if (strlen($this::PAGE_ID) && file_exists(SCRIPT_ROOT . $path)) {
            foreach (scandir(SCRIPT_ROOT . $path) as $file) {
                if (ends_at($file, ".css")) {
                    $this->heart->style_add($this->settings['shop_url_slash'] . $path . $file . "?version=" . VERSION);
                }
            }
        }

        // Globalne jsy cssy konkretnych modułów usług
        if (in_array($this::PAGE_ID, ["service_codes", "services", "user_service"])) {
            foreach ($this->heart->get_services_modules() as $module_info) {
                $path = "styles/services/" . $module_info['id'] . ".css";
                if (file_exists(SCRIPT_ROOT . $path)) {
                    $this->heart->style_add($this->settings['shop_url_slash'] . $path . "?version=" . VERSION);
                }

                $path = "jscripts/services/" . $module_info['id'] . ".js";
                if (file_exists(SCRIPT_ROOT . $path)) {
                    $this->heart->script_add($this->settings['shop_url_slash'] . $path . "?version=" . VERSION);
                }
            }
        }

        return $this->content($get, $post);
    }
}