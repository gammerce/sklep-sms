<?php

$heart->register_page("change_password", "PageChangePassword");

class PageChangePassword extends PageSimple implements I_BeLoggedMust
{
    const PAGE_ID = "change_password";
    protected $template = "change_password";

    public function __construct()
    {
        global $lang;
        $this->title = $lang->translate('change_password');

        parent::__construct();
    }
}