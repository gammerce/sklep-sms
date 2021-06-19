<?php
namespace App\View\Pages\Admin;

use App\Support\Meta;
use App\Theme\Template;
use App\Support\Version;
use App\Translation\TranslationManager;
use App\User\Permission;
use Symfony\Component\HttpFoundation\Request;

class PageAdminUpdateWeb extends PageAdmin
{
    const PAGE_ID = "update_web";

    private Version $version;
    private Meta $meta;

    public function __construct(
        Template $template,
        TranslationManager $translationManager,
        Version $version,
        Meta $meta
    ) {
        parent::__construct($template, $translationManager);

        $this->version = $version;
        $this->meta = $meta;
    }

    public function getPrivilege(): Permission
    {
        return Permission::UPDATE();
    }

    public function getTitle(Request $request): string
    {
        return $this->lang->t("update_web");
    }

    public function getContent(Request $request)
    {
        $newestVersion = $this->version->getNewestWeb();
        $currentVersion = $this->meta->getVersion();

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
