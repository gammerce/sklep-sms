<?php
namespace App\Pages;

class PageRegulations extends PageSimple
{
    const PAGE_ID = 'regulations';
    protected $templateName = 'regulations_desc';

    public function __construct()
    {
        parent::__construct();

        $this->heart->pageTitle = $this->title = $this->lang->translate('regulations');
    }
}
