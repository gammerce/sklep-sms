<?php
namespace App\View\Pages;

use App\View\Interfaces\IBeLoggedMust;

abstract class PageAdmin extends Page implements IBeLoggedMust
{
    protected $privilege = 'acp';

    public function getContent(array $query, array $body)
    {
        if (!get_privileges($this->privilege)) {
            return $this->lang->t('no_privileges');
        }

        // Dodajemy wszystkie skrypty
        $path = "build/js/static/admin/pages/" . $this::PAGE_ID . "/";
        if (strlen($this::PAGE_ID) && $this->fileSystem->exists($this->path->to($path))) {
            foreach ($this->fileSystem->scanDirectory($this->path->to($path)) as $file) {
                if (ends_at($file, ".js")) {
                    $this->heart->addScript($this->url->versioned($path . $file));
                }
            }
        }

        // Dodajemy wszystkie css
        $path = "build/css/static/admin/pages/" . $this::PAGE_ID . "/";
        if (strlen($this::PAGE_ID) && $this->fileSystem->exists($this->path->to($path))) {
            foreach ($this->fileSystem->scanDirectory($this->path->to($path)) as $file) {
                if (ends_at($file, ".css")) {
                    $this->heart->addStyle($this->url->versioned($path . $file));
                }
            }
        }

        // Globalne jsy cssy konkretnych modułów usług
        if (in_array($this::PAGE_ID, ["service_codes", "services", "user_service"])) {
            foreach ($this->heart->getEmptyServiceModules() as $serviceModule) {
                $path = "build/css/static/services/{$serviceModule->getModuleId()}.css";
                if ($this->fileSystem->exists($this->path->to($path))) {
                    $this->heart->addStyle($this->url->versioned($path));
                }

                $path = "build/js/static/services/{$serviceModule->getModuleId()}.js";
                if ($this->fileSystem->exists($this->path->to($path))) {
                    $this->heart->addScript($this->url->versioned($path));
                }
            }
        }

        return $this->content($query, $body);
    }
}
