<?php

/**
 * Created by MilyGosc.
 * URL: https://forum.sklep-sms.pl/showthread.php?tid=88
 */

class PageTransferujOk extends PageSimple
{
    const PAGE_ID = 'transferuj_ok';
    protected $templateName = 'transferuj_ok';

    public function __construct()
    {
        parent::__construct();

        $this->heart->page_title = $this->title = 'Płatność Zaakceptowana';
    }
}
