<?php

/**
 * Created by MilyGosc.
 * URL: https://forum.sklep-sms.pl/showthread.php?tid=88
 */

class PageTransferujBad extends PageSimple
{
    const PAGE_ID = "transferuj_bad";
    protected $template = "transferuj_bad";

    public function __construct()
    {
        global $lang;
        $this->title = "Płatność Odrzucona";

        parent::__construct();
    }
}