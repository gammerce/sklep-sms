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
        global $db, $settings, $lang;

        // Brak podanego kodu
        if (!strlen($get['code'])) {
            return $lang->translate('no_reset_key');
        }

        $result = $db->query($db->prepare(
            "SELECT `uid` FROM `" . TABLE_PREFIX . "users` " .
            "WHERE `reset_password_key` = '%s'",
            [$get['code']]
        ));

        if (!$db->num_rows($result)) // Nie znalazło użytkownika z takim kodem
        {
            return $lang->translate('wrong_reset_key');
        }

        $row = $db->fetch_array_assoc($result);
        $sign = md5($row['uid'] . $settings['random_key']);

        $output = eval($this->template->render("reset_password"));

        return $output;
    }
}
