<?php

class PageRegulations extends PageSimple
{
    const PAGE_ID = 'regulations';
    protected $template = 'regulations_desc';

    public function __construct()
    {
        parent::__construct();

        $this->heart->page_title = $this->title = $this->lang->translate('regulations');
    }
}
