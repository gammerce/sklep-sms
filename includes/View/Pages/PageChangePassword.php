<?php
namespace App\View\Pages;

use App\View\Interfaces\IBeLoggedMust;
use Symfony\Component\HttpFoundation\Request;

class PageChangePassword extends Page implements IBeLoggedMust
{
    const PAGE_ID = "change_password";

    public function getTitle(Request $request)
    {
        return $this->lang->t("change_password");
    }

    public function getContent(array $query, array $body)
    {
        return $this->template->render("change_password");
    }
}
