<?php

/**
 * Created by MilyGosc.
 * URL: http://forum.sklep-sms.pl/showthread.php?tid=88
 */

$heart->register_page("transferuj_bad", "PageTransferujBad");

class PageTransferujBad extends PageSimple
{
    const PAGE_ID = "transferuj_bad";
    protected $template = "transferuj_bad";

    function __construct()
    {
        global $lang;
        $this->title = "Płatność Odrzucona";

        parent::__construct();
    }
}