<?php
namespace App\View\Pages\Shop;

use App\View\Interfaces\IBeLoggedCannot;
use App\View\Pages\Page;
use Symfony\Component\HttpFoundation\Request;

class PageLogin extends Page implements IBeLoggedCannot
{
    const PAGE_ID = "login";

    public function getTitle(Request $request): string
    {
        return $this->lang->t("sign_in");
    }

    public function getContent(Request $request)
    {
        return $this->template->render("shop/pages/login");
    }
}
