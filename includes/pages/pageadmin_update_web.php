<?php

use App\Template;
use App\Translator;
use App\Version;

$heart->register_page("update_web", "PageAdminUpdateWeb", "admin");

class PageAdminUpdateWeb extends PageAdmin
{
    const PAGE_ID = "update_web";
    protected $privilage = "update";

    /** @var Version */
    private $version;

    /** @var Template */
    private $template;

    /** @var Translator */
    private $lang;

    public function __construct(Version $version, Template $template, Translator $lang)
    {
        global $lang;
        $this->title = $lang->translate('update_web');

        parent::__construct();

        $this->version = $version;
        $this->template = $template;
        $this->lang = $lang;
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