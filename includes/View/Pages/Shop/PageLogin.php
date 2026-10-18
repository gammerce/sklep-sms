<?php
namespace App\View\Pages\Shop;

use App\Support\EmailChangeStatus;
use App\View\Interfaces\IBeLoggedCannot;
use App\View\Pages\Page;
use Symfony\Component\HttpFoundation\Request;

class PageLogin extends Page implements IBeLoggedCannot
{
    const PAGE_ID = "login";

    public function getTitle(?Request $request = null): string
    {
        return $this->lang->t("sign_in");
    }

    public function getContent(Request $request)
    {
        $steamError = $this->pullSessionMessage($request, "steam_error");
        $emailStatus = $this->queryMessage($request, "email_status", EmailChangeStatus::KEYS);

        return $this->template->render("shop/pages/login", compact("steamError", "emailStatus"));
    }
}
