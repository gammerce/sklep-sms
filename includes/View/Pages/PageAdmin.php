<?php
namespace App\View\Pages;

use App\View\Interfaces\IBeLoggedMust;

abstract class PageAdmin extends Page implements IBeLoggedMust
{
    protected $privilege = "acp";

    public function getContent(array $query, array $body)
    {
        if (!has_privileges($this->privilege)) {
            return $this->lang->t("no_privileges");
        }

        $path = "build/js/admin/pages/{$this->getPageId()}/";
        if ($this->fileSystem->exists($this->path->to($path))) {
            foreach ($this->fileSystem->scanDirectory($this->path->to($path)) as $file) {
                if (ends_at($file, ".js")) {
                    $this->heart->addScript($this->url->versioned($path . $file));
                }
            }
        }

        return $this->content($query, $body);
    }

    public function getPagePath()
    {
        return "/admin/{$this->getPageId()}";
    }
}
