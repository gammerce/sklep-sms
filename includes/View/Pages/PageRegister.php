<?php
namespace App\View\Pages;

use App\View\Interfaces\IBeLoggedCannot;
use Symfony\Component\HttpFoundation\Request;

class PageRegister extends Page implements IBeLoggedCannot
{
    const PAGE_ID = "register";

    public function getTitle(Request $request)
    {
        return $this->lang->t("register");
    }

    public function getContent(array $query, array $body)
    {
        $session = request()->getSession();

        $antispamQuestion = $this->db
            ->query("SELECT * FROM `ss_antispam_questions` ORDER BY RAND() LIMIT 1")
            ->fetch();
        $session->set("asid", $antispamQuestion["id"]);

        return $this->template->render("register", compact("antispamQuestion"));
    }
}
