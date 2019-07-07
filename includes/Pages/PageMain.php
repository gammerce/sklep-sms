<?php
namespace App\Pages;

class PageMain extends PageSimple
{
    const PAGE_ID = 'home';
    protected $templateName = 'home';

    public function __construct()
    {
        parent::__construct();

        $this->heart->page_title = $this->title = $this->lang->translate('main_page');
    }
}
