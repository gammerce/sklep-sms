<?php
namespace App\View\Pages\Shop;

use App\View\Interfaces\IBeLoggedCannot;
use App\View\Pages\Page;
use Symfony\Component\HttpFoundation\Request;

class PageForgottenPassword extends Page implements IBeLoggedCannot
{
    const PAGE_ID = "forgotten_password";

    public function getTitle(Request $request): string
    {
        return $this->lang->t("forgotten_password");
    }

    public function getContent(Request $request)
    {
        return $this->template->render("shop/pages/forgotten_password");
    }
}
