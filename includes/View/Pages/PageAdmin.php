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

        $path = "build/js/admin/pages/" . $this::PAGE_ID . "/";
        if (strlen($this::PAGE_ID) && $this->fileSystem->exists($this->path->to($path))) {
            foreach ($this->fileSystem->scanDirectory($this->path->to($path)) as $file) {
                if (ends_at($file, ".js")) {
                    $this->heart->addScript($this->url->versioned($path . $file));
                }
            }
        }

        if (in_array($this::PAGE_ID, ["service_codes", "services", "user_service"])) {
            foreach ($this->heart->getEmptyServiceModules() as $serviceModule) {
                $path = "build/css/general/services/{$serviceModule->getModuleId()}.css";
                if ($this->fileSystem->exists($this->path->to($path))) {
                    $this->heart->addStyle($this->url->versioned($path));
                }
            }
        }

        return $this->content($query, $body);
    }
}
