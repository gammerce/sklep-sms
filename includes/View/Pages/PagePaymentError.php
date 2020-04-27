<?php
/**
 * Created by MilyGosc.
 * URL: https://forum.sklep-sms.pl/showthread.php?tid=88
 */
namespace App\View\Pages;

use Symfony\Component\HttpFoundation\Request;

class PagePaymentError extends Page
{
    const PAGE_ID = 'payment_error';

    public function getTitle(Request $request)
    {
        return "Płatność Odrzucona";
    }

    public function getContent(array $query, array $body)
    {
        return $this->template->render("payment/payment_error");
    }
}
