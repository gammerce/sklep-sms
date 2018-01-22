<?php

class PageContact extends PageSimple
{
    const PAGE_ID = 'contact';
    protected $template = 'contact';

    public function __construct()
    {
        parent::__construct();

        $this->heart->page_title = $this->title = $this->lang->translate('contact');
    }
}
