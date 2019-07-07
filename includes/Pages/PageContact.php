<?php
namespace App\Pages;

class PageContact extends PageSimple
{
    const PAGE_ID = 'contact';
    protected $templateName = 'contact';

    public function __construct()
    {
        parent::__construct();

        $this->heart->page_title = $this->title = $this->lang->translate('contact');
    }
}
