<?php

class PageContact extends PageSimple
{
    const PAGE_ID = "contact";
    protected $template = "contact";

    public function __construct()
    {
        global $lang;
        $this->title = $lang->translate('contact');

        parent::__construct();
    }
}