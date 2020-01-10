<?php
namespace App\View\Pages;

class PageContact extends PageSimple
{
    const PAGE_ID = 'contact';
    protected $templateName = 'contact';

    public function __construct()
    {
        parent::__construct();

        $this->heart->pageTitle = $this->title = $this->lang->t('contact');
    }
}
