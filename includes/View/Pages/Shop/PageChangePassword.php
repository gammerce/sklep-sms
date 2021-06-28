<?php
namespace App\View\Pages\Shop;

use App\View\Interfaces\IBeLoggedMust;
use App\View\Pages\Page;
use Symfony\Component\HttpFoundation\Request;

class PageChangePassword extends Page implements IBeLoggedMust
{
    const PAGE_ID = "change_password";

    public function getTitle(Request $request = null): string
    {
        return $this->lang->t("change_password");
    }

    public function getContent(Request $request)
    {
        return $this->template->render("shop/pages/change_password");
    }
}
