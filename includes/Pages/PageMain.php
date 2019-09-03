<?php
namespace App\Pages;

class PageMain extends PageSimple
{
    const PAGE_ID = 'home';
    protected $templateName = 'home';

    public function __construct()
    {
        parent::__construct();

        $this->heart->pageTitle = $this->title = $this->lang->translate('main_page');
    }
}
