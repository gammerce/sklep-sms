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
        if (!strlen($query['code'])) {
            return $this->lang->t('no_reset_key');
        }

        $statement = $this->db->statement(
            "SELECT `uid` FROM `ss_users` WHERE `reset_password_key` = ?"
        );
        $statement->execute([$query['code']]);

        if (!$statement->rowCount()) {
            return $this->lang->t('wrong_reset_key');
        }

        $row = $statement->fetch();
        $sign = md5($row['uid'] . $this->settings->getSecret());

        return $this->template->render("reset_password", compact('row', 'sign'));
    }
}
