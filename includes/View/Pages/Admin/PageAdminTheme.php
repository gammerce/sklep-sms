<?php
namespace App\View\Pages\Admin;

use App\User\Permission;
use Symfony\Component\HttpFoundation\Request;

class PageAdminTheme extends PageAdmin
{
    const PAGE_ID = "theme";

    public function getPrivilege(): Permission
    {
        return Permission::SETTINGS_MANAGEMENT();
    }

    public function getTitle(Request $request = null): string
    {
        return $this->lang->t("theme");
    }

    public function getContent(Request $request)
    {
        $pageTitle = $this->template->render("admin/page_title", [
            "buttons" => "",
            "title" => $this->getTitle($request),
        ]);

        return $this->template->render("admin/pages/theme", compact("pageTitle"));
    }
}
