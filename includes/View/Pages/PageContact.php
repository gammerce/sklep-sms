<?php
namespace App\View\Pages;

use Symfony\Component\HttpFoundation\Request;

class PageContact extends Page
{
    const PAGE_ID = "contact";

    public function getTitle(Request $request)
    {
        return $this->lang->t("contact");
    }

    public function getContent(Request $request)
    {
        return $this->template->render("contact");
    }
}
