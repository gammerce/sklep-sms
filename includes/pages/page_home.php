<?php

$heart->register_page("home", "PageMain");

class PageMain extends PageSimple
{
    const PAGE_ID = "home";
    protected $template = "home";

    public function __construct()
    {
        global $lang;
        $this->title = $lang->translate('main_page');

        parent::__construct();
    }
}