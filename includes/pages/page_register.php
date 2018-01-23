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
        global $db, $settings, $lang;

        $antispam_question = $db->fetch_array_assoc($db->query(
            "SELECT * FROM `" . TABLE_PREFIX . "antispam_questions` " .
            "ORDER BY RAND() " .
            "LIMIT 1"
        ));
        $_SESSION['asid'] = $antispam_question['id'];

        $output = eval($this->template->render("register"));

        return $output;
    }
}
