<?php
namespace App\View\Pages\Shop;

use App\View\Pages\Page;
use Symfony\Component\HttpFoundation\Request;

class PageSignUp extends Page
{
    const PAGE_ID = "sign_up";

    public function getTitle(Request $request)
    {
        return $this->lang->t("sign_up");
    }

    public function getContent(Request $request)
    {
        return $this->template->render("sign_up");
    }
}
