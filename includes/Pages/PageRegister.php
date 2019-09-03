<?php
namespace App\Pages;

use App\Interfaces\IBeLoggedCannot;
use Symfony\Component\HttpFoundation\Request;

class PageRegister extends Page implements IBeLoggedCannot
{
    const PAGE_ID = 'register';

    public function __construct()
    {
        parent::__construct();

        $this->heart->pageTitle = $this->title = $this->lang->translate('register');
    }

    protected function content(array $query, array $body)
    {
        /** @var Request $request */
        $request = $this->app->make(Request::class);
        $session = $request->getSession();

        $antispamQuestion = $this->db->fetchArrayAssoc(
            $this->db->query(
                "SELECT * FROM `" .
                    TABLE_PREFIX .
                    "antispam_questions` " .
                    "ORDER BY RAND() " .
                    "LIMIT 1"
            )
        );
        $session->set("asid", $antispamQuestion['id']);

        return $this->template->render("register", compact('antispamQuestion'));
    }
}
