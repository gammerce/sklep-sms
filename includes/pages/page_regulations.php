<?php

$heart->register_page("regulations", "PageRegulations");

class PageRegulations extends PageSimple
{
    const PAGE_ID = "regulations";
    protected $template = "regulations_desc";

    public function __construct()
    {
        global $lang;
        $this->title = $lang->translate('regulations');

        parent::__construct();
    }
}