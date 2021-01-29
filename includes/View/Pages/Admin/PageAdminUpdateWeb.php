<?php
namespace App\View\Pages\Admin;

use App\Support\Template;
use App\Support\Version;
use App\System\Application;
use App\Translation\TranslationManager;
use App\User\Permission;
use Symfony\Component\HttpFoundation\Request;

class PageAdminUpdateWeb extends PageAdmin
{
    const PAGE_ID = "update_web";

    private Version $version;
    private Application $app;

    public function __construct(
        Template $template,
        TranslationManager $translationManager,
        Version $version,
        Application $app
    ) {
        parent::__construct($template, $translationManager);

        $this->version = $version;
        $this->app = $app;
    }

    public function getPrivilege()
    {
        return Permission::UPDATE();
    }

    public function getTitle(Request $request)
    {
        return $this->lang->t("update_web");
    }

    public function getContent(Request $request)
    {
        $newestVersion = $this->version->getNewestWeb();
        $currentVersion = $this->app->version();

        if (version_compare($currentVersion, $newestVersion) >= 0) {
            return $this->template->render("admin/no_update");
        }

        $pageTitle = $this->template->render("admin/page_title", [
            "buttons" => "",
            "title" => $this->getTitle($request),
        ]);

        return $this->template->render(
            "admin/update_web",
            compact("currentVersion", "newestVersion", "pageTitle")
        );
    }
}
