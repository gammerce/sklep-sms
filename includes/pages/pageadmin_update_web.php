<?php

use App\Template;
use App\Version;

class PageAdminUpdateWeb extends PageAdmin
{
    const PAGE_ID = "update_web";
    protected $privilage = "update";

    /** @var Version */
    private $version;

    public function __construct(Version $version, Template $template)
    {
        parent::__construct();

        $this->heart->page_title = $this->title = $this->lang->translate('update_web');
        $this->version = $version;
    }

    protected function content($get, $post)
    {
        $newestVersion = $this->version->getNewestWeb();

        // Mamy najnowszą wersję
        if (VERSION === $newestVersion) {
            return eval($this->template->render("admin/no_update"));
        }

        return eval($this->template->render("admin/update_web"));
    }
}