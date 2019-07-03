<?php

class PageResetPassword extends Page implements I_BeLoggedCannot
{
    const PAGE_ID = 'reset_password';

    public function __construct()
    {
        parent::__construct();

        $this->heart->page_title = $this->title = $this->lang->translate('reset_password');
    }

    protected function content($get, $post)
    {
        // Brak podanego kodu
        if (!strlen($get['code'])) {
            return $this->lang->translate('no_reset_key');
        }

        $result = $this->db->query(
            $this->db->prepare(
                "SELECT `uid` FROM `" .
                    TABLE_PREFIX .
                    "users` " .
                    "WHERE `reset_password_key` = '%s'",
                [$get['code']]
            )
        );

        if (!$this->db->num_rows($result)) {
            // Nie znalazło użytkownika z takim kodem
            return $this->lang->translate('wrong_reset_key');
        }

        $row = $this->db->fetch_array_assoc($result);
        $sign = md5($row['uid'] . $this->settings['random_key']);

        return $this->template->render("reset_password", compact('row', 'sign'));
    }
}
