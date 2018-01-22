<?php

class PageForgottenPassword extends PageSimple implements I_BeLoggedCannot
{
    const PAGE_ID = 'forgotten_password';
    protected $template = 'forgotten_password';

    public function __construct()
    {
        parent::__construct();

        $this->heart->page_title = $this->title = $this->lang->translate('forgotten_password');
    }
}
