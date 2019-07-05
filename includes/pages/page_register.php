<?php

use App\Interfaces\IBeLoggedCannot;
use Symfony\Component\HttpFoundation\Request;

class PageRegister extends Page implements IBeLoggedCannot
{
    const PAGE_ID = 'register';

    public function __construct()
    {
        parent::__construct();

        $this->heart->page_title = $this->title = $this->lang->translate('register');
    }

    protected function content($get, $post)
    {
        /** @var Request $request */
        $request = $this->app->make(Request::class);
        $session = $request->getSession();

        $antispam_question = $this->db->fetch_array_assoc(
            $this->db->query(
                "SELECT * FROM `" .
                    TABLE_PREFIX .
                    "antispam_questions` " .
                    "ORDER BY RAND() " .
                    "LIMIT 1"
            )
        );
        $session->set("asid", $antispam_question['id']);

        return $this->template->render("register", compact('antispam_question'));
    }
}
