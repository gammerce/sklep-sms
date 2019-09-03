<?php
namespace App\Pages;

use App\Interfaces\IBeLoggedCannot;

class PageForgottenPassword extends PageSimple implements IBeLoggedCannot
{
    const PAGE_ID = 'forgotten_password';
    protected $templateName = 'forgotten_password';

    public function __construct()
    {
        parent::__construct();

        $this->heart->pageTitle = $this->title = $this->lang->translate('forgotten_password');
    }
}
