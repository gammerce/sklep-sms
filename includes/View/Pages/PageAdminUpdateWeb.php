<?php
namespace App\View\Pages;

use App\Support\Version;

class PageAdminUpdateWeb extends PageAdmin
{
    const PAGE_ID = "update_web";
    protected $privilege = "update";

    /** @var Version */
    private $version;

    public function __construct(Version $version)
    {
        parent::__construct();

        $this->heart->pageTitle = $this->title = $this->lang->t("update_web");
        $this->version = $version;
    }

    protected function content(array $query, array $body)
    {
        $newestVersion = $this->version->getNewestWeb();
        $currentVersion = $this->app->version();

        if (version_compare($currentVersion, $newestVersion) >= 0) {
            return $this->template->render("admin/no_update");
        }

        $pageTitle = $this->template->render("admin/page_title", [
            "buttons" => "",
            "title" => $this->title,
        ]);

        return $this->template->render(
            "admin/update_web",
            compact("currentVersion", "newestVersion", "pageTitle")
        );
    }
}
