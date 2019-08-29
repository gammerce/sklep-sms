<?php
namespace App\Pages;

use App\Interfaces\IBeLoggedMust;

abstract class PageAdmin extends Page implements IBeLoggedMust
{
    protected $privilege = 'acp';

    public function get_content($get, $post)
    {
        if (!get_privileges($this->privilege)) {
            return $this->lang->translate('no_privileges');
        }

        // Dodajemy wszystkie skrypty
        $path = "jscripts/admin/pages/" . $this::PAGE_ID . "/";
        if (strlen($this::PAGE_ID) && file_exists($this->app->path($path))) {
            foreach (scandir($this->app->path($path)) as $file) {
                if (ends_at($file, ".js")) {
                    $this->heart->scriptAdd(
                        $this->settings['shop_url_slash'] .
                            $path .
                            $file .
                            "?version=" .
                            $this->app->version()
                    );
                }
            }
        }

        // Dodajemy wszystkie css
        $path = "styles/admin/pages/" . $this::PAGE_ID . "/";
        if (strlen($this::PAGE_ID) && file_exists($this->app->path($path))) {
            foreach (scandir($this->app->path($path)) as $file) {
                if (ends_at($file, ".css")) {
                    $this->heart->styleAdd(
                        $this->settings['shop_url_slash'] .
                            $path .
                            $file .
                            "?version=" .
                            $this->app->version()
                    );
                }
            }
        }

        // Globalne jsy cssy konkretnych modułów usług
        if (in_array($this::PAGE_ID, ["service_codes", "services", "user_service"])) {
            foreach ($this->heart->getServicesModules() as $module_info) {
                $path = "styles/services/" . $module_info['id'] . ".css";
                if (file_exists($this->app->path($path))) {
                    $this->heart->styleAdd(
                        $this->settings['shop_url_slash'] .
                            $path .
                            "?version=" .
                            $this->app->version()
                    );
                }

                $path = "jscripts/services/" . $module_info['id'] . ".js";
                if (file_exists($this->app->path($path))) {
                    $this->heart->scriptAdd(
                        $this->settings['shop_url_slash'] .
                            $path .
                            "?version=" .
                            $this->app->version()
                    );
                }
            }
        }

        return $this->content($get, $post);
    }
}
