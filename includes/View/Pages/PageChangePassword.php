<?php
namespace App\View\Pages;

use App\View\Interfaces\IBeLoggedMust;

class PageChangePassword extends PageSimple implements IBeLoggedMust
{
    const PAGE_ID = 'change_password';
    protected $templateName = 'change_password';

    public function __construct()
    {
        parent::__construct();

        $this->heart->pageTitle = $this->title = $this->lang->t('change_password');
    }
}
