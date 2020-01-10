<?php
namespace App\View\Pages;

use App\View\Interfaces\IBeLoggedCannot;

class PageForgottenPassword extends PageSimple implements IBeLoggedCannot
{
    const PAGE_ID = 'forgotten_password';
    protected $templateName = 'forgotten_password';

    public function __construct()
    {
        parent::__construct();

        $this->heart->pageTitle = $this->title = $this->lang->t('forgotten_password');
    }
}
