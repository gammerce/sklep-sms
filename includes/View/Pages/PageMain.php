<?php
namespace App\View\Pages;

use Symfony\Component\HttpFoundation\Request;

class PageMain extends Page
{
    const PAGE_ID = "home";

    public function getTitle(Request $request)
    {
        return $this->lang->t("main_page");
    }

    public function getContent(Request $request)
    {
        return $this->template->render("home");
    }
}
