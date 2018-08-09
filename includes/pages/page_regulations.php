<?php

class PageRegulations extends PageSimple
{
    const PAGE_ID = 'regulations';
    protected $templateName = 'regulations_desc';

    public function __construct()
    {
        parent::__construct();

        $this->heart->page_title = $this->title = $this->lang->translate('regulations');
    }
}
