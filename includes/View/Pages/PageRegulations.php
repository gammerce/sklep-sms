<?php
namespace App\View\Pages;

use Symfony\Component\HttpFoundation\Request;

class PageRegulations extends Page
{
    const PAGE_ID = "regulations";

    public function getTitle(Request $request)
    {
        return $this->lang->t("regulations");
    }

    public function getContent(Request $request)
    {
        return $this->template->render("regulations_desc");
    }
}
