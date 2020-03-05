<?php
/**
 * Created by MilyGosc.
 * URL: https://forum.sklep-sms.pl/showthread.php?tid=88
 */
namespace App\View\Pages;

class PagePaymentError extends PageSimple
{
    const PAGE_ID = 'payment_error';
    protected $templateName = 'payment/payment_error';

    public function __construct()
    {
        parent::__construct();

        $this->heart->pageTitle = $this->title = 'Płatność Odrzucona';
    }
}
