<?php
/**
 * Created by MilyGosc.
 * URL: https://forum.sklep-sms.pl/showthread.php?tid=88
 */
namespace App\View\Pages;

class PageTPaySuccess extends PageSimple
{
    const PAGE_ID = 'tpay_success';
    protected $templateName = 'payment/tpay_success';

    public function __construct()
    {
        parent::__construct();

        $this->heart->pageTitle = $this->title = 'Płatność Zaakceptowana';
    }
}
