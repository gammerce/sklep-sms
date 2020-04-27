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

    public function getContent(array $query, array $body)
    {
        $navbar = $this->template->render("navbar");
        $footer = $this->template->render("footer");

        return $this->template->render("home", compact("navbar", "footer"));
    }
}
