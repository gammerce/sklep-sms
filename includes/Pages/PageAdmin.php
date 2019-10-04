<?php
namespace App\Pages;

use App\Interfaces\IBeLoggedMust;

abstract class PageAdmin extends Page implements IBeLoggedMust
{
    protected $privilege = 'acp';

    public function getContent(array $query, array $body)
    {
        if (!get_privileges($this->privilege)) {
            return $this->lang->translate('no_privileges');
        }

        // Dodajemy wszystkie skrypty
        $path = "build/js_old/admin/pages/" . $this::PAGE_ID . "/";
        if (strlen($this::PAGE_ID) && file_exists($this->app->path($path))) {
            foreach (scandir($this->app->path($path)) as $file) {
                if (ends_at($file, ".js")) {
                    $this->heart->scriptAdd($this->url->versioned($path . $file));
                }
            }
        }

        // Dodajemy wszystkie css
        $path = "build/stylesheets_old/admin/pages/" . $this::PAGE_ID . "/";
        if (strlen($this::PAGE_ID) && file_exists($this->app->path($path))) {
            foreach (scandir($this->app->path($path)) as $file) {
                if (ends_at($file, ".css")) {
                    $this->heart->styleAdd($this->url->versioned($path . $file));
                }
            }
        }

        // Globalne jsy cssy konkretnych modułów usług
        if (in_array($this::PAGE_ID, ["service_codes", "services", "user_service"])) {
            foreach ($this->heart->getServicesModules() as $moduleInfo) {
                $path = "build/stylesheets_old/services/" . $moduleInfo['id'] . ".css";
                if (file_exists($this->app->path($path))) {
                    $this->heart->styleAdd($this->url->versioned($path));
                }

                $path = "build/js_old/services/" . $moduleInfo['id'] . ".js";
                if (file_exists($this->app->path($path))) {
                    $this->heart->scriptAdd($this->url->versioned($path));
                }
            }
        }

        return $this->content($query, $body);
    }
}
