<?php

use App\Interfaces\IBeLoggedMust;

class PageChangePassword extends PageSimple implements IBeLoggedMust
{
    const PAGE_ID = 'change_password';
    protected $templateName = 'change_password';

    public function __construct()
    {
        parent::__construct();

        $this->heart->page_title = $this->title = $this->lang->translate('change_password');
    }
}
