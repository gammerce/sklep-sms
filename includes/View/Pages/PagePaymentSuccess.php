<?php
namespace App\View\Pages;

class PagePaymentSuccess extends PageSimple
{
    const PAGE_ID = 'payment_success';
    protected $templateName = 'payment/payment_success';

    public function __construct()
    {
        parent::__construct();

        $this->heart->pageTitle = $this->title = 'Płatność Odrzucona';
    }
}
