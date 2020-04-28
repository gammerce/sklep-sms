<?php
namespace App\View\Pages\Shop;

use App\Support\Database;
use App\Support\Template;
use App\Translation\TranslationManager;
use App\View\Interfaces\IBeLoggedCannot;
use App\View\Pages\Page;
use Symfony\Component\HttpFoundation\Request;

class PageRegister extends Page implements IBeLoggedCannot
{
    const PAGE_ID = "register";

    /** @var Database */
    private $db;

    public function __construct(
        Template $template,
        TranslationManager $translationManager,
        Database $db
    ) {
        parent::__construct($template, $translationManager);
        $this->db = $db;
    }

    public function getTitle(Request $request)
    {
        return $this->lang->t("register");
    }

    public function getContent(Request $request)
    {
        $antispamQuestion = $this->db
            ->query("SELECT * FROM `ss_antispam_questions` ORDER BY RAND() LIMIT 1")
            ->fetch();

        $session = $request->getSession();
        $session->set("asid", $antispamQuestion["id"]);

        return $this->template->render("register", compact("antispamQuestion"));
    }
}
