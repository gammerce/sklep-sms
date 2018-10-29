<?php

class PageRegister extends Page implements I_BeLoggedCannot
{
    const PAGE_ID = 'register';

    public function __construct()
    {
        parent::__construct();

        $this->heart->page_title = $this->title = $this->lang->translate('register');
    }

    protected function content($get, $post)
    {
        $antispam_question = $this->db->fetch_array_assoc($this->db->query(
            "SELECT * FROM `" . TABLE_PREFIX . "antispam_questions` " .
            "ORDER BY RAND() " .
            "LIMIT 1"
        ));
        $_SESSION['asid'] = $antispam_question['id'];

        return $this->template->render("register", compact('antispam_question'));
    }
}
