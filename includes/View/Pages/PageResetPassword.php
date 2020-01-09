<?php
namespace App\View\Pages;

use App\View\Interfaces\IBeLoggedCannot;

class PageResetPassword extends Page implements IBeLoggedCannot
{
    const PAGE_ID = 'reset_password';

    public function __construct()
    {
        parent::__construct();

        $this->heart->pageTitle = $this->title = $this->lang->t('reset_password');
    }

    protected function content(array $query, array $body)
    {
        // Brak podanego kodu
        if (!strlen($query['code'])) {
            return $this->lang->t('no_reset_key');
        }

        $result = $this->db->query(
            $this->db->prepare(
                "SELECT `uid` FROM `" .
                    TABLE_PREFIX .
                    "users` " .
                    "WHERE `reset_password_key` = '%s'",
                [$query['code']]
            )
        );

        if (!$result->rowCount()) {
            // Nie znalazło użytkownika z takim kodem
            return $this->lang->t('wrong_reset_key');
        }

        $row = $result->fetch();
        $sign = md5($row['uid'] . $this->settings['random_key']);

        return $this->template->render("reset_password", compact('row', 'sign'));
    }
}
